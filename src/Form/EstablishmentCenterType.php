<?php

// src/Form/EstablishmentCenterType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class EstablishmentCenterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('director', FormationCenterContactType::class, [
                'label' => 'Directeur',
            ])
            ->add('campusDirector', FormationCenterContactType::class, [
                'label' => 'Directrice du campus',
            ])
            ->add('alternanceManager', FormationCenterContactType::class, [
                'label' => 'Responsable de l’alternance',
            ])
            ->add('handicapReferent', FormationCenterContactType::class, [
                'label' => 'Référent handicap',
            ]);
    }
}