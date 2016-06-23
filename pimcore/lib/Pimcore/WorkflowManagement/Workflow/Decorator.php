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

namespace Pimcore\WorkflowManagement\WorkFlow;

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

    public function getAvailableActionsForForm($actionConfigs)
    {
        $availableActions = [];
        foreach ($actionConfigs as $actionConfig) {
            $availableActions[] = [
                'value' => $actionConfig['name'],
                'label' => $actionConfig['label']
            ];
        }

        return $availableActions;
    }


    public function getAvailableStatesForForm($stateConfigs)
    {
        $availableStates = [];
        foreach ($stateConfigs as $stateConfig) {
            $availableStates[] = [
                'value' => $stateConfig['name'],
                'label' => $stateConfig['label'],
                'color' => $stateConfig['color']
            ];
        }

        return $availableStates;
    }

    public function getAvailableStatusesForForm($statusConfigs)
    {
        $availableStatuses = [];
        foreach ($statusConfigs as $statusConfig) {
            $availableStatuses[] = [
                'value' => $statusConfig['name'],
                'label' => $statusConfig['label'],
            ];
        }

        return $availableStatuses;
    }


    public function getStatusLabel($statusName)
    {
        if (!$this->workflow) {
           throw new \Exception('Decorator needs a workflow to produce labels');
        }

        $config = $this->workflow->getStatusConfig($statusName);

        //todo add translation support
        return $config['label'];
    }


    public function getActionLabel($actionName)
    {
        if (!$this->workflow) {
            throw new \Exception('Decorator needs a workflow to produce labels');
        }

        $config = $this->workflow->getActionConfig($actionName);

        //todo add translation support
        return $config['label'];
    }

    /**
     * Returns the note type title
     * @param $actionName
     * @return string
     */
    public function getNoteType($actionName, $formData)
    {
        $config = $this->workflow->getActionConfig($actionName);
        if(!empty($config['note_type'])) {
            return $config['note_type'];
        }

        if ($this->workflow->isGlobalAction($actionName)) {
            return 'Global action';
        }

        return 'Status update';
    }


    public function getNoteTitle($actionName, $formData)
    {
        $config = $this->workflow->getActionConfig($actionName);
        if(!empty($config['note_title'])) {
            return $config['note_title'];
        }

        if ($this->workflow->isGlobalAction($actionName) || $formData['oldStatus'] === $formData['newStatus']) {
            return $this->getActionLabel($actionName);
        }

        return $this->getStatusLabel($formData['oldStatus']) . ' ->' . $this->getStatusLabel($formData['newStatus']);
    }




}