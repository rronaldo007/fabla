<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class DataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('timeSlot', ChoiceType::class, [
                'label' => 'Time Slot',
                'choices' => [
                    'Morning (8:00 - 12:00)' => 'morning',
                    'Afternoon (13:00 - 17:00)' => 'afternoon',
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please select a time slot.'
                    ])
                ]
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
                'required' => true,
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Please select a date.'
                    ])
                ]
            ]);
    }
    
    public function getParent()
    {
        return FormType::class;
    }
}