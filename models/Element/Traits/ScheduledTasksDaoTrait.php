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
    public function deleteAllTasks(array $ignoreIds = []): void
    {
        $type = Service::getElementType($this->model);
        if ($this->model->getId()) {
            $where = '`cid` = ' . $this->model->getId() . ' AND `ctype` = ' . $this->db->quote($type);
            if ($ignoreIds) {
                $where .= ' AND `id` NOT IN (' . implode(',', $ignoreIds) . ')';
            }
            $this->db->executeStatement('DELETE FROM schedule_tasks WHERE ' . $where);
        }
    }
}
