<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\CallbackTransformer;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'attr' => ['class' => 'form-input']
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_staff',  // CHANGED: 'Editor' => 'ROLE_EDITOR' to 'Staff' => 'ROLE_staff'
                    'User' => 'ROLE_USER',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => 'Roles',
                'attr' => ['class' => 'form-checkboxes']
            ])
            ->add('password', PasswordType::class, [
                'required' => false,
                'label' => 'Password',
                'mapped' => false,  // ADD THIS: Password field is not mapped to entity
                'empty_data' => '', // ADD THIS: Allow empty password
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
                    // Transform array to array for choice field
                    return $rolesArray ?? [];
                },
                function ($rolesArray) {
                    // Transform array back to array
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