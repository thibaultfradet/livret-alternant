<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateBehaviorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label_behavior', TextType::class, [
                'label' => 'Libellé du comportement',
                'required' => true,
            ])
            ->add('niveau1', TextType::class, [
                'label' => 'Niveau 1',
                'required' => true,
            ])
            ->add('niveau2', TextType::class, [
                'label' => 'Niveau 2',
                'required' => true,
            ])
            ->add('niveau3', TextType::class, [
                'label' => 'Niveau 3',
                'required' => true,
            ])
            ->add('niveau4', TextType::class, [
                'label' => 'Niveau 4',
                'required' => true,
            ])
            ->add('niveau5', TextType::class, [
                'label' => 'Niveau 5',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // This form is not directly bound to an entity
            'data_class' => null,
        ]);
    }
}