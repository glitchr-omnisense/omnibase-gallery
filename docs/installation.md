---
title: "Installation and configuration"
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

It needs `omnisense/omnibase` alone; with `omnibase/admin` installed the back
office screens are registered as well.

## Requirements

- PHP 8.2 or later;
- doctrine/orm 2.15 or 3;
- omnisense/omnibase 3.0 or 4.0, omnibridge/symfony 1.x;
- Symfony 6, 7 or 8 (config, dependency-injection, form, http-kernel).

Suggested: omnibase/admin, for the back office screens.

## Configuration

```yaml
# config/packages/gallery.yaml
gallery:
    path: galerie                              # /galerie, /galerie/{slug}; "realisations", "photos", "en/gallery"...
    per_page: 24                               # albums on a page of the list
    taxon_class: Base\Entity\Thread\Taxon      # the taxa that have a page of their own
    sitemap: true                              # albums and taxa pages in /sitemap.xml
```

Every key is optional; the [configuration reference](configuration.md) says
what each one does.
