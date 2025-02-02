<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('new_password', PasswordType::class, [
                'label' => 'New Password',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirm New Password',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('reset', SubmitType::class, [
                'label' => 'Reset Password',
                'attr' => ['class' => 'btn btn-success w-100'],
            ]);
    }
}
    