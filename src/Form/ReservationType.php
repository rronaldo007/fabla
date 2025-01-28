<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Enum\TimeSlot;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
                'input' => 'datetime',
                'required' => true,
            ])
            ->add('timeSlot', EnumType::class, [
                'class' => TimeSlot::class,
                'label' => 'Time Slot',
                'required' => true,
                'choice_label' => function (TimeSlot $timeSlot) {
                    return match($timeSlot) {
                        TimeSlot::MORNING => 'Morning (8:00 - 12:00)',
                        TimeSlot::AFTERNOON => 'Afternoon (13:00 - 17:00)',
                    };
                },
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Create Reservation',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'csrf_protection' => true,
            'allow_extra_fields' => false, // Explicitly disallow extra fields
        ]);
    }
}