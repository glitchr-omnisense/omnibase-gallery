<?php

namespace Base\Gallery\Twig;

use Base\Entity\Thread\Taxon;
use Base\Gallery\Repository\AlbumRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/** What a host's own pages ask the gallery: a few albums for a home page or a family's page, the taxa for a menu. */
final class GalleryExtension extends AbstractExtension
{
    public function __construct(
        private readonly AlbumRepository $albums,
        #[Autowire('%gallery.taxon_class%')] private readonly string $taxonClass = Taxon::class,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            // gallery_albums(6), gallery_albums(3, true), gallery_albums(4, null, 'enseigne'), gallery_albums(taxon: category)
            new TwigFunction('gallery_albums', fn (int $limit = 6, ?bool $featured = null, Taxon|string|null $taxon = null): array => $this->albums->findLatest($limit, $featured, $taxon instanceof Taxon ? $taxon->getSlug() : $taxon)),
            new TwigFunction('gallery_count', fn (Taxon|string|null $taxon = null): int => $this->albums->countPublished($taxon instanceof Taxon ? $taxon->getSlug() : $taxon)),
            new TwigFunction('gallery_taxa', fn (): array => $this->albums->findTaxa($this->taxonClass)),
        ];
    }
}
