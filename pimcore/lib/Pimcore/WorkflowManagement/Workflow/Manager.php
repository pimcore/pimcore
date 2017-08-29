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

namespace Pimcore\WorkflowManagement\Workflow;

use Pimcore\Event\Model\WorkflowEvent;
use Pimcore\Event\WorkflowEvents;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Element\WorkflowState;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Concrete as ConcreteObject;
use Pimcore\WorkflowManagement\Workflow;

class Manager
{
    /**
     * The element for this workflow
     *
     * @var ConcreteObject|Document|Asset
     */
    protected $element = null;

    /**
     * The user using the workflow
     * All actions will be recorded against this user
     *
     * @var \Pimcore\Model\User
     */
    protected $user = null;

    /**
     * The data submitted when an action is performed
     */
    protected $actionData = null;

    /**
     * Any errors within the workflow will be stored here
     *
     * @var mixed
     */
    protected $error = null;

    /**
     * The loaded workflow
     *
     * @var Workflow
     */
    protected $workflow;

    /**
     * An array of the different pimcore user ids that the current user is related to
     * - first in array is the users id,
     * - any additional are the role ids that have been assigned to the user
     *
     * @var array $userIds
     */
    protected $userIds = [];

    /**
     *
     * @param      $element
     * @param null $user - optional parameter so that importers can use some functions of manager too.
     *
     * @throws \Exception
     */
    public function __construct($element, $user=null)
    {
        $this->element = $element;
        $this->user = $user;

        $this->initWorkflow();

        if ($this->user) {
            $this->initUserIds();
        }
    }

    /**
     * Loads the workflow into the manager
     *
     * @throws \Exception
     */
    private function initWorkflow()
    {
        $config = Workflow\Config::getElementWorkflowConfig($this->element);
        $this->workflow = Workflow\Factory::getWorkflowFromConfig($config);

        if (!$this->workflow) {
            throw new \Exception("Cannot load workflow configuration for object [{$this->element->getId()}] of type [{$this->element->getType()}]");
        }
    }

    private function initUserIds()
    {
        $this->userIds = array_merge([$this->user->getId()], $this->user->getRoles());
    }

    /**
     * @return Asset|Document|Concrete
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @return null|\Pimcore\Model\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return null|WorkflowState
     */
    public function getWorkflowStateForElement()
    {
        $elementType = Service::getElementType($this->element);
        $workflowState = WorkflowState::getByPrimary($this->element->getId(), $elementType, $this->workflow->getId());
        if (empty($workflowState)) {
            $workflowState = new WorkflowState();
            $workflowState->setCid($this->element->getId());
            $workflowState->setCtype($elementType);
            $workflowState->setWorkflowId($this->workflow->getId());
        }

        return $workflowState;
    }

    /**
     * Return the element state
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getElementState()
    {
        try {
            $state = $this->getWorkflowStateForElement()->getState();

            //check for null on new objects
            if (is_null($state)) {
                $state = $this->workflow->getDefaultState();
            }

            return $state;
        } catch (\Exception $e) {
            throw new \Exception('Cannot get state of element.');
        }
    }

    /**
     * Returns the current status of an object
     */
    public function getElementStatus()
    {
        try {
            $status = $this->getWorkflowStateForElement()->getStatus();

            //check for null on new objects
            if (is_null($status)) {
                $status = $this->workflow->getDefaultStatus();
            }

            return $status;
        } catch (\Exception $e) {
            throw new \Exception('Cannot get status of element.');
        }
    }

    /**
     * @param $newState
     *
     * @throws \Exception
     */
    public function setElementState($newState)
    {
        try {
            $workflowState = $this->getWorkflowStateForElement();
            $workflowState->setState($newState);
            $workflowState->save();
        } catch (\Exception $e) {
            throw new \Exception('Cannot set element state.');
        }
    }

    /**
     * @param $newStatus
     *
     * @throws \Exception
     */
    public function setElementStatus($newStatus)
    {
        try {
            $workflowState = $this->getWorkflowStateForElement();
            $workflowState->setStatus($newStatus);
            $workflowState->save();
        } catch (\Exception $e) {
            throw new \Exception('Cannot set element status.');
        }
    }

