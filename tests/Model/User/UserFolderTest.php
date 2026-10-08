<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Model\User;

use Exception;
use Pimcore\Model\User;
use Pimcore\Model\User\AbstractUser;
use Pimcore\Model\User\Folder;
use Pimcore\Model\User\Role;
use Pimcore\Tests\Support\Test\ModelTestCase;

class UserFolderTest extends ModelTestCase
{
    private const NAME_PREFIX = 'pees1660_';

    /**
     * @var AbstractUser[]
     */
    private array $createdEntities = [];

    protected function tearDown(): void
    {
        // delete in reverse creation order so children are removed before their parents
        foreach (array_reverse($this->createdEntities) as $entity) {
            if ($entity->getId() && $entity::getById($entity->getId())) {
                $entity->delete();
            }
        }
        $this->createdEntities = [];

        parent::tearDown();
    }

    public function testSameUserFolderNameIsAllowedUnderDifferentParents(): void
    {
        $parentA = $this->createEntity(Folder::class, self::NAME_PREFIX . 'parent_a');
        $parentB = $this->createEntity(Folder::class, self::NAME_PREFIX . 'parent_b');

        $childA = $this->createEntity(Folder::class, self::NAME_PREFIX . 'child', $parentA->getId());
        $childB = $this->createEntity(Folder::class, self::NAME_PREFIX . 'child', $parentB->getId());

        $this->assertNotNull($childA->getId());
        $this->assertNotNull($childB->getId());
        $this->assertNotSame($childA->getId(), $childB->getId());
        $this->assertSame($childA->getName(), $childB->getName());
    }

    public function testSameUserFolderNameIsRejectedUnderSameParent(): void
    {
        $parent = $this->createEntity(Folder::class, self::NAME_PREFIX . 'parent');
        $this->createEntity(Folder::class, self::NAME_PREFIX . 'child', $parent->getId());

        $this->expectException(Exception::class);
        $this->createEntity(Folder::class, self::NAME_PREFIX . 'child', $parent->getId());
    }

    public function testSameUserFolderNameIsRejectedOnRootLevel(): void
    {
        $this->createEntity(Folder::class, self::NAME_PREFIX . 'root_folder');

        $this->expectException(Exception::class);
        $this->createEntity(Folder::class, self::NAME_PREFIX . 'root_folder');
    }

    public function testSameRoleFolderNameIsAllowedUnderDifferentParents(): void
    {
        $parentA = $this->createEntity(Role\Folder::class, self::NAME_PREFIX . 'role_parent_a');
        $parentB = $this->createEntity(Role\Folder::class, self::NAME_PREFIX . 'role_parent_b');

        $childA = $this->createEntity(Role\Folder::class, self::NAME_PREFIX . 'role_child', $parentA->getId());
        $childB = $this->createEntity(Role\Folder::class, self::NAME_PREFIX . 'role_child', $parentB->getId());

        $this->assertNotNull($childA->getId());
        $this->assertNotNull($childB->getId());
        $this->assertNotSame($childA->getId(), $childB->getId());
    }

    public function testSameRoleFolderNameIsRejectedUnderSameParent(): void
    {
        $parent = $this->createEntity(Role\Folder::class, self::NAME_PREFIX . 'role_parent');
        $this->createEntity(Role\Folder::class, self::NAME_PREFIX . 'role_child', $parent->getId());

        $this->expectException(Exception::class);
        $this->createEntity(Role\Folder::class, self::NAME_PREFIX . 'role_child', $parent->getId());
    }

    public function testUserNamesStayUniqueAcrossDifferentFolders(): void
    {
        $parentA = $this->createEntity(Folder::class, self::NAME_PREFIX . 'users_a');
        $parentB = $this->createEntity(Folder::class, self::NAME_PREFIX . 'users_b');

        $this->createEntity(User::class, self::NAME_PREFIX . 'someuser', $parentA->getId());

        $this->expectException(Exception::class);
        $this->createEntity(User::class, self::NAME_PREFIX . 'someuser', $parentB->getId());
    }

    public function testRoleNamesStayUniqueAcrossDifferentFolders(): void
    {
        $parentA = $this->createEntity(Role\Folder::class, self::NAME_PREFIX . 'roles_a');
        $parentB = $this->createEntity(Role\Folder::class, self::NAME_PREFIX . 'roles_b');

        $this->createEntity(Role::class, self::NAME_PREFIX . 'somerole', $parentA->getId());

        $this->expectException(Exception::class);
        $this->createEntity(Role::class, self::NAME_PREFIX . 'somerole', $parentB->getId());
    }

    public function testUserAndFolderMayShareTheSameName(): void
    {
        $folder = $this->createEntity(Folder::class, self::NAME_PREFIX . 'shared_name');
        $user = $this->createEntity(User::class, self::NAME_PREFIX . 'shared_name');

        $this->assertNotNull($folder->getId());
        $this->assertNotNull($user->getId());
    }

    public function testFolderCanBeMovedToAnotherParent(): void
    {
        $parentA = $this->createEntity(Folder::class, self::NAME_PREFIX . 'move_a');
        $parentB = $this->createEntity(Folder::class, self::NAME_PREFIX . 'move_b');
        $child = $this->createEntity(Folder::class, self::NAME_PREFIX . 'move_child', $parentA->getId());

        $child->setParentId($parentB->getId());
        $child->save();

        $reloaded = Folder::getById($child->getId());
        $this->assertSame($parentB->getId(), $reloaded->getParentId());
    }

    public function testMovingFolderToParentWithSameNamedFolderIsRejected(): void
    {
        $parentA = $this->createEntity(Folder::class, self::NAME_PREFIX . 'mv_a');
        $parentB = $this->createEntity(Folder::class, self::NAME_PREFIX . 'mv_b');
        $child = $this->createEntity(Folder::class, self::NAME_PREFIX . 'mv_child', $parentA->getId());
        $this->createEntity(Folder::class, self::NAME_PREFIX . 'mv_child', $parentB->getId());

        $this->expectException(Exception::class);
        $child->setParentId($parentB->getId());
        $child->save();
    }

    /**
     * @template T of AbstractUser
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function createEntity(string $class, string $name, int $parentId = 0): AbstractUser
    {
        /** @var AbstractUser $entity */
        $entity = $class::create([
            'parentId' => $parentId,
            'name' => $name,
        ]);

        $this->createdEntities[] = $entity;

        return $entity;
    }
}
