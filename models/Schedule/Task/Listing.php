<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Schedule\Task;

use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Schedule\Task\Listing\Dao getDao()
 * @method Model\Schedule\Task[] load()
 * @method Model\Schedule\Task current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return Model\Schedule\Task[]
     */
    public function getTasks()
    {
        return $this->getData();
    }

    /**
     * @param Model\Schedule\Task[]|null $tasks
     *
     * @return static
     */
    public function setTasks($tasks)
    {
        return $this->setData($tasks);
    }
}
