<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\SharedResource;
use App\Enum\TimeSlot;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startTime', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
                'attr' => ['class' => 'form-control']
            ])
            ->add('timeSlot', EnumType::class, [
                'class' => TimeSlot::class,
                'label' => 'Time Slot',
                'attr' => ['class' => 'form-control']
            ])
            ->add('resource', EntityType::class, [
                'class' => SharedResource::class,
                'choice_label' => 'name',
                'label' => 'Resource',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}