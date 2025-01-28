<?php

namespace App\Form;

use App\Entity\CandidateProfile;
use App\Entity\Nationality;
use App\Entity\School;
use App\Entity\Specialization;
use App\Entity\UserProfile;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CandidateProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('programEntryDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Program Entry Date',
            ])
            ->add('currentYear', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Current Academic Year',
            ])
            ->add('currentSchool', EntityType::class, [
                'class' => School::class,
                'choice_label' => 'name', // Use a meaningful property like "name" instead of "id"
                'label' => 'Current School',
            ])
            ->add('specialization', EntityType::class, [
                'class' => Specialization::class,
                'choice_label' => 'name', // Use "name" instead of "id" for better UX
                'label' => 'Specialization',
            ])
            ->add('nationality', EntityType::class, [
                'class' => Nationality::class,
                'choice_label' => 'name', // Use "name" for a user-friendly selection
                'label' => 'Nationality',
            ])
            ->add('userProfile', EntityType::class, [
                'class' => UserProfile::class,
                'choice_label' => function ($userProfile) {
                    return $userProfile->getUser()->getEmail(); // Access the email via the User entity
                },
                'label' => 'User Profile',
            ])
            ->add('studentCardPath', FileType::class, [
                'label' => 'Upload Student Card',
                'mapped' => false, // Prevent automatic mapping to the entity
                'required' => false, // Make file upload optional
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'application/pdf',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF, JPG, or PNG file.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CandidateProfile::class,
        ]);
    }
}
