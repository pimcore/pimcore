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
        return $this->getPendingMarking($subject) ?? $this->getPersistedMarking($subject);
    }

    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        $subject = $this->checkIfSubjectIsValid($subject);

        if (!empty($context[self::CONTEXT_SAVE_VERSION]) && $subject instanceof AbstractElement) {
            // the subject is only saved as a version (draft) after this transition:
            // keep the place with the draft instead of committing it to the state table
            $this->setPendingMarking($subject, $marking);

            return;
        }

        $this->persistPlaces($subject, array_keys($marking->getPlaces()));

        // a directly persisted marking supersedes whatever was pending on the subject; it is
        // cleared only now, so that a failed write leaves the draft's place in memory
        $this->setPendingMarking($subject, null);
    }

    public function getPersistedMarking(ElementInterface $subject): Marking
    {
        $placeName = '';

        if ($workflowState = WorkflowState::getByPrimary($subject->getId(), Service::getElementType($subject), $this->workflowName)) {
            $placeName = $workflowState->getPlace();
        }

        if (!$placeName) {
            return new Marking();
        }

        return $this->createMarking(explode(',', $placeName));
    }

    public function getPendingMarking(ElementInterface $subject): ?Marking
    {
        if (!$subject instanceof AbstractElement) {
            return null;
        }

        $places = $subject->getPendingWorkflowMarking($this->workflowName);

        return $places === null ? null : $this->createMarking($places);
    }

    public function setPendingMarking(ElementInterface $subject, ?Marking $marking): void
    {
        if (!$subject instanceof AbstractElement) {
            if ($marking !== null) {
                throw new LogicException('A marking can only be kept pending on elements extending ' . AbstractElement::class);
            }

            return;
        }

        $subject->setPendingWorkflowMarking(
            $this->workflowName,
            $marking === null ? null : array_keys($marking->getPlaces())
        );
    }

    public function persistPendingMarking(ElementInterface $subject): void
    {
        $marking = $this->getPendingMarking($subject);
        if ($marking === null) {
            return;
        }

        $this->persistPlaces($subject, array_keys($marking->getPlaces()));
        $this->setPendingMarking($subject, null);
    }

    public function getProperty(): string
    {
        return $this->workflowName;
    }

    /**
     * @param string[] $places
     */
    protected function persistPlaces(ElementInterface $subject, array $places): void
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
