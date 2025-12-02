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

namespace Pimcore\Model\Schedule\Task;

use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Schedule\Task\Listing\Dao getDao()
 * @method Model\Schedule\Task[] load()
 * @method Model\Schedule\Task|false current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return Model\Schedule\Task[]
     */
    public function getTasks(): array
    {
        return $this->getData();
    }

    /**
     * @param Model\Schedule\Task[]|null $tasks
     *
     * @return $this
     */
    public function setTasks(?array $tasks): static
    {
        return $this->setData($tasks);
    }
}
