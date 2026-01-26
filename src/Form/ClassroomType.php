<?php

namespace App\Form;

use App\Entity\Classroom;
use App\Entity\Diploma;
use App\Entity\SchoolYear;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClassroomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User|null $currentUser */
        $currentUser = $options['currentUser'] ?? null;

        $builder
            ->add('diploma', EntityType::class, [
                'class' => Diploma::class,
                'choice_label' => 'label',
                'label' => 'Diplôme',
                'placeholder' => 'Sélectionner un diplôme',
                'required' => true,
                'query_builder' => function (EntityRepository $er) use ($currentUser) {
                    $qb = $er->createQueryBuilder('d')
                        ->orderBy('d.label', 'ASC');

                    if ($currentUser && $currentUser->getEstablishment()) {
                        $qb
                            ->andWhere('d.establishment = :establishment')
                            ->setParameter('establishment', $currentUser->getEstablishment());
                    }

                    return $qb;
                },
            ])
            ->add('schoolYear', EntityType::class, [
                'class' => SchoolYear::class,
                'choice_label' => fn(SchoolYear $year) =>
                    $year->getLabel() . ($year->isActive() ? ' (Active)' : ''),
                'label' => 'Année scolaire',
                'placeholder' => 'Sélectionner une année scolaire',
                'required' => true,
                'data' => $options['activeSchoolYear'] ?? null,
            ])
            ->add('principalTeacher', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $user) =>
                    $user->getFirstName() . ' ' . $user->getLastName(),
                'label' => 'Professeur principal',
                'required' => false,
                'placeholder' => 'Sélectionner un professeur principal',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Classroom::class,
            'activeSchoolYear' => null,
            'currentUser' => null,
        ]);
    }
}