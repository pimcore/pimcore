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

use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Element\WorkflowState;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;

class StateTableMarkingStore implements PendingMarkingStoreInterface
{
    private string $workflowName;

    public function __construct(string $workflowName)
    {
        $this->workflowName = $workflowName;
    }

    public function getMarking(object $subject): Marking
    {
        $subject = $this->checkIfSubjectIsValid($subject);

        // a marking pending on the subject (draft) takes precedence over the persisted one
        if ($subject instanceof AbstractElement) {
            $pendingPlaces = $subject->getPendingWorkflowMarking($this->workflowName);
            if ($pendingPlaces !== null) {
                return $this->createMarking($pendingPlaces);
            }
        }

        $placeName = '';

        if ($workflowState = WorkflowState::getByPrimary($subject->getId(), Service::getElementType($subject), $this->workflowName)) {
            $placeName = $workflowState->getPlace();
        }

        if (!$placeName) {
            return new Marking();
        }

        return $this->createMarking(explode(',', $placeName));
    }

    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        $subject = $this->checkIfSubjectIsValid($subject);
        $places = array_keys($marking->getPlaces());

        if ($subject instanceof AbstractElement) {
            if (!empty($context[self::CONTEXT_SAVE_VERSION])) {
                // the subject is only saved as a version (draft) after this transition:
                // keep the place with the draft instead of committing it to the state table
                $subject->setPendingWorkflowMarking($this->workflowName, $places);

                return;
            }

            // a directly persisted marking supersedes whatever was pending on the subject
            $subject->setPendingWorkflowMarking($this->workflowName, null);
        }

        $this->persistPlaces($subject, $places);
    }

    public function persistPendingMarking(ElementInterface $subject): void
    {
        if (!$subject instanceof AbstractElement) {
            return;
        }

        $places = $subject->getPendingWorkflowMarking($this->workflowName);
        if ($places === null) {
            return;
        }

        $this->persistPlaces($subject, $places);
        $subject->setPendingWorkflowMarking($this->workflowName, null);
    }

    public function getProperty(): string
    {
        return $this->workflowName;
    }

    /**
     * @param string[] $places
     */
    private function persistPlaces(ElementInterface $subject, array $places): void
    {
        $type = Service::getElementType($subject);

        if (!$workflowState = WorkflowState::getByPrimary($subject->getId(), $type, $this->workflowName)) {
            $workflowState = new WorkflowState();
            $workflowState->setCtype($type);
            $workflowState->setCid($subject->getId());
            $workflowState->setWorkflow($this->workflowName);
        }

        $workflowState->setPlace(implode(',', $places));
        $workflowState->save();
    }

    /**
     * @param string[] $placeNames
     */
    private function createMarking(array $placeNames): Marking
    {
        $places = [];
        foreach ($placeNames as $place) {
            $places[$place] = 1;
        }

        return new Marking($places);
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
