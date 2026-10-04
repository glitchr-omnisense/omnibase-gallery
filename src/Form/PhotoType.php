<?php

namespace Base\Gallery\Form;

use Base\Field\Type\ImageType;
use Base\Gallery\Entity\Photo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * One photograph of an album in the back office: the picture, its place,
 * its caption, what a screen reader says, the credit, a free label and
 * whether it is the cover.
 *
 * The texts name their domain (@gallery.…): omnibase gives every field the
 * "fields" domain, which the form's own translation_domain does not reach.
 */
class PhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', ImageType::class, ['label' => '@gallery.admin.photo.file', 'required' => false, 'multiple' => false, 'class' => Photo::class, 'data_mapping' => 'file'])
            ->add('position', IntegerType::class, ['label' => '@gallery.admin.photo.position', 'required' => false, 'help' => '@gallery.admin.photo.position_help', 'attr' => ['min' => 1]])
            ->add('caption', TextType::class, ['label' => '@gallery.admin.photo.caption', 'required' => false])
            ->add('alt', TextType::class, ['label' => '@gallery.admin.photo.alt', 'required' => false, 'getter' => fn (Photo $photo): ?string => $photo->getOwnAlt(), 'setter' => function (Photo &$photo, ?string $alt): void { $photo->setAlt($alt); }, 'help' => '@gallery.admin.photo.alt_help'])
            ->add('credit', TextType::class, ['label' => '@gallery.admin.photo.credit', 'required' => false])
            ->add('label', TextType::class, ['label' => '@gallery.admin.photo.label', 'required' => false, 'help' => '@gallery.admin.photo.label_help', 'attr' => ['maxlength' => 60]])
            ->add('cover', CheckboxType::class, ['label' => '@gallery.admin.photo.cover', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Photo::class,
            'empty_data' => fn () => new Photo(),
            'translation_domain' => 'gallery',
        ]);
    }
}
