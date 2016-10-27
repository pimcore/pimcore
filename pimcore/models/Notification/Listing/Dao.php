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
 * @package    Notification
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Notification\Listing;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Notification;

/**
 * @author Piotr Ćwięcek <pcwiecek@divante.pl>
 * @author Kamil Karkus <kkarkus@divante.pl>
 *
 * @property \Pimcore\Model\Notification\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * @var callable function
     */
    protected $onCreateQueryCallback;

    /**
     * Loads a list of objects (all are an instance of Notification) for the given parameters an return them
     *
     * @return Notification[]
     */
    public function load()
    {
        $notifications = [];
        $select = (string) $this->getQuery(['id']);

        $notificationsData = $this->db->fetchAll($select, $this->model->getConditionVariables());

        foreach ($notificationsData as $notificationData) {
            if ($notification = Notification::getById($notificationData["id"])) {
                $notifications[] = $notification;
            }
        }

        $this->model->setNotifications($notifications);

        return $notifications;
    }

    /**
     * @param array $columns
     *
     * @return \Zend_Db_Select
     */
    public function getQuery($columns)
    {
        $select = $this->db->select();
        $select->from(
            [ "notifications" ], $columns
        );
        $this->addConditions($select);
        $this->addOrder($select);
        $this->addLimit($select);
        $this->addGroupBy($select);

        if ($this->onCreateQueryCallback) {
            $closure = $this->onCreateQueryCallback;
            $closure($select);
        }

        return $select;
    }

    /**
     * Loads a list of notification ids for the specicifies parameters, returns an array of ids
     *
     * @return int[]
     */
    public function loadIdList()
    {
        $select = (string) $this->getQuery(['id']);
        $notificationIds = $this->db->fetchCol($select, $this->model->getConditionVariables());

        return $notificationIds;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        $select = $this->getQuery([new \Zend_Db_Expr('COUNT(*)')]);
        $amount = (int)$this->db->fetchOne($select, $this->model->getConditionVariables());

        return $amount;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        try {
            $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM notifications " . $this->getCondition(), $this->model->getConditionVariables());
        } catch (\Exception $e) {
            Logger::warning($e->getMessage());
        }

        return $amount;
    }

    /**
     * @param callable $callback
     *
     * @return void
     */
    public function onCreateQuery(callable $callback)
    {
        $this->onCreateQueryCallback = $callback;
    }
}
