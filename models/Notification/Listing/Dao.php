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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

declare(strict_types=1);

namespace Pimcore\Model\Notification\Listing;

use Pimcore\Model\Listing\Dao\AbstractDao;
use Pimcore\Model\Notification;

/**
 * @property \Pimcore\Model\Notification\Listing $model
 */
class Dao extends AbstractDao
{
    const DB_TABLE_NAME = 'notifications';

    /**
     * @return int
     */
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

    public function getTotalCount()
    {
        return $this->count();
    }

    /**
     * @return array
     *
     * @throws \Doctrine\DBAL\DBALException
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

        $ids = $this->db->fetchCol($sql, $this->getModel()->getConditionVariables());

        foreach ($ids as $id) {
            $notification = Notification::getById((int) $id);

            if ($notification instanceof Notification) {
                $notifications[] = $notification;
            }
        }

        $this->getModel()->setNotifications($notifications);

        return $notifications;
    }

    /**
     * @return Notification\Listing
     */
    protected function getModel(): Notification\Listing
    {
        return $this->model;
    }
}
