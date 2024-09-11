<?php
declare(strict_types=1);

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

namespace Pimcore\Model\User;

use Exception;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Event\Model\UserRoleEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Event\UserRoleEvents;
use Pimcore\Model;
use ReflectionClass;

/**
 * @method \Pimcore\Model\User\AbstractUser\Dao getDao()
 * @method void setLastLoginDate()
 */
abstract class AbstractUser extends Model\AbstractModel implements AbstractUserInterface
{
    use RecursionBlockingEventDispatchHelperTrait;

    protected ?int $id = null;

    protected ?int $parentId = null;

    protected ?string $name = null;

    protected string $type = '';

    public static function getById(int $id): static|null
    {
        if ($id < 0) {
            return null;
        }

        $cacheKey = 'user_' . $id;

        try {
            if (RuntimeCache::isRegistered($cacheKey)) {
                $user = RuntimeCache::get($cacheKey);
            } else {
                $reflectionClass = new ReflectionClass(static::class);
                if ($reflectionClass->isAbstract()) {
                    $user = new Model\User();
                    $user->setType('');
                } else {
                    $user = new static();
                }
                $user->getDao()->getById($id);
                $className = Service::getClassNameForType($user->getType());

                if (get_class($user) !== $className) {
                    /** @var AbstractUser $user */
                    $user = $className::getById($user->getId());
                }

                RuntimeCache::set($cacheKey, $user);
            }
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }

        if (!$user || !static::typeMatch($user)) {
            return null;
        }

        return $user;
    }

    public static function create(array $values = []): static
    {
        $user = new static();
        self::checkCreateData($values);
        $user->setValues($values);
        $user->save();

        return $user;
    }

    public static function getByName(string $name): ?static
    {
        try {
            $user = new static();
            $user->getDao()->getByName($name);

            return $user;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    /**
     * @return $this
     */
    public function setParentId(int $parentId): static
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return $this
     *
     * @throws Exception
     */
    public function save(): static
    {
        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
            $this->dispatchEvent(new UserRoleEvent($this), UserRoleEvents::PRE_UPDATE);
        } else {
            $this->dispatchEvent(new UserRoleEvent($this), UserRoleEvents::PRE_ADD);
        }

        if (!preg_match('/^[a-zA-Z0-9\-\.~_@]+$/', $this->getName())) {
            throw new Exception('Invalid name for user/role `' . $this->getName() . '` (allowed characters: a-z A-Z 0-9 -.~_@)');
        }

        $this->beginTransaction();

        try {
            if (!$this->getId()) {
                $this->getDao()->create();
            }

            $this->update();

            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();

            throw $e;
        }

        if ($isUpdate) {
            $this->dispatchEvent(new UserRoleEvent($this), UserRoleEvents::POST_UPDATE);
        } else {
            $this->dispatchEvent(new UserRoleEvent($this), UserRoleEvents::POST_ADD);
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        if ($this->getId() < 1) {
            throw new Exception('Deleting the system user is not allowed!');
        }
        $parentUserId = $this->getParentId();

        $this->dispatchEvent(new UserRoleEvent($this), UserRoleEvents::PRE_DELETE);

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

        $cacheKey = 'user_' . $this->getId();
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::set($cacheKey, null);
        }

        if ($parentUserId && $parentUserId > 1) {
            $parentCacheKey = 'user_' . $parentUserId;
            if (RuntimeCache::isRegistered($parentCacheKey)) {
                RuntimeCache::set($parentCacheKey, null);
            }
        }

        $this->dispatchEvent(new UserRoleEvent($this), UserRoleEvents::POST_DELETE);
    }

    /**
     * https://github.com/pimcore/pimcore/issues/7085
     *
     * @throws Exception
     */
    private function cleanupUserRoleRelations(): void
    {
        $userRoleListing = new Listing();
        $userRoleListing->setCondition('FIND_IN_SET(' . $this->getId() . ',roles)');
        $userRoleListing = $userRoleListing->load();
        if (count($userRoleListing)) {
            foreach ($userRoleListing as $relatedUser) {
                $userRoles = $relatedUser->getRoles();
                $key = array_search($this->getId(), $userRoles);
                if (false !== $key) {
                    unset($userRoles[$key]);
                    $relatedUser->setRoles($userRoles);
                    $relatedUser->save();
                }
            }
        }
    }

    /**
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @throws Exception
     */
    protected function update(): void
    {
        $this->getDao()->update();
    }

    /**
     * @internal
     */
    protected static function typeMatch(AbstractUser $user): bool
    {
        $staticType = static::class;
        if ($staticType !== AbstractUser::class && !$user instanceof $staticType) {
            return false;
        }

        return true;
    }
}
