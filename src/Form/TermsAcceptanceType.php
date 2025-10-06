<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class TermsAcceptanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // English comment: Submit button to accept the terms
            ->add('accept', SubmitType::class, [
                'label' => "J'accepte",
                'attr' => [
                    'class' => 'btn btn-danger',
                    'onclick' => "return confirm('Êtes-vous sûr de vouloir accepter les conditions générales d\'utilisation ?')"
                ]
            ]);
    }
}