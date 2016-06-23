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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\WorkflowManagement;

class Workflow
{

    /**
     * Workflow Id
     * @var int $id
     */
    private $id;

    /**
     * The name of the workflow
     * @var string
     */
    private $name;

    /**
     *
     * @var string $type
     */
    private $type;


    /**
     *
     * @var int $classes
     */
    private $classes;

    /**
     * Cache of valid states in this workflow
     * @var array
     */
    private $states;

    /**
     * Cache of valid statuses in this workflow
     * @var array
     */
    private $statuses;


    /**
     * Cache of valid actions in this workflow
     * @var array
     */
    private $actions;


    /**
     * The actual workflow
     * @var array
     */
    private $transitionDefinitions;

    /**
     * The default state of the element
     * @var string
     */
    private $defaultState;

    /**
     * The default status of the element
     * @var
     */
    private $defaultStatus;

    /**
     * Determines whether or not to allow unpublished elements to
     * have actions
     * @var bool
     */
    public $allowUnpublished;


    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->type = $data['type'];
        $this->classes = $data['classes'];
        $this->states = $data['states'];
        $this->statuses = $data['statuses'];
        $this->actions = $data['actions'];
        $this->transitionDefinitions = $data['transitionDefinitions'];
        $this->defaultState = $data['defaultState'];
        $this->defaultStatus = $data['defaultStatus'];
        $this->allowUnpublished = $data['allowUnpublished'];
    }

    public function getId() {
        return $this->id;
    }

    /**
     * Returns the default state for new elements
     * @return string
     */
    public function getDefaultState()
    {
        return $this->defaultState;
    }

    /**
     * Returns the default status for new elements
     * @return string
     */
    public function getDefaultStatus()
    {
        return $this->defaultStatus;
    }

    /**
     * @return bool
     */
    public function getAllowUnpublished()
    {
        return (bool) $this->allowUnpublished;
    }

    /**
     * Return an array of valid workflow action names
     * @return array
     */
    public function getValidActions()
    {
        $validActions = [];
        foreach ($this->actions as $action) {
            $validActions[] = $action['name'];
        }

        return $validActions;
    }

    /**
     * Returns an array of valid workflow state names
     * @return array
     */
    public function getValidStates()
    {
        $validStates = [];
        foreach ($this->states as $state) {
            $validStates[] = $state['name'];
        }

        return $validStates;
    }

    /**
     * Returns whether or not a state name is valid within the workflow
     * @param $state
     * @return bool
     */
    public function isValidState($stateName)
    {
        return in_array($stateName, $this->getValidStates());
    }

    /**
     * Returns an array of valid workflow status names
     * @return array
     */
    public function getValidStatuses()
    {
        $validStatuses = [];
        foreach ($this->statuses as $status) {
            $validStatuses[] = $status['name'];
        }

        return $validStatuses;
    }

    /**
     * Returns whether or not a status name is valid within the workflow
     * @param $status
     * @return bool
     */
    public function isValidStatus($statusName)
    {
        return in_array($statusName, $this->getValidStatuses());
    }

    /**
     * Returns an array of valid workflow global actions names
     * @return array
     */
    public function getValidGlobalActions()
    {
        if (!empty($this->transitionDefinitions['globalActions'])) {
            return array_keys($this->transitionDefinitions['globalActions']);
        }

        return [];
    }

    /**
     * Returns whether or not an action is valid in this workflow
     * @param $actionName
     * @return bool
     */
    public function isValidAction($actionName)
    {
        return in_array($actionName, $this->getValidActions());
    }

    /**
     * Returns whether or not an action name is a global action
     * @param $actionName
     * @return bool
     */
    public function isGlobalAction($actionName)
    {
        return in_array($actionName, $this->getValidGlobalActions());
    }


    public function getStateConfig($stateName)
    {
        foreach ($this->states as $state) {
            if ($state['name'] === $stateName) {
                return $state;
            }
        }

        return false;
    }


    public function getStatusConfig($statusName)
    {
        foreach ($this->statuses as $status) {
            if ($status['name'] === $statusName) {
                return $status;
            }
        }

        return false;
    }

    public function getTransitionDefinitions()
    {
        return $this->transitionDefinitions;
    }

    public function getValidActionsForStatus($statusName)
    {
        return array_keys($this->transitionDefinitions[$statusName]['validActions']);
    }

    /**
     * Returns the statuses where an element should be published
     * @return array
     */
    public function getPublishedStatuses()
    {
        $statuses = [];

        foreach ($this->statuses as $config) {
            if (isset($config['elementPublished']) && $config['elementPublished'] == true) {
                $statuses[] = $config['name'];
            }
        }

        return $statuses;
    }


    /**
     * Returns a configuration for an action
     * If a status is given then the configuration for the status will be applied too
     * @param $actionName
     * @param $statusName
     * @return $array|null
     * @throws \Exception
     */
    public function getActionConfig($actionName, $statusName=null)
    {
        $actionConfig = null;

        foreach ($this->actions as $action) {
            if ($action['name'] === $actionName) {
                $actionConfig = $action;
            }
        }

        if ($statusName && !$this->isGlobalAction($actionName)) {

            //check the status has this action
            if (!array_key_exists($actionName, $this->transitionDefinitions[$statusName]['validActions'])) {
                throw new \Exception("Cannot merge action configuration [{$actionName}] for status [{$statusName}], action name is not valid in status");
            }

            //merge configuration keys
            $extendedActionConfig = $this->transitionDefinitions[$statusName]['validActions'][$actionName];
            if (is_array($extendedActionConfig)) {
                $actionConfig = ($actionConfig + $extendedActionConfig); //we want to overwrite numeric keys
            }
        }

        return $actionConfig;
    }

    /**
     * Returns all of the valid users for an action
     * if a status is given it will be taken into consideration.
     * @param $actionName
     * @param $statusName
     * @return array|null
     */
    public function getValidUsersForAction($actionName, $statusName=null)
    {
        $actionConfig = $this->getActionConfig($actionName, $statusName);
        if (!empty($actionConfig['users']) && is_array($actionConfig['users'])) {
            return $actionConfig['users'];
        }
        return null;
    }


    /**
     * Returns additional fields for an action.
     * @param $actionName
     * @return array
     */
    public function getAdditionalFieldsForAction($actionName, $statusName=null)
    {

        $actionConfig = $this->getActionConfig($actionName, $statusName);

        if (empty($actionConfig['additionalFields'])) {
            return null;
        }

        $fields = [];
        foreach($actionConfig['additionalFields'] as $field) {

            if (isset($field['fieldType'])) {
                //support for pimcore tags
                $newField = $field;

                if($field['fieldType'] === 'user') {
                    $def = new \Pimcore\Model\Object\ClassDefinition\Data\User();
                    $def->configureOptions();
                    $newField['options'] = $def->getOptions();
                }

            } else {
                //support simple extjs types
                $newField = $field;
            }

            $fields[] = $newField;
        }

        return $fields;
    }

}
