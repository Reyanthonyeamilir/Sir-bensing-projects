<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType; // FIXED THIS LINE
use Symfony\Component\Form\CallbackTransformer;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'attr' => ['class' => 'form-input'],
                'label' => 'Username',
                'required' => true,
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_staff',
                    'User' => 'ROLE_USER',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => 'Roles',
                'attr' => ['class' => 'form-checkboxes']
            ])
            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Active Account',
                'attr' => ['class' => 'form-checkbox'],
                'help' => 'Uncheck to deactivate this account'
            ])
            ->add('password', PasswordType::class, [
                'required' => false,
                'label' => 'Password',
                'mapped' => false,
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Leave blank to keep current password'
                ],
                'help' => 'Enter new password only if you want to change it'
            ])
        ;

        // Data transformer for roles array to string and back
        $builder->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                function ($rolesArray) {
                    return $rolesArray ?? [];
                },
                function ($rolesArray) {
                    return array_values(array_unique($rolesArray ?? []));
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}