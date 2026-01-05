<?php

namespace App\Form;

use App\Entity\SchoolYear;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SchoolYearType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', null, [
                'label' => 'Libellé de l’année scolaire',
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Année scolaire active',
                'required' => false,
            ])
            ->add('termsContent', TextareaType::class, [
                'label' => 'Conditions générales',
                'required' => false,
                'attr' => [
                    'class' => 'summernote',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SchoolYear::class,
        ]);
    }
}