<?php

namespace Pimcore\Workflow\MarkingStore;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MultipleStateMarkingStore;

class DataObjectMultipleStateMarkingStore extends MultipleStateMarkingStore
{
    private $property;
    private $propertyAccessor;

    /**
     * @param string                         $property
     * @param PropertyAccessorInterface|null $propertyAccessor
     */
    public function __construct($property = 'marking', PropertyAccessorInterface $propertyAccessor = null)
    {
        parent::__construct($property, $propertyAccessor);
        $this->property = $property;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    /**
     * @inheritdoc
     *
     * @throws LogicException
     */
    public function getMarking($subject)
    {
        $this->checkIfSubjectIsValid($subject);

        $marking = (array) $this->propertyAccessor->getValue($subject, $this->property);

        $_marking = [];
        foreach ($marking as $place) {
            $_marking[$place] = 1;
        }

        return new Marking($_marking);
    }

    /**
     * @inheritdoc
     *
     * @throws LogicException
     * @throws \Exception
     */
    public function setMarking($subject, Marking $marking)
    {
        $subject = $this->checkIfSubjectIsValid($subject);

        $places = array_keys($marking->getPlaces());
        $this->propertyAccessor->setValue($subject, $this->property, $places);
    }

    /**
     * @param object $subject
     *
     * @return Concrete
     */
    private function checkIfSubjectIsValid($subject): Concrete
    {
        if (!$subject instanceof Concrete) {
            throw new LogicException('data_object_multiple_state marking store works for pimcore data objects only.');
        }

        return $subject;
    }
}
