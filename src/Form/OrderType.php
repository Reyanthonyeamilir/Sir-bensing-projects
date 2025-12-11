<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Petproducts;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',   // show usernames instead of IDs
                'placeholder' => 'Select customer',
            ])
            ->add('product', EntityType::class, [
                'class' => Petproducts::class,
                'choice_label' => 'product_name', // show product names
                'placeholder' => 'Select product',
            ])
            ->add('quantity', IntegerType::class)
            ->add('amount', NumberType::class, [
                'attr' => ['readonly' => true],   // amount is auto-computed
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'pending',
                    'Completed' => 'completed',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
