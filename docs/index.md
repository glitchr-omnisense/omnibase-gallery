---
title: Gallery
order: 10
---

# Installation and configuration

```bash
composer require omnibase/gallery:dev-main
```

```php
// config/bundles.php
Base\Gallery\GalleryBundle::class => ['all' => true],
```

```yaml
# config/routes.yaml
gallery_controller:
    resource: "@GalleryBundle/src/Controller/Client"
    type: attribute
    prefix: /
```

```bash
bin/console make:migration && bin/console doctrine:migrations:migrate   # gallery_album, gallery_photo
bin/console assets:install public --symlink --relative                  # bundles/gallery/css/gallery.css
bin/console cache:pool:clear --all                                      # omnibase keeps the routes in a pool
```

It needs `glitchr/omnibase` alone; with `omnibase/admin` installed the back
office screens are registered as well.

## Configuration

```yaml
# config/packages/gallery.yaml
gallery:
    path: galerie                              # /galerie, /galerie/{slug}; "realisations", "photos", "en/gallery"...
    per_page: 24                               # albums on a page of the list
    taxon_class: Base\Entity\Thread\Taxon      # the taxa that have a page of their own
    sitemap: true                              # albums and taxa pages in /sitemap.xml
```

| Key | Default | |
|---|---|---|
| `path` | `galerie` | The first segment of the three routes. One or more URL segments, no slash around. |
| `per_page` | `24` | |
| `taxon_class` | `Base\Entity\Thread\Taxon` | With the default, a taxon has a page once it classifies a published album. With an application's own class (`App\Entity\Portfolio\Category`), every taxon of that class has a page, empty or not - and the taxa of the other bundles (a shop's, a FAQ's) never do, nor are they offered in an album's screen of the back office. Listed by their `position` when the class has that field. |
| `sitemap` | `true` | |

The parameters are flat: `%gallery.path%`, `%gallery.per_page%`,
`%gallery.taxon_class%`, `%gallery.sitemap%`.

## What it stores

| Entity | Table | |
|---|---|---|
| `Base\Gallery\Entity\Album` | `gallery_album` (JOINED on omnibase's `thread`, discriminator `gallery_album`) | `madeAt` (the day the work was made), `featured`, `photos` |
| `Base\Gallery\Entity\Photo` | `gallery_photo` | `album`, `file` (an upload, 32 MB, images), `caption`, `alt`, `credit`, `position`, `cover`, `label` |

Everything else an album has is the thread's: `title`, `excerpt`, `content`
(translated), `slug`, `state`, `publishedAt`, `tags`, `taxa`, `owners`,
`connexes` (other threads it is linked to).

```php
$album = new Album($user, null, 'Enseigne de la boulangerie');
$album->setMadeAt(new \DateTime('2026-03-14'))->setFeatured(true);
$album->addPhoto((new Photo())->setFile(new File($path))->setCaption('De nuit')->setLabel('Après'));
$album->publish();                    // PUBLISH, dated now; publish(false): back to draft
$em->persist($album);

$album->getOrderedPhotos();           // by place, then in the order added
$album->getCover();                   // the photo marked, else the first
$album->getPhotosByLabel();           // ['Avant' => [...], 'Après' => [...]]
$album->renumberPhotos();             // places 1, 2, 3...
```

## The routes

| Route | Path | |
|---|---|---|
| `gallery_index` | `/{path}` | every published album; `?tag=slug` filters, `?page=2` pages |
| `gallery_taxon` | `/{path}/{slug}` | the albums of one taxon, with its label and description |
| `gallery_album` | `/{path}/{slug}` | one album |

A taxon's page and an album's page share one pattern: the taxon is looked
for first. Give an album another slug than a taxon's.

The list's order: the featured albums first, then the most recent work
(`madeAt`, else the publication date).

## Sitemap

`Base\Gallery\EventListener\SitemapListener` adds every published album and
every taxon page to omnibase's `/sitemap.xml` (`SitemapEvent::BUILD`), the
albums with the day they last changed.

## JSON-LD

An album's page carries schema.org's `ImageGallery`
(`Base\Gallery\Service\JsonLd::album()`): its name, description, dates,
keywords (its tags) and each photograph as an `ImageObject` with its
address, caption and credit.
