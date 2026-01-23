<?php

namespace App\Form;

use App\Entity\Diploma;
use App\Entity\Period;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TutorEvaluationFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $diplomas = $options['diplomas'];
        $periods = $options['periods'];

        $builder
            ->add('diploma', EntityType::class, [
                'class' => Diploma::class,
                'choices' => $diplomas,
                'choice_label' => 'label',
                'required' => false,
                'placeholder' => '-- Tous --',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('period', EntityType::class, [
                'class' => Period::class,
                'choices' => $periods,
                'choice_label' => fn(Period $period) => sprintf(
                    'Période %d (%s - %s)',
                    $period->getPeriodNumber(),
                    $period->getStartDate()->format('d/m/Y'),
                    $period->getEndDate()->format('d/m/Y')
                ),
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'diplomas' => [],
            'periods' => [],
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}
