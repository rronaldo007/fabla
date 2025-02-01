<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

class JuryFormType extends AbstractType
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
                'attr' => ['class' => 'form-control']
            ]);

        // Only add the password field if it's not an edit form
        if (!$options['is_edit']) {
            $builder->add('password', PasswordType::class, [
                'label' => 'Password',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password must be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
                'attr' => ['class' => 'form-control']
            ]);
        }

        $builder
            ->add('userProfile', UserProfileFormType::class, [
                'label' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['is_edit'] ? 'Update Jury Member' : 'Create Jury Member',
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
