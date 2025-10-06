<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClassroomFilesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Field for uploading the calendar image
            ->add('calendar_file', FileType::class, [
                'label' => 'Calendrier (Image)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => 'image/*' // only allow image files
                ],
            ])
            // Field for uploading the schedule image
            ->add('schedule_file', FileType::class, [
                'label' => 'Emploi du temps (Image)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => 'image/*' // only allow image files
                ],
            ])
            // Field for uploading the teacher list image
            ->add('teacher_list', FileType::class, [
                'label' => 'Liste des professeurs (Image)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => 'image/*' // only allow image files
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}