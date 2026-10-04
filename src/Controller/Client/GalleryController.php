<?php

namespace Base\Gallery\Controller\Client;

use Base\Attributes\Attribute\Sitemap;
use Base\Entity\Thread\Taxon;
use Base\Gallery\Entity\Album;
use Base\Gallery\Repository\AlbumRepository;
use Base\Gallery\Service\JsonLd;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The gallery: every published album a page at a time, filtered by a tag
 * (?tag=); one page per taxon (/galerie/{taxon}: the albums it classifies,
 * with its label and description); one page per album, its photographs in
 * order. The first segment is gallery.path ("galerie" by default).
 *
 * A taxon's page and an album's page share one pattern, /{path}/{slug}:
 * the taxon is looked for first - they are few, and an album never takes a
 * taxon's slug knowingly.
 */
class GalleryController extends AbstractController
{
    public function __construct(
        private readonly AlbumRepository $albums,
        private readonly JsonLd $jsonLd,
        #[Autowire('%gallery.per_page%')] private readonly int $perPage = 24,
        #[Autowire('%gallery.taxon_class%')] private readonly string $taxonClass = Taxon::class,
    ) {
    }

    #[Sitemap(priority: 0.8, changefreq: 'weekly')]
    #[Route('/%gallery.path%', name: 'gallery_index')]
    public function index(Request $request): Response
    {
        return $this->list($request, null);
    }

    // Listed in /sitemap.xml by EventListener\SitemapListener, one entry a taxon and an album: the attribute gives their priority.
    #[Sitemap(priority: 0.7, changefreq: 'monthly')]
    #[Route('/%gallery.path%/{slug}', name: 'gallery_taxon', requirements: ['slug' => '[a-z0-9][a-z0-9\-]*'])]
    #[Route('/%gallery.path%/{slug}', name: 'gallery_album', requirements: ['slug' => '[a-z0-9][a-z0-9\-]*'])]
    public function show(Request $request, string $slug): Response
    {
        $taxon = $this->albums->findTaxon($slug, $this->taxonClass);
        if ($taxon) {
            return $this->list($request, $taxon);
        }

        $album = $this->albums->findOnePublished($slug)
            ?? throw $this->createNotFoundException(sprintf('No published album nor taxon "%s".', $slug));

        return $this->album($request, $album);
    }

    private function list(Request $request, ?Taxon $taxon): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $tag = $request->query->get('tag') ?: null;
        $count = $this->albums->countPublished($taxon?->getSlug(), $tag);

        return $this->render('@Gallery/client/index.html.twig', [
            'albums' => $this->albums->findPublishedPage($page, $this->perPage, $taxon?->getSlug(), $tag),
            'taxon' => $taxon,
            'taxa' => $this->albums->findTaxa($this->taxonClass),
            'tags' => $this->albums->findTags($taxon?->getSlug()),
            'tag' => $tag,
            'count' => $count,
            'page' => $page,
            'pages' => max(1, (int) ceil($count / $this->perPage)),
        ]);
    }

    private function album(Request $request, Album $album): Response
    {
        [$previous, $next] = $this->albums->findNeighbours($album);
        $base = $request->getSchemeAndHttpHost().$request->getBasePath();

        return $this->render('@Gallery/client/album.html.twig', [
            'album' => $album,
            'photos' => $album->getOrderedPhotos(),
            'previous' => $previous,
            'next' => $next,
            'jsonld' => $this->jsonLd->album(
                $album,
                $this->generateUrl('gallery_album', ['slug' => $album->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                static fn (string $path): string => str_starts_with($path, '/') ? $base.$path : $path,
            ),
        ]);
    }
}
