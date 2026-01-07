<?php

namespace App\Form;

use App\Entity\TutorEvaluation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TutorEvaluationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('skillEvaluation', CollectionType::class, [
                'entry_type' => TutorEvaluationSkillFormType::class,
                'allow_add' => true,
                'by_reference' => false,
                'label' => 'Évaluation des compétences',
            ])
            ->add('behaviorEvaluation', CollectionType::class, [
                'entry_type' => TutorEvaluationBehaviorFormType::class,
                'allow_add' => true,
                'by_reference' => false,
                'label' => 'Évaluation du comportement',
            ])
            ->add('strengths', TextareaType::class, [
                'label' => 'Forces',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de renseigner les forces.',
                    ]),
                ],
            ])
            ->add('weaknesses', TextareaType::class, [
                'label' => 'Faiblesses',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de renseigner les faiblesses.',
                    ]),
                ],
            ])
            ->add('goals', TextareaType::class, [
                'label' => 'Objectifs',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de renseigner les objectifs.',
                    ]),
                ],
            ])
            ->add('remarks', TextareaType::class, [
                'label' => 'Remarques',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de renseigner les remarques.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TutorEvaluation::class,
        ]);
    }
}