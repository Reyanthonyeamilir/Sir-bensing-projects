<?php

namespace App\Form;

use App\Entity\PetSale;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PetSaleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image')
            ->add('dogbreed')
            ->add('dogage')
            ->add('datepurchased')
            ->add('datetosale')
            ->add('discription')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PetSale::class,
        ]);
    }
}
