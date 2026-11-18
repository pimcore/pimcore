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

namespace Pimcore\Tests\Support\Helper\Element;

/**
 * Counts the calls to deleteAllTasks() of the DAO it is used in and delegates to the real implementation.
 */
trait DeleteAllTasksCountingDaoTrait
{
    private int $deleteAllTasksCalls = 0;

    public function deleteAllTasks(array $ignoreIds = []): void
    {
        $this->deleteAllTasksCalls++;
        parent::deleteAllTasks($ignoreIds);
    }

    public function getDeleteAllTasksCalls(): int
    {
        return $this->deleteAllTasksCalls;
    }
}
