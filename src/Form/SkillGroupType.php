<?php
// src/Form/SkillGroupType.php
namespace App\Form;

use App\Entity\SkillGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SkillGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Nom du groupe',
                'required' => true,
                'attr' => ['placeholder' => 'Entrez le nom du groupe']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SkillGroup::class,
        ]);
    }
}