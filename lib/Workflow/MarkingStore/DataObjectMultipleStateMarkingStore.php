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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

class DataObjectMultipleStateMarkingStore implements MarkingStoreInterface
{
    private string $property;

    private \Symfony\Component\PropertyAccess\PropertyAccessor|PropertyAccessorInterface $propertyAccessor;

    public function __construct(string $property = 'marking', PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->property = $property;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    public function getMarking(object $subject): Marking
    {
        $this->checkIfSubjectIsValid($subject);

        $marking = (array) $this->propertyAccessor->getValue($subject, $this->property);

        $_marking = [];
        foreach ($marking as $place) {
            $_marking[$place] = 1;
        }

        return new Marking($_marking);
    }

    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        $subject = $this->checkIfSubjectIsValid($subject);

        $places = array_keys($marking->getPlaces());
        $this->propertyAccessor->setValue($subject, $this->property, $places);
    }

    /**
     * @throws LogicException
     */
    private function checkIfSubjectIsValid(object $subject): Concrete
    {
        if (!$subject instanceof Concrete) {
            throw new LogicException('data_object_multiple_state marking store works for pimcore data objects only.');
        }

        return $subject;
    }
}
