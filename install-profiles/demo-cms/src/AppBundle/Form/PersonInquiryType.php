<?php

namespace AppBundle\Form;

use Pimcore\Model\Object\ClassDefinition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class PersonInquiryType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var ClassDefinition\Data\Select $genderFieldDefinition */
        $genderFieldDefinition = $this->getFieldDefinition('person', 'gender');

        $builder
            ->add('gender', ChoiceType::class, [
                'label'    => 'Gender',
                'required' => true,
                'choices'  => $this->getSelectChoices($genderFieldDefinition)
            ])
            ->add('firstname', TextType::class, [
                'label'       => 'Firstname',
                'required'    => true,
                'constraints' => [ // validation constraints
                    new Length(['min' => 5])
                ]
            ])
            ->add('lastname', TextType::class, [
                'label'    => 'Lastname',
                'required' => true
            ])
            ->add('email', EmailType::class, [
                'label'    => 'E-Mail',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'example@example.com'
                ]
            ])
            ->add('message', TextareaType::class, [
                'label'    => 'Message',
                'required' => true
            ])
            ->add('terms', CheckboxType::class, [
                'label'    => 'I accept the terms of use',
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

    /**
     * Loads select choices for a select field
     *
     * @param ClassDefinition\Data\Select $fieldDefinition
     * @param bool $allowEmpty
     *
     * @return array
     */
    private function getSelectChoices(ClassDefinition\Data\Select $fieldDefinition, $allowEmpty = false): array
    {
        $choices = [];
        foreach ($fieldDefinition->getOptions() as $fieldOption) {
            if (empty($fieldOption['key']) && !$allowEmpty) {
                continue;
            }

            $choices[$fieldOption['value']] = $fieldOption['key'];
        }

        return $choices;
    }

    /**
     * Loads a field definition
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return ClassDefinition\Data
     */
    private function getFieldDefinition(string $className, string $fieldName): ClassDefinition\Data
    {
        static $fieldDefinitions;

        if (null === $fieldDefinitions) {
            $fieldDefinitions = [];
        }

        $cacheKey = $className . ':' . $fieldName;
        if (isset($fieldDefinitions[$cacheKey])) {
            return $fieldDefinitions[$cacheKey];
        }

        $classDefinition = ClassDefinition::getByName($className);

        if (!$classDefinition) {
            throw new \InvalidArgumentException(sprintf('Class definition for class "%s" could no be loaded', $className));
        }

        $fieldDefinition = $classDefinition->getFieldDefinition($fieldName);

        if (!$fieldDefinition) {
            throw new \InvalidArgumentException(sprintf(
                'Field definition "%s" for class "%s" does could not be loaded',
                $fieldName,
                $className
            ));
        }

        $fieldDefinitions[$cacheKey] = $fieldDefinition;

        return $fieldDefinition;
    }
}
