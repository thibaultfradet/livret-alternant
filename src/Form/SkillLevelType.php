<?php
// src/Form/SkillLevelType.php

namespace App\Form;

use App\Entity\SkillLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SkillLevelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Libellé du niveau',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Entrez le libellé du niveau',
                ],
            ])
            ->add('color', ColorType::class, [ 
                'label' => 'Couleur',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SkillLevel::class,
        ]);
    }
}