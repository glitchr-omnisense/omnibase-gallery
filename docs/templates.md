---
title: Pages, Twig and styles
order: 20
---

# Pages, Twig and styles

## In a site's own pages

```twig
{# the six latest albums, the featured ones first #}
{% for album in gallery_albums(6) %}
    {% include '@Gallery/client/_album_card.html.twig' with {album: album} only %}
{% endfor %}

{{ gallery_albums(3, true) }}                 {# three featured albums #}
{{ gallery_albums(4, null, 'enseigne') }}     {# four of one taxon, by its slug… #}
{{ gallery_albums(4, null, category) }}       {# …or the taxon itself #}
{{ gallery_count() }} {{ gallery_count('enseigne') }}
{% for taxon in gallery_taxa() %}<a href="{{ path('gallery_taxon', {slug: taxon.slug}) }}">{{ taxon }}</a>{% endfor %}
```

| Function | Returns |
|---|---|
| `gallery_albums(limit = 6, featured = null, taxon = null)` | the published albums, in the list's order; `featured: true` only the featured ones, `false` only the others |
| `gallery_count(taxon = null)` | how many are published |
| `gallery_taxa()` | the taxa that have a page (`gallery.taxon_class`) |

## The partials

| Partial | Takes | |
|---|---|---|
| `@Gallery/client/_album_card.html.twig` | `album`, `heading` (the title's level, 3) | cover, year, title, preview; block `gallery_card_meta` |
| `@Gallery/client/_photos.html.twig` | `photos`, `group` (the lightbox's name) | each photograph through omnibase's `|lightbox`, with its label, caption and credit |
| `@Gallery/client/_filters.html.twig` | `taxa`, `taxon`, `tags`, `tag` | the taxa (pages) and the tags (`?tag=`) |

The pictures go through omnibase's `|picture` (cards) and `|lightbox`
(an album's photographs: a thumbnail that opens the full picture, `srcset`
at 800 and 1600).

## The pages and their blocks

`@Gallery/client/index.html.twig` and `@Gallery/client/album.html.twig`
extend the site's `layout1.html.twig`. A site overrides them in
`templates/bundles/GalleryBundle/client/` - whole, or block by block by
extending the original with `@!Gallery`:

```twig
{# templates/bundles/GalleryBundle/client/album.html.twig #}
{% extends '@!Gallery/client/album.html.twig' %}

{% block gallery_album_after %}
    <a href="{{ path('app_quote', {like: album.slug}) }}">Un projet comme celui-ci ?</a>
{% endblock %}
```

| Template | Blocks |
|---|---|
| `index.html.twig` | `gallery_head`, `gallery_filters`, `gallery_list` |
| `album.html.twig` | `gallery_album_head`, `gallery_album_photos`, `gallery_album_body`, `gallery_album_after` |

## Styles

`bundles/gallery/css/gallery.css` is linked by the two pages. It is neutral:
it takes the page's font and colour, and a site sets its own on `:root` (or
on any element above: the cards and the photographs work outside `.gallery`
too, on a home page).

```css
:root {
    --gallery-accent: #d4382d;       /* the current filter, labels, focus */
    --gallery-accent-ink: #fff;
    --gallery-ink: #000;
    --gallery-muted: #555;
    --gallery-line: #ddd;
    --gallery-surface: #f4f3ed;      /* behind a picture that loads */
    --gallery-gap: 1.5rem;
    --gallery-radius: 0;
    --gallery-columns-min: 17rem;    /* a card's least width */
    --gallery-ratio: 4 / 3;          /* a card's cover */
    --gallery-max: 76rem;
}
```

No scroll library and no animation tied to scrolling: the page scrolls the
way the browser does.
