<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Workflow\MarkingStore;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Workflow\Manager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

class DataObjectSplittedStateMarkingStore implements MarkingStoreInterface
{
    const ALLOWED_PLACE_FIELD_TYPES = ['input', 'select', 'multiselect'];

    private string $workflowName;

    private array $stateMapping;

    private PropertyAccessorInterface $propertyAccessor;

    private Manager $workflowManager;

    public function __construct(string $workflowName, array $places, array $stateMapping, PropertyAccessorInterface $propertyAccessor, Manager $workflowManager)
    {
        $this->workflowName = $workflowName;

        $this->validateStateMapping($places, $stateMapping);

        $this->stateMapping = $stateMapping;
        $this->propertyAccessor = $propertyAccessor;
        $this->workflowManager = $workflowManager;
    }

    public function getMarking(object $subject): Marking
    {
        $this->checkIfSubjectIsValid($subject);

        $properties = array_unique(array_values($this->stateMapping));

        $placeNames = [];
        foreach ($properties as $property) {
            $propertyPlaces = $this->propertyAccessor->getValue($subject, $property);

            if (is_null($propertyPlaces) || $propertyPlaces === '') {
                continue;
            }

            $placeNames = array_merge($placeNames, (array) $propertyPlaces);
        }

        $places = [];
        foreach ($placeNames as $place) {
            if ($this->workflowManager->getPlaceConfig($this->workflowName, $place)) {
                $places[$place] = 1;
            }
        }

        return new Marking($places);
    }

    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        $subject = $this->checkIfSubjectIsValid($subject);
        $places = array_keys($marking->getPlaces());

        $groupedProperties = [];

        foreach (array_unique(array_values($this->stateMapping)) as $property) {
            $groupedProperties[$property] = [];
        }

        foreach ($places as $place) {
            $property = $this->stateMapping[$place];
            $groupedProperties[$property][] = $place;
        }

        foreach ($groupedProperties as $property => $places) {
            $this->setProperty($subject, $property, $places);
        }
    }

    /**
     *
     * @return string[]
     */
    public function getMappedPlaces(string $fieldName): array
    {
        $places = [];
        foreach ($this->stateMapping as $place => $_fieldName) {
            if ($fieldName === $_fieldName) {
                $places[] = $place;
            }
        }

        return $places;
    }

    private function setProperty(Concrete $subject, string $property, mixed $places): void
    {
        $fd = $subject->getClass()->getFieldDefinition($property);

        if (!in_array($fd->getFieldtype(), self::ALLOWED_PLACE_FIELD_TYPES)) {
            throw new LogicException(sprintf('field type "%s" not allowed as marking store - allowed types are [%s]', $fd->getFieldtype(), implode(', ', self::ALLOWED_PLACE_FIELD_TYPES)));
        }

        if ($fd->getFieldtype() !== 'multiselect') {
            if (count($places) > 1) {
                throw new LogicException(sprintf('field type "%s" is not able to handle multiple values - given values are [%s]', $fd->getFieldtype(), implode(', ', $places)));
            }

            $places = array_shift($places);
        }

        $this->propertyAccessor->setValue($subject, $property, $places);
    }

    /**
     * @throws LogicException
     */
    private function checkIfSubjectIsValid(object $subject): Concrete
    {
        if (!$subject instanceof Concrete) {
            throw new LogicException('data_object_splitted_state marking store works for pimcore data objects only.');
        }

        return $subject;
    }

    /**
     * @throws LogicException
     */
    private function validateStateMapping(array $places, array $stateMapping): void
    {
        $diff = array_diff($places, array_keys($stateMapping));

        if (count($diff) > 0) {
            throw new LogicException(sprintf('State mapping and places configuration need to match each other [detected differences: %s].', implode(', ', $diff)));
        }
    }
}
