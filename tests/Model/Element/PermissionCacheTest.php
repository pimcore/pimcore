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

use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\PermissionCache;
use Pimcore\Model\Element\PermissionCacheScope;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Unit coverage for the request-scoped element permission memoization service, plus an integration
 * assertion that AbstractElement::isAllowed() is actually served from the cache without re-querying.
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

        $this->assertNull($cache->get($user, $element, 'view', PermissionCacheScope::Single));
    }

    public function testGetReturnsPreviouslySetValue(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);
        $element = $this->inMemoryObject(10);

        $cache->set($user, $element, 'view', PermissionCacheScope::Single, true);
        $cache->set($user, $element, 'publish', PermissionCacheScope::Single, false);

        $this->assertTrue($cache->get($user, $element, 'view', PermissionCacheScope::Single));
        $this->assertFalse($cache->get($user, $element, 'publish', PermissionCacheScope::Single));
    }

    public function testResetClearsAllEntries(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);
        $element = $this->inMemoryObject(10);

        $cache->set($user, $element, 'view', PermissionCacheScope::Single, true);
        $cache->reset();

        $this->assertNull($cache->get($user, $element, 'view', PermissionCacheScope::Single));
    }

    public function testKeyIsolationBetweenUsers(): void
    {
        $cache = new PermissionCache();
        $userA = $this->inMemoryUser(1);
        $userB = $this->inMemoryUser(2);
        $element = $this->inMemoryObject(10);

        $cache->set($userA, $element, 'view', PermissionCacheScope::Single, true);

        $this->assertTrue($cache->get($userA, $element, 'view', PermissionCacheScope::Single));
        $this->assertNull(
            $cache->get($userB, $element, 'view', PermissionCacheScope::Single),
            'a value cached for one user must not leak to another'
        );
    }

    public function testKeyIsolationBetweenPermissionTypes(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);
        $element = $this->inMemoryObject(10);

        $cache->set($user, $element, 'view', PermissionCacheScope::Single, true);

        $this->assertNull(
            $cache->get($user, $element, 'publish', PermissionCacheScope::Single),
            'permission types must be cached independently'
        );
    }

    public function testKeyIsolationBetweenElementIds(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);
        $elementA = $this->inMemoryObject(10);
        $elementB = $this->inMemoryObject(11);

        $cache->set($user, $elementA, 'view', PermissionCacheScope::Single, true);

        $this->assertNull(
            $cache->get($user, $elementB, 'view', PermissionCacheScope::Single),
            'different element ids must be cached independently'
        );
    }

    public function testKeyIsolationBetweenElementTypes(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);
        $object = $this->inMemoryObject(10);
        $asset = new Asset\Folder();
        $asset->setId(10);

        $cache->set($user, $object, 'view', PermissionCacheScope::Single, true);

        $this->assertNull(
            $cache->get($user, $asset, 'view', PermissionCacheScope::Single),
            'same id on different element types must not collide'
        );
    }

    public function testKeyIsolationBetweenScopes(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);
        $element = $this->inMemoryObject(10);

        // the single- and batch-permission DAO paths compute "list" differently, so a value cached
        // for one scope must never be served for the other
        $cache->set($user, $element, 'list', PermissionCacheScope::Single, true);

        $this->assertTrue($cache->get($user, $element, 'list', PermissionCacheScope::Single));
        $this->assertNull(
            $cache->get($user, $element, 'list', PermissionCacheScope::Batch),
            'single and batch permission results must not share a cache entry'
        );
    }

    public function testKeyIsolationBetweenRoleSets(): void
    {
        $cache = new PermissionCache();
        $element = $this->inMemoryObject(10);

        // same user id, different role set (e.g. changed in-memory before being persisted)
        $userWithRoleA = $this->inMemoryUser(1);
        $userWithRoleA->setRoles([5]);
        $userWithRoleB = $this->inMemoryUser(1);
        $userWithRoleB->setRoles([6]);

        $cache->set($userWithRoleA, $element, 'view', PermissionCacheScope::Single, true);

        $this->assertNull(
            $cache->get($userWithRoleB, $element, 'view', PermissionCacheScope::Single),
            'a different role set must not reuse a result cached for another role set'
        );
    }

    public function testUnsavedElementsAreNotCached(): void
    {
        $cache = new PermissionCache();
        $user = $this->inMemoryUser(1);

        // an element without an id has no stable identity; caching it would collapse every unsaved
        // element of the same type onto one key
        $unsaved = new DataObject\Folder();

        $cache->set($user, $unsaved, 'view', PermissionCacheScope::Single, true);

        $this->assertNull(
            $cache->get($user, $unsaved, 'view', PermissionCacheScope::Single),
            'elements without an id must not be cached'
        );
    }

    /**
     * Integration: a permission check on a real element is served from the shared cache on the
     * second call, so it must not re-query users_workspaces_*. Proven by mutating the workspace row
     * directly (which fires no Pimcore event and therefore does not invalidate the cache): the
     * cached call keeps returning the pre-mutation result, and only a reset() picks up the change.
     */
    public function testIsAllowedIsServedFromCacheWithoutRequerying(): void
    {
        $root = new DataObject\Folder();
        $root->setParentId(1);
        $root->setKey('permission-cache-test-' . uniqid());
        $root->save();

        $user = $this->makeObjectUser($root);

        $cache = \Pimcore::getContainer()->get(PermissionCache::class);
        $cache->reset();

        $this->assertNull(
            $cache->get($user, $root, 'view', PermissionCacheScope::Single),
            'precondition: nothing cached yet'
        );

        // first call queries the DAO and memoizes the granted permission
        $this->assertTrue($root->isAllowed('view', $user), 'workspace grants view on the first lookup');
        $this->assertTrue(
            $cache->get($user, $root, 'view', PermissionCacheScope::Single),
            'isAllowed() must memoize the DAO result'
        );

        // remove the grant behind the cache's back (raw delete fires no event -> no invalidation)
        Db::get()->delete('users_workspaces_object', ['cid' => $root->getId()]);

        // if isAllowed() re-queried the DAO here it would now return false; the cache must shield it
        $this->assertTrue(
            $root->isAllowed('view', $user),
            'the second call must be served from the cache without re-querying the workspace table'
        );

        // only after an explicit reset does the DAO run again and observe the removed grant
        $cache->reset();
        $this->assertFalse(
            $root->isAllowed('view', $user),
            'after reset the DAO is queried again and sees the removed workspace grant'
        );

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
