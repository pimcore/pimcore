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

namespace Pimcore\Model;

use Pimcore\Logger;

/**
 * Class Workflow
 *
 * @method Workflow\Dao getDao()
 *
 * @package Pimcore\Model
 */
class Workflow extends AbstractModel
{
    /**
     * @var int $id
     */
    public $id;

    /**
     * The name of the workflow
     *
     * @var string
     */
    public $name;

    /**
     * Cache of valid states in this workflow
     *
     * @var array
     */
    public $states;

    /**
     * Cache of valid statuses in this workflow
     *
     * @var array
     */
    public $statuses;

    /**
     * Cache of valid actions in this workflow
     *
     * @var array
     */
    public $actions;

    /**
     * The actual workflow
     *
     * @var array
     */
    public $transitionDefinitions;

    /**
     * The default state of the element
     *
     * @var string
     */
    public $defaultState;

    /**
     * The default status of the element
     *
     * @var
     */
    public $defaultStatus;

    /**
     * Determines whether or not to allow unpublished elements to
     * have actions
     *
     * @var bool
     */
    public $allowUnpublished;

    /**
     * @var array
     */
    public $workflowSubject;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @param int $id
     *
     * @return Workflow
     */
    public static function getById($id)
    {
        $cacheKey = 'workflow_' . $id;

        try {
            $workflow = \Pimcore\Cache\Runtime::get($cacheKey);
            if (!$workflow) {
                throw new \Exception('Workflow in registry is null');
            }
        } catch (\Exception $e) {
            try {
                $workflow = new self();
                \Pimcore\Cache\Runtime::set($cacheKey, $workflow);
                $workflow->setId(intval($id));
                $workflow->getDao()->getById();
            } catch (\Exception $e) {
                Logger::error($e);

                return null;
            }
        }

        return $workflow;
    }

    /**
     * @return Workflow
     */
    public static function create()
    {
        $workflow = new self();
        $workflow->save();

        return $workflow;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function getAllowUnpublished()
    {
        return $this->allowUnpublished;
    }

    /**
     * @param bool $allowUnpublished
     */
    public function setAllowUnpublished($allowUnpublished)
    {
        $this->allowUnpublished = $allowUnpublished;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param array $actions
     */
    public function setActions($actions)
    {
        $this->actions = $actions;
    }

    /**
     * Return an array of valid workflow action names
     *
     * @return array
     */
    public function getValidActions()
    {
        $validActions = [];
        foreach ($this->getActions() as $action) {
            $validActions[] = $action['name'];
        }

        return $validActions;
    }

    /**
     * @return array
     */
    public function getStates()
    {
        return $this->states;
    }

    /**
     * @param array $states
     */
    public function setStates($states)
    {
        $this->states = $states;
    }

    /**
     * Returns an array of valid workflow state names
     *
     * @return array
     */
    public function getValidStates()
    {
        $validStates = [];
        foreach ($this->getStates() as $state) {
            $validStates[] = $state['name'];
        }

        return $validStates;
    }

    /**
     * Returns whether or not a state name is valid within the workflow
     *
     * @param $stateName
     *
     * @return bool
     */
    public function isValidState($stateName)
    {
        return in_array($stateName, $this->getValidStates());
    }

    /**
     * @return array
     */
    public function getStatuses()
    {
        return $this->statuses;
    }

    /**
     * @param array $statuses
     */
    public function setStatuses($statuses)
    {
        $this->statuses = $statuses;
    }

    /**
     * Returns an array of valid workflow status names
     *
     * @return array
     */
    public function getValidStatuses()
    {
        $validStatuses = [];
        foreach ($this->getStatuses() as $status) {
            $validStatuses[] = $status['name'];
        }

        return $validStatuses;
    }

    /**
     * Returns whether or not a status name is valid within the workflow
     *
     * @param $statusName
     *
     * @return bool
     */
    public function isValidStatus($statusName)
    {
        return in_array($statusName, $this->getValidStatuses());
    }

    /**
     * Returns an array of valid workflow global actions names
     *
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
     *
     * @param $actionName
     *
     * @return bool
     */
    public function isValidAction($actionName)
    {
        return in_array($actionName, $this->getValidActions());
    }

    /**
     * Returns whether or not an action name is a global action
     *
     * @param $actionName
     *
     * @return bool
     */
    public function isGlobalAction($actionName)
    {
        return in_array($actionName, $this->getValidGlobalActions());
    }

    /**
     * @param $stateName
     *
     * @return bool|mixed
     */
    public function getStateConfig($stateName)
    {
        foreach ($this->getStates() as $state) {
            if ($state['name'] === $stateName) {
                return $state;
            }
        }

        return false;
    }

    /**
     * @param $statusName
     *
     * @return bool|mixed
     */
    public function getStatusConfig($statusName)
    {
        foreach ($this->getStatuses() as $status) {
            if ($status['name'] === $statusName) {
                return $status;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getTransitionDefinitions()
    {
        return $this->transitionDefinitions;
    }

    /**
     * @param array $transitionDefinitions
     */
    public function setTransitionDefinitions($transitionDefinitions)
    {
        $this->transitionDefinitions = $transitionDefinitions;
    }

    /**
     * @param $statusName
     *
     * @return array
     */
    public function getValidActionsForStatus($statusName)
    {
        return array_keys($this->transitionDefinitions[$statusName]['validActions']);
    }

    /**
     * Returns the statuses where an element should be published
     *
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
     * @return string
     */
    public function getDefaultState()
    {
        return $this->defaultState;
    }

    /**
     * @param string $defaultState
     */
    public function setDefaultState($defaultState)
    {
        $this->defaultState = $defaultState;
    }

    /**
     * @return mixed
     */
    public function getDefaultStatus()
    {
        return $this->defaultStatus;
    }

    /**
     * @param mixed $defaultStatus
     */
    public function setDefaultStatus($defaultStatus)
    {
        $this->defaultStatus = $defaultStatus;
    }

    /**
     * Returns a configuration for an action
     * If a status is given then the configuration for the status will be applied too
     *
     * @param $actionName
     * @param $statusName
     *
     * @return array|null
     *
     * @throws \Exception
     */
    public function getActionConfig($actionName, $statusName = null)
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
     *
     * @param $actionName
     * @param $statusName
     *
     * @return array|null
     */
    public function getValidUsersForAction($actionName, $statusName = null)
    {
        $actionConfig = $this->getActionConfig($actionName, $statusName);
        if (!empty($actionConfig['users']) && is_array($actionConfig['users'])) {
            return $actionConfig['users'];
        }

        return null;
    }

    /**
     * Returns additional fields for an action.
     *
     * @param $actionName
     * @param $statusName
     *
     * @return array
     */
    public function getAdditionalFieldsForAction($actionName, $statusName = null)
    {
        $actionConfig = $this->getActionConfig($actionName, $statusName);

        if (empty($actionConfig['additionalFields'])) {
            return null;
        }

        $fields = [];
        foreach ($actionConfig['additionalFields'] as $field) {
            if (isset($field['fieldType'])) {
                //support for pimcore tags
                $newField = $field;

                if ($field['fieldType'] === 'user') {
                    $def = new \Pimcore\Model\DataObject\ClassDefinition\Data\User();
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

    /**
     * @return array
     */
    public function getWorkflowSubject()
    {
        return $this->workflowSubject;
    }

    /**
     * @param array $workflowSubject
     */
    public function setWorkflowSubject($workflowSubject)
    {
        $this->workflowSubject = $workflowSubject;
    }

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $modificationDate
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;
    }
}
