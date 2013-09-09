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
 * @package    User
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class User_Abstract extends Pimcore_Model_Abstract {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $parentId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * @param integer $id
     * @return User
     */
    public static function getById($id) {

        $cacheKey = "user_" . $id;
        try {
            if(Zend_Registry::isRegistered($cacheKey)) {
                $user =  Zend_Registry::get($cacheKey);
            } else {
                $user = new static();
                $user->getResource()->getById($id);

                if(get_class($user) == "User_Abstract") {
                    $className = User_Service::getClassNameForType($user->getType());
                    $user = $className::getById($user->getId());
                }

                Zend_Registry::set($cacheKey, $user);
            }

            return $user;
        }
        catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param array $values
     * @return User
     */
    public static function create($values = array()) {
        $user = new static();
        $user->setValues($values);
        $user->save();
        return $user;
    }

    /**
     * @param string $name
     * @return User
     */
    public static function getByName($name) {

        try {
            $user = new static();
            $user->getResource()->getByName($name);
            return $user;
        }
        catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * @return integer
     */
    public function getParentId() {
        return $this->parentId;
    }

    /**
     * @param integer $parentId
     * @return void
     */
    public function setParentId($parentId) {
        $this->parentId = $parentId;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     *
     */
    public function delete() {

        // delete all childs
        $list = new User_List();
        $list->setCondition("parentId = ?", $this->getId());
        $list->load();

        if(is_array($list->getUsers())){
            foreach ($list->getUsers() as $user) {
                $user->delete();
            }
        }

        // now delete the current user
        $this->getResource()->delete();
        Pimcore_Model_Cache::clearAll();
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}
