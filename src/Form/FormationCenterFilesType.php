<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormationCenterFilesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Single file upload field for the formation center
            ->add('formation_center_file', FileType::class, [
                'label' => 'Fichier du centre de formation (Image)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => 'image/*' // Only allow image files
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}