<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SchoolYearCoverType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Field for uploading the cover page image
            ->add('cover_file', FileType::class, [
                'label' => 'Page de garde (Image)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => 'image/*' // only allow image files
                ],
                'help' => 'Formats acceptés : JPEG, PNG, GIF, WebP. La page de garde sera utilisée pour cette année scolaire.'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}