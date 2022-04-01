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
use Pimcore\Bundle\AdminBundle\Helper\GridHelperService;
use Pimcore\Model\Search;
use Pimcore\Model\DataObject;
use Pimcore\Model\User;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;


class ModelPermissionsTest extends ModelTestCase
{

    /**
     *  created object tree
     *
     * /permissionfoo --> allowed
     * /permissionfoo/bars --> not allowed
     * /permissionbar/bars/hugo --> ?? --> should not be found
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

    protected function prepareObjectTree() {

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

    protected function createFolder(string $key, int $parentId): DataObject\Folder {

        $folder = new DataObject\Folder();
        $folder->setKey($key);
        $folder->setParentId($parentId);
        $folder->save();

        $searchEntry = new Search\Backend\Data($folder);
        $searchEntry->save();

        return $folder;
    }

    protected function createObject(string $key, int $parentId): DataObject\AbstractObject {
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

    protected function prepareUsers() {
        //create role
        $role = new User\Role();
        $role->setName('Testrole');
        $role->setWorkspacesObject([
            (new User\Workspace\DataObject())->setValues(['cId' => $this->groupfolder->getId(), 'cPath' => $this->groupfolder->getFullpath(), 'list' => true, 'view' => true]),
        ]);
        $role->save();

        //create user 1
        $this->userPermissionTest1 = new User();
        $this->userPermissionTest1->setName('Permissiontest1');
        $this->userPermissionTest1->setPermissions(['objects']);
        $this->userPermissionTest1->setRoles([$role->getId()]);
        $this->userPermissionTest1->setWorkspacesObject([
            (new User\Workspace\DataObject())->setValues(['cId' => $this->permissionfoo->getId(), 'cPath' => $this->permissionfoo->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->permissionbar->getId(), 'cPath' => $this->permissionbar->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->foo->getId(), 'cPath' => $this->foo->getFullpath(), 'list' => false, 'view' => false]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->bars->getId(), 'cPath' => $this->bars->getFullpath(), 'list' => false, 'view' => false]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->userfolder->getId(), 'cPath' => $this->userfolder->getFullpath(), 'list' => true, 'view' => true]),
        ]);
        $this->userPermissionTest1->save();

        //create user 2
        $this->userPermissionTest2 = new User();
        $this->userPermissionTest2->setName('Permissiontest2');
        $this->userPermissionTest2->setPermissions(['objects']);
        $this->userPermissionTest2->setRoles([$role->getId()]);
        $this->userPermissionTest2->setWorkspacesObject([
            (new User\Workspace\DataObject())->setValues(['cId' => $this->permissionfoo->getId(), 'cPath' => $this->permissionfoo->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->permissionbar->getId(), 'cPath' => $this->permissionbar->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->foo->getId(), 'cPath' => $this->foo->getFullpath(), 'list' => false, 'view' => false]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->bars->getId(), 'cPath' => $this->bars->getFullpath(), 'list' => false, 'view' => false]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->userfolder->getId(), 'cPath' => $this->userfolder->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\DataObject())->setValues(['cId' => $this->groupfolder->getId(), 'cPath' => $this->groupfolder->getFullpath(), 'list' => false, 'view' => false]),
        ]);
        $this->userPermissionTest2->save();
    }

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->prepareObjectTree();
        $this->prepareUsers();

    }

    protected function tearDown(): void
    {
        parent::tearDown();

        TestHelper::cleanUp();
        User::getByName('Permissiontest1')->delete();
        User::getByName('Permissiontest2')->delete();
        User\Role::getByName('Testrole')->delete();

    }

    protected function doHasChildrenTest(DataObject\AbstractObject $element, bool $resultAdmin, bool $resultPermissionTest1, bool $resultPermissionTest2) {

        $admin = User::getByName('admin');

        $this->assertEquals(
            $resultAdmin,
            $element->getDao()->hasChildren(
                [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER],true, $admin
            ),
            'Has children of `' . $element->getFullpath() . '` for user admin'
        );

        $this->assertEquals(
            $resultPermissionTest1,
            $element->getDao()->hasChildren(
                [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER],true, $this->userPermissionTest1
            ),
            'Has children of `' . $element->getFullpath() . '` for user UserPermissionTest1'
        );

        $this->assertEquals(
            $resultPermissionTest2,
            $element->getDao()->hasChildren(
                [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER],true, $this->userPermissionTest2
            ),
            'Has children of `' . $element->getFullpath() . '` for user UserPermissionTest2'
        );

    }

    public function testHasChildren()
    {
        $this->doHasChildrenTest($this->permissionfoo, true, true, true); //didn't work before
        $this->doHasChildrenTest($this->bars, true, true, true);
        $this->doHasChildrenTest($this->hugo, false, false, false);
        $this->doHasChildrenTest($this->userfolder, true, true, true);
        $this->doHasChildrenTest($this->groupfolder, true, true, false); //didn't work before
        $this->doHasChildrenTest($this->grouptestobject, false, false, false);
        $this->doHasChildrenTest($this->permissionbar, true, false, false);
        $this->doHasChildrenTest($this->foo, true, false, false);
        $this->doHasChildrenTest($this->hiddenobject, false, false, false);
    }

    protected function doIsAllowedTest(DataObject\AbstractObject $element, string $type, bool $resultAdmin, bool $resultPermissionTest1, bool $resultPermissionTest2) {

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

    }


    public function testIsAllowed() {

        $this->doIsAllowedTest($this->permissionfoo, 'list', true, true, true);
        $this->doIsAllowedTest($this->permissionfoo, 'view', true, true, true);

        $this->doIsAllowedTest($this->bars, 'list', true, true, true);
        $this->doIsAllowedTest($this->bars, 'view', true, false, false);

        $this->doIsAllowedTest($this->hugo, 'list', true, false, false);
        $this->doIsAllowedTest($this->hugo, 'view', true, false, false);

        $this->doIsAllowedTest($this->userfolder, 'list', true, true, true);
        $this->doIsAllowedTest($this->userfolder, 'view', true, true, true);

       $this->doIsAllowedTest($this->groupfolder, 'list', true, true, false);
       $this->doIsAllowedTest($this->groupfolder, 'view', true, true, false);

       $this->doIsAllowedTest($this->grouptestobject, 'list', true, true, false);
       $this->doIsAllowedTest($this->grouptestobject, 'view', true, true, false);

        $this->doIsAllowedTest($this->permissionbar, 'list', true, true, true);
        $this->doIsAllowedTest($this->permissionbar, 'view', true, true, true);

        $this->doIsAllowedTest($this->foo, 'list', true, false, false);
        $this->doIsAllowedTest($this->foo, 'view', true, false, false);

        $this->doIsAllowedTest($this->hiddenobject, 'list',true, false, false);
        $this->doIsAllowedTest($this->hiddenobject, 'view',true, false, false);

    }



    protected function buildController(string $classname, User $user)
    {
        $dataObjectController = Stub::construct($classname, [], [
            'getAdminUser' => function () use ($user) {
                return $user;
            },
            'adminJson' => function($data) {
                return $data;
            }
        ]);

        return $dataObjectController;
    }

    protected function doTestTreeGetChildsById(DataObject\AbstractObject $element, User $user, array $expectedChildren) {
        $controller = $this->buildController('\\Pimcore\\Bundle\\AdminBundle\\Controller\\Admin\\DataObject\\DataObjectController', $user);

        $request = new Request([
            'node' => $element->getId()
        ]);
        $eventDispatcher = new EventDispatcher();

        $responseData = $controller->treeGetChildsByIdAction(
            $request,
            $eventDispatcher
        );



        $responsePaths = [];
        foreach($responseData['nodes'] as $node) {
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

        foreach($expectedChildren as $path) {
            $this->assertContains(
                $path,
                $responsePaths,
                'Children of `' . $element->getFullpath() . '` do to not contain `' . $path . '` for user `' . $user->getName() . '`'
            );
        }

    }

    public function testTreeGetChildsById() {

        $admin = User::getByName('admin');


        // test /permissionfoo
        $this->doTestTreeGetChildsById(
            $this->permissionfoo,
            $admin,
            [$this->bars->getFullpath()]
        );

        $this->doTestTreeGetChildsById( //did not work before (count vs. total)
            $this->permissionfoo,
            $this->userPermissionTest1,
            [$this->bars->getFullpath()]
        );

        $this->doTestTreeGetChildsById( //did not work before
            $this->permissionfoo,
            $this->userPermissionTest2,
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

        $this->doTestTreeGetChildsById( //did not work before (count vs. total)
            $this->bars,
            $this->userPermissionTest2,
            [$this->userfolder->getFullpath()]
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

        $this->doTestTreeGetChildsById( //did not work before (count vs. total)
            $this->groupfolder,
            $this->userPermissionTest2,
            []
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


    }

    protected function doTestSearch(string $searchText, User $user, array $expectedResultPaths, int $limit = 100) {
        $controller = $this->buildController('\\Pimcore\\Bundle\\AdminBundle\\Controller\\Searchadmin\\SearchController', $user);

        $request = new Request([
            'type' => 'object',
            'query' => $searchText,
            'start' => 0,
            'limit' => $limit
        ]);

        $responseData = $controller->findAction(
            $request,
            new EventDispatcher(),
            new GridHelperService()
        );

        $responsePaths = [];
        foreach($responseData['data'] as $node) {
            $responsePaths[] = $node['fullpath'];
        }

        $this->assertCount(
            $responseData['total'],
            $responseData['data'],
            'Assert total count of response matches count of nodes array for `' . $searchText . '` for user `' . $user->getName() . '`'
        );

        $this->assertCount(
            count($expectedResultPaths),
            $responseData['data'],
            'Assert number of expected result matches count of nodes array for `' . $searchText . '` for user `' . $user->getName() . '` (' . print_r($responsePaths, true) . ')'
        );

        foreach($expectedResultPaths as $path) {
            $this->assertContains(
                $path,
                $responsePaths,
                'Result for `' . $searchText . '` does not contain `' . $path . '` for user `' . $user->getName() . '`'
            );
        }

    }

    public function testSearch() {
        $admin = User::getByName('admin');

        //search hugo
        $this->doTestSearch('hugo', $admin, [$this->hugo->getFullpath()]);
        $this->doTestSearch('hugo', $this->userPermissionTest1, []);
        $this->doTestSearch('hugo', $this->userPermissionTest2, []);

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

        //search hidden object
        $this->doTestSearch('hiddenobject', $admin, [$this->hiddenobject->getFullpath()]);
        $this->doTestSearch('hiddenobject', $this->userPermissionTest1, []);
        $this->doTestSearch('hiddenobject', $this->userPermissionTest2, []);

    }

    public function testManyElementSearch() {
        $admin = User::getByName('admin');

        //prepare additional data
        $manyElements = $this->createFolder('manyElements', 1);
        $manyElementList = [];
        $elementCount = 100;

        for($i = 1; $i <= $elementCount; $i++) {
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


        //search hugo
        $this->doTestSearch('manyelement', $admin, array_merge(
                array_map(function($item) { return $item->getFullpath(); }, $manyElementList),
                [ $manyElementX->getFullpath() ]
            ), $elementCount + 1
        );
        $this->doTestSearch('manyelement', $this->userPermissionTest1, [$manyElementX->getFullpath()], $elementCount + 1);
        $this->doTestSearch('manyelement', $this->userPermissionTest2, [$manyElementX->getFullpath()], $elementCount + 1);

        //TODO this needs to be fixed, see issue https://github.com/pimcore/pimcore/issues/11822
//        $this->doTestSearch('manyelement', $this->userPermissionTest1, [$manyElementX->getFullpath()], $elementCount);
//        $this->doTestSearch('manyelement', $this->userPermissionTest2, [$manyElementX->getFullpath()], $elementCount);


    }

}

