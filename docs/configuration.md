---
title: "Configuration reference"
order: 40
---

# Configuration reference

## The `gallery` tree

Dumped with Symfony's `YamlReferenceDumper` from
`Base\Gallery\DependencyInjection\GalleryConfiguration`:

```yaml
gallery:

    # The first segment of the gallery's addresses: /galerie, /galerie/{slug}. No slash.
    path:                 galerie

    # Albums listed per page.
    per_page:             24

    # The taxa that have a page of their own (/galerie/{taxon}): this class and its subclasses. With the default, omnibase's Taxon, a taxon has a page once it classifies a published album; with an application's own class, every taxon of that class has one.
    taxon_class:          Base\Entity\Thread\Taxon

    # The albums and the taxa pages in /sitemap.xml.
    sitemap:              true
```

| Key | Default | |
|---|---|---|
| `path` | `galerie` | The first segment of the three routes. One or more URL segments, no slash around. |
| `per_page` | `24` | |
| `taxon_class` | `Base\Entity\Thread\Taxon` | With the default, a taxon has a page once it classifies a published album. With an application's own class (`App\Entity\Portfolio\Category`), every taxon of that class has a page, empty or not - and the taxa of the other bundles (a shop's, a FAQ's) never do, nor are they offered in an album's screen of the back office. Listed by their `position` when the class has that field. |
| `sitemap` | `true` | |

The parameters are flat: `%gallery.path%`, `%gallery.per_page%`,
`%gallery.taxon_class%`, `%gallery.sitemap%`.

After changing `path`, clear the route pool: `bin/console cache:pool:clear
--all` (omnisense/omnibase keeps the routes in a cache pool).

## Services

| Service | Role |
|---|---|
| `Base\Gallery\Repository\AlbumRepository`, `PhotoRepository` | the queries ([Albums and photographs](concepts.md)) |
| `Base\Gallery\Service\JsonLd` | `album($album, $url, $absolute)`, `photo($photo, $absolute)` |
| `Base\Gallery\EventListener\SitemapListener` | the albums and the taxa pages of `/sitemap.xml` |
| `Base\Gallery\Twig\GalleryExtension` | `gallery_albums()`, `gallery_count()`, `gallery_taxa()` |
| `Base\Gallery\Form\PhotoType` | a photograph in the album's form |

The admin screens are loaded when omnibase/admin is installed.

## Translations

The domain `gallery`, in English, French and German (ICU format). A site
overrides a message in its own `translations/gallery+intl-icu.<locale>.yaml`.
