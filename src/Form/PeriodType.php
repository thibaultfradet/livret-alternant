<?php

namespace App\Form;

use App\Entity\Period;
use App\Entity\SchoolYear;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User|null $currentUser */
        $currentUser = $options['currentUser'] ?? null;

        $builder
            ->add('periodNumber', IntegerType::class, [
                'label' => 'Numéro de période',
                'attr' => ['placeholder' => 'Ex: 1, 2, 3...'],
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text', // HTML5 date picker
                'html5' => true,
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('schoolYear', EntityType::class, [
                'class' => SchoolYear::class,
                'choice_label' => 'label',
                'label' => 'Année scolaire',
                'placeholder' => 'Sélectionnez une année',
                'query_builder' => function (EntityRepository $er) use ($currentUser) {
                    $qb = $er->createQueryBuilder('sy')
                        ->where('sy.disabledAt IS NULL')
                        ->orderBy('sy.label', 'ASC');

                    if ($currentUser && $currentUser->getEstablishment()) {
                        $qb
                            ->andWhere('sy.establishment = :establishment')
                            ->setParameter('establishment', $currentUser->getEstablishment());
                    }

                    return $qb;
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Period::class,
            'currentUser' => null,
        ]);
    }
}