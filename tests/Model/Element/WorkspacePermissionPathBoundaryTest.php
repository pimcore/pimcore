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

use Pimcore\Model\DataObject;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Workspace permissions are matched against the element path. A workspace configured on
 * "/perm-test/Car" must NOT apply to the sibling subtree "/perm-test/Carpets", even though
 * the string "/perm-test/Car" is a raw prefix of "/perm-test/Carpets". This guards against a
 * regression where LOCATE()-based matching ignored the path boundary.
 *
 * @group model.element.permission
 */
class WorkspacePermissionPathBoundaryTest extends ModelTestCase
{
    private ?User $user = null;

    private ?User\Role $role = null;

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        $this->user?->delete();
        $this->role?->delete();
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testWorkspaceDoesNotLeakToPrefixSiblingSubtree(): void
    {
        $root = $this->createFolder('perm-test', 1);
        $car = $this->createFolder('Car', $root->getId());          // workspace target
        $carpets = $this->createFolder('Carpets', $root->getId());  // string-prefix sibling
        $rug = $this->createFolder('rug', $carpets->getId());       // nested under the sibling

        $user = $this->createUserWithObjectWorkspace($car);
        $userIds = array_map(intval(...), array_merge($user->getRoles(), [$user->getId()]));

        // --- ancestor direction (Element\Dao::InheritingPermission via isInheritingPermission) ---
        $this->assertSame(
            1,
            $car->getDao()->isInheritingPermission('list', $userIds),
            'control: the workspace element itself must resolve list=1'
        );
        $this->assertSame(
            0,
            $carpets->getDao()->isInheritingPermission('list', $userIds),
            'workspace on /perm-test/Car must NOT grant list on the sibling /perm-test/Carpets'
        );
        $this->assertSame(
            0,
            $rug->getDao()->isInheritingPermission('list', $userIds),
            'workspace on /perm-test/Car must NOT grant list on /perm-test/Carpets/rug'
        );

        // --- traversal direction (AbstractObject\Dao::getChildAmount EXISTS subquery) ---
        // As the workspace user, expanding /perm-test must list "Car" (own workspace) but NOT the
        // sibling "Carpets" (no workspace at or below it).
        $listableChildren = $root->getDao()->getChildAmount(
            [DataObject::OBJECT_TYPE_FOLDER],
            $user
        );
        $this->assertSame(
            1,
            $listableChildren,
            'tree expand must count only "Car", not the prefix-sibling "Carpets"'
        );
    }

    private function createFolder(string $key, int $parentId): DataObject\Folder
    {
        $folder = new DataObject\Folder();
        $folder->setParentId($parentId);
        $folder->setKey($key);
        $folder->save();

        return $folder;
    }

    private function createUserWithObjectWorkspace(DataObject\AbstractObject $workspaceTarget): User
    {
        $role = new User\Role();
        $role->setParentId(0);
        $role->setName('perm_boundary_role_' . uniqid());
        $role->setPermissions(['objects']);

        $workspace = new User\Workspace\DataObject();
        $workspace->setCid($workspaceTarget->getId());
        $workspace->setCpath($workspaceTarget->getRealFullPath());
        $workspace->setList(true);
        $workspace->setView(true);
        $role->setWorkspacesObject([$workspace]);
        $role->save();
        $this->role = $role;

        $user = new User();
        $user->setParentId(0);
        $user->setName('perm_boundary_user_' . uniqid());
        $user->setActive(true);
        $user->setAdmin(false);
        $user->setPermissions(['objects']);
        $user->setRoles([$role->getId()]);
        $user->save();
        $this->user = $user;

        return $user;
    }
}
