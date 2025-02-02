<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForgotPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['placeholder' => 'Enter your registered email', 'class' => 'form-control'],
            ])
            ->add('send', SubmitType::class, [
                'label' => 'Send Reset Link',
                'attr' => ['class' => 'btn btn-primary w-100'],
            ]);
    }
}
