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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;
use Pimcore\Tool\Session;

/**
 * @internal
 *
 * @method \Pimcore\Model\Element\Editlock\Dao getDao()
 * @method void delete()
 * @method void save()
 */
final class Editlock extends Model\AbstractModel
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $cid;

    /**
     * @var string
     */
    protected $ctype;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var int
     */
    protected $date;

    /**
     * @var string
     */
    protected $cpath;

    /**
     * @param int $cid
     * @param string $ctype
     *
     * @return bool
     */
    public static function isLocked($cid, $ctype)
    {
        if ($lock = self::getByElement($cid, $ctype)) {
            if ((time() - $lock->getDate()) > 3600 || $lock->getSessionId() === Session::getSessionId()) {
                // lock is out of date unlock it
                self::unlock($cid, $ctype);

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param int $cid
     * @param string $ctype
     *
     * @return null|Editlock
     */
    public static function getByElement($cid, $ctype)
    {
        try {
            $lock = new self();
            $lock->getDao()->getByElement($cid, $ctype);

            return $lock;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $sessionId
     *
     * @return bool|null
     */
    public static function clearSession($sessionId)
    {
        try {
            $lock = new self();
            $lock->getDao()->clearSession($sessionId);

            return true;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param int $cid
     * @param string $ctype
     *
     * @return bool|Editlock
     */
    public static function lock($cid, $ctype)
    {

        // try to get user
        if (!$user = \Pimcore\Tool\Admin::getCurrentUser()) {
            return false;
        }

        $lock = new self();
        $lock->setCid($cid);
        $lock->setCtype($ctype);
        $lock->setDate(time());
        $lock->setUserId($user->getId());
        $lock->setSessionId(Session::getSessionId());
        $lock->save();

        return $lock;
    }

    /**
     * @param int $cid
     * @param string $ctype
     *
     * @return bool
     */
    public static function unlock($cid, $ctype)
    {
        if ($lock = self::getByElement($cid, $ctype)) {
            $lock->delete();
        }

        return true;
    }

    /**
     * @return int
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $cid
     *
     * @return $this
     */
    public function setCid($cid)
    {
        $this->cid = (int) $cid;

        return $this;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @param int $userId
     *
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = (int) $userId;

        return $this;
    }

    /**
     * @return string
     */
    public function getCtype()
    {
        return $this->ctype;
    }

    /**
     * @param string $ctype
     *
     * @return $this
     */
    public function setCtype($ctype)
    {
        $this->ctype = (string) $ctype;

        return $this;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     *
     * @return $this
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = (string) $sessionId;

        return $this;
    }

    /**
     * @return Model\User|null
     */
    public function getUser()
    {
        if ($user = Model\User::getById($this->getUserId())) {
            return $user;
        }

        return null;
    }

    /**
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = (int) $date;

        return $this;
    }

    /**
     * @param string $cpath
     *
     * @return $this
     */
    public function setCpath($cpath)
    {
        $this->cpath = $cpath;

        return $this;
    }

    /**
     * @return string
     */
    public function getCpath()
    {
        return $this->cpath;
    }
}
