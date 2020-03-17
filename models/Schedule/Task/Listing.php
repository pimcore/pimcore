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
 * @method Model\Schedule\Task current()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var Model\Schedule\Task[]|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $tasks = null;

    public function __construct()
    {
        $this->tasks = & $this->data;
    }

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
