<?php

namespace App\Form;

use App\Entity\Petproducts;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PetproductsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product_name', TextType::class, [
                'label' => 'Product Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter product name']
            ])
            ->add('category', TextType::class, [
                'label' => 'Category',
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g., Food, Toys, Accessories']
            ])
            ->add('sub_category', TextType::class, [
                'label' => 'Sub Category',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Optional sub-category']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Product description...']
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00', 'step' => '0.01']
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock Quantity',
                'attr' => ['class' => 'form-control', 'placeholder' => '0']
            ])
            ->add('brand', TextType::class, [
                'label' => 'Brand',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Brand name']
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Product Image',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF, WebP)',
                    ])
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active Product',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label']
            ])
            ->add('createdAt', DateTimeType::class, [
                'label' => 'Created Date',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Petproducts::class,
        ]);
    }
}