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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
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
        $elementId = $this->model->getId();
        if (!$elementId) {
            return;
        }

        // The ids are read first (a non-locking consistent read) and the rows are then deleted by primary key.
        // A DELETE filtered by cid and ctype may be executed through the ctype index, which locks every scanned
        // row of that element type until the transaction commits and serializes concurrent saves of other elements.
        $sql = 'SELECT id FROM schedule_tasks WHERE cid = ? AND ctype = ?';
        $params = [$elementId, Service::getElementType($this->model)];
        $types = [ParameterType::INTEGER, ParameterType::STRING];

        if ($ignoreIds !== []) {
            $sql .= ' AND id NOT IN (?)';
            $params[] = $ignoreIds;
            $types[] = ArrayParameterType::INTEGER;
        }

        $taskIds = array_map('intval', $this->db->fetchFirstColumn($sql, $params, $types));
        if ($taskIds === []) {
            return;
        }

        $this->db->executeStatement(
            'DELETE FROM schedule_tasks WHERE id IN (?)',
            [$taskIds],
            [ArrayParameterType::INTEGER]
        );
    }
}
