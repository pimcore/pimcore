<?php

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

namespace Pimcore\Tests\Model\Element;

use Codeception\Util\Stub;
use Pimcore\Bundle\AdminBundle\Controller\Searchadmin\SearchController;
use Pimcore\Bundle\AdminBundle\Helper\GridHelperService;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Search;
use Pimcore\Model\User;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ModelDataObjectPermissionsTest extends ModelTestCase
{
    /**
     *  created object tree
     *
     * /permissionfoo --> allowed
     * /permissionfoo/bars --> not allowed
     * /permissionfoo/bars/hugo --> ?? --> should not be found
     * /permissionfoo/bars/userfolder --> allowed
     * /permissionfoo/bars/userfolder/usertestobject --> ??   --> should be found
     * /permissionfoo/bars/groupfolder --> allowed role
     * /permissionfoo/bars/groupfolder --> not allowed user
     * /permissionfoo/bars/groupfolder/grouptestobject --> ??   --> should NOT be found
     *
     * /permissionbar --> allowed
     * /permissionbar/foo --> not allowed
     * /permissionbar/foo/hiddenobject --> ??       --> should not be found
     *
     * /permission'"cpath --> not specified
     * /permission'"cpath/a --> not specified
     * /permission'"cpath/a/b --> not specified
     * /permission'"cpath/a/b/c --> allowed
     * /permission'"cpath/abcdefghjkl --> allowed
     *
     * -- only for many elements search test
     * /manyElemnents --> not allowed
     * /manyElements/manyelement 1
     * ...
     * /manyElements/manyelement 100
     * /manyElements/manyelement X --> allowed
     *
     */

    /**
     * @var DataObject\Folder
     */
    protected $permissionfoo;

    /**
     * @var DataObject\Folder
     */
    protected $permissionbar;

    /**
     * @var DataObject\Folder
     */
    protected $permissioncpath;

    /**
     * @var DataObject\Folder
     */
    protected $foo;

    /**
     * @var DataObject\Folder
     */
    protected $bar;

    /**
     * @var DataObject\Folder
     */
    protected $bars;

    /**
     * @var DataObject\Folder
     */
    protected $userfolder;

    /**
     * @var DataObject\Folder
     */
    protected $groupfolder;

    /**
     * @var DataObject\AbstractObject
     */
    protected $hiddenobject;

    /**
     * @var DataObject\AbstractObject
     */
    protected $hugo;

    /**
     * @var DataObject\AbstractObject
     */
    protected $usertestobject;

    /**
     * @var DataObject\AbstractObject
     */
    protected $grouptestobject;

    /**
     * @var DataObject\Folder
     */
    protected $a;

    /**
     * @var DataObject\Folder
     */
    protected $b;

    /**
     * @var DataObject\AbstractObject
     */
    protected $c;

    /**
     * @var DataObject\AbstractObject
     */
    protected $abcdefghjkl;

    /**
     * @var Asset
     */
    protected $assetElement;

    protected function prepareObjectTree()
    {

        //example based on https://github.com/pimcore/pimcore/issues/11540
        $this->permissioncpath = $this->createFolder('permission\'"cpath', 1);
        $this->a = $this->createFolder('a', $this->permissioncpath->getId());
        $this->b = $this->createFolder('b', $this->a->getId());
        $this->c = $this->createObject('c', $this->b->getId());
        $this->abcdefghjkl = $this->createObject('abcdefghjkl', $this->permissioncpath->getId());

        $this->permissionfoo = $this->createFolder('permissionfoo', 1);
        $this->permissionbar = $this->createFolder('permissionbar', 1);
        $this->foo = $this->createFolder('foo', $this->permissionbar->getId());
        $this->bars = $this->createFolder('bars', $this->permissionfoo->getId());
        $this->userfolder = $this->createFolder('userfolder', $this->bars->getId());
        $this->groupfolder = $this->createFolder('groupfolder', $this->bars->getId());

        $this->hiddenobject = $this->createObject('hiddenobject', $this->foo->getId());
        $this->hugo = $this->createObject('hugo', $this->bars->getId());
        $this->usertestobject = $this->createObject('usertestobject', $this->userfolder->getId());
        $this->grouptestobject = $this->createObject('grouptestobject', $this->groupfolder->getId());
    }

    protected function createFolder(string $key, int $parentId): DataObject\Folder
    {
        $folder = new DataObject\Folder();
        $folder->setKey($key);
        $folder->setParentId($parentId);
        $folder->save();

        $searchEntry = new Search\Backend\Data($folder);
        $searchEntry->save();

        return $folder;
    }

    protected function createObject(string $key, int $parentId): DataObject\AbstractObject
    {
        $object = TestHelper::createEmptyObject();

        $object->setKey($key);
        $object->setInput($key);
        $object->setParentId($parentId);
        $object->setPublished(true);

        $object->save();

        $searchEntry = new Search\Backend\Data($object);
        $searchEntry->save();

        return $object;
    }

    protected function createAsset(string $key, int $parentId): Asset
    {
        $asset = new Asset\Image();

        $asset->setKey($key);
        $asset->setParentId($parentId);
        $asset->setType('image');
        $asset->setData('data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
        $asset->setFilename($key);
        $asset->save();

        $searchEntry = new Search\Backend\Data($asset);
        $searchEntry->save();

        return $asset;
    }

    protected function prepareUsers()
    {
        //create role
        $role = new User\Role();
        $role->setName('Testrole');
        $role->setWorkspacesObject([
            (new User\Workspace\DataObject())->setValues(['cId' => $this->groupfolder->getId(), 'cPath' => $this->groupfolder->getFullpath(), 'list' => true, 'view' => true, 'save'=>true, 'publish'=>false ]),
        ]);
        $role->save();

        $role2 = new User\Role();
        $role2->setName('dummyRole');
        $role2->setWorkspacesObject([
            (new User\Workspace\DataObject())->setValues(['cId' => $this->groupfolder->getId(), 'cPath' => $this->groupfolder->getFullpath(), 'list' => false, 'view' => false, 'save'=>false, 'publish'=>false, 'settings' => true ]),
        ]);
        $role2->save();

        //create user 1
        $this->userPermissionTest1 = new User();
        $this->userPermissionTest1->setName('Permissiontest1');
        $this->userPermissionTest1->setPermissions(['objects']);
        $this->userPermissionTest1->setRoles([$role->getId(), $role2->getId()]);
        $this->userPermissionTest1->setWorkspacesObject([
            (new User\Workspace\DataObject())->setValues(['cId' => $this->permissionfoo->getId(), 'cPath' => $this->permissionfoo->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->permissionbar->getId(), 'cPath' => $this->permissionbar->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->foo->getId(), 'cPath' => $this->foo->getFullpath(), 'list' => false, 'view' => false]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->bars->getId(), 'cPath' => $this->bars->getFullpath(), 'list' => false, 'view' => false]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->userfolder->getId(), 'cPath' => $this->userfolder->getFullpath(), 'list' => true, 'view' => true, 'create'=> true, 'rename'=> true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->c->getId(), 'cPath' => $this->c->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->abcdefghjkl->getId(), 'cPath' => $this->abcdefghjkl->getFullpath(), 'list' => true, 'view' => true]),
        ]);
        $this->userPermissionTest1->save();

        //create user 2
        $this->userPermissionTest2 = new User();
        $this->userPermissionTest2->setName('Permissiontest2');
        $this->userPermissionTest2->setPermissions(['objects']);
        $this->userPermissionTest2->setRoles([$role->getId(), $role2->getId()]);
        $this->userPermissionTest2->setWorkspacesObject([
            (new User\Workspace\DataObject())->setValues(['cId' => $this->permissionfoo->getId(), 'cPath' => $this->permissionfoo->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->permissionbar->getId(), 'cPath' => $this->permissionbar->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->foo->getId(), 'cPath' => $this->foo->getFullpath(), 'list' => false, 'view' => false]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->bars->getId(), 'cPath' => $this->bars->getFullpath(), 'list' => false, 'view' => false]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->userfolder->getId(), 'cPath' => $this->userfolder->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->groupfolder->getId(), 'cPath' => $this->groupfolder->getFullpath(), 'list' => false, 'view' => false, 'save'=>true, 'publish'=>true, 'settings' => false]),
        ]);
        $this->userPermissionTest2->save();

        //create user 3, with no roles, only usertestobject allowed
        $this->userPermissionTest3 = new User();
        $this->userPermissionTest3->setName('Permissiontest3');
        $this->userPermissionTest3->setPermissions(['objects']);
        $this->userPermissionTest3->setWorkspacesObject([
            (new User\Workspace\DataObject())->setValues(['cId' => $this->usertestobject->getId(), 'cPath' => $this->usertestobject->getFullpath(), 'list' => true, 'view' => true]),
        ]);
        $this->userPermissionTest3->save();

        //create user 4, with no user workspace rules, only from roles
        $this->userPermissionTest4 = new User();
        $this->userPermissionTest4->setName('Permissiontest4');
        $this->userPermissionTest4->setPermissions(['objects']);
        $this->userPermissionTest4->setRoles([$role->getId(), $role2->getId()]);
        $this->userPermissionTest4->save();

        //create user 5, with no roles, assets and data objects allowed in parallel
        $this->userPermissionTest5 = new User();
        $this->userPermissionTest5->setName('Permissiontest5');
        $this->userPermissionTest5->setPermissions(['assets', 'objects']);
        $this->userPermissionTest5->setWorkspacesObject([
            (new User\Workspace\DataObject())->setValues(['cId' => $this->usertestobject->getId(), 'cPath' => $this->usertestobject->getFullpath(), 'list' => true, 'view' => true]),
        ]);
        $this->userPermissionTest5->setWorkspacesAsset([
            (new User\Workspace\Asset())->setValues(['cId' => $this->assetElement->getId(), 'cPath' => $this->assetElement->getFullpath(), 'list' => true, 'view' => true]),
        ]);
        $this->userPermissionTest5->save();

        //create user 6, with no roles, with no permissions set but workspaces configured --> should not find anything
        $this->userPermissionTest6 = new User();
        $this->userPermissionTest6->setName('Permissiontest6');
        $this->userPermissionTest6->setPermissions([]);
        $this->userPermissionTest6->setWorkspacesObject([
            (new User\Workspace\DataObject())->setValues(['cId' => $this->usertestobject->getId(), 'cPath' => $this->usertestobject->getFullpath(), 'list' => true, 'view' => true]),
        ]);
        $this->userPermissionTest6->setWorkspacesAsset([
            (new User\Workspace\Asset())->setValues(['cId' => $this->assetElement->getId(), 'cPath' => $this->assetElement->getFullpath(), 'list' => true, 'view' => true]),
        ]);

        $this->userPermissionTest6->save();
    }

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->prepareObjectTree();
        $this->assetElement = $this->createAsset('assetelement.gif', 1);
        $this->prepareUsers();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        TestHelper::cleanUp();
        User::getByName('Permissiontest1')->delete();
        User::getByName('Permissiontest2')->delete();
        User::getByName('Permissiontest3')->delete();
        User::getByName('Permissiontest4')->delete();
        User::getByName('Permissiontest5')->delete();
        User::getByName('Permissiontest6')->delete();
        User\Role::getByName('Testrole')->delete();
        User\Role::getByName('Dummyrole')->delete();
    }

    protected function doHasChildrenTest(DataObject\AbstractObject $element, bool $resultAdmin, bool $resultPermissionTest1, bool $resultPermissionTest2, bool $resultPermissionTest3, bool $resultPermissionTest4)
    {
        $admin = User::getByName('admin');

        $this->assertEquals(
            $resultAdmin,
            $element->getDao()->hasChildren(
                [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER], true, $admin
            ),
            'Has children of `' . $element->getFullpath() . '` for user admin'
        );

        $this->assertEquals(
            $resultPermissionTest1,
            $element->getDao()->hasChildren(
                [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER], true, $this->userPermissionTest1
            ),
            'Has children of `' . $element->getFullpath() . '` for user UserPermissionTest1'
        );

        $this->assertEquals(
            $resultPermissionTest2,
            $element->getDao()->hasChildren(
                [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER], true, $this->userPermissionTest2
            ),
            'Has children of `' . $element->getFullpath() . '` for user UserPermissionTest2'
        );

        $this->assertEquals(
            $resultPermissionTest3,
            $element->getDao()->hasChildren(
                [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER], true, $this->userPermissionTest3
            ),
            'Has children of `' . $element->getFullpath() . '` for user UserPermissionTest3'
        );

        $this->assertEquals(
            $resultPermissionTest4,
            $element->getDao()->hasChildren(
                [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER], true, $this->userPermissionTest4
            ),
            'Has children of `' . $element->getFullpath() . '` for user UserPermissionTest4'
        );
    }

    public function testHasChildren()
    {
        $this->doHasChildrenTest($this->a, true, true, false, false, false);
        $this->doHasChildrenTest($this->permissionfoo, true, true, true, true, true);
        $this->doHasChildrenTest($this->bars, true, true, true, true, true);
        $this->doHasChildrenTest($this->hugo, false, false, false, false, false);
        $this->doHasChildrenTest($this->userfolder, true, true, true, true, false);
        $this->doHasChildrenTest($this->groupfolder, true, true, false, false, true);
        $this->doHasChildrenTest($this->grouptestobject, false, false, false, false, false);
        $this->doHasChildrenTest($this->permissionbar, true, false, false, false, false);
        $this->doHasChildrenTest($this->foo, true, false, false, false, false);
        $this->doHasChildrenTest($this->hiddenobject, false, false, false, false, false);
    }

    protected function doIsAllowedTest(
       DataObject\AbstractObject $element,
       string $type, bool $resultAdmin,
       bool $resultPermissionTest1,
       bool $resultPermissionTest2,
       bool $resultPermissionTest3,
       bool $resultPermissionTest4,
       bool $resultPermissionTest5,
       bool $resultPermissionTest6
    ) {
        $admin = User::getByName('admin');

        $this->assertEquals(
            $resultAdmin,
            $element->isAllowed($type, $admin),
            '`' . $type . '` of `' . $element->getFullpath() . '` is allowed for admin'
        );

        $this->assertEquals(
            $resultPermissionTest1,
            $element->isAllowed($type, $this->userPermissionTest1),
            '`' . $type . '` of `' . $element->getFullpath() . '` is allowed for UserPermissionTest1'
        );

        $this->assertEquals(
            $resultPermissionTest2,
            $element->isAllowed($type, $this->userPermissionTest2),
            '`' . $type . '` of `' . $element->getFullpath() . '` is allowed for UserPermissionTest2'
        );

        $this->assertEquals(
            $resultPermissionTest3,
            $element->isAllowed($type, $this->userPermissionTest3),
            '`' . $type . '` of `' . $element->getFullpath() . '` is allowed for UserPermissionTest3'
        );

        $this->assertEquals(
            $resultPermissionTest4,
            $element->isAllowed($type, $this->userPermissionTest4),
            '`' . $type . '` of `' . $element->getFullpath() . '` is allowed for UserPermissionTest4'
        );

        $this->assertEquals(
            $resultPermissionTest5,
            $element->isAllowed($type, $this->userPermissionTest5),
            '`' . $type . '` of `' . $element->getFullpath() . '` is allowed for UserPermissionTest5'
        );
        $this->assertEquals(
            $resultPermissionTest6,
            $element->isAllowed($type, $this->userPermissionTest6),
            '`' . $type . '` of `' . $element->getFullpath() . '` is allowed for UserPermissionTest6'
        );
    }

    public function testIsAllowed()
    {
        $this->doIsAllowedTest($this->permissionfoo, 'list', true, true, true, true, true, true, false);
        $this->doIsAllowedTest($this->permissionfoo, 'view', true, true, true, false, false, false, false);

        $this->doIsAllowedTest($this->bars, 'list', true, true, true, true, true, true, false);
        $this->doIsAllowedTest($this->bars, 'view', true, false, false, false, false, false, false);

        $this->doIsAllowedTest($this->hugo, 'list', true, false, false, false, false, false, false);
        $this->doIsAllowedTest($this->hugo, 'view', true, false, false, false, false, false, false);

        $this->doIsAllowedTest($this->userfolder, 'list', true, true, true, true, false, true, false);
        $this->doIsAllowedTest($this->userfolder, 'view', true, true, true, false, false, false, false);

        $this->doIsAllowedTest($this->usertestobject, 'list', true, true, true, true, false, true, false);
        $this->doIsAllowedTest($this->usertestobject, 'view', true, true, true, true, false, true, false);

        $this->doIsAllowedTest($this->groupfolder, 'list', true, true, false, false, true, false, false);
        $this->doIsAllowedTest($this->groupfolder, 'view', true, true, false, false, true, false, false);

        $this->doIsAllowedTest($this->grouptestobject, 'list', true, true, false, false, true, false, false);
        $this->doIsAllowedTest($this->grouptestobject, 'view', true, true, false, false, true, false, false);

        $this->doIsAllowedTest($this->permissionbar, 'list', true, true, true, false, false, false, false);
        $this->doIsAllowedTest($this->permissionbar, 'view', true, true, true, false, false, false, false);

        $this->doIsAllowedTest($this->foo, 'list', true, false, false, false, false, false, false);
        $this->doIsAllowedTest($this->foo, 'view', true, false, false, false, false, false, false);

        $this->doIsAllowedTest($this->hiddenobject, 'list', true, false, false, false, false, false, false);
        $this->doIsAllowedTest($this->hiddenobject, 'view', true, false, false, false, false, false, false);
    }

    protected function doAreAllowedTest(DataObject\AbstractObject $element, User $user, array $expectedPermissions)
    {
        $calculatedPermissions = $element->getUserPermissions($user);

        foreach ($expectedPermissions as $type => $expectedPermission) {
            $this->assertEquals(
                $expectedPermission,
                $calculatedPermissions[$type],
                sprintf('Expected permission `%s` does not match for element %s for user %s', $type, $element->getFullpath(), $user->getName())
            );
        }
    }

    public function testAreAllowed()
    {
        $admin = User::getByName('admin');

        //check permissions of groupfolder (directly defined) and grouptestobject (inherited)
        foreach ([$this->groupfolder, $this->grouptestobject] as $element) {
            $this->doAreAllowedTest($element, $admin,
                [
                    'save' => 1,
                    'delete' => 1,
                    'publish' => 1,
                    'settings' => 1,
                    'versions' => 1,
                ]
            );
            $this->doAreAllowedTest($element, $this->userPermissionTest1,
                [
                    'save' => 1,
                    'delete' => 0,
                    'publish' => 0,
                    'settings' => 1,
                    'versions' => 0,
                ]
            );
            $this->doAreAllowedTest($element, $this->userPermissionTest2,
                [
                    'save' => 1,
                    'delete' => 0,
                    'publish' => 1,
                    'settings' => 0,
                    'versions' => 0,
                ]
            );
            $this->doAreAllowedTest($element, $this->userPermissionTest3, []);
            $this->doAreAllowedTest($element, $this->userPermissionTest4,
                [
                    'list' => 1,
                    'view' => 1,
                ]
            );

            $this->doAreAllowedTest($element, $this->userPermissionTest6,
                []
            );
        }

        //check permissions of userfolder (directly defined) and usertestobject (inherited)
        foreach ([$this->userfolder, $this->usertestobject] as $element) {
            $this->doAreAllowedTest($element, $admin,
                [
                    'view' => 1,
                    'delete' => 1,
                    'publish' => 1,
                    'versions' => 1,
                    'create' => 1,
                    'rename' => 1,
                ]
            );
            $this->doAreAllowedTest($element, $this->userPermissionTest1,
                [
                    'view' => 1,
                    'delete' => 0,
                    'publish' => 0,
                    'versions' => 0,
                    'create' => 1,
                    'rename' => 1,
                ]
            );
            $this->doAreAllowedTest($element, $this->userPermissionTest2,
                [
                    'view' => 1,
                    'delete' => 0,
                    'publish' => 0,
                    'versions' => 0,
                    'create' => 0,
                    'rename' => 0,
                ]
            );

            $this->doAreAllowedTest($element, $this->userPermissionTest6,
                [
                    'list' => 0,
                    'view' => 0,
                    'save' => 0,
                    'publish' => 0,
                ]
            );
        }

        //check when no parent workspace is found, it should be allow list=1 when children are found, in this case for
        // admin and user1 to get to `c`
        foreach ([$this->a, $this->b, $this->c] as $element) {
            $this->doAreAllowedTest($element, $admin,
                [
                    'list' => 1,
                    'delete' => 1,
                    'publish' => 1,
                    'versions' => 1,
                ]
            );
            $this->doAreAllowedTest($element, $this->userPermissionTest1,
                [
                    'list' => 1,
                    'delete' => 0,
                    'publish' => 0,
                    'versions' => 0,
                ]
            );
            $this->doAreAllowedTest($element, $this->userPermissionTest2,
                [
                    'list' => 0,
                    'delete' => 0,
                    'publish' => 0,
                    'versions' => 0,
                ]
            );
        }
    }

    protected function buildController(string $classname, User $user)
    {
        $dataObjectController = Stub::construct($classname, [], [
            'getAdminUser' => function () use ($user) {
                return $user;
            },
            'adminJson' => function ($data) {
                return $data;
            },
        ]);

        return $dataObjectController;
    }

    /**
     * @param DataObject\AbstractObject $element
     * @param User $user
     * @param array|null $expectedChildren When null,the master permission is disabled
     *
     * @throws \ReflectionException
     */
    protected function doTestTreeGetChildsById(DataObject\AbstractObject $element, User $user, ?array $expectedChildren)
    {
        $controller = $this->buildController('\\Pimcore\\Bundle\\AdminBundle\\Controller\\Admin\\DataObject\\DataObjectController', $user);

        $request = new Request([
            'node' => $element->getId(),
        ]);
        $eventDispatcher = new EventDispatcher();

        try {
            TestHelper::callMethod($controller, 'checkPermission', ['objects']);
            $responseData = $controller->treeGetChildsByIdAction(
                $request,
                $eventDispatcher
            );
        } catch (\Exception $e) {
            if (is_null($expectedChildren)) {
                $this->assertInstanceOf(AccessDeniedHttpException::class, $e, 'Assert master object permission');

                return;
            }
        }

        $responsePaths = [];
        foreach ($responseData['nodes'] as $node) {
            $responsePaths[] = $node['path'];
        }

        $this->assertCount(
            $responseData['total'],
            $responseData['nodes'],
            'Assert total count of response matches count of nodes array for `' . $element->getFullpath() . '` for user `' . $user->getName() . '`'
        );

        $this->assertCount(
            count($expectedChildren),
            $responseData['nodes'],
            'Assert number of expected result matches count of nodes array for `' . $element->getFullpath() . '` for user `' . $user->getName() . '` (' . print_r($responsePaths, true) . ')'
        );

        foreach ($expectedChildren as $path) {
            $this->assertContains(
                $path,
                $responsePaths,
                'Children of `' . $element->getFullpath() . '` do to not contain `' . $path . '` for user `' . $user->getName() . '`'
            );
        }
    }

    public function testTreeGetChildsById()
    {
        $admin = User::getByName('admin');

        // test /permissionfoo
        $this->doTestTreeGetChildsById(
            $this->permissionfoo,
            $admin,
            [$this->bars->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->permissionfoo,
            $this->userPermissionTest1,
            [$this->bars->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->permissionfoo,
            $this->userPermissionTest2,
            [$this->bars->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->permissionfoo,
            $this->userPermissionTest3,
            [$this->bars->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->permissionfoo,
            $this->userPermissionTest4,
            [$this->bars->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->permissionfoo,
            $this->userPermissionTest5,
            [$this->bars->getFullpath()]
        );

        // test /permissionfoo/bars
        $this->doTestTreeGetChildsById(
            $this->bars,
            $admin,
            [$this->hugo->getFullpath(), $this->userfolder->getFullpath(), $this->groupfolder->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->bars,
            $this->userPermissionTest1,
            [$this->userfolder->getFullpath(), $this->groupfolder->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->bars,
            $this->userPermissionTest2,
            [$this->userfolder->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->bars,
            $this->userPermissionTest3,
            [$this->userfolder->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->bars,
            $this->userPermissionTest4,
            [$this->groupfolder->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->bars,
            $this->userPermissionTest5,
            [$this->userfolder->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->bars,
            $this->userPermissionTest6,
            null
        );

        // test /permissionfoo/bars/userfolder
        $this->doTestTreeGetChildsById(
            $this->userfolder,
            $admin,
            [$this->usertestobject->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->userfolder,
            $this->userPermissionTest1,
            [$this->usertestobject->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->userfolder,
            $this->userPermissionTest2,
            [$this->usertestobject->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->userfolder,
            $this->userPermissionTest3,
            [$this->usertestobject->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->userfolder,
            $this->userPermissionTest4,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->userfolder,
            $this->userPermissionTest5,
            [$this->usertestobject->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->userfolder,
            $this->userPermissionTest6,
            null
        );

        // test /permissionfoo/bars/groupfolder
        $this->doTestTreeGetChildsById(
            $this->groupfolder,
            $admin,
            [$this->grouptestobject->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->groupfolder,
            $this->userPermissionTest1,
            [$this->grouptestobject->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->groupfolder,
            $this->userPermissionTest2,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->groupfolder,
            $this->userPermissionTest3,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->groupfolder,
            $this->userPermissionTest4,
            [$this->grouptestobject->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->groupfolder,
            $this->userPermissionTest5,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->groupfolder,
            $this->userPermissionTest6,
            null
        );

        // test /permissionbar
        $this->doTestTreeGetChildsById(
            $this->permissionbar,
            $admin,
            [$this->foo->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->permissionbar,
            $this->userPermissionTest1,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->permissionbar,
            $this->userPermissionTest2,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->permissionbar,
            $this->userPermissionTest3,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->permissionbar,
            $this->userPermissionTest4,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->permissionbar,
            $this->userPermissionTest5,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->permissionbar,
            $this->userPermissionTest6,
            null
        );
        // test /permissionbar/foo
        $this->doTestTreeGetChildsById(
            $this->foo,
            $admin,
            [$this->hiddenobject->getFullpath()]
        );

        $this->doTestTreeGetChildsById(
            $this->foo,
            $this->userPermissionTest1,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->foo,
            $this->userPermissionTest2,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->foo,
            $this->userPermissionTest3,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->foo,
            $this->userPermissionTest4,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->foo,
            $this->userPermissionTest5,
            []
        );

        $this->doTestTreeGetChildsById(
            $this->foo,
            $this->userPermissionTest6,
            null
        );
    }

    protected function doTestSearch(string $searchText, User $user, array $expectedResultPaths, int $limit = 100)
    {
        /**
         * @var SearchController $controller
         */
        $controller = $this->buildController('\\Pimcore\\Bundle\\AdminBundle\\Controller\\Searchadmin\\SearchController', $user);

        $request = new Request([
            'type' => 'object',
            'query' => $searchText,
            'start' => 0,
            'limit' => $limit,
        ]);

        $responseData = $controller->findAction(
            $request,
            new EventDispatcher(),
            new GridHelperService()
        );

        $responsePaths = [];
        foreach ($responseData['data'] as $node) {
            $responsePaths[] = $node['fullpath'];
        }

        $this->assertCount(
            $responseData['total'],
            $responseData['data'],
            '[Search] Assert total count of response matches count of nodes array for `' . $searchText . '` for user `' . $user->getName() . '`'
        );

        $this->assertCount(
            count($expectedResultPaths),
            $responseData['data'],
            '[Search] Assert number of expected result matches count of nodes array for `' . $searchText . '` for user `' . $user->getName() . '` (' . print_r($responsePaths, true) . ')'
        );

        foreach ($expectedResultPaths as $path) {
            $this->assertContains(
                $path,
                $responsePaths,
                '[Search] Result for `' . $searchText . '` does not contain `' . $path . '` for user `' . $user->getName() . '`'
            );
        }
    }

    public function testSearch()
    {
        $admin = User::getByName('admin');

        //search hugo
        $this->doTestSearch('hugo', $admin, [$this->hugo->getFullpath()]);
        $this->doTestSearch('hugo', $this->userPermissionTest1, []);
        $this->doTestSearch('hugo', $this->userPermissionTest2, []);
        $this->doTestSearch('hugo', $this->userPermissionTest3, []);
        $this->doTestSearch('hugo', $this->userPermissionTest4, []);
        $this->doTestSearch('hugo', $this->userPermissionTest5, []);
        $this->doTestSearch('hugo', $this->userPermissionTest6, []);

        //search bars
        $this->doTestSearch('bars', $admin, [
            $this->bars->getFullpath(),
            $this->hugo->getFullpath(),
            $this->userfolder->getFullpath(),
            $this->usertestobject->getFullpath(),
            $this->groupfolder->getFullpath(),
            $this->grouptestobject->getFullpath(),
        ]);
        $this->doTestSearch('bars', $this->userPermissionTest1, [
            $this->bars->getFullpath(),
            $this->userfolder->getFullpath(),
            $this->usertestobject->getFullpath(),
            $this->groupfolder->getFullpath(),
            $this->grouptestobject->getFullpath(),
        ]);
        $this->doTestSearch('bars', $this->userPermissionTest2, [
            $this->bars->getFullpath(),
            $this->userfolder->getFullpath(),
            $this->usertestobject->getFullpath(),
        ]);

        $this->doTestSearch('bars', $this->userPermissionTest3, [
            $this->usertestobject->getFullpath(),
        ]);

        $this->doTestSearch('bars', $this->userPermissionTest4, [
            $this->groupfolder->getFullpath(),
            $this->grouptestobject->getFullpath(),
        ]);

        $this->doTestSearch('bars', $this->userPermissionTest5, [
            $this->usertestobject->getFullpath(),
        ]);

        $this->doTestSearch('bars', $this->userPermissionTest6, []);

        //search hidden object
        $this->doTestSearch('hiddenobject', $admin, [$this->hiddenobject->getFullpath()]);
        $this->doTestSearch('hiddenobject', $this->userPermissionTest1, []);
        $this->doTestSearch('hiddenobject', $this->userPermissionTest2, []);
        $this->doTestSearch('hiddenobject', $this->userPermissionTest3, []);
        $this->doTestSearch('hiddenobject', $this->userPermissionTest4, []);
        $this->doTestSearch('hiddenobject', $this->userPermissionTest5, []);
        $this->doTestSearch('hiddenobject', $this->userPermissionTest6, []);

        //search for asset
        $this->doTestSearch('assetelement', $admin, []);
        $this->doTestSearch('assetelement', $this->userPermissionTest1, []);
        $this->doTestSearch('assetelement', $this->userPermissionTest2, []);
        $this->doTestSearch('assetelement', $this->userPermissionTest3, []);
        $this->doTestSearch('assetelement', $this->userPermissionTest4, []);
        $this->doTestSearch('assetelement', $this->userPermissionTest5, []);
        $this->doTestSearch('assetelement', $this->userPermissionTest6, []);
    }

    public function testManyElementSearch()
    {
        $admin = User::getByName('admin');

        //prepare additional data
        $manyElements = $this->createFolder('manyElements', 1);
        $manyElementList = [];
        $elementCount = 50;

        for ($i = 1; $i <= $elementCount; $i++) {
            $manyElementList[] = $this->createObject('manyelement ' . $i, $manyElements->getId());
        }
        $manyElementX = $this->createObject('manyelement X', $manyElements->getId());

        //update role
        $role = User\Role::getByName('Testrole');
        $role->setWorkspacesObject([
            (new User\Workspace\DataObject())->setValues(['cId' => $this->groupfolder->getId(), 'cPath' => $this->groupfolder->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $manyElementX->getId(), 'cPath' => $manyElementX->getFullpath(), 'list' => true, 'view' => true]),
        ]);
        $role->save();

        //search manyelement
        $this->doTestSearch('manyelement', $admin, array_merge(
            array_map(function ($item) {
                return $item->getFullpath();
            }, $manyElementList),
            [ $manyElementX->getFullpath() ]
        ), $elementCount + 1
        );
        $this->doTestSearch('manyelement', $this->userPermissionTest1, [$manyElementX->getFullpath()], $elementCount + 1);
        $this->doTestSearch('manyelement', $this->userPermissionTest2, [$manyElementX->getFullpath()], $elementCount + 1);
        $this->doTestSearch('manyelement', $this->userPermissionTest3, [], $elementCount + 1);
        $this->doTestSearch('manyelement', $this->userPermissionTest4, [$manyElementX->getFullpath()], $elementCount + 1);

        $this->doTestSearch('manyelement', $this->userPermissionTest1, [$manyElementX->getFullpath()], $elementCount);
        $this->doTestSearch('manyelement', $this->userPermissionTest2, [$manyElementX->getFullpath()], $elementCount);
        $this->doTestSearch('manyelement', $this->userPermissionTest3, [], $elementCount);
        $this->doTestSearch('manyelement', $this->userPermissionTest4, [$manyElementX->getFullpath()], $elementCount);
    }

    protected function doTestQuickSearch(string $searchText, User $user, array $expectedResultPaths, int $limit = 100)
    {
        /**
         * @var SearchController $controller
         */
        $controller = $this->buildController('\\Pimcore\\Bundle\\AdminBundle\\Controller\\Searchadmin\\SearchController', $user);

        $request = new Request([
            'query' => $searchText,
            'start' => 0,
            'limit' => $limit,
        ]);

        $responseData = $controller->quicksearchAction(
            $request,
            new EventDispatcher(),
        );

        $responsePaths = [];
        foreach ($responseData['data'] as $node) {
            $responsePaths[] = $node['fullpathList'];
        }

        $this->assertCount(
            count($expectedResultPaths),
            $responseData['data'],
            '[Quicksearch] Assert number of expected result matches count of nodes array for `' . $searchText . '` for user `' . $user->getName() . '` (' . print_r($responsePaths, true) . ')'
        );

        foreach ($expectedResultPaths as $path) {
            $this->assertContains(
                $path,
                $responsePaths,
                '[Quicksearch] Result for `' . $searchText . '` does not contain `' . $path . '` for user `' . $user->getName() . '`'
            );
        }
    }

    public function testQuickSearch()
    {
        $admin = User::getByName('admin');

        //search hugo
        $this->doTestQuickSearch('hugo', $admin, [$this->hugo->getFullpath()]);
        $this->doTestQuickSearch('hugo', $this->userPermissionTest1, []);
        $this->doTestQuickSearch('hugo', $this->userPermissionTest2, []);
        $this->doTestQuickSearch('hugo', $this->userPermissionTest3, []);
        $this->doTestQuickSearch('hugo', $this->userPermissionTest4, []);
        $this->doTestQuickSearch('hugo', $this->userPermissionTest5, []);
        $this->doTestQuickSearch('hugo', $this->userPermissionTest6, []);

        //search bars
        $this->doTestQuickSearch('bars', $admin, [
            $this->hugo->getFullpath(),
            $this->usertestobject->getFullpath(),
            $this->grouptestobject->getFullpath(),
        ]);
        $this->doTestQuickSearch('bars', $this->userPermissionTest1, [
            $this->usertestobject->getFullpath(),
            $this->grouptestobject->getFullpath(),
        ]);
        $this->doTestQuickSearch('bars', $this->userPermissionTest2, [
            $this->usertestobject->getFullpath(),
        ]);
        $this->doTestQuickSearch('bars', $this->userPermissionTest3, [
            $this->usertestobject->getFullpath(),
        ]);
        $this->doTestQuickSearch('bars', $this->userPermissionTest4, [
            $this->grouptestobject->getFullpath(),
        ]);
        $this->doTestQuickSearch('bars', $this->userPermissionTest5, [
            $this->usertestobject->getFullpath(),
        ]);
        $this->doTestQuickSearch('bars', $this->userPermissionTest6, []);

        //search hidden object
        $this->doTestQuickSearch('hiddenobject', $admin, [$this->hiddenobject->getFullpath()]);
        $this->doTestQuickSearch('hiddenobject', $this->userPermissionTest1, []);
        $this->doTestQuickSearch('hiddenobject', $this->userPermissionTest2, []);
        $this->doTestQuickSearch('hiddenobject', $this->userPermissionTest3, []);
        $this->doTestQuickSearch('hiddenobject', $this->userPermissionTest4, []);

        //search for asset
        $this->doTestQuickSearch('assetelement', $admin, [$this->assetElement->getFullPath()]);
        $this->doTestQuickSearch('assetelement', $this->userPermissionTest1, []);
        $this->doTestQuickSearch('assetelement', $this->userPermissionTest2, []);
        $this->doTestQuickSearch('assetelement', $this->userPermissionTest3, []);
        $this->doTestQuickSearch('assetelement', $this->userPermissionTest4, []);
        $this->doTestQuickSearch('assetelement', $this->userPermissionTest5, [$this->assetElement->getFullPath()]);
        $this->doTestQuickSearch('assetelement', $this->userPermissionTest6, []);
    }
}
