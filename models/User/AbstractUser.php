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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User;

use Pimcore\Event\Model\UserRoleEvent;
use Pimcore\Event\UserRoleEvents;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\User\AbstractUser\Dao getDao()
 */
class AbstractUser extends Model\AbstractModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
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
     * @param int $id
     *
     * @return static|null
     */
    public static function getById($id)
    {
        $cacheKey = 'user_' . $id;
        try {
            if (\Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
                $user = \Pimcore\Cache\Runtime::get($cacheKey);
            } else {
                $user = new static();
                $user->getDao()->getById($id);

                if (get_class($user) == 'Pimcore\\Model\\User\\AbstractUser') {
                    $className = Service::getClassNameForType($user->getType());
                    $user = $className::getById($user->getId());
                }

                \Pimcore\Cache\Runtime::set($cacheKey, $user);
            }

            return $user;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param array $values
     *
     * @return static
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
     *
     * @return static|null
     */
    public static function getByName($name)
    {
        try {
            $user = new static();
            $user->getDao()->getByName($name);

            return $user;
        } catch (\Exception $e) {
            return null;
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
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     *
     * @return $this
     */
    public function setParentId($parentId)
    {
        $this->parentId = (int)$parentId;

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
     *
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
     *
     * @throws \Exception
     */
    public function save()
    {
        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventDispatcher()->dispatch(UserRoleEvents::PRE_UPDATE, new UserRoleEvent($this));
        } else {
            \Pimcore::getEventDispatcher()->dispatch(UserRoleEvents::PRE_ADD, new UserRoleEvent($this));
        }

        if (!preg_match('/^[a-zA-Z0-9\-\.~_@]+$/', $this->getName())) {
            throw new \Exception('Invalid name for user/role `' . $this->getName() . '` (allowed characters: a-z A-Z 0-9 -.~_@)');
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
            \Pimcore::getEventDispatcher()->dispatch(UserRoleEvents::POST_UPDATE, new UserRoleEvent($this));
        } else {
            \Pimcore::getEventDispatcher()->dispatch(UserRoleEvents::POST_ADD, new UserRoleEvent($this));
        }

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        if ($this->getId() < 1) {
            throw new \Exception('Deleting the system user is not allowed!');
        }

        \Pimcore::getEventDispatcher()->dispatch(UserRoleEvents::PRE_DELETE, new UserRoleEvent($this));

        $type = $this->getType();

        // delete all children
        $list = ($type === 'role' || $type === 'rolefolder') ? new Model\User\Role\Listing() : new Listing();
        $list->setCondition('parentId = ?', $this->getId());
        foreach ($list as $user) {
            $user->delete();
        }

        // remove user-role relations
        if ($type === 'role') {
            $this->cleanupUserRoleRelations();
        }

        // now delete the current user
        $this->getDao()->delete();
        \Pimcore\Cache::clearAll();

        \Pimcore::getEventDispatcher()->dispatch(UserRoleEvents::POST_DELETE, new UserRoleEvent($this));
    }

    /**
     * https://github.com/pimcore/pimcore/issues/7085
     *
     * @throws \Exception
     */
    private function cleanupUserRoleRelations()
    {
        $userRoleListing = new Listing();
        $userRoleListing->setCondition('FIND_IN_SET(' . $this->getId() . ',roles)');
        $userRoleListing = $userRoleListing->load();
        if (count($userRoleListing)) {
            /** @var Model\User $relatedUser */
            foreach ($userRoleListing as $relatedUser) {
                $userRoles = $relatedUser->getRoles();
                if (is_array($userRoles)) {
                    $key = array_search($this->getId(), $userRoles);
                    if (false !== $key) {
                        unset($userRoles[$key]);
                        $relatedUser->setRoles($userRoles);
                        $relatedUser->save();
                    }
                }
            }
        }
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

    public function update()
    {
        $this->getDao()->update();
    }
}
