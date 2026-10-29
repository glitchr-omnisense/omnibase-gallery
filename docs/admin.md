---
title: The back office
order: 36
---

# The back office

With `omnibase/admin` installed, two screens are registered (they are open
to `ROLE_ADMIN`):

| Screen | Controller | |
|---|---|---|
| Albums | `Base\Gallery\Controller\Admin\Crud\AlbumCrudController` | title, state, slug, the day the work was made, publication date, featured, preview, text, tags, taxa, and the photographs |
| Photographs | `Base\Gallery\Controller\Admin\Crud\PhotoCrudController` | every photograph, album by album and in order: to fix a caption or a credit without opening the album |

```php
// App\Controller\Admin\DashboardController::configureWidgetItems()
yield MenuItem::section('Galerie', 'fa-solid fa-images')->setSubItems([
    MenuItem::linkToCrud(\Base\Gallery\Entity\Album::class, 'Albums', 'fa-solid fa-images'),
    MenuItem::linkToCrud(\Base\Gallery\Entity\Photo::class, 'Photos', 'fa-solid fa-camera'),
]);
```

## The photographs of an album

They are typed in the album's own form (`Base\Gallery\Form\PhotoType`: the
picture, its place, caption, alternative text, credit, label, cover). Saved,
the album rewrites the places 1, 2, 3… in the order typed - a photograph
given the place of another comes before the ones added after it - and keeps
one cover at most (none ticked: the first photograph).

## Publier, À la une

Four actions on the list, the detail and the form
(`#[AdminAction]`, POST with a CSRF token): **Publier** / **Dépublier**
(`Album::publish()`: the state, and the publication date the first time),
**Mettre à la une** / **Retirer de la une** (`featured`). Only the one that
applies is shown.
