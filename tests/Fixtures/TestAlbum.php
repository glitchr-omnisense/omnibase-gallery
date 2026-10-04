<?php

namespace Base\Gallery\Tests\Fixtures;

use Base\Gallery\Entity\Album;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * An Album that keeps its texts in plain properties: omnibase's Thread
 * reads them from a translation entity through the kernel's localizer,
 * which a unit test has none of. Everything the gallery adds is the real one.
 */
class TestAlbum extends Album
{
    private ?string $testTitle;
    private ?string $testExcerpt = null;
    private ?int $testId;
    private Collection $testTags;
    public ?\DateTimeInterface $testPublishedAt = null;
    public ?\DateTimeInterface $testUpdatedAt = null;
    public mixed $testState = null;

    public function __construct(?string $title = null, ?string $slug = null, ?int $id = null)
    {
        // Thread's constructor is not run: it needs the kernel.
        $this->testTitle = $title;
        $this->slug = $slug;
        $this->testId = $id;
        $this->photos = new ArrayCollection();
        $this->testTags = new ArrayCollection();
    }

    public function getId(): ?int { return $this->testId; }

    public function getTitle(?string $locale = null, int $inheritanceDepthIfNotSet = 0): ?string { return $this->testTitle; }
    public function setTitle(?string $title, ?string $locale = null, int $inheritanceDepthIfNotSet = 0) { $this->testTitle = $title; return $this; }

    public function getExcerpt(?string $locale = null, int $inheritanceDepthIfNotSet = 0): ?string { return $this->testExcerpt; }
    public function setExcerpt(?string $excerpt, ?string $locale = null, int $inheritanceDepthIfNotSet = 0) { $this->testExcerpt = $excerpt; return $this; }

    public function getContent(?string $locale = null, int $inheritanceDepthIfNotSet = 0): ?string { return null; }

    public function getTags(): Collection { return $this->testTags; }
    public function withTags(string ...$tags): self { $this->testTags = new ArrayCollection($tags); return $this; }

    public function getState() { return $this->testState; }
    public function setState($state): self { $this->testState = $state; return $this; }

    public function getPublishedAt(): ?\DateTimeInterface { return $this->testPublishedAt; }
    public function setPublishedAt(?\DateTimeInterface $publishedAt): self { $this->testPublishedAt = $publishedAt; return $this; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->testUpdatedAt; }
}
