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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\Model\Notification;

/**
 * Class Admin_NotificationController
 *
 * @author Piotr Ćwięcek <pcwiecek@divante.pl>
 * @author Kamil Karkus <kkarkus@divante.pl>
 */
class Admin_NotificationController extends \Pimcore\Controller\Action\Admin\Document
{

    /**
     * Retrieves notifications json list for currently logged user.
     *
     * @throws Exception
     *
     * @return void
     */
    public function listAction()
    {
        $offset = $this->getParam("start") ? $this->getParam("start") : 0;
        $limit = $this->getParam("limit") ? $this->getParam("limit") : 40;

        $notifications = new Notification\Listing();
        $notifications
            ->setCondition('user = ?', $this->user->getId())
            ->setOffset($offset)
            ->setLimit($limit);
        $data = [];
        /** @var Notification $notification */
        foreach ($notifications->load() as $notification) {
            $date = new Zend_Date($notification->getCreationDate());
            $tmp = [
                'id' => $notification->getId(),
                'title' => $notification->getTitle(),
                'from' => '',
                'date' => $date->get('YYYY-MM-dd HH:mm:ss'),
                'unread' => $notification->isUnread()
            ];
            /** @var \Pimcore\Model\User $fromUser */
            $fromUser = \Pimcore\Model\User::getById($notification->getFromUser());
            if ($fromUser) {
                $tmp['from'] = $fromUser->getFirstname() . ' ' . $fromUser->getLastname();
                if (0 === strlen(trim($tmp['from']))) {
                    $tmp['from'] = $fromUser->getName();
                }
            }
            $data[] = $tmp;
        }

        $this->_helper->json([
            "data" => $data,
            "success" => true,
            "total" => $notifications->getTotalCount(),
        ]);
    }

    /**
     * Retrieves unread notifications json list for currently logged user.
     *
     * @return void
     */
    public function unreadAction()
    {
        $dc = substr($this->_getParam('_dc', ''), 0, -3); // convert to unix timestamp
        $interval = $this->_getParam('interval', 10);
        $notifications = new Notification\Listing();
        $notifications->setCondition('user = ? AND unread = 1 AND creationDate >= ?', [
            $this->user->getId(),
            $dc - $interval
        ]);
        $data = [];
        /** @var Notification $notification */
        foreach ($notifications->load() as $notification) {
            $date = new Zend_Date($notification->getCreationDate());
            $tmp = [
                'id' => $notification->getId(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'from' => '',
                'date' => $date->get('YYYY-MM-dd HH:mm:ss'),
                'type' => $notification->getType(),
            ];
            /** @var \Pimcore\Model\User $fromUser */
            $fromUser = \Pimcore\Model\User::getById($notification->getFromUser());
            if ($fromUser) {
                $tmp['from'] = $fromUser->getFirstname() . ' ' . $fromUser->getLastname();
                if (0 === strlen(trim($tmp['from']))) {
                    $tmp['from'] = $fromUser->getName();
                }
            }
            $data[] = $tmp;
        }

        $this->_helper->json([
            "data" => $data,
            "success" => true,
            "total" => $notifications->getTotalCount(),
        ]);
    }

    /**
     * Retrieves json detailed notification data for a given id.
     *
     * @throws Exception
     *
     * @return void
     */
    public function detailsAction()
    {
        $id = (int)$this->getParam('id');
        $notification = Notification::getById($id);

        if (!$notification) {
            throw new Exception('Notification not found');
        }

        $date = new Zend_Date($notification->getCreationDate());
        $data = [
            'id' => $notification->getId(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'from' => '',
            'date' => $date->get('YYYY-MM-dd HH:mm:ss'),
            'type' => $notification->getType(),
        ];
        /** @var \Pimcore\Model\User $fromUser */
        $fromUser = \Pimcore\Model\User::getById($notification->getFromUser());
        if ($fromUser) {
            $data['from'] = $fromUser->getFirstname() . ' ' . $fromUser->getLastname();
            if (0 === strlen(trim($data['from']))) {
                $data['from'] = $fromUser->getName();
            }
        }

        $this->changeStatus($notification);
        $this->_helper->json([
            "data" => $data,
            "success" => true,
        ]);
    }

    /**
     * Delete notification for a given id.
     *
     * @throws Exception
     *
     * @return void
     */
    public function deleteAction()
    {
        $id = (int)$this->getParam('id');
        $notification = Notification::getById($id);

        if (!$notification) {
            throw new Exception('Notification not found');
        }

        $notification->delete();
        $this->_helper->json([
            "success" => true,
        ]);
    }

    /**
     * Deletes all notifications for currently logged user.
     *
     * @return void
     */
    public function deleteAllAction()
    {
        $notifications = new Notification\Listing();
        $notifications->setCondition('user = ?', [
            $this->user->getId(),
        ]);
        /** @var Notification $notification */
        foreach ($notifications->load() as $notification) {
            $notification->delete();
        }
        $this->_helper->json([
            "success" => true,
        ]);
    }

    /**
     * Retrieves unread notifications count for currently logged user.
     *
     * @return void
     */
    public function unreadCountAction()
    {
        $notifications = new Notification\Listing();
        $notifications->setCondition('user = ? AND unread = 1', $this->user->getId());

        $this->_helper->json([
            "success" => true,
            "total" => $notifications->getTotalCount(),
        ]);
    }

    /**
     * Marks a notification as read.
     *
     * @throws Exception
     *
     * @return void
     */
    public function markAsReadAction()
    {
        $id = (int)$this->getParam('id');
        $notification = Notification::getById($id);

        if (!$notification) {
            throw new Exception('Notification not found');
        }

        $this->changeStatus($notification);
        $this->_helper->json([
            "success" => true,
        ]);
    }

    /**
     * Marks a notification as read and saves an object.
     *
     * @param Notification $object
     *
     * @return void
     */
    private function changeStatus(Notification $object)
    {
        $object->setUnread(false);
        $object->save();
    }
}
