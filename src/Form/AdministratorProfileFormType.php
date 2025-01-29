<?php

namespace App\Form;

use App\Entity\UserProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;

class AdministratorProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'First Name'
            ])
            ->add('last_name', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Last Name'
            ])
            ->add('phone', TelType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'pattern' => '[0-9]*',
                    'inputmode' => 'numeric',
                    'placeholder' => 'Enter phone number (digits only) e.g. 0123456789'
                ],
                'required' => false,
                'label' => 'Phone Number',
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[0-9]*$/',
                        'message' => 'Phone number can only contain digits'
                    ]),
                    new Length([
                        'min' => 10,
                        'max' => 15,
                        'minMessage' => 'Phone number must be at least {{ limit }} digits',
                        'maxMessage' => 'Phone number cannot be longer than {{ limit }} digits'
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserProfile::class,
        ]);
    }
}