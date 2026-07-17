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
 * "/perm-test/Car" and one configured on the string-prefix sibling "/perm-test/Carpets" must
 * stay isolated from each other, even though "/perm-test/Car" is a raw prefix of
 * "/perm-test/Carpets". This guards against a regression where LOCATE()-based matching ignored
 * the path boundary in either direction.
 *
 * @group model.element.permission
 */
class WorkspacePermissionPathBoundaryTest extends ModelTestCase
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

    /**
     * Ancestor direction (Element\Dao::InheritingPermission via isInheritingPermission):
     * a workspace on "/perm-test/Car" must not inherit down onto the sibling "/perm-test/Carpets",
     * whose path shares the "/perm-test/Car" prefix.
     */
    public function testAncestorMatchDoesNotLeakToPrefixSibling(): void
    {
        [$root, $car, $carpets, $rug] = $this->createTree();
        $user = $this->createUserWithObjectWorkspace($car);
        $userIds = array_map(intval(...), array_merge($user->getRoles(), [$user->getId()]));

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
    }

    /**
     * Traversal / descendant direction (AbstractObject\Dao::getChildAmount EXISTS subquery):
     * a workspace on the longer sibling "/perm-test/Carpets" must not make the shorter
     * prefix-sibling "/perm-test/Car" appear as a listable child of "/perm-test". This is the
     * reverse of the ancestor case: "/perm-test/Car" is a raw prefix of "/perm-test/Carpets".
     */
    public function testTraversalMatchDoesNotLeakToPrefixSibling(): void
    {
        [$root, $car, $carpets] = $this->createTree();
        $user = $this->createUserWithObjectWorkspace($carpets);

        $listableChildren = $root->getDao()->getChildAmount([DataObject::OBJECT_TYPE_FOLDER], $user);

        $this->assertSame(
            1,
            $listableChildren,
            'workspace on /perm-test/Carpets must count only "Carpets", not the prefix-sibling "Car"'
        );
    }

    /**
     * @return array{0: DataObject\Folder, 1: DataObject\Folder, 2: DataObject\Folder, 3: DataObject\Folder}
     */
    private function createTree(): array
    {
        $root = $this->createFolder('perm-test', 1);
        $car = $this->createFolder('Car', $root->getId());
        $carpets = $this->createFolder('Carpets', $root->getId());
        $rug = $this->createFolder('rug', $carpets->getId());

        return [$root, $car, $carpets, $rug];
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
        $this->createdRoles[] = $role;

        $user = new User();
        $user->setParentId(0);
        $user->setName('perm_boundary_user_' . uniqid());
        $user->setActive(true);
        $user->setAdmin(false);
        $user->setPermissions(['objects']);
        $user->setRoles([$role->getId()]);
        $user->save();
        $this->createdUsers[] = $user;

        return $user;
    }
}
