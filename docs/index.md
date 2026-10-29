---
title: omnibase/gallery
order: 1
---

# omnibase/gallery

omnibase/gallery is the portfolio of a site of the omnibase family: albums of
photographs - a sign maker's works, a studio's projects, a craftsman's
before and after - listed, filtered by tag and by taxon, each with its
photographs in order. Its namespace is `Base\Gallery\`; it needs
omnisense/omnibase alone, and registers its back office screens when
omnibase/admin is installed.

## What it adds

- `Album`, an omnisense/omnibase `Thread` (title, text, slug, state, tags,
  taxa... are the thread's) with the day the work was made, a "featured" flag
  and its photographs; `Photo`, a picture with its caption, alternative
  text, credit, place, cover flag and label.
- Three routes under `gallery.path` (`galerie` by default): the list, a
  taxon's page and an album's page.
- schema.org's `ImageGallery` on an album, the albums and taxa in
  `/sitemap.xml`.
- Three Twig functions and three partials for a site's own pages.
- Two back office screens with Publish and Featured.
- English, French and German messages (domain `gallery`).

## Pages

- [Installation and configuration](installation.md)
- [Albums and photographs](concepts.md)
- [Pages, Twig and styles](templates.md)
- [The back office](admin.md)
- [Extending Album in an application](extending.md)
- [Configuration reference](configuration.md)
- [Testing](testing.md)
