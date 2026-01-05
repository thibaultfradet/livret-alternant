<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BehaviorLevelReplacementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label_level_replacement', TextType::class, [
                'label' => 'Nouveau libellé',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Entrez le libellé du nouveau niveau'
                ],
                'help' => 'Attention, cette action est irréversible !',
                'help_attr' => ['class' => 'text-danger small fw-bold mt-1']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}