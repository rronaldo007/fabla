<?php

namespace App\Form;

use App\Entity\UserProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class UserProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'attr' => ['class' => 'form-control']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'attr' => ['class' => 'form-control']
            ])
            ->add('phone', TelType::class, [
                'label' => 'Phone Number',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[0-9]*$/',
                        'message' => 'Phone number must contain only digits'
                    ]),
                    new Length([
                        'min' => 10,
                        'max' => 15,
                        'minMessage' => 'Phone number must be at least {{ limit }} digits',
                        'maxMessage' => 'Phone number cannot exceed {{ limit }} digits'
                    ])
                ],
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Complete Address',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('juryProfile', JuryProfileFormType::class, [
                'label' => false, // Embed JuryProfile inside UserProfile
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserProfile::class,
        ]);
    }
}
