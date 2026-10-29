---
title: Extending Album in an application
order: 37
---

# Extending Album in an application

An album is a creation; what a creation *is* belongs to the site. A sign
maker's realisation has a customer, a town and a technical sheet; a
caterer's event has a number of guests. That is a subclass of `Album` in
the application - not a field added here, and not another bundle.

## The entity

```php
namespace App\Entity\Portfolio;

use App\Repository\Portfolio\RealisationRepository;
use Base\Database\Attribute\DiscriminatorEntry;
use Base\Gallery\Entity\Album;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RealisationRepository::class)]
#[ORM\Table(name: 'portfolio_realisation')]
#[DiscriminatorEntry(value: 'portfolio_realisation')]     // JOINED on thread, like Album on Thread
class Realisation extends Album
{
    #[ORM\Column(length: 160, nullable: true)]
    protected ?string $client = null;

    #[ORM\Column(type: 'json')]
    protected array $facts = [];                           // [{label, value}]: support, format, pose

    // getters and setters
}
```

Its row is three tables joined: `thread`, `gallery_album`,
`portfolio_realisation`. A migration creates the third.

## The repository

Every query of `Base\Gallery\Repository\AlbumRepository` is on `Album`:
with JOINED inheritance a `Realisation` comes back as a `Realisation`, so
the pages, `gallery_albums()` and the sitemap need nothing. For the site's
own queries, extend it and pass the class:

```php
class RealisationRepository extends AlbumRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Realisation::class);   // its queries now return realisations only
    }
}
```

## The taxa

The taxa that get a page are those of `gallery.taxon_class`. A site whose
albums are classified by its own taxon (a subclass of omnibase's `Taxon`,
with an introduction and a cover, say) names it:

```yaml
gallery:
    path: realisations
    taxon_class: App\Entity\Portfolio\Category
```

`/realisations/enseigne` is then the page of the `Category` whose slug is
`enseigne`, with `taxon` in the template being that `Category`.

## The pages

Override the templates in `templates/bundles/GalleryBundle/client/` and
extend the originals block by block (`@!Gallery/...`): `album` is the
subclass, its own fields are there.

```twig
{% extends '@!Gallery/client/album.html.twig' %}

{% block gallery_album_body %}
    <dl>{% for fact in album.facts %}<dt>{{ fact.label }}</dt><dd>{{ fact.value }}</dd>{% endfor %}</dl>
    {{ parent() }}
{% endblock %}
```

## The back office

Extend the album's screen: the class, then the fields.

```php
namespace App\Controller\Admin\Crud\Portfolio;

use App\Entity\Portfolio\Realisation;
use Base\Field\TextField;
use Base\Gallery\Controller\Admin\Crud\AlbumCrudController;

class RealisationCrudController extends AlbumCrudController
{
    public static function getEntityFqcn(): string
    {
        return Realisation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield from parent::configureFields($pageName);
        yield TextField::new('client', 'Client')->setColumns(6);
    }
}
```

The photographs, Publier and À la une come with it. Link the menu to the
subclass (`MenuItem::linkToCrud(Realisation::class, …)`).

## Taking Album over instead

To change `Album` itself for the whole site, declare
`App\Entity\Gallery\Album extends Base\Gallery\Entity\Album`: omnibase maps
`App\Entity\Gallery` over the bundle's entities (App wins), as it does for
its own.
