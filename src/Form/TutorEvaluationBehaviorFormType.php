<?php

namespace App\Form;

use App\Entity\BehaviorCriteria;
use App\Entity\BehaviorLevel;
use App\Entity\SkillCriteria;
use App\Entity\SkillLevel;
use App\Entity\TutorEvaluationBehavior;
use App\Entity\TutorEvaluationSkill;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TutorEvaluationBehaviorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('behaviorCriteria', EntityType::class, [
                'class' => BehaviorCriteria::class,
                'choice_label' => 'label',
            ])
            ->add('behaviorLevel', EntityType::class, [
                'class' => BehaviorLevel::class,
                'choice_label' => 'label',
                'placeholder' => 'Sélectionnez un niveau',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TutorEvaluationBehavior::class,
        ]);
    }
}
