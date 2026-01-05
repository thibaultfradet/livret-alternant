<?php
// src/Form/SkillLevelType.php
namespace App\Form;

use App\Entity\SkillLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SkillLevelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Libellé du niveau',
                'required' => true,
                'attr' => ['placeholder' => 'Entrez le libellé du niveau']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SkillLevel::class,
        ]);
    }
}