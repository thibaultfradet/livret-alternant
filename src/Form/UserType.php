<?php

namespace App\Form;

use App\Entity\Classroom;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;


class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('firstName', TextType::class, ['label' => 'Prénom'])
            ->add('lastName', TextType::class, ['label' => 'Nom'])
            
            // Checkbox for main teacher role
            ->add('isProfPrincipal', CheckboxType::class, [
                'label' => 'Est un professeur référent',
                'required' => false,
                'mapped' => false,
            ])
            
            // Checkbox for teaching team member role
            ->add('isTeamMember', CheckboxType::class, [
                'label' => 'Est un membre de l\'équipe pédagogique',
                'required' => false,
                'mapped' => false,
            ])
            
            // Checkbox for alternance student
            ->add('isAlternance', CheckboxType::class, [
                'label' => 'Contrat d\'alternance',
                'required' => false,
            ])
            
            // Select classroom for students
            ->add('classroom', EntityType::class, [
                'class' => Classroom::class,
                'choice_label' => function($classroom) {
                    return $classroom->getDiploma()->getLabel(); // display diploma label
                },
                'placeholder' => 'Sélectionnez une classe',
                'required' => false,
                'label' => 'Classe',
            ])

            // Tutor information fields (for students)
            ->add('tutorFirstName', TextType::class, [
                'label' => 'Prénom du tuteur',
                'required' => false,
                'mapped' => false,
            ])
            ->add('tutorLastName', TextType::class, [
                'label' => 'Nom du tuteur',
                'required' => false,
                'mapped' => false,
            ])
            ->add('tutorEmail', EmailType::class, [
                'label' => 'Email du tuteur',
                'required' => false,
                'mapped' => false,
            ])
            ->add('tutorPhone', TextType::class, [
                'label' => 'Téléphone du tuteur',
                'required' => false,
                'mapped' => false,
            ])
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'required' => false,
                'mapped' => false,
            ])
            ->add('companyAddress', TextType::class, [
                'label' => 'Adresse de l\'entreprise',
                'required' => false,
                'mapped' => false,
            ])
            ->add('dateDebutContract', DateType::class, [
                'label' => 'Date de début du contrat',
                'required' => false,
                'mapped' => false,
                'widget' => 'single_text',
            ])
            ->add('dateFinContract', DateType::class, [
                'label' => 'Date de fin du contrat',
                'required' => false,
                'mapped' => false,
                'widget' => 'single_text',
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