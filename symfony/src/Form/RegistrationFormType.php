<?php

namespace App\Form;

use App\Entity\Country;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'placeholder' => 'Choose an option',
                'required' => true,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
                ])
            ->add('name', TextType::class, ['label' => 'Name (letters, numbers, hyphens and underscores allowed)','attr' => ['placeholder' => 'username'],'constraints' => [
                new NotBlank(['message' => 'Please enter a username',]),],])
            ->add('email', EmailType::class, ['attr' => ['placeholder' => 'example@mail.com'],'constraints' => [
                new NotBlank(['message' => 'Please enter a valid email',]),
                //new Assert\Email(['message' => 'address must be valid',]),
                //new Length(['min' => 5,'minMessage' => 'Please enter a valid email',]),

            ],])
            ->add('plainPassword', PasswordType::class, [
                                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped'      => false,
                'attr'        => [
                    'autocomplete' => 'new-password',
                    'placeholder'  => '6 characters min',
                ],
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
            ])
            ->add('captcha', CaptchaType::class, ['invalid_message' => 'Captcha code does not match.'],)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => User::class,
            ]
        );
    }//end configureOptions()
}//end class
