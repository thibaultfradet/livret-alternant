<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire dédié à l'édition d'un membre du personnel pédagogique
 * (professeur référent ROLE_PT et/ou membre de l'équipe pédagogique ROLE_TTM).
 * Les deux rôles sont interchangeables ; aucun champ alternant/tuteur n'est exposé.
 */
class StaffType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('firstName', TextType::class, ['label' => 'Prénom'])
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('isProfPrincipal', CheckboxType::class, [
                'label' => 'Est un professeur référent',
                'required' => false,
                'mapped' => false,
            ])
            ->add('isTeamMember', CheckboxType::class, [
                'label' => "Est un membre de l'équipe pédagogique",
                'required' => false,
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
