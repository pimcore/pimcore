<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Element_Editlock extends Pimcore_Model_Abstract {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $cid;

    /**
     * @var string
     */
    public $ctype;

    /**
     * @var integer
     */
    public $userId;

    /**
     * @var string
     */
    public $sessionId;

    /**
     * @var integer
     */
    public $date;

    /**
     * @var
     */
    public $cpath;


    public static function isLocked($cid, $ctype) {

        if ($lock = self::getByElement($cid, $ctype)) {
            if ((time() - $lock->getDate()) > 3600 || $lock->getSessionId() == session_id()) {
                // lock is out of date unlock it
                self::unlock($cid, $ctype);
                return false;
            }
            return true;
        }

        return false;
    }

    public static function getByElement($cid, $ctype) {

        try {
            $lock = new self();
            $lock->getResource()->getByElement($cid, $ctype);
            return $lock;
        }
        catch (Exception $e) {
            return null;
        }
    }

    public static function lock($cid, $ctype) {

        // try to get user
        if(!$user = Pimcore_Tool_Admin::getCurrentUser()) {
            return false;
        }

        $lock = new self();
        $lock->setCid($cid);
        $lock->setCtype($ctype);
        $lock->setDate(time());
        $lock->setUserId($user->getId());
        $lock->setSessionId(session_id());
        $lock->save();

        return $lock;
    }

    public static function unlock($cid, $ctype) {
        if ($lock = self::getByElement($cid, $ctype)) {
            $lock->delete();
        }
        return true;
    }

    /**
     * @return integer
     */
    public function getCid() {
        return $this->cid;
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @return void
     */
    public function setCid($cid) {
        $this->cid = (int) $cid;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
    }

    /**
     * @param integer $userId
     * @return void
     */
    public function setUserId($userId) {
        if ($userId) {
            if ($user = User::getById($userId)) {
                $this->userId = (int) $userId;
                $this->setUser($user);
            }
        }
    }

    /**
     * @return string
     */
    public function getCtype() {
        return $this->ctype;
    }

    /**
     * @param string $ctype
     * @return void
     */
    public function setCtype($ctype) {
        $this->ctype = (string) $ctype;
    }

    /**
     * @return string
     */
    public function getSessionId() {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     * @return void
     */
    public function setSessionId($sessionId) {
        $this->sessionId = (string) $sessionId;
    }

    /**
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param User $user
     * @return void
     */
    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * @return integer
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * @param integer $date
     * @return void
     */
    public function setDate($date) {
        $this->date = (int) $date;
    }

    /**
     * @param  $cpath
     */
    public function setCpath($cpath)
    {
        $this->cpath = $cpath;
    }

    /**
     * @return
     */
    public function getCpath()
    {
        return $this->cpath;
    }
}
