<?php

namespace Base\Gallery\Entity;

use Base\Database\Attribute\Uploader;
use Base\Gallery\Repository\PhotoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A photograph of an album: the file, the words under it (caption), the
 * words a screen reader says (alt), who took it (credit), its place in the
 * album, whether it is the album's cover, and a free label - "Avant",
 * "Après", "Détail" - that a site may show on it or group by.
 */
#[ORM\Entity(repositoryClass: PhotoRepository::class)]
#[ORM\Table(name: 'gallery_photo')]
#[ORM\Index(fields: ['album', 'position'], name: 'gallery_photo_order_idx')]
class Photo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\ManyToOne(targetEntity: Album::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected ?Album $album = null;

    /** The picture: an upload. Untyped: the form hands the setter an UploadedFile; the column keeps its name. */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Uploader(max_size: '32MB', mime_types: ['image/*'], missable: true)]
    protected $file = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $caption = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    protected ?string $alt = null;

    /** The photographer, as it must be printed. */
    #[ORM\Column(length: 160, nullable: true)]
    #[Assert\Length(max: 160)]
    protected ?string $credit = null;

    /** Null until it joins an album, which gives it the next place. */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $position = null;

    #[ORM\Column(type: 'boolean')]
    protected bool $cover = false;

    /** Free: "Avant", "Après", "Détail", "De nuit". */
    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(max: 60)]
    protected ?string $label = null;

    public function __toString(): string
    {
        return $this->caption ?? $this->alt ?? ('#'.$this->id);
    }

    public function getId(): ?int { return $this->id; }

    public function getAlbum(): ?Album { return $this->album; }
    public function setAlbum(?Album $album): self { $this->album = $album; return $this; }

    /** Where the storage keeps the picture (what omnibase's |picture, |thumbnail and |lightbox take). */
    public function getFile(): ?string { return Uploader::getPublic($this, 'file'); }
    public function setFile($file): self { $this->file = $file ?: null; return $this; }
    /** Whether a picture is set, without asking the storage where it is. */
    public function hasFile(): bool { return null !== $this->file && '' !== $this->file; }

    /**
     * The picture's address on the site ("/uploads/…"), what an <img> or a
     * JSON-LD ImageObject takes: the storage gives its public path from the
     * server's root, the public directory is taken off.
     */
    public function getFileUrl(): ?string
    {
        return self::publicUrl($this->hasFile() ? $this->getFile() : null);
    }

    /** "/srv/app/public/uploads/a.jpg" → "/uploads/a.jpg"; a URL or a site path as it is. */
    public static function publicUrl(?string $path): ?string
    {
        if (null === $path || '' === $path || preg_match('#^(?:https?:)?//#i', $path)) {
            return $path ?: null;
        }
        $public = strpos($path, '/public/');

        return false !== $public ? substr($path, $public + \strlen('/public')) : $path;
    }

    public function getCaption(): ?string { return $this->caption; }
    public function setCaption(?string $caption): self { $this->caption = self::clean($caption); return $this; }

    /** What a screen reader says: the alt typed, else the caption, else the album's title. */
    public function getAlt(): string
    {
        return $this->alt ?? $this->caption ?? (string) $this->album?->getTitle();
    }

    /** The alt as typed (the form's value): null when none was. */
    public function getOwnAlt(): ?string { return $this->alt; }
    public function setOwnAlt(?string $alt): self { return $this->setAlt($alt); }
    public function setAlt(?string $alt): self { $this->alt = self::clean($alt); return $this; }

    public function getCredit(): ?string { return $this->credit; }
    public function setCredit(?string $credit): self { $this->credit = self::clean($credit); return $this; }

    /** The credit with its sign: "Anna Meyer" is printed "© Anna Meyer". */
    public function getCreditLine(): ?string
    {
        if (null === $this->credit) {
            return null;
        }

        return preg_match('/^(©|\(c\)|photo\b|foto\b)/iu', $this->credit) ? $this->credit : '© '.$this->credit;
    }

    public function getPosition(): ?int { return $this->position; }
    public function setPosition(?int $position): self { $this->position = $position; return $this; }

    public function isCover(): bool { return $this->cover; }
    public function setCover(bool $cover): self { $this->cover = $cover; return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(?string $label): self { $this->label = self::clean($label); return $this; }

    private static function clean(?string $text): ?string
    {
        $text = null === $text ? '' : trim($text);

        return '' === $text ? null : $text;
    }
}
