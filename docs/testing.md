---
title: "Testing"
order: 60
---

# Testing

## Running the suite

From the package's checkout, with its dev dependencies:

```bash
vendor/bin/phpunit
```

Inside a host application:

```bash
php vendor/bin/phpunit -c vendor/omnibase/gallery/phpunit.xml.dist
```

In omnisense/omnibase-harness, mounting the checkout in place of the
installed package:

```bash
docker run --rm --network none \
    -v "$PWD":/srv/demo/vendor/omnibase/gallery:ro \
    omnisense/omnibase-harness:latest test gallery
```

On this checkout, in the harness:

```text
OK (30 tests, 85 assertions)
```

## What the tests check

| Test | Checks |
|---|---|
| `Controller\CrudRequiredFieldsTest` | omnibase/admin's `CrudFieldsTestCase` on the two screens |
| `DependencyInjection\ConfigurationTest` | the defaults; a site's own path and taxon class; a path with slashes around is refused |
| `Entity\AlbumTest` | a photograph joining an album takes the next place, once; the order shown; the cover marked, else the first; one cover at most; removing and renumbering; the photographs by label; publishing sets the state and the date once; featured and made at |
| `Entity\PhotoTest` | the alt typed, else the caption, else the album's title; no album, an empty alt; the texts trimmed, empty ones null; the credit's sign once; a stored file's public address; cover, place and string form |
| `Repository\NeighboursTest` | in the middle of the list; the first has no previous, the last no next; an album not listed has none |
| `Service\JsonLdTest` | an album is an `ImageGallery` of its photographs in order; without photographs nor dates it keeps to what it knows; the paths left as they are without a host |
