<?php

namespace Base\Gallery\Controller\Admin\Crud;

use Base\Admin\Config\Actions;
use Base\Admin\Config\Crud;
use Base\Admin\Controller\AbstractCrudController;
use Base\Admin\Filter\Filters;
use Base\Field\AssociationField;
use Base\Field\BooleanField;
use Base\Field\IdField;
use Base\Field\ImageField;
use Base\Field\IntegerField;
use Base\Field\TextField;
use Base\Gallery\Controller\Admin\OpenToAdminsTrait;
use Base\Gallery\Entity\Album;
use Base\Gallery\Entity\Photo;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Every photograph of every album, album by album and in order: to find
 * one, fix a caption or a credit without opening its album. A photograph
 * is added from its album's screen as well.
 */
class PhotoCrudController extends AbstractCrudController
{
    use OpenToAdminsTrait;

    public static function getEntityFqcn(): string
    {
        return Photo::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-camera';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)->setDefaultSort(['album' => 'DESC', 'position' => 'ASC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('album')->add('cover')->add('label');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->openToAdmins(parent::configureActions($actions));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('album', '@gallery.admin.photo.album')->setFormType(EntityType::class)->setFormTypeOptions(['class' => Album::class])->setColumns(6);
        yield ImageField::new('file', '@gallery.admin.photo.file')->setColumns(6)->setRequired(false);
        yield IntegerField::new('position', '@gallery.admin.photo.position')->setColumns(2)->setRequired(false);
        yield BooleanField::new('cover', '@gallery.admin.photo.cover')->setColumns(2);
        yield TextField::new('label', '@gallery.admin.photo.label')->setColumns(4)->setRequired(false)->setHelp('@gallery.admin.photo.label_help');
        yield TextField::new('caption', '@gallery.admin.photo.caption')->setColumns(12)->setRequired(false);
        yield TextField::new('ownAlt', '@gallery.admin.photo.alt')->setColumns(6)->setRequired(false)->hideOnIndex()->setHelp('@gallery.admin.photo.alt_help');
        yield TextField::new('credit', '@gallery.admin.photo.credit')->setColumns(6)->setRequired(false)->hideOnIndex();
    }
}
