<?php

// src/Form/EstablishmentCenterType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class EstablishmentCenterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('generalInfo', TextareaType::class, [
            'label' => 'Informations générales',
            'required' => false,
            'attr' => [
                'rows' => 4,
            ],
        ]);

        //  Directeur 
        $builder->add('director', FormationCenterContactType::class, [
            'label' => 'Directeur',
        ]);

        //  Directeur de campus 
        $builder->add('campusDirector', FormationCenterContactType::class, [
            'label' => 'Directeur de campus',
        ]);

        //  Responsable alternance 
        $builder->add('alternanceManager', FormationCenterContactType::class, [
            'label' => 'Responsable de l’alternance',
        ]);

        //  Référent handicap 
        $builder->add('handicapReferent', FormationCenterContactType::class, [
            'label' => 'Référent handicap',
        ]);
    }
}