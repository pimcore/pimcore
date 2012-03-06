<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Schedule
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Schedule_Task_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of thumanils for the specicifies parameters, returns an array of Schedule_Task elements
     *
     * @return array
     */
    public function load() {

        $tasks = array();
        $tasksData = $this->db->fetchCol("SELECT id FROM schedule_tasks" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($tasksData as $taskData) {
            $tasks[] = Schedule_Task::getById($taskData);
        }

        $this->model->setTasks($tasks);
        return $tasks;
    }

}
