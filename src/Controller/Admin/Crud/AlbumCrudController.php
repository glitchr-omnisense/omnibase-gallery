<?php

namespace Base\Gallery\Controller\Admin\Crud;

use Base\Admin\Attribute\AdminAction;
use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Admin\Controller\AbstractCrudController;
use Base\Admin\Filter\Filters;
use Base\Field\BooleanField;
use Base\Field\CollectionField;
use Base\Field\DateField;
use Base\Field\DateTimeField;
use Base\Field\EditorField;
use Base\Field\IdField;
use Base\Field\SelectField;
use Base\Field\SlugField;
use Base\Field\StateField;
use Base\Field\TextareaField;
use Base\Field\TextField;
use Base\Gallery\Controller\Admin\OpenToAdminsTrait;
use Base\Gallery\Entity\Album;
use Base\Entity\Thread\Taxon;
use Base\Gallery\Form\PhotoType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * An album in the back office: its title, the day the work was made, the
 * preview shown on the list, its text, its tags and taxa, and its
 * photographs - each with its place, saved in that order. Two buttons:
 * "Publier" (or back to draft) and "À la une".
 *
 * An application's subclass of Album has its own screen by extending this
 * one: getEntityFqcn() returns its class, configureFields() yields
 * parent's fields and its own (docs/extending.md).
 */
class AlbumCrudController extends AbstractCrudController
{
    use OpenToAdminsTrait;

    /** The taxa an album is classified by (gallery.taxon_class): the families of the site's own class when it names one. */
    protected string $taxonClass = Taxon::class;

    #[Required]
    public function setTaxonClass(#[Autowire('%gallery.taxon_class%')] string $taxonClass = Taxon::class): void
    {
        $this->taxonClass = $taxonClass;
    }

    public static function getEntityFqcn(): string
    {
        return Album::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-images';
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('state')->add('featured')->add('tags')->add('taxa');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        foreach ([Actions::PAGE_INDEX, Actions::PAGE_DETAIL, Actions::PAGE_EDIT] as $page) {
            $actions
                ->add($page, Action::new('publish', '@gallery.admin.album.action.publish', 'fa-solid fa-eye')
                    ->linkToCrudAction('publish')->displayIf(fn ($album) => $album instanceof Album && !$album->isPublished()))
                ->add($page, Action::new('unpublish', '@gallery.admin.album.action.unpublish', 'fa-solid fa-eye-slash')
                    ->linkToCrudAction('unpublish')->displayIf(fn ($album) => $album instanceof Album && $album->isPublished()))
                ->add($page, Action::new('feature', '@gallery.admin.album.action.feature', 'fa-regular fa-star')
                    ->linkToCrudAction('feature')->displayIf(fn ($album) => $album instanceof Album && !$album->isFeatured()))
                ->add($page, Action::new('unfeature', '@gallery.admin.album.action.unfeature', 'fa-solid fa-star')
                    ->linkToCrudAction('unfeature')->displayIf(fn ($album) => $album instanceof Album && $album->isFeatured()));
        }

        return $this->openToAdmins($actions, 'publish', 'unpublish', 'feature', 'unfeature');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('title', '@gallery.admin.album.title')->setColumns(8);
        yield StateField::new('state')->setColumns(4);
        yield SlugField::new('slug')->setTargetFieldName('title')->setColumns(4)->hideOnIndex();
        yield DateField::new('madeAt', '@gallery.admin.album.made_at')->setColumns(3)->setRequired(false)->setHelp('@gallery.admin.album.made_at_help');
        yield DateTimeField::new('publishedAt', '@gallery.admin.album.published_at')->setRequired(false)->setColumns(3)->hideOnIndex();
        yield BooleanField::new('featured', '@gallery.admin.album.featured')->setColumns(2);
        yield TextareaField::new('excerpt', '@gallery.admin.album.excerpt')->setRequired(false)->hideOnIndex()->setHelp('@gallery.admin.album.excerpt_help');
        yield EditorField::new('content', '@gallery.admin.album.content')->hideOnIndex();
        yield SelectField::new('tags', '@gallery.admin.album.tags')->allowMultipleChoices()->setRequired(false)->setColumns(6)->hideOnIndex();
        // The taxa of gallery.taxon_class only: with a site's own class (a Category), the other bundles'
        // taxa - a shop's categories of the same names - are not offered beside its families, twice each.
        yield SelectField::new('taxa', '@gallery.admin.album.taxa')->setClass($this->taxonClass)->allowMultipleChoices()->setRequired(false)->setColumns(6)->hideOnIndex();
        yield CollectionField::new('photos', '@gallery.admin.album.photos')->setEntryType(PhotoType::class)->allowAdd()->allowDelete()->allowObject()->hideOnIndex()
            ->setFormTypeOptions(['by_reference' => false])->setHelp('@gallery.admin.album.photos_help');
    }

    public function createEntity(string $entityFqcn): object
    {
        /** @var Album $album */
        $album = new $entityFqcn();
        $user = $this->getUser();
        if ($user instanceof \Base\Entity\User) {
            $album->addOwner($user);
        }

        return $album;
    }

    /** Saved in the order typed: the places rewritten 1, 2, 3..., one cover at most. */
    public function persistEntity(EntityManagerInterface $entityManager, object $entity): void
    {
        $this->tidy($entity);
        parent::persistEntity($entityManager, $entity);
    }

    public function updateEntity(EntityManagerInterface $entityManager, object $entity): void
    {
        $this->tidy($entity);
        parent::updateEntity($entityManager, $entity);
    }

    #[AdminAction('/{entityId}/publish')]
    public function publish(string $entityId): Response
    {
        return $this->change($entityId, fn (Album $album) => $album->publish(), '@gallery.admin.album.flash.published');
    }

    #[AdminAction('/{entityId}/unpublish')]
    public function unpublish(string $entityId): Response
    {
        return $this->change($entityId, fn (Album $album) => $album->publish(false), '@gallery.admin.album.flash.unpublished');
    }

    #[AdminAction('/{entityId}/feature')]
    public function feature(string $entityId): Response
    {
        return $this->change($entityId, fn (Album $album) => $album->setFeatured(true), '@gallery.admin.album.flash.featured');
    }

    #[AdminAction('/{entityId}/unfeature')]
    public function unfeature(string $entityId): Response
    {
        return $this->change($entityId, fn (Album $album) => $album->setFeatured(false), '@gallery.admin.album.flash.unfeatured');
    }

    private function change(string $entityId, callable $change, string $flash): Response
    {
        /** @var Album $album */
        $album = $this->findEntity($entityId);
        $change($album);
        $this->entityManager->flush();
        $this->addFlash('success', $flash);

        return $this->redirectToIndex();
    }

    private function tidy(object $album): void
    {
        if (!$album instanceof Album) {
            return;
        }
        $album->renumberPhotos();
        $covers = array_values(array_filter($album->getOrderedPhotos(), fn ($photo) => $photo->isCover()));
        if (\count($covers) > 1) {
            $album->setCover($covers[0]);
        }
    }
}
