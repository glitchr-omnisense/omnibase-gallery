---
title: "Albums and photographs"
order: 20
---

# Albums and photographs

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

## An album

| Method | Does |
|---|---|
| `addPhoto($photo)` | adds it once, at the next place |
| `removePhoto($photo)` | takes it out |
| `getOrderedPhotos()` | the photographs by place, then in the order added |
| `getCover()`, `setCover($photo)` | the photograph marked as the cover, else the first; marking one unmarks the others |
| `getPhotosByLabel()` | the photographs carrying a label ("Avant", "Après"), by label, in order |
| `renumberPhotos()` | rewrites the places 1, 2, 3... |
| `publish(bool $published = true, ?DateTimeInterface $at = null)` | published now (or at `$at`), the publication date set the first time; `false`, back to draft |

## A photograph

| Method | Returns |
|---|---|
| `getFile()`, `hasFile()` | the stored picture's public path; whether one is set, without asking the storage |
| `getFileUrl()` | its address on the site (the public directory taken off), for JSON-LD |
| `getAlt()` | what a screen reader says: the alt typed, else the caption, else the album's title; empty without an album |
| `getOwnAlt()` | the alt as typed, or null |
| `getCreditLine()` | the credit with its sign: "Anna Meyer" is printed "© Anna Meyer"; a credit opening with ©, (c), "photo" or "foto" is left as it is |

Captions, alts, credits and labels are trimmed; an empty one is null. The
picture is optional at the database level (`missable`): a photograph whose
file went missing is kept.

## The repository

`Base\Gallery\Repository\AlbumRepository`:

| Method | Returns |
|---|---|
| `findPublishedPage($page, $perPage, $taxon, $tag, $featured)` | a page of the published albums, featured first, then the most recent work |
| `countPublished($taxon, $tag, $featured)` | how many |
| `findLatest($limit = 6, $featured, $taxon)` | the first ones of the list |
| `findAllPublished()` | every published album, for the sitemap |
| `findOnePublished($slug)` | one published album |
| `findNeighbours($album)` | the previous and the next album of the list; none for an album that is not listed |
| `findTags($taxon)` | the tags of the published albums (of one taxon), by priority then slug |
| `findTaxa($class)` | the taxa that have a page, by `position` when the class has one |
| `findTaxon($slug, $class)` | one of them |

The repository takes its entity class as an argument, so an application's
own album class can have a repository extending this one
([Extending Album in an application](extending.md)).
