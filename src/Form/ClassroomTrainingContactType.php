<?php

namespace App\Form;

use App\Entity\Classroom;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClassroomTrainingContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('trainingContactName', TextType::class, [
                'label' => 'Nom du référent de formation',
                'required' => false,
            ])
            ->add('trainingContactPhone', TextType::class, [
                'label' => 'Téléphone du référent de formation',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Classroom::class,
        ]);
    }
}