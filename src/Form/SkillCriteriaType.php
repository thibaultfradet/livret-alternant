<?php
// src/Form/SkillCriteriaType.php
namespace App\Form;

use App\Entity\SkillCriteria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SkillCriteriaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Libellé de la compétence',
                'required' => true,
                'attr' => ['placeholder' => 'Entrez le libellé de la compétence']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SkillCriteria::class,
        ]);
    }
}