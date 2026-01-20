<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class FormationCenterContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'empty_data' => '',
            ]);
    }
}