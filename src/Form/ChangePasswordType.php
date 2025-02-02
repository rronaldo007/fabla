<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('current_password', PasswordType::class, [
                'label' => 'Current Password',
                'mapped' => false, // This field is not related to the entity
                'attr' => ['autocomplete' => 'current-password'],
            ])
            ->add('new_password', PasswordType::class, [
                'label' => 'New Password',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirm New Password',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Change Password',
                'attr' => ['class' => 'btn btn-primary w-100'],
            ]);
    }
}
