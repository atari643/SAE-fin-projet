<?php

namespace App\Form;

use App\Entity\Country;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditUserAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /* ->add('name') */
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped'      => false,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'Please enter a password']
                    ),
                    new Length(
                        [
                            'min'        => 6,
                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                            // max length allowed by Symfony for security reasons
                            'max'        => 4096,
                        ]
                    ),
                ],
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options' => ['label' => 'New Password', 'attr' => ['autocomplete' => 'new-password','placeholder'  => '6 characters min'],],
                'second_options' => ['label' => 'Repeat Password', 'attr' => ['autocomplete' => 'new-password','placeholder'  => '6 characters min'],],
            ])
            /* ->add('country', EntityType::class, [
                'class' => Country::class,
'choice_label' => 'name',
            ]) */
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
