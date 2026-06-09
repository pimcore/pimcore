<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Notification\Listing;

use Doctrine\DBAL\Exception;
use Pimcore\Model\Listing\Dao\AbstractDao;
use Pimcore\Model\Notification;

/**
 * @internal
 *
 * @property \Pimcore\Model\Notification\Listing $model
 */
class Dao extends AbstractDao
{
    const DB_TABLE_NAME = 'notifications';

    public function count(): int
    {
        $sql = sprintf('SELECT COUNT(*) AS num FROM `%s`%s', static::DB_TABLE_NAME, $this->getCondition());

        try {
            $count = (int) $this->db->fetchOne($sql, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        } catch (\Exception $ex) {
            $count = 0;
        }

        return $count;
    }

    public function getTotalCount(): int
    {
        return $this->count();
    }

    /**
     *
     * @throws Exception
     */
    public function load(): array
    {
        $notifications = [];
        $sql = sprintf(
            'SELECT id FROM `%s`%s%s%s',
            static::DB_TABLE_NAME,
            $this->getCondition(),
            $this->getOrder(),
            $this->getOffsetLimit()
        );

        $ids = $this->db->fetchFirstColumn($sql, $this->model->getConditionVariables(), $this->model->getConditionVariableTypes());
        foreach ($ids as $id) {
            $notification = Notification::getById((int) $id);

            if ($notification instanceof Notification) {
                $notifications[] = $notification;
            }
        }

        $this->model->setNotifications($notifications);

        return $notifications;
    }
}
