<?php

namespace App\Form;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationExchangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $excludeUser = $options['excludeUser'];

        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getUserProfile()->getFirstName() . ' ' . 
                           $user->getUserProfile()->getLastName();
                },
                'query_builder' => function (EntityRepository $er) use ($excludeUser) {
                    return $er->createQueryBuilder('u')
                        ->where('u != :currentUser')
                        ->setParameter('currentUser', $excludeUser)
                        ->orderBy('u.id', 'ASC');
                },
                'label' => 'Exchange with User',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'excludeUser' => null,
        ]);

        $resolver->setAllowedTypes('excludeUser', ['null', User::class]);
    }
}