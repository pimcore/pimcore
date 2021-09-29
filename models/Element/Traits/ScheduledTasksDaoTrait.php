<?php

/**
 * Pimcore.
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

/**
 * @internal
 */
trait ScheduledTasksDaoTrait
{
    /**
     * Deletes all scheduled tasks assigned to the element.
     *
     * @param int[] $ignoreIds
     */
    public function deleteAllTasks(array $ignoreIds = [])
    {
        $type = Service::getElementType($this->model);
        $where = '`cid` = '.$this->db->quote($this->model->getId()).' AND `ctype` = '.$this->db->quote($type);
        if ($ignoreIds) {
            $where .= ' AND `id` NOT IN ('.implode(',', $ignoreIds).')';
        }
        $this->db->deleteWhere('schedule_tasks', $where);
    }
}
