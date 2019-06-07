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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Schedule\Task;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Schedule\Task\Listing\Dao getDao()
 * @method Model\Schedule\Task[] load()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var array|null
     */
    protected $tasks = null;

    /**
     * @return Model\Schedule\Task[]
     */
    public function getTasks()
    {
        if ($this->tasks === null) {
            $this->getDao()->load();
        }

        return $this->tasks;
    }

    /**
     * @param array $tasks
     *
     * @return $this
     */
    public function setTasks($tasks)
    {
        $this->tasks = $tasks;

        return $this;
    }
}
