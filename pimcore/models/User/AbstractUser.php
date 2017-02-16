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
 * @package    User
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\User\AbstractUser\Dao getDao()
 */
class AbstractUser extends Model\AbstractModel
{

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
     * @return AbstractUser
     */
    public static function getById($id)
    {
        $cacheKey = "user_" . $id;
        try {
            if (\Zend_Registry::isRegistered($cacheKey)) {
                $user =  \Zend_Registry::get($cacheKey);
            } else {
                $user = new static();
                $user->getDao()->getById($id);

                if (get_class($user) == "Pimcore\\Model\\User\\AbstractUser") {
                    $className = Service::getClassNameForType($user->getType());
                    $user = $className::getById($user->getId());
                }

                \Zend_Registry::set($cacheKey, $user);
            }

            return $user;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param array $values
     * @return self
     */
    public static function create($values = [])
    {
        $user = new static();
        $user->setValues($values);
        $user->save();

        return $user;
    }

    /**
     * @param string $name
     * @return self
     */
    public static function getByName($name)
    {
        try {
            $user = new static();
            $user->getDao()->getByName($name);

            return $user;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param integer $parentId
     * @return $this
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * @return $this
     * @throws \Exception
     */
    public function save()
    {
        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("user.preUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("user.preAdd", $this);
        }

        $this->beginTransaction();
        try {
            if (!$this->getId()) {
                $this->getDao()->create();
            }

            $this->update();

            $this->commit();
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }

        if ($isUpdate) {
            \Pimcore::getEventManager()->trigger("user.postUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("user.postAdd", $this);
        }

        return $this;
    }

    /**
     *
     */
    public function delete()
    {
        \Pimcore::getEventManager()->trigger("user.preDelete", $this);

        // delete all childs
        $list = new Listing();
        $list->setCondition("parentId = ?", $this->getId());
        $list->load();

        if (is_array($list->getUsers())) {
            foreach ($list->getUsers() as $user) {
                $user->delete();
            }
        }

        // now delete the current user
        $this->getDao()->delete();
        \Pimcore\Cache::clearAll();

        \Pimcore::getEventManager()->trigger("user.postDelete", $this);
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
