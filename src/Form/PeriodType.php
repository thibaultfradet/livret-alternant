<?php

namespace App\Form;

use App\Entity\Period;
use App\Entity\SchoolYear;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('periodNumber')
            ->add('startDate')
            ->add('endDate')
            ->add('disabledAt')
            ->add('schoolYear', EntityType::class, [
                'class' => SchoolYear::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Period::class,
        ]);
    }
}
