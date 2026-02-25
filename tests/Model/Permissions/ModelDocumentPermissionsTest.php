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

use Codeception\Stub;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ModelDocumentPermissionsTest extends ModelTestCase
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
     * /permissioncpath --> not specified
     * /permissioncpath/a --> not specified
     * /permissioncpath/a/b --> not specified
     * /permissioncpath/a/b/c --> allowed
     * /permissioncpath/abcdefghjkl --> allowed
     *
     * -- only for many elements search test
     * /manyElemnents --> not allowed
     * /manyElements/manyelement 1
     * ...
     * /manyElements/manyelement 100
     * /manyElements/manyelement X --> allowed
     *
     */
    protected Document\Folder $permissionfoo;

    protected Document\Folder $permissionbar;

    protected Document\Folder $foo;

    protected Document\Folder $bar;

    protected Document\Folder $bars;

    protected Document\Folder $userfolder;

    protected Document\Folder $groupfolder;

    protected Document $hiddenobject;

    protected Document $hugo;

    protected Document $usertestobject;

    protected Document $grouptestobject;

    protected Document\Folder $a;

    protected Document\Folder $b;

    protected Document $c;

    protected Document $abcdefghjkl;

    protected function prepareObjectTree(): void
    {
        //example based on https://github.com/pimcore/pimcore/issues/11540
        $this->permissioncpath = $this->createFolder('permissioncpath', 1);
        $this->a = $this->createFolder('a', $this->permissioncpath->getId());
        $this->b = $this->createFolder('b', $this->a->getId());
        $this->c = $this->createPage('c', $this->b->getId());
        $this->abcdefghjkl = $this->createPage('abcdefghjkl', $this->permissioncpath->getId());

        $this->permissionfoo = $this->createFolder('permissionfoo', 1);
        $this->permissionbar = $this->createFolder('permissionbar', 1);
        $this->foo = $this->createFolder('foo', $this->permissionbar->getId());
        $this->bars = $this->createFolder('bars', $this->permissionfoo->getId());
        $this->userfolder = $this->createFolder('userfolder', $this->bars->getId());
        $this->groupfolder = $this->createFolder('groupfolder', $this->bars->getId());

        $this->hiddenobject = $this->createPage('hiddenobject', $this->foo->getId());
        $this->hugo = $this->createPage('hugo', $this->bars->getId());
        $this->usertestobject = $this->createPage('usertestobject', $this->userfolder->getId());
        $this->grouptestobject = $this->createPage('grouptestobject', $this->groupfolder->getId());
    }

    protected function createFolder(string $key, int $parentId): Document\Folder
    {
        $folder = new Document\Folder();
        $folder->setKey($key);
        $folder->setParentId($parentId);
        $folder->save();

        $searchEntry = new Search\Backend\Data($folder);
        $searchEntry->save();

        return $folder;
    }

    protected function createPage(string $key, int $parentId): Document
    {
        $document =  new Page();

        $document->setKey($key);
        $document->setParentId($parentId);
        $document->setPublished(true);

        $document->save();

        $searchEntry = new Search\Backend\Data($document);
        $searchEntry->save();

        return $document;
    }

    protected function prepareUsers(): void
    {
        //create role
        $role = new User\Role();
        $role->setName('Testrole');
        $role->setWorkspacesDocument([
            (new User\Workspace\Document())->setValues(['cId' => $this->groupfolder->getId(), 'cPath' => $this->groupfolder->getFullpath(), 'list' => true, 'view' => true, 'save'=>true, 'publish'=>false]),
        ]);
        $role->save();

        $role2 = new User\Role();
        $role2->setName('dummyRole');
        $role2->setWorkspacesDocument([
            (new User\Workspace\Document())->setValues(['cId' => $this->groupfolder->getId(), 'cPath' => $this->groupfolder->getFullpath(), 'list' => false, 'view' => false, 'save'=>false, 'publish'=>false, 'unpublish' => true ]),
        ]);
        $role2->save();

        //create user 1
        $this->userPermissionTest1 = new User();
        $this->userPermissionTest1->setName('Permissiontest1');
        $this->userPermissionTest1->setPermissions(['documents']);
        $this->userPermissionTest1->setRoles([$role->getId(), $role2->getId()]);
        $this->userPermissionTest1->setWorkspacesDocument([
            (new User\Workspace\Document())->setValues(['cId' => $this->permissionfoo->getId(), 'cPath' => $this->permissionfoo->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\Document())->setValues(['cId' => $this->permissionbar->getId(), 'cPath' => $this->permissionbar->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\Document())->setValues(['cId' => $this->foo->getId(), 'cPath' => $this->foo->getFullpath(), 'list' => false, 'view' => false]),
            (new User\Workspace\Document())->setValues(['cId' => $this->bars->getId(), 'cPath' => $this->bars->getFullpath(), 'list' => false, 'view' => false]),
            (new User\Workspace\Document())->setValues(['cId' => $this->userfolder->getId(), 'cPath' => $this->userfolder->getFullpath(), 'list' => true, 'view' => true, 'create'=> true, 'rename'=> true]),
            (new User\Workspace\Document())->setValues(['cId' => $this->c->getId(), 'cPath' => $this->c->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\Document())->setValues(['cId' => $this->abcdefghjkl->getId(), 'cPath' => $this->abcdefghjkl->getFullpath(), 'list' => true, 'view' => true]),
        ]);
        $this->userPermissionTest1->save();

        //create user 2
        $this->userPermissionTest2 = new User();
        $this->userPermissionTest2->setName('Permissiontest2');
        $this->userPermissionTest2->setPermissions(['documents']);
        $this->userPermissionTest2->setRoles([$role->getId(), $role2->getId()]);
        $this->userPermissionTest2->setWorkspacesDocument([
            (new User\Workspace\Document())->setValues(['cId' => $this->permissionfoo->getId(), 'cPath' => $this->permissionfoo->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\Document())->setValues(['cId' => $this->permissionbar->getId(), 'cPath' => $this->permissionbar->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\Document())->setValues(['cId' => $this->foo->getId(), 'cPath' => $this->foo->getFullpath(), 'list' => false, 'view' => false]),
            (new User\Workspace\Document())->setValues(['cId' => $this->bars->getId(), 'cPath' => $this->bars->getFullpath(), 'list' => false, 'view' => false]),
            (new User\Workspace\Document())->setValues(['cId' => $this->userfolder->getId(), 'cPath' => $this->userfolder->getFullpath(), 'list' => true, 'view' => true]),
            (new User\Workspace\Document())->setValues(['cId' => $this->groupfolder->getId(), 'cPath' => $this->groupfolder->getFullpath(), 'list' => false, 'view' => false, 'save'=>true, 'publish'=>true, 'unpublish' => false]),
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
        User\Role::getByName('Dummyrole')->delete();
    }

    protected function doHasChildrenTest(Document $element, bool $resultAdmin, bool $resultPermissionTest1, bool $resultPermissionTest2): void
    {
        $admin = User::getByName('admin');

        $this->assertEquals(
            $resultAdmin,
            $element->getDao()->hasChildren(true, $admin),
            'Has children of `' . $element->getFullpath() . '` for user admin'
        );

        $this->assertEquals(
            $resultPermissionTest1,
            $element->getDao()->hasChildren(true, $this->userPermissionTest1),
            'Has children of `' . $element->getFullpath() . '` for user UserPermissionTest1'
        );

        $this->assertEquals(
            $resultPermissionTest2,
            $element->getDao()->hasChildren(true, $this->userPermissionTest2),
            'Has children of `' . $element->getFullpath() . '` for user UserPermissionTest2'
        );
    }

    public function testHasChildren(): void
    {
        $this->doHasChildrenTest($this->a, true, true, false); //didn't work before
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

    protected function doIsAllowedTest(Document $element, string $type, bool $resultAdmin, bool $resultPermissionTest1, bool $resultPermissionTest2): void
    {
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

    public function testIsAllowed(): void
    {
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

        $this->doIsAllowedTest($this->hiddenobject, 'list', true, false, false);
        $this->doIsAllowedTest($this->hiddenobject, 'view', true, false, false);
    }

    protected function doAreAllowedTest(Document $element, User $user, array $expectedPermissions): void
    {
        $calculatedPermissions = $element->getUserPermissions($user);

        foreach ($expectedPermissions as $type => $expectedPermission) {
            $this->assertEquals(
                $expectedPermission,
                $calculatedPermissions[$type],
                sprintf('Expected permission does not match for element %s for user %s', $element->getFullpath(), $user->getName())
            );
        }
    }

    public function testAreAllowed(): void
    {
        $admin = User::getByName('admin');

        //check permissions of groupfolder (directly defined) and grouptestobject.gif (inherited)
        foreach ([$this->groupfolder, $this->grouptestobject] as $element) {
            $this->doAreAllowedTest($element, $admin,
                [
                    'save' => 1,
                    'delete' => 1,
                    'publish' => 1,
                    'unpublish' => 1,
                    'versions' => 1,
                ]
            );
            $this->doAreAllowedTest($element, $this->userPermissionTest1,
                [
                    'save' => 1,
                    'delete' => 0,
                    'publish' => 0,
                    'unpublish' => 1,
                    'versions' => 0,
                ]
            );
            $this->doAreAllowedTest($element, $this->userPermissionTest2,
                [
                    'save' => 1,
                    'delete' => 0,
                    'publish' => 1,
                    'unpublish' => 0,
                    'versions' => 0,
                ]
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

}
