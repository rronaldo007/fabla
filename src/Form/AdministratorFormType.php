<?php

namespace App\Form;

use App\Entity\User;
use App\Form\AdministratorProfileFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

// AdministratorFormType.php
class AdministratorFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter an email address']),
                    new Email(['message' => 'Please enter a valid email address']),
                ],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter email address']
            ]);

        // Add password field with different validation based on whether it's edit mode
        $builder->add('password', PasswordType::class, [
            'label' => 'Password',
            'required' => !$options['is_edit'],
            'constraints' => $options['is_edit'] ? [] : [
                new NotBlank(['message' => 'Please enter a password']),
                new Length(['min' => 6, 'minMessage' => 'Password must be at least {{ limit }} characters']),
            ],
            'attr' => ['class' => 'form-control', 'placeholder' => $options['is_edit'] ? 'Leave blank to keep current password' : 'Enter password'],
            'empty_data' => '',
        ]);

        $builder
            ->add('userProfile', AdministratorProfileFormType::class, ['label' => false])
            ->add('save', SubmitType::class, [
                'label' => $options['is_edit'] ? 'Update Administrator' : 'Create Administrator',
                'attr' => ['class' => 'btn btn-primary mt-3']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}

