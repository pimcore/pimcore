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
 * @category   Pimcore
 * @package    Schedule
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Schedule\Task\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Schedule\Task\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{

    /**
     * Loads a list of thumanils for the specicifies parameters, returns an array of Schedule\Task elements
     *
     * @return array
     */
    public function load()
    {
        $tasks = [];
        $tasksData = $this->db->fetchCol("SELECT id FROM schedule_tasks" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($tasksData as $taskData) {
            $tasks[] = Model\Schedule\Task::getById($taskData);
        }

        $this->model->setTasks($tasks);

        return $tasks;
    }
}
