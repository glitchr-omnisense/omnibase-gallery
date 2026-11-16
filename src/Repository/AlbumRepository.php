<?php

namespace Base\Gallery\Repository;

use Base\Entity\Thread\Tag;
use Base\Entity\Thread\Taxon;
use Base\Enum\ThreadState;
use Base\Gallery\Entity\Album;
use Base\Database\Repository\Entities;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Parser;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * The published albums. Every query is on Album: with JOINED inheritance an
 * application's subclass comes back as itself, so the pages and the Twig
 * function need no other repository. A subclass's own repository extends
 * this one and passes its class. (INSTANCE OF takes the class's name, not
 * its discriminator as a parameter: the name covers the subclasses.)
 *
 * On omnibase's finder language. The list's order - featured first, then the most recent work,
 * COALESCE(madeAt, publishedAt), then the id - is an expression the language does not order by: the
 * albums of the criteria are read, then ordered and sliced here (a gallery holds tens to a few hundred
 * albums; a statement would be the way if one grew to thousands).
 *
 * Another class's entities (the tags, the taxa) are read through the repository's own backend (entitiesOf()):
 * the constructor stays (backend, class) for the subclasses' repositories.
 *
 * @method Album|null find($id, $lockMode = null, $lockVersion = null)
 * @method Album|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Album[]    findAll()
 * @method Album[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders, string $entityClass = Album::class)
    {
        parent::__construct($finders, $entityClass);
    }

    /**
     * A page of the published albums - the featured ones first, then the
     * most recent work (madeAt, else the publication date) - of one taxon
     * and/or one tag when their slugs are given.
     *
     * @return list<Album>
     */
    public function findPublishedPage(int $page = 1, int $perPage = 24, ?string $taxon = null, ?string $tag = null, ?bool $featured = null): array
    {
        return \array_slice($this->ordered($this->filtered($this->published(), $taxon, $tag, $featured)), max(0, $page - 1) * $perPage, $perPage);
    }

    public function countPublished(?string $taxon = null, ?string $tag = null, ?bool $featured = null): int
    {
        // One taxon slug, one tag slug: a join gives each album once.
        return $this->count($this->filtered($this->published(), $taxon, $tag, $featured));
    }

    /** @return list<Album> */
    public function findLatest(int $limit = 6, ?bool $featured = null, ?string $taxon = null): array
    {
        return $this->findPublishedPage(1, $limit, $taxon, null, $featured);
    }

    /** @return list<Album> every published album, for a sitemap */
    public function findAllPublished(): array
    {
        return $this->ordered($this->published());
    }

    public function findOnePublished(string $slug): ?Album
    {
        return $this->findOneBy([...$this->published(), 'slug' => $slug]);
    }

    /** @return array{0: ?Album, 1: ?Album} the one before and the one after in the list's order */
    public function findNeighbours(Album $album): array
    {
        $albums = [];
        foreach ($this->ordered($this->published()) as $one) {
            $albums[(int) $one->getId()] = $one;
        }

        return self::neighbours(array_keys($albums), (int) $album->getId(), fn (int $id) => $albums[$id]);
    }

    /**
     * The ids before and after $id in $ids, each turned into its album.
     *
     * @param list<int> $ids
     *
     * @return array{0: mixed, 1: mixed}
     */
    public static function neighbours(array $ids, int $id, callable $load): array
    {
        $at = array_search($id, $ids, true);
        if (false === $at) {
            return [null, null];
        }

        return [
            isset($ids[$at - 1]) ? $load($ids[$at - 1]) : null,
            isset($ids[$at + 1]) ? $load($ids[$at + 1]) : null,
        ];
    }

    /**
     * The tags of the published albums (of one taxon when given): the list's filters - the albums read,
     * then the tags among theirs, highest priority first, then by slug, each once. (State only: an album
     * scheduled for later gives its tags already, as the query did.)
     *
     * @return list<Tag>
     */
    public function findTags(?string $taxon = null): array
    {
        $criteria = ['state' => ThreadState::PUBLISH];
        if ($taxon) {
            $criteria['taxa.slug'] = $taxon;
        }

        return $this->ofAlbums(Tag::class, $this->findBy($criteria), ['priority' => 'DESC', 'slug' => 'ASC']);
    }

    /**
     * The taxa that have a page: of $class (omnibase's Taxon and its
     * subclasses by default). With the default class, only those that
     * classify a published album; with an application's class, all of them -
     * by their `position` when the class has that field.
     *
     * @param class-string<Taxon> $class
     *
     * @return list<Taxon>
     */
    public function findTaxa(string $class = Taxon::class): array
    {
        // In their own order when the class has one (a "position" property), else as they were made.
        $orderBy = (property_exists($class, 'position') ? ['position' => 'ASC'] : []) + ['id' => 'ASC'];
        if (Taxon::class === $class) {
            return $this->ofAlbums($class, $this->findBy(['state' => ThreadState::PUBLISH]), $orderBy);
        }

        return $this->entitiesOf($class)->findBy([], $orderBy);
    }

    /** @param class-string<Taxon> $class */
    public function findTaxon(string $slug, string $class = Taxon::class): ?Taxon
    {
        foreach ($this->findTaxa($class) as $taxon) {
            if ($taxon->getSlug() === $slug) {
                return $taxon;
            }
        }

        return null;
    }

    /**
     * The entities of $class (Tag, Taxon) that hold one of these albums among their threads, in that order,
     * each once; none without an album (the inner join's answer).
     *
     * @param class-string $class
     * @param list<Album>  $albums
     *
     * @return list<object>
     */
    private function ofAlbums(string $class, array $albums, array $orderBy): array
    {
        if (!$albums) {
            return [];
        }
        $found = [];
        foreach ($this->entitiesOf($class)->findBy(['threads' => $albums], $orderBy) as $entity) {
            $found[spl_object_id($entity)] ??= $entity;
        }

        return array_values($found);
    }

    /**
     * The entities of another class on this repository's backend, with the whole finder language (the parent
     * class serves any entity).
     *
     * @param class-string $class
     */
    private function entitiesOf(string $class): Entities
    {
        return new ServiceEntityRepository($this->finders(), $class);
    }

    /**
     * The albums of these criteria in the list's order: featured first, then COALESCE(madeAt, publishedAt)
     * newest first, then the id, highest first.
     *
     * @return list<Album>
     */
    private function ordered(array $criteria): array
    {
        $albums = [];
        foreach ($this->findBy($criteria) as $album) {
            $albums[(int) $album->getId()] = $album;
        }
        usort($albums, static function (Album $a, Album $b): int {
            $d = static fn (Album $x): ?string => ($x->getMadeAt() ?? $x->getPublishedAt())?->format('Y-m-d H:i:s');

            return [(bool) $b->isFeatured(), $d($b), $b->getId()] <=> [(bool) $a->isFeatured(), $d($a), $a->getId()];
        });

        return $albums;
    }

    /** @return array<int|string, mixed> */
    private function filtered(array $criteria, ?string $taxon, ?string $tag, ?bool $featured): array
    {
        if ($taxon) {
            $criteria['taxa.slug'] = $taxon;
        }
        if ($tag) {
            $criteria['tags.slug'] = $tag;
        }
        if (null !== $featured) {
            $criteria['featured'] = $featured;
        }

        return $criteria;
    }

    /** @return array<int|string, mixed> published, and their publication date come */
    private function published(): array
    {
        return [
            'state' => ThreadState::PUBLISH,
            Group::of(Parser::SEPARATOR_OR,
                Criterion::of('publishedAt', null, Parser::OPTION_NULL),
                Criterion::of('publishedAt', new \DateTime(), Parser::OPTION_LOWER_EQUAL),
            ),
        ];
    }
}
