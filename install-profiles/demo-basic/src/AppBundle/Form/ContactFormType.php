<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactFormType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gender', ChoiceType::class, [
                'label' => 'Gender',
                'required' => true,
                'choices' => [
                    'Female' => 'female',
                    'Male' => 'male'
                ]
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Firstname',
                'required' => true
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Lastname',
                'required' => true
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-Mail',
                'required' => true,
                'attr' => [
                    'placeholder' => 'example@example.com'
                ]
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'required' => true
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Submit'
            ]);
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
    }
}
