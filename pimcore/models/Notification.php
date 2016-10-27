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

namespace Pimcore\Model;

use Pimcore\Logger;

/**
 * @author Piotr Ćwięcek <pcwiecek@divante.pl>
 * @author Kamil Karkus <kkarkus@divante.pl>
 *
 * @method \Pimcore\Model\Notification\Dao getDao()
 */
class Notification extends AbstractModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string info|error|success
     */
    public $type = 'info';

    /**
     * @var string
     */
    public $title = '';

    /**
     * @var string
     */
    public $message = '';

    /**
     * @var null|int
     */
    public $fromUser;

    /**
     * @var int
     */
    public $user;

    /**
     * @var bool
     */
    public $unread = true;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @param int $id
     *
     * @return Notification|null
     */
    public static function getById($id)
    {
        $id = intval($id);

        if ($id < 1) {
            return null;
        }

        $cacheKey = "notification_" . $id;

        try {
            $notification = \Zend_Registry::get($cacheKey);
            if (!$notification) {
                throw new \Exception("Notification in registry is null");
            }
        } catch (\Exception $e) {
            try {
                if (!$notification = \Pimcore\Cache::load($cacheKey)) {
                    $notification = new Notification();
                    $notification->getDao()->getById($id);

                    \Zend_Registry::set($cacheKey, $notification);
                    \Pimcore\Cache::save($notification, $cacheKey);
                } else {
                    \Zend_Registry::set($cacheKey, $notification);
                }
            } catch (\Exception $e) {
                Logger::warning($e->getMessage());

                return null;
            }
        }

        if (!$notification) {
            return null;
        }

        return $notification;
    }

    /**
     * @return $this
     */
    public static function create()
    {
        $notification = new self();
        $notification->save();

        return $notification;
    }

    /**
     * @return void
     */
    public function clearDependentCache()
    {
        try {
            \Pimcore\Cache::clearTags(["notification_" . $this->getId()]);
        } catch (\Exception $e) {
            Logger::crit($e);
        }
    }

    /**
     * @return void
     */
    public function delete()
    {
        \Pimcore::getEventManager()->trigger("notification.preDelete", $this);

        $this->getDao()->delete();
        $this->clearDependentCache();
        \Zend_Registry::set("document_" . $this->getId(), null);

        \Pimcore::getEventManager()->trigger("notification.postDelete", $this);
    }

    /**
     * @return void
     */
    public function save()
    {
        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("notification.preUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("notification.preAdd", $this);
        }

        $this->getDao()->save();
        $this->clearDependentCache();

        if ($isUpdate) {
            \Pimcore::getEventManager()->trigger("notification.postUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("notification.postAdd", $this);
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int)$id;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getFromUser()
    {
        return $this->fromUser;
    }

    /**
     * @param int|null $fromUser
     *
     * @return $this
     */
    public function setFromUser($fromUser)
    {
        $this->fromUser = $fromUser;
        return $this;
    }

    /**
     * @return int
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param int $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isUnread()
    {
        return $this->unread;
    }

    /**
     * @return boolean
     */
    public function isRead()
    {
        return !$this->unread;
    }

    /**
     * @param boolean $unread
     *
     * @return $this
     */
    public function setUnread($unread)
    {
        $this->unread = $unread;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }
}
