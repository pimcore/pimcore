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

namespace Pimcore\Tests\Model\Element;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\PermissionCache;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Unit coverage for the request-scoped element permission memoization service, plus an
 * integration assertion that AbstractElement::isAllowed() populates the cache.
 *
 * @group model.element.permission
 */
class PermissionCacheTest extends ModelTestCase
{
    /** @var User[] */
    private array $createdUsers = [];

    /** @var User\Role[] */
    private array $createdRoles = [];

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        foreach ($this->createdUsers as $user) {
            $user->delete();
        }
        foreach ($this->createdRoles as $role) {
            $role->delete();
        }
        $this->createdUsers = [];
        $this->createdRoles = [];
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testReturnsNullWhenNotCached(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);
        $element = $this->inMemoryObject(10);

        $this->assertNull($cache->get($user, $element, 'view'));
    }

    public function testGetReturnsPreviouslySetValue(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);
        $element = $this->inMemoryObject(10);

        $cache->set($user, $element, 'view', true);
        $cache->set($user, $element, 'publish', false);

        $this->assertTrue($cache->get($user, $element, 'view'));
        $this->assertFalse($cache->get($user, $element, 'publish'));
    }

    public function testResetClearsAllEntries(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);
        $element = $this->inMemoryObject(10);

        $cache->set($user, $element, 'view', true);
        $cache->reset();

        $this->assertNull($cache->get($user, $element, 'view'));
    }

    public function testKeyIsolationBetweenUsers(): void
    {
        $cache = new PermissionCache();
        $userA = $this->inMemoryUser(1);
        $userB = $this->inMemoryUser(2);
        $element = $this->inMemoryObject(10);

        $cache->set($userA, $element, 'view', true);

        $this->assertTrue($cache->get($userA, $element, 'view'));
        $this->assertNull($cache->get($userB, $element, 'view'), 'a value cached for one user must not leak to another');
    }

    public function testKeyIsolationBetweenPermissionTypes(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);
        $element = $this->inMemoryObject(10);

        $cache->set($user, $element, 'view', true);

        $this->assertNull($cache->get($user, $element, 'publish'), 'permission types must be cached independently');
    }

    public function testKeyIsolationBetweenElementIds(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);
        $elementA = $this->inMemoryObject(10);
        $elementB = $this->inMemoryObject(11);

        $cache->set($user, $elementA, 'view', true);

        $this->assertNull($cache->get($user, $elementB, 'view'), 'different element ids must be cached independently');
    }

    public function testKeyIsolationBetweenElementTypes(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);
        $object = $this->inMemoryObject(10);
        $asset = new Asset\Folder();
        $asset->setId(10);

        $cache->set($user, $object, 'view', true);

        $this->assertNull($cache->get($user, $asset, 'view'), 'same id on different element types must not collide');
    }

    /**
     * Integration: a permission check on a real element populates the shared cache instance, so a
     * repeated check is served from memory instead of re-querying users_workspaces_*.
     */
    public function testIsAllowedPopulatesCache(): void
    {
        $root = new DataObject\Folder();
        $root->setParentId(1);
        $root->setKey('permission-cache-test-' . uniqid());
        $root->save();

        $user = $this->makeObjectUser($root);

        $cache = \Pimcore::getContainer()->get(PermissionCache::class);
        $cache->reset();

        $this->assertNull($cache->get($user, $root, 'view'), 'precondition: nothing cached yet');

        $first = $root->isAllowed('view', $user);
        $this->assertNotNull($cache->get($user, $root, 'view'), 'isAllowed() must memoize the DAO result');
        $this->assertSame($first, $cache->get($user, $root, 'view'));

        // repeated call returns the same result
        $this->assertSame($first, $root->isAllowed('view', $user));

        $root->delete();
    }

    private function inMemoryUser(int $id): User
    {
        $user = new User();
        $user->setId($id);

        return $user;
    }

    private function inMemoryObject(int $id): DataObject\Folder
    {
        $object = new DataObject\Folder();
        $object->setId($id);

        return $object;
    }

    private function makeObjectUser(DataObject\AbstractObject $target): User
    {
        $workspace = new User\Workspace\DataObject();
        $workspace->setCid($target->getId());
        $workspace->setCpath($target->getRealFullPath());
        $workspace->setList(true);
        $workspace->setView(true);

        $role = new User\Role();
        $role->setParentId(0);
        $role->setName('permission_cache_role_' . uniqid());
        $role->setPermissions(['objects']);
        $role->setWorkspacesObject([$workspace]);
        $role->save();
        $this->createdRoles[] = $role;

        $user = new User();
        $user->setParentId(0);
        $user->setName('permission_cache_user_' . uniqid());
        $user->setActive(true);
        $user->setAdmin(false);
        $user->setPermissions(['objects']);
        $user->setRoles([$role->getId()]);
        $user->save();
        $this->createdUsers[] = $user;

        return $user;
    }
}
