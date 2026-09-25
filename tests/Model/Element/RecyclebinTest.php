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

use Exception;
use Pimcore;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\Recyclebin\Item;
use Pimcore\Model\User;
use Pimcore\Model\User\Workspace\DataObject as DataObjectWorkspace;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;
use ReflectionProperty;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class RecyclebinTest
 *
 * @package Pimcore\Tests\Model\Element
 *
 * @group model.element.recyclebin
 */
class RecyclebinTest extends ModelTestCase
{
    protected User $user;

    private ?TokenInterface $originalToken = null;

    private ?User $restrictedUser = null;

    private ?User\Role $restrictedRole = null;

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->createDummyUser();

        $this->originalToken = $this->tokenStorage()->getToken();
    }

    public function tearDown(): void
    {
        $this->tokenStorage()->setToken($this->originalToken);

        $this->restrictedUser?->delete();
        $this->restrictedRole?->delete();
        $this->restrictedUser = null;
        $this->restrictedRole = null;

        parent::tearDown();
    }

    protected function createDummyUser(): void
    {
        if (!$user = User::getByName('test-user')) {
            $user = new User();
            $user->setAdmin(true);
            $user
                ->setName('test-user')
                ->save();
        }

        $this->user = $user;
    }

    /**
     * Regression test for GHSA-mwmm-cqj2-55wg: restoring a DataObject from the recycle bin must not
     * persist the intermediate stub object before the "publish" permission on the target parent has
     * been checked. A user without publish rights on the restore target must not be able to leave a
     * stub object behind in that subtree.
     */
    public function testRestoreDeniesUserWithoutPublishPermissionAndLeavesNoStub(): void
    {
        $folder = TestHelper::createObjectFolder('restricted-');
        $object = TestHelper::createEmptyObject('recyclebin-', false);
        $object->setParentId($folder->getId());
        $object->save();
        $objectId = $object->getId();
        $objectPath = $object->getFullPath();

        Item::create($object, $this->user);
        $object->delete();

        $this->loginAs($this->createUserWithFolderWorkspace($folder, publish: false));

        $recycledItems = new Item\Listing();
        $recycledItems->setCondition('`path` = ?', $objectPath);
        $recycledItem = $recycledItems->current();

        try {
            $recycledItem->restore();
            $this->fail('Expected an exception because the user lacks the "publish" permission on the restore target.');
        } catch (Exception $e) {
            $this->assertSame('Not sufficient permissions', $e->getMessage());
        }

        $this->assertNull(
            DataObject::getById($objectId, ['force' => true]),
            'no stub object may be persisted when the restore is denied for insufficient permissions'
        );
    }

    /**
     * Control for testRestoreDeniesUserWithoutPublishPermissionAndLeavesNoStub: a user who does have
     * the "publish" permission on the restore target must still be able to restore normally.
     */
    public function testRestoreSucceedsForUserWithPublishPermission(): void
    {
        $folder = TestHelper::createObjectFolder('allowed-');
        $object = TestHelper::createEmptyObject('recyclebin-', false);
        $object->setParentId($folder->getId());
        $object->save();
        $objectId = $object->getId();
        $objectPath = $object->getFullPath();

        Item::create($object, $this->user);
        $object->delete();

        $this->loginAs($this->createUserWithFolderWorkspace($folder, publish: true));

        $recycledItems = new Item\Listing();
        $recycledItems->setCondition('`path` = ?', $objectPath);
        $recycledItems->current()->restore();

        $restoredObject = DataObject::getById($objectId, ['force' => true]);
        $this->assertIsObject($restoredObject, 'Restored object with sufficient permissions');
    }

    private function createUserWithFolderWorkspace(DataObject\Folder $folder, bool $publish): User
    {
        $workspace = new DataObjectWorkspace();
        $workspace->setCid($folder->getId());
        $workspace->setCpath($folder->getRealFullPath());
        $workspace->setList(true);
        $workspace->setView(true);
        $workspace->setPublish($publish);

        $role = new User\Role();
        $role->setParentId(0);
        $role->setName('recyclebin_role_' . uniqid());
        $role->setPermissions(['objects', 'recyclebin']);
        $role->setWorkspacesObject([$workspace]);
        $role->save();
        $this->restrictedRole = $role;

        $user = new User();
        $user->setParentId(0);
        $user->setName('recyclebin_user_' . uniqid());
        $user->setActive(true);
        $user->setAdmin(false);
        $user->setPermissions(['objects', 'recyclebin']);
        $user->setRoles([$role->getId()]);
        $user->save();
        $this->restrictedUser = $user;

        return $user;
    }

    private function loginAs(User $user): void
    {
        $this->tokenStorage()->setToken(new UsernamePasswordToken(new SecurityUser($user), 'pimcore_admin'));
    }

    private function tokenStorage(): TokenStorageInterface
    {
        // security.token_storage is inlined out of the compiled container and cannot be fetched by
        // id. Item::restore() resolves the current user via Admin::getCurrentUser(), which reads the
        // token storage held by the public TokenStorageUserResolver service, so we reach the same
        // shared instance through that resolver rather than replacing the service.
        $resolver = Pimcore::getContainer()->get(TokenStorageUserResolver::class);

        return (new ReflectionProperty(TokenStorageUserResolver::class, 'tokenStorage'))->getValue($resolver);
    }

    /**
     * Verifies that an object can be moved to recycle bin and restored
     *
     */
    public function testSimpleObjectRecycleAndRestore(): void
    {
        $object = TestHelper::createEmptyObject();
        $objectId = $object->getId();

        //add to recyclebin
        Item::create($object, $this->user);

        $object->delete();

        $storage = Storage::get('recycle_bin');

        //recycle asserts
        $recycledItems = new Item\Listing();
        $this->assertTrue($storage->fileExists($recycledItems->current()->getStorageFile()));

        $recycledStorage = unserialize($storage->read($recycledItems->current()->getStorageFile()));
        $this->assertEquals($objectId, $recycledStorage->getId(), 'Recycled Object not found.');

        $this->assertEquals($recycledItems->current()->getStorageFile(), $recycledItems->current()->getStoreageFile());    // deprecated method name

        //restore asserts
        $recycledItems->current()->restore();

        $restoredObject = DataObject::getById($objectId);
        $this->assertIsObject($restoredObject, 'Restored simple object');
    }

    /**
     * Verifies that object with children can be moved to recyclebin and restored
     *
     */
    public function testRecursiveObjectRecycleAndRestore(): void
    {
        // create parent object
        $parent = TestHelper::createEmptyObject();
        $parentId = $parent->getId();
        $parentPath = $parent->getFullPath();

        // create child object
        $child = TestHelper::createEmptyObject();
        $child->setParentId($parentId);
        $child->save();
        $childId = $child->getId();

        //add to recyclebin
        Item::create($parent, $this->user);

        $parent->delete();

        $recycledItems = new Item\Listing();
        $recycledItems->setCondition('`path` = ?', $parentPath);

        $this->assertEquals(2, $recycledItems->current()->getAmount(), 'Expected 2 recycled item');

        $storage = Storage::get('recycle_bin');
        //recycle bin item storage file
        $recycledContent = unserialize($storage->read($recycledItems->current()->getStorageFile()));

        $this->assertEquals($parentId, $recycledContent->getId(), 'Expected recycled parent object ID');
        $this->assertCount(1, $recycledContent->getChildren(DataObject::$types, true)->getData(), 'Expected recycled child object');

        //restore deleted items (parent + child)
        $recycledItems->current()->restore();

        $restoredParent = DataObject::getById($parentId);
        $restoredChild = DataObject::getById($childId);

        $this->assertIsObject($restoredParent, 'Expected restored parent object');
        $this->assertIsObject($restoredChild, 'Expected restored child object');
    }

    /**
     * Verifies that an object data is restored properly
     *
     */
    public function testObjectDataRecycleAndRestore(): void
    {
        // create target object
        $inputText = TestHelper::generateRandomString();

        //create relation object
        $relationObject = TestHelper::createEmptyObject();

        // create source object
        $sourceObject = TestHelper::createEmptyObject();
        $sourceObject->setInput($inputText);
        $sourceObject->setObjects([$relationObject]); //set relation
        $sourceObject->setLobjects([$relationObject]); //set localized relation
        $sourceObject->save();

        $sourceObjectPath = $sourceObject->getFullPath();
        $sourceObjectId = $sourceObject->getId();

        //add to recyclebin
        Item::create($sourceObject, $this->user);
        $sourceObject->delete();

        //restore deleted items (parent + child)
        $recycledItems = new Item\Listing();
        $recycledItems->setCondition('`path` = ?', $sourceObjectPath);
        $recycledItems->current()->restore();

        //load relation and check if relation loads correctly
        $restoredSourceObject = DataObject::getById($sourceObjectId);
        $restoredRelation = $restoredSourceObject->getLobjects();
        $restoredLocalizedRelation = $restoredSourceObject->getLobjects();

        $this->assertEquals($inputText, $restoredSourceObject->getInput(), 'Input data not restored properly');
        $this->assertEquals($relationObject->getId(), $restoredRelation[0]->getId(), 'Simple object relation not restored properly');
        $this->assertEquals($relationObject->getId(), $restoredLocalizedRelation[0]->getId(), 'Localized object relation not restored properly');
    }
}
