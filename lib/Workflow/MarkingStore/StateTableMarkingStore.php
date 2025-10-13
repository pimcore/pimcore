<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Workflow\MarkingStore;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Element\WorkflowState;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

class StateTableMarkingStore implements MarkingStoreInterface
{
    private string $workflowName;

    public function __construct(string $workflowName)
    {
        $this->workflowName = $workflowName;
    }

    public function getMarking(object $subject): Marking
    {
        $subject = $this->checkIfSubjectIsValid($subject);

        $placeName = '';

        if ($workflowState = WorkflowState::getByPrimary($subject->getId(), Service::getElementType($subject), $this->workflowName)) {
            $placeName = $workflowState->getPlace();
        }

        if (!$placeName) {
            return new Marking();
        }

        $placeName = explode(',', $placeName);
        $places = [];
        foreach ($placeName as $place) {
            $places[$place] = 1;
        }

        return new Marking($places);
    }

    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        $subject = $this->checkIfSubjectIsValid($subject);
        $type = Service::getElementType($subject);

        if (!$workflowState = WorkflowState::getByPrimary($subject->getId(), $type, $this->workflowName)) {
            $workflowState = new WorkflowState();
            $workflowState->setCtype($type);
            $workflowState->setCid($subject->getId());
            $workflowState->setWorkflow($this->workflowName);
        }

        $workflowState->setPlace(implode(',', array_keys($marking->getPlaces())));
        $workflowState->save();
    }

    public function getProperty(): string
    {
        return $this->workflowName;
    }

    /**
     * @throws LogicException
     */
    private function checkIfSubjectIsValid(object $subject): ElementInterface
    {
        if (!$subject instanceof ElementInterface) {
            throw new LogicException('state_table marking store works for pimcore elements (documents, assets, data objects) only.');
        }

        return $subject;
    }
}
