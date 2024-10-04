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

namespace Pimcore\Workflow;

use Exception;
use Pimcore;
use Pimcore\Event\Workflow\GlobalActionEvent;
use Pimcore\Event\WorkflowEvents;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Workflow\EventSubscriber\ChangePublishedStateSubscriber;
use Pimcore\Workflow\EventSubscriber\NotesSubscriber;
use Pimcore\Workflow\MarkingStore\StateTableMarkingStore;
use Pimcore\Workflow\Notes\CustomHtmlServiceInterface;
use Pimcore\Workflow\Place\PlaceConfig;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Manager
{
    private Registry $workflowRegistry;

    private NotesSubscriber $notesSubscriber;

    private ExpressionService $expressionService;

    private EventDispatcherInterface $eventDispatcher;

    /**
     * @var PlaceConfig[][]
     */
    private array $placeConfigs = [];

    /**
     * @var GlobalAction[][]
     */
    private array $globalActions = [];

    /**
     * @var WorkflowConfig[]
     */
    private array $workflows = [];

    public function __construct(Registry $workflowRegistry, NotesSubscriber $notesSubscriber, ExpressionService $expressionService, EventDispatcherInterface $eventDispatcher)
    {
        $this->workflowRegistry = $workflowRegistry;
        $this->notesSubscriber = $notesSubscriber;
        $this->expressionService = $expressionService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     *
     * @return $this
     */
    public function addPlaceConfig(string $workflowName, string $place, array $placeConfig): static
    {
        $this->placeConfigs[$workflowName] = $this->placeConfigs[$workflowName] ?? [];
        $this->placeConfigs[$workflowName][$place] = new PlaceConfig($place, $placeConfig, $this->expressionService, $workflowName);

        return $this;
    }

    /**
     *
     * @return $this
     */
    public function addGlobalAction(string $workflowName, string $action, array $actionConfig, CustomHtmlServiceInterface $customHtmlService = null): static
    {
        $this->globalActions[$workflowName] = $this->globalActions[$workflowName] ?? [];
        $this->globalActions[$workflowName][$action] = new GlobalAction($action, $actionConfig, $this->expressionService, $workflowName, $customHtmlService);

        return $this;
    }

    /**
     * @return GlobalAction[]
     */
    public function getGlobalActions(string $workflowName): array
    {
        return $this->globalActions[$workflowName] ?? [];
    }

    public function getGlobalAction(string $workflowName, string $globalAction): ?GlobalAction
    {
        return $this->globalActions[$workflowName][$globalAction] ?? null;
    }

    public function getPlaceConfig(string $workflowName, string $place): ?PlaceConfig
    {
        return $this->placeConfigs[$workflowName][$place] ?? null;
    }

    /**
     * Returns all PlaceConfigs (for given marking) ordered by it's appearence in the workflow config file
     *
     *
     * @return PlaceConfig[];
     */
    public function getOrderedPlaceConfigs(WorkflowInterface $workflow, Marking $marking = null): array
    {
        if (is_null($marking)) {
            return $this->placeConfigs[$workflow->getName()] ?? [];
        }

        $placeNames = array_keys($marking->getPlaces());
        $placeConfigs = [];

        foreach ($this->placeConfigs[$workflow->getName()] ?? [] as $placeConfig) {
            if (in_array($placeConfig->getPlace(), $placeNames)) {
                $placeConfigs[] = $placeConfig;
            }
        }

        return $placeConfigs;
    }

    /**
     * @return array|PlaceConfig[]
     */
    public function getPlaceConfigsByWorkflowName(string $workflowName): array
    {
        return $this->placeConfigs[$workflowName] ?? [];
    }

    public function registerWorkflow(string $workflowName, array $options = []): void
    {
        $this->workflows[$workflowName] = new WorkflowConfig($workflowName, $options);

        uasort($this->workflows, function (WorkflowConfig $a, WorkflowConfig $b) {
            return $b->getPriority() <=> $a->getPriority();
        });
    }

    /**
     * @return string[]
     */
    public function getAllWorkflows(): array
    {
        return array_keys($this->workflows);
    }

    public function getWorkflowConfig(string $workflowName): WorkflowConfig
    {
        if (!isset($this->workflows[$workflowName])) {
            throw new LogicException(sprintf('workflow %s not found', $workflowName));
        }

        return $this->workflows[$workflowName];
    }

    /**
     *
     * @return WorkflowInterface[]
     */
    public function getAllWorkflowsForSubject(object $subject): array
    {
        $workflows = [];

        foreach ($this->getAllWorkflows() as $workflowName) {
            $workflow = $this->getWorkflowIfExists($subject, $workflowName);

            if (empty($workflow)) {
                continue;
            }

            $workflows[] = $workflow;
        }

        return $workflows;
    }

    public function getWorkflowIfExists(object $subject, string $workflowName): ?WorkflowInterface
    {
        try {
            $workflow = $this->workflowRegistry->get($subject, $workflowName);
        } catch (InvalidArgumentException $e) {
            // workflow does not apply to given subject
            return null;
        }

        return $workflow;
    }

    public function getWorkflowByName(string $workflowName): ?object
    {
        $config = $this->getWorkflowConfig($workflowName);

        return Pimcore::getContainer()->get($config->getType() . '.' . $workflowName);
    }

    /**
     *
     *
     * @throws ValidationException
     * @throws Exception
     */
    public function applyWithAdditionalData(
        WorkflowInterface $workflow,
        Asset|PageSnippet|Concrete $subject,
        string $transition,
        array $additionalData,
        bool $saveSubject = false
    ): Marking {
        $this->notesSubscriber->setAdditionalData($additionalData);

        $marking = $workflow->apply($subject, $transition, $additionalData);

        $this->notesSubscriber->setAdditionalData([]);

        $transition = $this->getTransitionByName($workflow->getName(), $transition);
        $changePublishedState = $transition instanceof Transition ? $transition->getChangePublishedState() : null;

        if ($saveSubject && $subject instanceof ElementInterface) {
            if ($changePublishedState === ChangePublishedStateSubscriber::SAVE_VERSION) {
                $subject->saveVersion();
            } else {
                $subject->save();
            }
        }

        return $marking;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function applyGlobalAction(
        WorkflowInterface $workflow,
        object $subject,
        string $globalAction,
        array $additionalData,
        bool $saveSubject = false
    ): Marking {
        $globalActionObj = $this->getGlobalAction($workflow->getName(), $globalAction);
        if (!$globalActionObj) {
            throw new LogicException(sprintf('global action %s not found', $globalAction));
        }

        $this->notesSubscriber->setAdditionalData($additionalData);

        $event = new GlobalActionEvent($workflow, $subject, $globalActionObj, [
            'additionalData' => $additionalData,
        ]);

        $this->eventDispatcher->dispatch($event, WorkflowEvents::PRE_GLOBAL_ACTION);

        $markingStore = $workflow->getMarkingStore();

        if (!empty($globalActionObj->getTos())) {
            $places = [];
            foreach ($globalActionObj->getTos() as $place) {
                $places[$place] = 1;
            }

            $markingStore->setMarking($subject, new Marking($places));
        }

        $this->eventDispatcher->dispatch($event, WorkflowEvents::POST_GLOBAL_ACTION);
        $this->notesSubscriber->setAdditionalData([]);

        if ($saveSubject && $subject instanceof ElementInterface) {
            $subject->save();
        }

        return $markingStore->getMarking($subject);
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getTransitionByName(string $workflowName, string $transitionName): ?\Symfony\Component\Workflow\Transition
    {
        $workflow = $this->getWorkflowByName($workflowName);

        foreach ($workflow->getDefinition()->getTransitions() as $transition) {
            if ($transition->getName() === $transitionName) {
                return $transition;
            }
        }

        return null;
    }

    /**
     * Forces an initial place being set (and stored) if the current place is empty.
     * We cannot apply a regular transition b/c it would be considered invalid by the state machine.
     *
     * As of Symfony 4.4.8 built-in implementations of @see \Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface
     * use strict `null` comparison when retrieving the current marking and throw an exception otherwise.
     *
     *
     * @return bool true if initial state was applied
     *
     * @throws Exception
     */
    public function ensureInitialPlace(string $workflowName, object $subject): bool
    {
        if (!$workflow = $this->getWorkflowIfExists($subject, $workflowName)) {
            return false;
        }

        $markingStore = $workflow->getMarkingStore();

        // check that the subject has a non-empty place
        $initialPlaces = $this->getInitialPlacesForWorkflow($workflow);
        $markingObject = $markingStore->getMarking($subject);
        foreach ($markingObject->getPlaces() as $placeName => $nbToken) {
            if ('' !== $placeName) {
                continue;
            }

            // fill empty place with initial place, if any
            $markingObject->unmark($placeName);
            foreach ($initialPlaces as $initialPlace) {
                $markingObject->mark($initialPlace);
            }

            $markingStore->setMarking($subject, $markingObject);

            // StateTableMarkingStore handles persistence of it's own
            if ($markingStore instanceof StateTableMarkingStore === false) {
                $wasOmitMandatoryCheck = $subject->getOmitMandatoryCheck();
                $subject->setOmitMandatoryCheck(true);
                $subject->save();
                $subject->setOmitMandatoryCheck($wasOmitMandatoryCheck);
            }

            return true;
        }

        return false;
    }

    public function getInitialPlacesForWorkflow(WorkflowInterface $workflow): array
    {
        $definition = $workflow->getDefinition();

        return $definition->getInitialPlaces();
    }

    public function isDeniedInWorkflow(ElementInterface $element, string $permissionType): bool
    {
        $userPermissions = $this->getWorkflowUserPermissions($element);

        return ($userPermissions[$permissionType] ?? null) === false;
    }

    private function getWorkflowUserPermissions(ElementInterface $element): array
    {
        $userPermissions = [];
        foreach ($this->getAllWorkflows() as $workflowName) {
            $workflow = $this->getWorkflowIfExists($element, $workflowName);

            if (empty($workflow)) {
                continue;
            }

            $marking = $workflow->getMarking($element);

            if (!count($marking->getPlaces())) {
                continue;
            }

            foreach ($this->getOrderedPlaceConfigs($workflow, $marking) as $placeConfig) {
                if (!empty($placeConfig->getPermissions($workflow, $element))) {
                    $userPermissions = array_merge(
                        $userPermissions,
                        $placeConfig->getUserPermissions($workflow, $element)
                    );
                }
            }
        }

        return $userPermissions;
    }
}
