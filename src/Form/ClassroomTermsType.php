<?php

namespace App\Form;

use App\Entity\Classroom;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ClassroomTermsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Field for professional terms
            ->add('termsConditionsPro', TextareaType::class, [
                'label' => 'Conditions Pro',
                'required' => false,
                'attr' => [
                    'class' => 'summernote',
                ],
            ])
            // Field for alternance terms
            ->add('termsConditionsAlternance', TextareaType::class, [
                'label' => 'Conditions Alternance',
                'required' => false,
                'attr' => [
                    'class' => 'summernote',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Bind this form to the Classroom entity
            'data_class' => Classroom::class,
        ]);
    }
}