    /**
     * @return Workflow
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * @param $data
     */
    public function setActionData($data)
    {
        $this->actionData = $data;
    }

    /**
     * @return null
     */
    public function getActionData()
    {
        return $this->actionData;
    }

    /**
     * Get the available actions that can be performed on an element
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getAvailableActions()
    {
        $status = $this->getElementStatus();

        if (!$this->workflow->isValidStatus($status)) {
            throw new \Exception("Element [{$this->element->getId()}] does not have a valid status [{$status}] within the workflow");
        }

        $availableActions = $this->workflow->getValidGlobalActions();
        $availableActions = array_merge($this->workflow->getValidActionsForStatus($status), $availableActions);

        //check user permissions on available actions
        $allowedActions = [];
        foreach ($availableActions as $actionName) {
            if ($this->userCanPerformAction($actionName)) {
                $allowedActions[$actionName] = $this->workflow->getActionConfig($actionName, $status);
            }
        }

        $event = new WorkflowEvent($this, [
            'actions' => $allowedActions
        ]);
        \Pimcore::getEventDispatcher()->dispatch(WorkflowEvents::PRE_RETURN_AVAILABLE_ACTIONS, $event);
        $allowedActions = $event->getArgument('actions');

        return $allowedActions;
    }

    /**
     * Returns the available state configurations given an action
     * NOTE: ASSUMES THE ACTION EXISTS
     *
     * @see self::isValidAction
     *
     * @param $actionName
     *
     * @return array
     */
    public function getAvailableStates($actionName)
    {
        $actionConfig = $this->workflow->getActionConfig($actionName, $this->getElementStatus());
        $globalAction = $this->workflow->isGlobalAction($actionName);
        $hasTransition = $this->actionHasTransition($actionConfig);

        //if the action is global just return the current object state
        if ($globalAction || !$hasTransition) {
            $objectState = $this->getElementState();
            $availableStates = [
                $objectState => $this->workflow->getStateConfig($objectState)
            ];
        } else {
            $availableStates = [];
            foreach ($actionConfig['transitionTo'] as $state => $statuses) {
                $availableStates[$state] = $this->workflow->getStateConfig($state);
            }
        }

        return $availableStates;
    }

    /**
     * Returns the available statuses given an action and a state
     *
     * @param $actionName
     * @param $stateName
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getAvailableStatuses($actionName, $stateName)
    {
        $actionConfig = $this->workflow->getActionConfig($actionName);
        $globalAction = $this->workflow->isGlobalAction($actionName);
        $hasTransition = $this->actionHasTransition($actionConfig);

        if ($globalAction || !$hasTransition) {
            $objectStatus = $this->getElementStatus();
            $availableStatuses = [
                $objectStatus => $this->workflow->getStatusConfig($objectStatus)
            ];
        } else {

            //we have a check here for the state being an existing one
            if (!isset($actionConfig['transitionTo'][$stateName])) {
                throw new \Exception("Workflow::getAvailableStatuses, State [{$stateName}] not valid for action [{$actionName}] on element [{$this->element->getId()}] with status [{$this->getElementStatus()}]");
            }

            $availableStatuses = [];

            foreach ($actionConfig['transitionTo'][$stateName] as $statusName) {
                $availableStatuses[$statusName] = $this->workflow->getStatusConfig($statusName);
            }
        }

        return $availableStatuses;
    }

    /**
     * Returns whether or not notes are required for a given action on the current object
     * Assumes the action is valid at that point in time
     *
     * @param string $actionName
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function getNotesRequiredForAction($actionName)
    {
        $actionConfig = $this->getWorkflow()->getActionConfig($actionName, $this->getElementStatus());

        return isset($actionConfig['notes']['required']) ? (bool) $actionConfig['notes']['required'] : false;
    }

    /**
     * Shortcut method - probably should clean this up a bit more
     *
     * @param $actionName
     *
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function getAdditionalFieldsForAction($actionName)
    {
        $additionalFields = $this->getWorkflow()->getAdditionalFieldsForAction($actionName, $this->getElementStatus());

        if (is_array($additionalFields)) {
            foreach ($additionalFields as &$field) {
                if ($field['fieldType'] === 'user') {
                    $userdata = new \Pimcore\Model\DataObject\ClassDefinition\Data\User();
                    $userdata->configureOptions();
                    $field['options'] = $userdata->getOptions();
                }
            }
        }

        return $additionalFields;
    }

    /**
     * Returns whether or not a user can perform an action
     * if a status is given then it will be taken into consideration
     *
     * @param      $actionName
     * @param null $statusName
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function userCanPerformAction($actionName, $statusName=null)
    {
        if (!$this->user) {
            throw new \Exception('No user is defined in this Workflow Manager!');
        }

        if ($this->user->isAdmin()) {
            return true;
        }

        $requiredUserIds = $this->workflow->getValidUsersForAction($actionName, $statusName);
        if ($requiredUserIds === null) {
            return true;
        }

        foreach ($requiredUserIds as $id) {
            if (in_array($id, $this->userIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $actionConfig
     *
     * @return bool
     */
    public function actionHasTransition($actionConfig)
    {
        return isset($actionConfig['transitionTo']) && is_array($actionConfig['transitionTo']);
    }

