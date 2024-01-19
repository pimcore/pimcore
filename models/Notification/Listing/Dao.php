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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
            $count = (int) $this->db->fetchOne($sql, $this->getModel()->getConditionVariables());
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

        $ids = $this->db->fetchFirstColumn($sql, $this->getModel()->getConditionVariables());

        foreach ($ids as $id) {
            $notification = Notification::getById((int) $id);

            if ($notification instanceof Notification) {
                $notifications[] = $notification;
            }
        }

        $this->getModel()->setNotifications($notifications);

        return $notifications;
    }

    protected function getModel(): Notification\Listing
    {
        return $this->model;
    }
}
