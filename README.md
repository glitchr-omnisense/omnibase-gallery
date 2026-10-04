# Gallery

Albums of photographs for the [omnibase](https://github.com/glitchr-studio/omnibase)
sites. **One album is one creation**: a sign hung, a van wrapped, a concert, a
recipe - shown by its photographs, in order.

- An `Album` **is** an omnibase `Thread`: title, excerpt, text, slug,
  publication state and date, tags and taxa come from the core. It adds the
  day the work was made (`madeAt`), a `featured` flag and its `Photo`s.
- A `Photo` is a file with a caption, an alternative text, a credit, a place
  in the album, a cover flag and a free label ("Avant", "Après").
- `/galerie` lists the published albums, filtered by tag; `/galerie/{taxon}`
  is the page of one taxon; `/galerie/{slug}` one album, with schema.org's
  `ImageGallery`. The first segment is configurable (`gallery.path`).
- `gallery_albums(limit, featured, taxon)` in Twig, two partials
  (`_album_card`, `_photos`), a neutral stylesheet on `--gallery-*` variables.
- A back office on omnibase/admin: the albums with their photographs in
  order, **Publier** and **À la une**.
- Written to be extended: an application's own entity (a realisation, a
  wedding, a dish) is a subclass of `Album`.

Nothing else: it does not follow a work while it is being done (that is
[omnibase/forge](https://github.com/glitchr-studio/omnibase-forge)), and it
knows no trade. No scroll library, no animation tied to scrolling.

## Install

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

Then a migration (`gallery_album`, `gallery_photo`) and
`bin/console assets:install` for the stylesheet.

## Documentation

- [Installation and configuration](docs/index.md)
- [Pages, Twig and styles](docs/templates.md)
- [The back office](docs/admin.md)
- [Extending Album in an application](docs/extending.md)

## Tests

```bash
vendor/bin/phpunit                                                      # checked out on its own
php vendor/bin/phpunit -c vendor/omnibase/gallery/phpunit.xml.dist      # from an application that requires it
```

## Licence

LGPL-3.0-or-later. See `LICENSE` (the GNU LGPL v3) and `COPYING` (the GNU GPL
v3 it supplements).
