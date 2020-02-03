<?php

declare(strict_types=1);

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

namespace Pimcore\Model\Document\Traits;

use Pimcore\Model\Schedule\Task;
use Pimcore\Model\Schedule\Task\Listing;

trait ScheduledTasksTrait
{
    /**
     * Contains all scheduled tasks
     *
     * @var Task[]
     */
    public $scheduledTasks = null;

    /**
     * @return Task[] the $scheduledTasks
     */
    public function getScheduledTasks()
    {
        if ($this->scheduledTasks === null) {
            $taskList = new Listing();
            $taskList->setCondition("cid = ? AND ctype='document'", $this->getId());

            $this->setScheduledTasks($taskList->load());
        }

        return $this->scheduledTasks;
    }

    /**
     * @param Task[] $scheduledTasks
     *
     * @return $this
     */
    public function setScheduledTasks($scheduledTasks)
    {
        $this->scheduledTasks = $scheduledTasks;

        return $this;
    }

    public function saveScheduledTasks()
    {
        $scheduledTasks = $this->getScheduledTasks();
        $this->getDao()->deleteAllTasks();

        if (is_array($scheduledTasks) && count($scheduledTasks) > 0) {
            /** @var Task $task */
            foreach ($scheduledTasks as $task) {
                $task->setId(null);
                $task->setDao(null);
                $task->setCid($this->getId());
                $task->setCtype('document');
                $task->save();
            }
        }
    }
}