    /**
     * Validates that a transition between requested states can be done on an element
     * NOTE: DOES NOT VALIDATE FIELDS @see performAction
     *
     * @param $actionName
     * @param $newStatus
     * @param $newState
     *
     * @return bool
     */
    public function validateAction($actionName, $newState, $newStatus)
    {
        $element = $this->element;

        if (!$this->workflow->isGlobalAction($actionName)) {
            $availableActions = $this->getAvailableActions();

            //check the action is available
            if (!array_key_exists($actionName, $availableActions)) {
                $this->error = "Workflow::validateTransition, Action [$actionName] not available for element [{$element->getId()}] with status [{$this->getElementStatus()}]";
                Logger::debug($this->error);

                return false;
            }

            $actionToTake = $availableActions[$actionName];

            if ($this->actionHasTransition($actionToTake)) {

                //check that the new state is correct for the action taken
                if (!array_key_exists($newState, $actionToTake['transitionTo'])) {
                    $this->error = "Workflow::validateTransition, State [$newState] not a valid transition state for action [$actionName] from status [{$this->getElementStatus()}]";
                    Logger::debug($this->error);

                    return false;
                }

                $availableNewStatuses = $actionToTake['transitionTo'][$newState];
                //check that the new status is valid for the action taken
                if (!in_array($newStatus, $availableNewStatuses)) {
                    $this->error = "Workflow::validateTransition, Status [$newState] not a valid transition status for action [$actionName] from status [{$this->getElementStatus()}]";
                    Logger::debug($this->error);

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     *
     * Performs an action
     *
     * @param mixed $actionName
     * @param array $formData
     *
     * @throws \Exception
     */
    public function performAction($actionName, $formData=[])
    {
        //store the current action data
        $this->setActionData($formData);

        \Pimcore::getEventDispatcher()->dispatch(WorkflowEvents::PRE_ACTION, new WorkflowEvent($this, [
            'actionName' => $actionName
        ]));

        //refresh the local copy after the event
        $formData = $this->getActionData();

        $actionConfig = $this->workflow->getActionConfig($actionName, $this->getElementStatus());
        $additional = $formData['additional'];

        //setup event listeners
        $this->registerActionEvents($actionConfig);

        //setup an array to hold the additional data that is not saved via a setterFn
        $actionNoteData = [];

        //process each field in the additional fields configuration
        if (isset($actionConfig['additionalFields']) && is_array($actionConfig['additionalFields'])) {
            foreach ($actionConfig['additionalFields'] as $additionalFieldConfig) {

                /**
                 * Additional Field example
                 * [
                 'name' => 'dateLastContacted',
                 'fieldType' => 'date',
                 'label' => 'Date of Conversation',
                 'required' => true,
                 'setterFn' => ''
                 ]
                 */
                $fieldName = $additionalFieldConfig['name'];

                //check required
                if ($additionalFieldConfig['required'] && empty($additional[$fieldName])) {
                    throw new \Exception("Workflow::performAction, fieldname [{$fieldName}] required for action [{$actionName}]");
                }

                //work out whether or not to set the value directly to the object or to add it to the note data
                if (!empty($additionalFieldConfig['setterFn'])) {
                    $setter = $additionalFieldConfig['setterFn'];

                    try {

                        //todo check here that the setter is being called on an Object

                        //TODO check that the field has a fieldType, (i.e if a workflow config has getter then the field should only be a pimcore tag and therefore 'fieldType' rather than 'type'.
                        //otherwise we could be erroneously setting pimcore fields
                        $additional[$fieldName] = Workflow\Service::getDataFromEditmode($additional[$fieldName], $additionalFieldConfig['fieldType']);

                        $this->element->$setter($additional[$fieldName]);
                    } catch (\Exception $e) {
                        Logger::error($e->getMessage());
                        throw new \Exception("Workflow::performAction, cannot set fieldname [{$fieldName}] using setter [{$setter}] in action [{$actionName}]");
                    }
                } else {
                    $actionNoteData[] = Workflow\Service::createNoteData($additionalFieldConfig, $additional[$fieldName]);
                }
            }
        }

        //save the old state and status in the form so that we can reference it later
        $formData['oldState'] = $this->getElementState();
        $formData['oldStatus'] = $this->getElementStatus();

        if ($this->element instanceof Concrete || $this->element instanceof Document\PageSnippet) {
            if (!$this->workflow->getAllowUnpublished() || in_array($formData['newStatus'], $this->workflow->getPublishedStatuses())) {
                $this->element->setPublished(true);

                if ($this->element instanceof Concrete) {
                    $this->element->setOmitMandatoryCheck(false);
                }

                $task = 'publish';
            } else {
                $this->element->setPublished(false);

                if ($this->element instanceof Concrete) {
                    $this->element->setOmitMandatoryCheck(true);
                }

                $task = 'unpublish';
            }
        } else {
            //all other elements do not support published or unpublished
            $task = 'publish';
        }

        try {
            \Pimcore::getEventDispatcher()->dispatch(WorkflowEvents::ACTION_BEFORE, new WorkflowEvent($this, [
                'actionConfig' => $actionConfig,
                'data' => $formData
            ]));

            //todo add some support to stop the action given the result from the event

            $this->element->setUserModification($this->user->getId());
            if (($task === 'publish' && $this->element->isAllowed('publish')) || ($task === 'unpublish' && $this->element->isAllowed('unpublish'))) {
                $this->element->save();
            } elseif ($this->element instanceof Concrete || $this->element instanceof Document\PageSnippet) {
                $this->element->saveVersion();
            } else {
                throw new \Exception('Operation not allowed for this element');
            }

            //transition the element
            $this->setElementState($formData['newState']);
            $this->setElementStatus($formData['newStatus']);

            // record a note against the object to show the transition
            $decorator = new Workflow\Decorator($this->workflow);
            $description = $formData['notes'];

            // create a note for this action
            $note = Workflow\Service::createActionNote(
                $this->element,
                $decorator->getNoteType($actionName, $formData),
                $decorator->getNoteTitle($actionName, $formData),
                $description,
                $actionNoteData
            );

            //notify users
            if (isset($actionConfig['notificationUsers']) && is_array($actionConfig['notificationUsers'])) {
                Workflow\Service::sendEmailNotification(
                    $actionConfig['notificationUsers'],
                    $note
                );
            }

            \Pimcore::getEventDispatcher()->dispatch(WorkflowEvents::ACTION_SUCCESS, new WorkflowEvent($this, [
                'actionConfig' => $actionConfig,
                'data' => $formData
            ]));
        } catch (\Exception $e) {
            \Pimcore::getEventDispatcher()->dispatch(WorkflowEvents::ACTION_FAILURE, new WorkflowEvent($this, [
                'actionConfig' => $actionConfig,
                'data' => $formData,
                'exception' => $e
            ]));
        }

        $this->unregisterActionEvents($actionConfig);

        \Pimcore::getEventDispatcher()->dispatch(WorkflowEvents::POST_ACTION, new WorkflowEvent($this, [
            'actionName' => $actionName
        ]));
    }

    /**
     * Returns the objects layout configuration given the current place in the workflow
     * If no layout is specified then null will be returned
     *
     * @return string|null
     */
    public function getObjectLayout()
    {
        $statusConfig = $this->workflow->getStatusConfig($this->getElementStatus());

        if (!empty($statusConfig['objectLayout']) && is_numeric($statusConfig['objectLayout'])) {
            return $statusConfig['objectLayout'];
        }

        return null;
    }

    /**
     * Used by performAction to initialise events
     *
     * @param $actionConfig
     */
    private function registerActionEvents($actionConfig)
    {
        if (isset($actionConfig['events'])) {
            if (isset($actionConfig['events']['before'])) {
                \Pimcore::getEventDispatcher()->addListener(WorkflowEvents::ACTION_BEFORE, $actionConfig['events']['before']);
            }
            if (isset($actionConfig['events']['success'])) {
                \Pimcore::getEventDispatcher()->addListener(WorkflowEvents::ACTION_SUCCESS, $actionConfig['events']['success']);
            }
            if (isset($actionConfig['events']['failure'])) {
                \Pimcore::getEventDispatcher()->addListener(WorkflowEvents::ACTION_FAILURE, $actionConfig['events']['failure']);
            }
        }
    }

    /**
     * Unregisters events (before, success, failure)
     *
     * @param $actionConfig
     */
    private function unregisterActionEvents($actionConfig)
    {
        if (isset($actionConfig['events'])) {
            if (isset($actionConfig['events']['before'])) {
                \Pimcore::getEventDispatcher()->removeListener(WorkflowEvents::ACTION_BEFORE, $actionConfig['events']['before']);
            }
            if (isset($actionConfig['events']['success'])) {
                \Pimcore::getEventDispatcher()->removeListener(WorkflowEvents::ACTION_SUCCESS, $actionConfig['events']['success']);
            }
            if (isset($actionConfig['events']['failure'])) {
                \Pimcore::getEventDispatcher()->removeListener(WorkflowEvents::ACTION_FAILURE, $actionConfig['events']['failure']);
            }
        }
    }

    /**
     * Returns whether or not an element has a workflow
     *
     * @param AbstractElement|Asset|ConcreteObject|Document $element
     *
     * @return bool
     */
    public static function elementHasWorkflow(AbstractElement $element)
    {
        $config = Workflow\Config::getElementWorkflowConfig($element);
        if (is_array($config)) {
            return true;
        }

        return false;
    }

    /**
     * Returns whether or not an element can be actioned
     *
     * @param AbstractElement $element
     *
     * @return bool
     */
    public static function elementCanAction(AbstractElement $element)
    {
        if (!self::elementHasWorkflow($element)) {
            return false;
        }

        $config = Workflow\Config::getElementWorkflowConfig($element);
        $subject = $config['workflowSubject'];

        if ($element instanceof Asset) {
            if (isset($subject['assetTypes'][0]) && !in_array($element->getType(), $subject['assetTypes'])) {
                return false;
            }

            return true;
        } elseif ($element instanceof AbstractObject && isset($subject['objectTypes'][0]) && !in_array($element->getType(), $subject['objectTypes'])) {
            return false;
        } elseif ($element instanceof Document && isset($subject['documentTypes'][0]) && !in_array($element->getType(), $subject['documentTypes'])) {
            return false;
        }

        /**
         * @var $element Document|ConcreteObject
         */
        if ($element->getPublished()) {
            return true;
        }

        $manager = new self($element);

        return $manager->getWorkflow()->getAllowUnpublished();
    }
}
