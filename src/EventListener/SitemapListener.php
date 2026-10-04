<?php

namespace Base\Gallery\EventListener;

use Base\Entity\Thread\Taxon;
use Base\Event\SitemapEvent;
use Base\Gallery\Repository\AlbumRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * The albums' and the taxa's own pages in /sitemap.xml: omnibase lists the
 * routes it can generate by itself (the gallery's first page), and these
 * are behind a slug it cannot know. Each album carries the day it was last
 * changed, so a crawler comes back to the ones that move.
 */
#[AsEventListener(event: SitemapEvent::BUILD)]
final class SitemapListener
{
    public function __construct(
        private readonly AlbumRepository $albums,
        #[Autowire('%gallery.taxon_class%')] private readonly string $taxonClass = Taxon::class,
        #[Autowire('%gallery.sitemap%')] private readonly bool $enabled = true,
    ) {
    }

    public function __invoke(SitemapEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }
        $sitemap = $event->getSitemapper();
        foreach ($this->albums->findTaxa($this->taxonClass) as $taxon) {
            $sitemap->register('gallery_taxon', ['slug' => $taxon->getSlug()]);
        }
        foreach ($this->albums->findAllPublished() as $album) {
            $sitemap->register('gallery_album', ['slug' => $album->getSlug()], $album->getUpdatedAt()?->format('c'));
        }
    }
}
