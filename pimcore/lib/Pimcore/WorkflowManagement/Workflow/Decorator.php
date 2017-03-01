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

namespace Pimcore\WorkflowManagement\Workflow;

use Pimcore\WorkflowManagement\Workflow;

class Decorator
{

    /**
     * @var Workflow $workflow
     */
    public $workflow;

    /**
     * @param null $workflow Workflow
     */
    public function __construct($workflow=null)
    {
        $this->workflow = $workflow;
    }

    /**
     * @param $key
     * @return string
     */
    private function translateLabel($key)
    {
        try {
            return \Pimcore\Model\Translation\Admin::getByKeyLocalized($key, false, true);
        } catch (\Exception $e) {
            return $key;
        }
    }

    /**
     * @param $actionConfigs
     * @return array
     */
    public function getAvailableActionsForForm($actionConfigs)
    {
        $availableActions = [];
        foreach ($actionConfigs as $actionConfig) {
            $availableActions[] = [
                'value' => $actionConfig['name'],
                'label' => $this->translateLabel($actionConfig['label'])
            ];
        }

        return $availableActions;
    }

    /**
     * @param $stateConfigs
     * @return array
     */
    public function getAvailableStatesForForm($stateConfigs)
    {
        $availableStates = [];
        foreach ($stateConfigs as $stateConfig) {
            $availableStates[] = [
                'value' => $stateConfig['name'],
                'label' => $this->translateLabel($stateConfig['label']),
                'color' => $stateConfig['color']
            ];
        }

        return $availableStates;
    }

    /**
     * @param $statusConfigs
     * @return array
     */
    public function getAvailableStatusesForForm($statusConfigs)
    {
        $availableStatuses = [];
        foreach ($statusConfigs as $statusConfig) {
            $availableStatuses[] = [
                'value' => $statusConfig['name'],
                'label' => $this->translateLabel($statusConfig['label']),
            ];
        }

        return $availableStatuses;
    }

    /**
     * @param $statusName
     * @return string
     * @throws \Exception
     */
    public function getStatusLabel($statusName)
    {
        if (!$this->workflow) {
            throw new \Exception('Decorator needs a workflow to produce labels');
        }

        $config = $this->workflow->getStatusConfig($statusName);

        return $this->translateLabel($config['label']);
    }

    /**
     * @param $actionName
     * @return string
     * @throws \Exception
     */
    public function getActionLabel($actionName)
    {
        if (!$this->workflow) {
            throw new \Exception('Decorator needs a workflow to produce labels');
        }

        $config = $this->workflow->getActionConfig($actionName);

        return $this->translateLabel($config['label']);
    }

    /**
     * Returns the note type title
     * @param $actionName
     * @param $formData
     * @return string
     */
    public function getNoteType($actionName, $formData)
    {
        $config = $this->workflow->getActionConfig($actionName);
        if (!empty($config['notes']['type'])) {
            return $config['notes']['type'];
        }

        if ($this->workflow->isGlobalAction($actionName)) {
            return 'Global action';
        }

        return 'Status update';
    }

    /**
     * @param $actionName
     * @param $formData
     * @return string
     */
    public function getNoteTitle($actionName, $formData)
    {
        $config = $this->workflow->getActionConfig($actionName);
        if (!empty($config['notes']['title'])) {
            return $config['notes']['title'];
        }

        if ($this->workflow->isGlobalAction($actionName) || $formData['oldStatus'] === $formData['newStatus']) {
            return $this->getActionLabel($actionName);
        }

        return $this->getStatusLabel($formData['oldStatus']) . ' ->' . $this->getStatusLabel($formData['newStatus']);
    }
}
