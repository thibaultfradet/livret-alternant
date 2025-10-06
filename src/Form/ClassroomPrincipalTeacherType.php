<?php

namespace App\Form;

use App\Entity\Classroom;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClassroomPrincipalTeacherType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('principalTeacher', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $user) => $user->getLastName() . " " . $user->getFirstName(),
                'placeholder' => 'Aucun',
                'query_builder' => function($repo) {
                    return $repo->createQueryBuilder('u')
                        ->where('u.roles NOT LIKE :role')
                        ->setParameter('role', '%ROLE_STUDENT%')
                        ->orderBy('u.lastName', 'ASC');
                },
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Classroom::class,
        ]);
    }
}