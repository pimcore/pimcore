<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Element\Traits;

use Pimcore\Model\Element\Service;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\Schedule\Task\Listing;

/**
 * @internal
 */
trait ScheduledTasksTrait
{
    /**
     * Contains all scheduled tasks
     *
     * @var Task[]|null
     */
    protected ?array $scheduledTasks = null;

    /**
     * @return Task[] the $scheduledTasks
     */
    public function getScheduledTasks(): array
    {
        if ($this->scheduledTasks === null) {
            $taskList = new Listing();
            $ctype = Service::getElementType($this);
            $taskList->setCondition('`cid` = ? AND `ctype` = ?', [$this->getId(), $ctype]);

            $this->setScheduledTasks($taskList->load());
        }

        return $this->scheduledTasks;
    }

    /**
     * @param Task[] $scheduledTasks
     *
     * @return $this
     */
    public function setScheduledTasks(array $scheduledTasks): static
    {
        $this->scheduledTasks = $scheduledTasks;

        return $this;
    }

    public function saveScheduledTasks(): void
    {
        $scheduledTasks = $this->getScheduledTasks();
        $ignoreIds = [];
        $ctype = Service::getElementType($this);
        foreach ($scheduledTasks as $task) {
            $task->setDao(null);
            $task->setCid($this->getId());
            $task->setCtype($ctype);
            $task->save();
            $ignoreIds[] = $task->getId();
        }
        $this->getDao()->deleteAllTasks($ignoreIds);
    }
}
