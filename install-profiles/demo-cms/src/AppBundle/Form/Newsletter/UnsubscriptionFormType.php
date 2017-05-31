<?php

declare(strict_types=1);

namespace AppBundle\Form\Newsletter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UnsubscriptionFormType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label'    => 'E-Mail',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'example@example.com'
                ]
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
