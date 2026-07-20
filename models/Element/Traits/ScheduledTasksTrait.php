<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
