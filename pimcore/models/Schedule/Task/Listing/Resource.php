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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Schedule\Task\Listing;

use Pimcore\Model;

class Resource extends Model\Listing\Resource\AbstractResource {

    /**
     * Loads a list of thumanils for the specicifies parameters, returns an array of Schedule\Task elements
     *
     * @return array
     */
    public function load() {

        $tasks = array();
        $tasksData = $this->db->fetchCol("SELECT id FROM schedule_tasks" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($tasksData as $taskData) {
            $tasks[] = Model\Schedule\Task::getById($taskData);
        }

        $this->model->setTasks($tasks);
        return $tasks;
    }

}
