<?php

namespace App\Form;

use App\Entity\SubjectStudy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

class SubjectStudyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Subject Name',
                'required' => true, // Make it mandatory
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Subject name is required.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Enter subject name',
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => true, // Make it mandatory
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Description is required.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Enter description',
                ],
            ])->add('videoPresantation', FileType::class, [
                'label' => 'Video Presentation (MP4)',
                'mapped' => false,
                'required' => true, // Ensure this is true
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Video presentation is required.',
                    ]),
                    new File([
                        'maxSize' => '50M',
                        'mimeTypes' => [
                            'video/mp4',
                            'video/quicktime', // MOV
                            'video/x-msvideo', // AVI
                            'video/mpeg', // MPEG
                        ],
                        'mimeTypesMessage' => 'Please upload a valid video file (MP4, AVI, MOV).',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubjectStudy::class,
        ]);
    }
}

