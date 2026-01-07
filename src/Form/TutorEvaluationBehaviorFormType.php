<?php

namespace App\Form;

use App\Entity\BehaviorCriteria;
use App\Entity\BehaviorLevel;
use App\Entity\TutorEvaluationBehavior;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TutorEvaluationBehaviorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('behaviorCriteria', EntityType::class, [
                'class' => BehaviorCriteria::class,
                'choice_label' => 'label',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de sélectionner un critère de comportement.',
                    ]),
                ],
            ])
            ->add('behaviorLevel', EntityType::class, [
                'class' => BehaviorLevel::class,
                'choice_label' => 'label',
                'placeholder' => 'Sélectionnez un niveau',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de sélectionner un niveau de comportement.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TutorEvaluationBehavior::class,
        ]);
    }
}