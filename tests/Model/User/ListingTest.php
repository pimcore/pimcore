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

use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool\Authentication;

/**
 * Verifies that the user/role listings only return their declared element
 * type and that the dedicated folder listings only return folders.
 */
class ListingTest extends ModelTestCase
{
    private const USERNAME_PREFIX = 'listingtest_user_';

    private const ROLENAME_PREFIX = 'listingtest_role_';

    private const FOLDERNAME_PREFIX = 'listingtest_folder_';

    private User\Folder $userFolder;

    private User $user;

    private User\Role\Folder $roleFolder;

    private User\Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFolder = $this->createUserFolder(self::FOLDERNAME_PREFIX . 'u');
        $this->user = $this->createUser(self::USERNAME_PREFIX . '1', $this->userFolder->getId());
        $this->roleFolder = $this->createRoleFolder(self::FOLDERNAME_PREFIX . 'r');
        $this->role = $this->createRole(self::ROLENAME_PREFIX . '1', $this->roleFolder->getId());
    }

    protected function tearDown(): void
    {
        $this->user->delete();
        $this->userFolder->delete();
        $this->role->delete();
        $this->roleFolder->delete();

        parent::tearDown();
    }

    public function testUserListingExcludesFolders(): void
    {
        $listing = new User\Listing();
        $listing->setCondition(
            'name LIKE ? OR name LIKE ?',
            [self::USERNAME_PREFIX . '%', self::FOLDERNAME_PREFIX . '%'],
        );

        $items = $listing->load();

        $this->assertNotEmpty($items, 'Expected the listing to find the test user.');
        foreach ($items as $item) {
            $this->assertInstanceOf(User::class, $item);
            $this->assertNotInstanceOf(User\Folder::class, $item);
            $this->assertSame('user', $item->getType());
        }
    }

    public function testUserFolderListingExcludesUsers(): void
    {
        $listing = new User\Folder\Listing();
        $listing->setCondition(
            'name LIKE ? OR name LIKE ?',
            [self::FOLDERNAME_PREFIX . '%', self::USERNAME_PREFIX . '%'],
        );

        $folders = $listing->getFolders();

        $this->assertNotEmpty($folders, 'Expected the folder listing to find the test folder.');
        foreach ($folders as $folder) {
            $this->assertInstanceOf(User\Folder::class, $folder);
            $this->assertSame('userfolder', $folder->getType());
        }
    }

    public function testRoleListingExcludesFolders(): void
    {
        $listing = new User\Role\Listing();
        $listing->setCondition(
            'name LIKE ? OR name LIKE ?',
            [self::ROLENAME_PREFIX . '%', self::FOLDERNAME_PREFIX . '%'],
        );

        $roles = $listing->getRoles();

        $this->assertNotEmpty($roles, 'Expected the listing to find the test role.');
        foreach ($roles as $role) {
            $this->assertInstanceOf(User\Role::class, $role);
            $this->assertNotInstanceOf(User\Role\Folder::class, $role);
            $this->assertSame('role', $role->getType());
        }
    }

    public function testRoleFolderListingExcludesRoles(): void
    {
        $listing = new User\Role\Folder\Listing();
        $listing->setCondition(
            'name LIKE ? OR name LIKE ?',
            [self::FOLDERNAME_PREFIX . '%', self::ROLENAME_PREFIX . '%'],
        );

        $folders = $listing->getFolders();

        $this->assertNotEmpty($folders, 'Expected the folder listing to find the test role folder.');
        foreach ($folders as $folder) {
            $this->assertInstanceOf(User\Role\Folder::class, $folder);
            $this->assertSame('rolefolder', $folder->getType());
        }
    }

    public function testUserFolderGetChildrenReturnsBothTypes(): void
    {
        $children = $this->userFolder->getChildren();

        $this->assertCount(1, $children);
        $this->assertContainsOnlyInstancesOf(User::class, $children);
        $this->assertSame($this->user->getId(), $children[0]->getId());
    }

    public function testRoleFolderGetChildrenReturnsBothTypes(): void
    {
        $children = $this->roleFolder->getChildren();

        $this->assertCount(1, $children);
        $this->assertContainsOnlyInstancesOf(User\Role::class, $children);
        $this->assertSame($this->role->getId(), $children[0]->getId());
    }

    private function createUserFolder(string $name): User\Folder
    {
        $folder = new User\Folder();
        $folder->setParentId(0);
        $folder->setName($name);
        $folder->save();

        return $folder;
    }

    private function createUser(string $username, int $parentId): User
    {
        return User::create([
            'parentId' => $parentId,
            'username' => $username,
            'password' => Authentication::getPasswordHash($username, $username),
            'active' => true,
        ]);
    }

    private function createRoleFolder(string $name): User\Role\Folder
    {
        $folder = new User\Role\Folder();
        $folder->setParentId(0);
        $folder->setName($name);
        $folder->save();

        return $folder;
    }

    private function createRole(string $name, int $parentId): User\Role
    {
        $role = new User\Role();
        $role->setParentId($parentId);
        $role->setName($name);
        $role->save();

        return $role;
    }
}
