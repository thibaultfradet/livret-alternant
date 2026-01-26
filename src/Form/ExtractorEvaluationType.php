<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExtractorEvaluationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Hidden input for period
            ->add('periodId', HiddenType::class)

            // Students checkboxes (choices injected from controller)
            ->add('selectedStudents', ChoiceType::class, [
                'choices' => $options['students'],      
                'choice_label' => fn($student) => $student->getFirstName() . ' ' . $student->getLastName(),
                'choice_value' => fn($student) => $student ? $student->getId() : '',  
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'students' => [], // we inject from controller
        ]);
    }
}
