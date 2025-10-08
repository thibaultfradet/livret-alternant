<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
            ])
            // Checkbox for main teacher role
            ->add('isProfPrincipal', CheckboxType::class, [
                'label' => 'Est un professeur référent',
                'required' => false,
                'mapped' => false, // Not a real entity field
            ])
            // Checkbox for teaching team member role
            ->add('isTeamMember', CheckboxType::class, [
                'label' => 'Est un membre de l\'équipe pédagogique',
                'required' => false,
                'mapped' => false, // Not a real entity field
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