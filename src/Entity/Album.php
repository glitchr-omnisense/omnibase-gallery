<?php

namespace Base\Gallery\Entity;

use Base\Database\Attribute\DiscriminatorEntry;
use Base\Entity\Thread;
use Base\Enum\ThreadState;
use Base\Gallery\Repository\AlbumRepository;
use Base\Service\Model\LinkableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * An album: one creation, shown by its photographs. An omnibase thread -
 * title, excerpt and text translated, slug, publication state and date,
 * tags (free words that filter the list), taxa (what the site classifies
 * with: each may have its own page) - with the day the work was made, a
 * "featured" flag and its photographs in order on top.
 *
 * Written to be extended: an application that needs more (a customer, a
 * town, a technical sheet) declares a JOINED subclass with its own
 * #[DiscriminatorEntry]; the repository, the pages, the Twig function and
 * the back office work on it as they are (docs/extending.md).
 */
#[ORM\Entity(repositoryClass: AlbumRepository::class)]
#[ORM\Table(name: 'gallery_album')]
#[DiscriminatorEntry(value: 'gallery_album')]
class Album extends Thread implements LinkableInterface
{
    public static function __iconizeStatic(): ?array
    {
        return ['fa-solid fa-images'];
    }

    public function __toLink(array $routeParameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
    {
        return $this->getRouter()->generate('gallery_album', array_merge($routeParameters, ['slug' => $this->getSlug()]), $referenceType);
    }

    /** The day the work was made (a sign hung, a van wrapped): the list's order, newest first. */
    #[ORM\Column(type: 'date', nullable: true)]
    protected ?\DateTimeInterface $madeAt = null;

    /** Shown first, and what gallery_albums(featured: true) returns. */
    #[ORM\Column(type: 'boolean')]
    protected bool $featured = false;

    /** @var Collection<int, Photo> */
    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'album', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    protected Collection $photos;

    public function __construct(?\Base\Entity\User $owner = null, ?Thread $parent = null, ?string $title = null, ?string $slug = null)
    {
        parent::__construct($owner, $parent, $title, $slug);
        $this->photos = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle() ?? '';
    }

    public function getMadeAt(): ?\DateTimeInterface { return $this->madeAt; }
    public function setMadeAt(?\DateTimeInterface $madeAt): self { $this->madeAt = $madeAt; return $this; }

    public function isFeatured(): bool { return $this->featured; }
    public function setFeatured(bool $featured): self { $this->featured = $featured; return $this; }

    /** @return Collection<int, Photo> as stored; getOrderedPhotos() for the order shown */
    public function getPhotos(): Collection { return $this->photos; }

    public function addPhoto(Photo $photo): self
    {
        if (!$this->photos->contains($photo)) {
            if (null === $photo->getPosition()) {
                $photo->setPosition($this->nextPosition());
            }
            $this->photos->add($photo);
            $photo->setAlbum($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): self
    {
        if ($this->photos->removeElement($photo) && $photo->getAlbum() === $this) {
            $photo->setAlbum(null);
        }

        return $this;
    }

    /**
     * The photographs in the order they are shown: by position, then in the
     * order they were added - also right after one was added or moved,
     * before the database has sorted them.
     *
     * @return list<Photo>
     */
    public function getOrderedPhotos(): array
    {
        $photos = array_values($this->photos->toArray());
        $rank = array_flip(array_map('spl_object_id', $photos));
        usort($photos, fn (Photo $a, Photo $b) => [$a->getPosition() ?? \PHP_INT_MAX, $rank[spl_object_id($a)]] <=> [$b->getPosition() ?? \PHP_INT_MAX, $rank[spl_object_id($b)]]);

        return $photos;
    }

    /** The photograph on the album's card and at the top of its page: the one marked, else the first. */
    public function getCover(): ?Photo
    {
        $photos = $this->getOrderedPhotos();
        foreach ($photos as $photo) {
            if ($photo->isCover()) {
                return $photo;
            }
        }

        return $photos[0] ?? null;
    }

    /** One cover at most: marking a photograph unmarks the others. */
    public function setCover(?Photo $cover): self
    {
        foreach ($this->photos as $photo) {
            $photo->setCover($photo === $cover);
        }

        return $this;
    }

    /** The photographs carrying a label ("Avant", "Après"), by label, in order. @return array<string, list<Photo>> */
    public function getPhotosByLabel(): array
    {
        $byLabel = [];
        foreach ($this->getOrderedPhotos() as $photo) {
            if (null !== $photo->getLabel()) {
                $byLabel[$photo->getLabel()][] = $photo;
            }
        }

        return $byLabel;
    }

    /** The positions rewritten 1, 2, 3... in the order shown (after a photograph was removed or moved). */
    public function renumberPhotos(): self
    {
        foreach ($this->getOrderedPhotos() as $index => $photo) {
            $photo->setPosition($index + 1);
        }

        return $this;
    }

    /** Publish it now (or at $at), or take it back to draft. */
    public function publish(bool $published = true, ?\DateTimeInterface $at = null): self
    {
        $this->setState($published ? ThreadState::PUBLISH : ThreadState::DRAFT);
        if ($published) {
            $this->setPublishedAt($at ? \DateTime::createFromInterface($at) : ($this->getPublishedAt() ?? new \DateTime()));
        }

        return $this;
    }

    private function nextPosition(): int
    {
        $last = 0;
        foreach ($this->photos as $photo) {
            $last = max($last, (int) $photo->getPosition());
        }

        return $last + 1;
    }
}
