<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Workflow;

use Pimcore\Event\Workflow\GlobalActionEvent;
use Pimcore\Event\WorkflowEvents;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Workflow\EventSubscriber\NotesSubscriber;
use Pimcore\Workflow\Place\PlaceConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\Exception\InvalidARgumentException;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;

class Manager
{
    /**
     * @var Registry
     */
    private $workflowRegistry;

    /**
     * @var NotesSubscriber
     */
    private $notesSubscriber;

    /**
     * @var ExpressionService
     */
    private $expressionService;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var PlaceConfig[][]
     */
    private $placeConfigs = [];

    /**
     * @var GlobalAction[][]
     */
    private $globalActions = [];

    /**
     * @var WorkflowConfig[]
     */
    private $workflows = [];

    public function __construct(Registry $workflowRegistry, NotesSubscriber $notesSubscriber, ExpressionService $expressionService, EventDispatcherInterface $eventDispatcher)
    {
        $this->workflowRegistry = $workflowRegistry;
        $this->notesSubscriber = $notesSubscriber;
        $this->expressionService = $expressionService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $place
     * @param array $placeConfig
     *
     * @return $this
     */
    public function addPlaceConfig(string $workflowName, string $place, array $placeConfig)
    {
        $this->placeConfigs[$workflowName] = $this->placeConfigs[$workflowName] ?? [];
        $this->placeConfigs[$workflowName][$place] = new PlaceConfig($place, $placeConfig, $this->expressionService);

        return $this;
    }

    /**
     * @param string $place
     * @param array $placeConfig
     *
     * @return $this
     */
    public function addGlobalAction(string $workflowName, string $action, array $actionConfig)
    {
        $this->globalActions[$workflowName] = $this->globalActions[$workflowName] ?? [];
        $this->globalActions[$workflowName][$action] = new GlobalAction($action, $actionConfig, $this->expressionService);

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
     * @param Workflow $workflow
     * @param Marking $marking
     *
     * @return PlaceConfig[];
     */
    public function getOrderedPlaceConfigs(Workflow $workflow, Marking $marking = null): array
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

    public function getPlaceConfigsByWorkflowName(string $workflowName)
    {
        return $this->placeConfigs[$workflowName] ?? [];
    }

    public function registerWorkflow(string $workflowName, array $options = [])
    {
        $this->workflows[$workflowName] = new WorkflowConfig($workflowName, $options);

        uasort($this->workflows, function (WorkflowConfig $a, WorkflowConfig $b) {
            return $a->getPriority() < $b->getPriority();
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
     * @param $subject
     *
     * @return Workflow[]
     */
    public function getAllWorkflowsForSubject($subject): array
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

    public function getWorkflowIfExists($subject, string $workflowName): ?Workflow
    {
        try {
            $workflow = $this->workflowRegistry->get($subject, $workflowName);
        } catch (InvalidARgumentException $e) { // workflow does not apply to given subject
            return null;
        }

        return $workflow;
    }

    /**
     * @param string $workflowName
     *
     * @return Workflow
     *
     * @throws \Exception
     */
    public function getWorkflowByName(string $workflowName): Workflow
    {
        $config = $this->getWorkflowConfig($workflowName);

        return \Pimcore::getContainer()->get($config->getType() . '.' . $workflowName);
    }

    /**
     * @param Workflow $workflow
     * @param $subject
     * @param string $transition
     * @param array $additionalData
     * @param bool $saveSubject
     *
     * @return Marking
     *
     * @throws ValidationException
     * @throws \Exception
     */
    public function applyWithAdditionalData(Workflow $workflow, $subject, string $transition, array $additionalData, $saveSubject = false)
    {
        $this->notesSubscriber->setAdditionalData($additionalData);

        $marking = $workflow->apply($subject, $transition);

        $this->notesSubscriber->setAdditionalData([]);

        if ($saveSubject && $subject instanceof AbstractElement) {
            if (method_exists($subject, 'getPublished') && !$subject->getPublished()) {
                $subject->saveVersion();
            } else {
                $subject->save();
            }
        }

        return $marking;
    }

    /**
     * @param Workflow $workflow
     * @param $subject
     * @param string $globalAction
     * @param array $additionalData
     * @param bool $saveSubject
     *
     * @return Marking
     *
     * @throws ValidationException
     * @throws \Exception
     */
    public function applyGlobalAction(Workflow $workflow, $subject, string $globalAction, array $additionalData, $saveSubject = false)
    {
        $globalActionObj = $this->getGlobalAction($workflow->getName(), $globalAction);
        if (!$globalActionObj) {
            throw new LogicException(sprintf('global action %s not found', $globalAction));
        }

        $this->notesSubscriber->setAdditionalData($additionalData);

        $event = new GlobalActionEvent($workflow, $subject, $globalActionObj, [
            'additionalData' => $additionalData
        ]);

        $this->eventDispatcher->dispatch(WorkflowEvents::PRE_GLOBAL_ACTION, $event);

        $markingStore = $workflow->getMarkingStore();

        if (!empty($globalActionObj->getTos())) {
            $places = [];
            foreach ($globalActionObj->getTos() as $place) {
                $places[$place] = 1;
            }

            $markingStore->setMarking($subject, new Marking($places));
        }

        $this->eventDispatcher->dispatch(WorkflowEvents::POST_GLOBAL_ACTION, $event);
        $this->notesSubscriber->setAdditionalData([]);

        if ($saveSubject && $subject instanceof AbstractElement) {
            $subject->save();
        }

        return $markingStore->getMarking($subject);
    }

    /**
     * @param string $workflowName
     * @param string $transitionName
     *
     * @return null|\Symfony\Component\Workflow\Transition
     *
     * @throws \Exception
     */
    public function getTransitionByName(string $workflowName, string $transitionName): ?\Symfony\Component\Workflow\Transition
    {
        if (!$workflow = $this->getWorkflowByName($workflowName)) {
            throw new \Exception(sprintf('workflow %s not found', $workflowName));
        }

        foreach ($workflow->getDefinition()->getTransitions() as $transition) {
            if ($transition->getName() === $transitionName) {
                return $transition;
            }
        }

        return null;
    }
}
