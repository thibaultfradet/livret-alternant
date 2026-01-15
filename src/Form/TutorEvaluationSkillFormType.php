<?php

namespace App\Form;

use App\Entity\SkillCriteria;
use App\Entity\SkillLevel;
use App\Entity\TutorEvaluationSkill;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TutorEvaluationSkillFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('skillCriteria', EntityType::class, [
                'class' => SkillCriteria::class,
                'choice_label' => 'label',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de sélectionner une compétence.',
                    ]),
                ],
                'attr' => [
                    'class' => 'd-none',
                ],
            ])
            ->add('skillLevel', EntityType::class, [
                'class' => SkillLevel::class,
                'choice_label' => 'label',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Merci de sélectionner un niveau de compétence.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TutorEvaluationSkill::class,
        ]);
    }
}