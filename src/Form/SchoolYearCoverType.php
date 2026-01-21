<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class SchoolYearCoverType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Field for uploading the first cover page image
            ->add('cover_file_1', FileType::class, [
                'label' => 'Page de garde 1 (Image)',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'Le fichier ne peut pas dépasser 5 Mo.',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF, WebP).'
                    ])
                ],
                'attr' => [
                    'accept' => 'image/*' // only allow image files
                ],
                'help' => 'Formats acceptés : JPEG, PNG, GIF, WebP. Taille max : 5 Mo. Première page de garde.'
            ])
            // Field for uploading the second cover page image
            ->add('cover_file_2', FileType::class, [
                'label' => 'Page de garde 2 (Image)',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'Le fichier ne peut pas dépasser 5 Mo.',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF, WebP).'
                    ])
                ],
                'attr' => [
                    'accept' => 'image/*' // only allow image files
                ],
                'help' => 'Formats acceptés : JPEG, PNG, GIF, WebP. Taille max : 5 Mo. Deuxième page de garde.'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}