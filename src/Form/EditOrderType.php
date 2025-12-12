<?php
// src/Form/EditOrderType.php

namespace App\Form;

use App\Entity\Order;
use App\Entity\User;
use App\Entity\Petproducts;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class EditOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('u.username', 'ASC');
                },
                'placeholder' => 'Select a customer',
                'required' => true,
                'attr' => ['class' => 'FORM-CONTROL']
            ])
            ->add('product', EntityType::class, [
                'class' => Petproducts::class,
                'choice_label' => function(Petproducts $product) {
                    return $product->getProductName() . ' - ₱' . number_format($product->getPrice(), 2);
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('p.product_name', 'ASC');
                },
                'placeholder' => 'Select a product',
                'required' => true,
                'attr' => ['class' => 'FORM-CONTROL']
            ])
            ->add('quantity', IntegerType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'FORM-CONTROL',
                    'min' => 1
                ]
            ])
            ->add('amount', NumberType::class, [
                'required' => true,
                'html5' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'FORM-CONTROL',
                    'readonly' => true
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'pending',
                    'Completed' => 'completed',
                ],
                'required' => true,
                'placeholder' => 'Select status',
                'attr' => ['class' => 'FORM-CONTROL', 'autocomplete' => 'off']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}