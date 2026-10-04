<?php

namespace Base\Gallery\Repository;

use Base\Entity\Thread\Tag;
use Base\Entity\Thread\Taxon;
use Base\Enum\ThreadState;
use Base\Gallery\Entity\Album;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * The published albums. Every query is on Album: with JOINED inheritance an
 * application's subclass comes back as itself, so the pages and the Twig
 * function need no other repository. A subclass's own repository extends
 * this one and passes its class. (INSTANCE OF takes the class's name, not
 * its discriminator as a parameter: the name covers the subclasses.)
 *
 * @extends ServiceEntityRepository<Album>
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, string $entityClass = Album::class)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * A page of the published albums - the featured ones first, then the
     * most recent work (madeAt, else the publication date) - of one taxon
     * and/or one tag when their slugs are given. Paged by hand: Doctrine's
     * Paginator walks the SQL with an output walker omnibase's own SqlWalker
     * is incompatible with.
     *
     * @return list<Album>
     */
    public function findPublishedPage(int $page = 1, int $perPage = 24, ?string $taxon = null, ?string $tag = null, ?bool $featured = null): array
    {
        $ids = array_column($this->ordered($this->filtered($this->published(), $taxon, $tag, $featured))
            ->select('a.id', 'a.featured AS HIDDEN f', 'COALESCE(a.madeAt, a.publishedAt) AS HIDDEN d')
            ->distinct()
            ->setFirstResult(max(0, $page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()->getScalarResult(), 'id');

        return $this->inOrder($ids);
    }

    public function countPublished(?string $taxon = null, ?string $tag = null, ?bool $featured = null): int
    {
        return (int) $this->filtered($this->published(), $taxon, $tag, $featured)
            ->select('COUNT(DISTINCT a.id)')
            ->getQuery()->getSingleScalarResult();
    }

    /** @return list<Album> */
    public function findLatest(int $limit = 6, ?bool $featured = null, ?string $taxon = null): array
    {
        return $this->findPublishedPage(1, $limit, $taxon, null, $featured);
    }

    /** @return list<Album> every published album, for a sitemap */
    public function findAllPublished(): array
    {
        return $this->ordered($this->published())->getQuery()->getResult();
    }

    public function findOnePublished(string $slug): ?Album
    {
        return $this->published()
            ->andWhere('a.slug = :slug')->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /** @return array{0: ?Album, 1: ?Album} the one before and the one after in the list's order */
    public function findNeighbours(Album $album): array
    {
        $ids = array_map('intval', array_column($this->ordered($this->published())
            ->select('a.id', 'a.featured AS HIDDEN f', 'COALESCE(a.madeAt, a.publishedAt) AS HIDDEN d')
            ->getQuery()->getScalarResult(), 'id'));

        return self::neighbours($ids, (int) $album->getId(), fn (int $id) => $this->find($id));
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

    /** @return list<Tag> the tags of the published albums (of one taxon when given): the list's filters */
    public function findTags(?string $taxon = null): array
    {
        $query = $this->getEntityManager()->getRepository(Tag::class)->createQueryBuilder('t')
            ->innerJoin('t.threads', 'a')
            ->andWhere('a INSTANCE OF '.$this->getEntityName())
            ->andWhere('a.state = :published')->setParameter('published', ThreadState::PUBLISH)
            ->groupBy('t.id')
            ->orderBy('t.priority', 'DESC')->addOrderBy('t.slug', 'ASC');
        if ($taxon) {
            $query->innerJoin('a.taxa', 'x')->andWhere('x.slug = :taxon')->setParameter('taxon', $taxon);
        }

        return $query->getQuery()->getResult();
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
        // In their own order when the class has one (a "position" field), else as they were made.
        $query = $this->getEntityManager()->getRepository($class)->createQueryBuilder('x');
        if ($this->getEntityManager()->getClassMetadata($class)->hasField('position')) {
            $query->orderBy('x.position', 'ASC');
        }
        $query->addOrderBy('x.id', 'ASC');
        if (Taxon::class === $class) {
            $query->innerJoin('x.threads', 'a')
                ->andWhere('a INSTANCE OF '.$this->getEntityName())
                ->andWhere('a.state = :published')->setParameter('published', ThreadState::PUBLISH)
                ->groupBy('x.id');
        }

        return $query->getQuery()->getResult();
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

    /** @param list<int|string> $ids @return list<Album> the albums of these ids, in the ids' order */
    private function inOrder(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $albums = [];
        foreach ($this->createQueryBuilder('a')->andWhere('a.id IN (:ids)')->setParameter('ids', $ids)->getQuery()->getResult() as $album) {
            $albums[$album->getId()] = $album;
        }

        return array_values(array_filter(array_map(fn ($id) => $albums[(int) $id] ?? null, $ids)));
    }

    private function filtered(QueryBuilder $query, ?string $taxon, ?string $tag, ?bool $featured): QueryBuilder
    {
        if ($taxon) {
            $query->innerJoin('a.taxa', 'x')->andWhere('x.slug = :taxon')->setParameter('taxon', $taxon);
        }
        if ($tag) {
            $query->innerJoin('a.tags', 't')->andWhere('t.slug = :tag')->setParameter('tag', $tag);
        }
        if (null !== $featured) {
            $query->andWhere('a.featured = :featured')->setParameter('featured', $featured);
        }

        return $query;
    }

    private function ordered(QueryBuilder $query): QueryBuilder
    {
        return $query
            ->addSelect('a.featured AS HIDDEN f', 'COALESCE(a.madeAt, a.publishedAt) AS HIDDEN d')
            ->orderBy('f', 'DESC')->addOrderBy('d', 'DESC')->addOrderBy('a.id', 'DESC');
    }

    private function published(): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.state = :published')->setParameter('published', ThreadState::PUBLISH)
            ->andWhere('a.publishedAt IS NULL OR a.publishedAt <= CURRENT_TIMESTAMP()');
    }
}
