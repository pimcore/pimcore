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
use Pimcore\Model\Document;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Workspace permissions are matched against the element path. A workspace on "/root/Car" and one
 * on the string-prefix sibling "/root/Carpets" must stay isolated, even though "/root/Car" is a
 * raw prefix of "/root/Carpets". This guards against a regression where LOCATE()-based matching
 * ignored the "/" path boundary — in both the ancestor direction (Element\Dao::InheritingPermission)
 * and the descendant/traversal direction (getChildAmount / accessible-listing subqueries for
 * objects, assets and documents).
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
     * Ancestor direction (Element\Dao::InheritingPermission, shared by all element types).
     * Verifies both the boundary exclusion and — importantly — that the ancestor-path expansion
     * still grants permission to a genuine descendant (so an implementation that matched only the
     * element itself would fail).
     */
    public function testAncestorMatchIsBoundaryCorrect(): void
    {
        [$root, $car, $carpets, $rug] = $this->createObjectTree();

        // Workspace on "/root/Car": must not leak onto the prefix-sibling "/root/Carpets" subtree.
        $carUser = $this->objectUser($car);
        $carIds = $this->userIds($carUser);
        $this->assertSame(1, $car->getDao()->isInheritingPermission('list', $carIds), 'control: workspace element itself resolves list=1');
        $this->assertSame(0, $carpets->getDao()->isInheritingPermission('list', $carIds), 'must not grant list on the prefix-sibling /root/Carpets');
        $this->assertSame(0, $rug->getDao()->isInheritingPermission('list', $carIds), 'must not grant list on /root/Carpets/rug');

        // Workspace on "/root/Carpets": must grant list on the genuine descendant "rug"
        // (ancestor-path expansion), while still not reaching the prefix-sibling "/root/Car".
        $carpetsUser = $this->objectUser($carpets);
        $carpetsIds = $this->userIds($carpetsUser);
        $this->assertSame(1, $rug->getDao()->isInheritingPermission('list', $carpetsIds), 'must grant list on the genuine descendant /root/Carpets/rug');
        $this->assertSame(0, $car->getDao()->isInheritingPermission('list', $carpetsIds), 'must not grant list on the prefix-sibling /root/Car');
    }

    /**
     * Traversal direction for DataObjects (AbstractObject\Dao::getChildAmount).
     * The workspace is placed on the *nested* "rug" so the match on "Carpets" goes through the
     * slash-bounded LOCATE arm (not the equality arm), proving that legitimate descendant
     * traversal is preserved while the prefix-sibling "Car" is excluded.
     */
    public function testObjectChildAmountIsBoundaryCorrect(): void
    {
        [$root, $car, $carpets, $rug] = $this->createObjectTree();
        $user = $this->objectUser($rug);

        $this->assertSame(
            1,
            $root->getDao()->getChildAmount([DataObject::OBJECT_TYPE_FOLDER], $user),
            'only "Carpets" (ancestor of the workspace) is listable, not the prefix-sibling "Car"'
        );
    }

    /**
     * Traversal direction for Assets (Asset\Dao::getChildAmount).
     */
    public function testAssetChildAmountIsBoundaryCorrect(): void
    {
        [$root, $car, $carpets, $rug] = $this->createAssetTree();
        $user = $this->assetUser($rug);

        $this->assertSame(1, $root->getDao()->getChildAmount($user), 'only "Carpets" is listable, not the prefix-sibling "Car"');
    }

    /**
     * Accessible-listing path for Assets (Asset\Listing::filterAccessibleByUser) — a separate
     * traversal SQL site from the DAO.
     */
    public function testAssetAccessibleListingIsBoundaryCorrect(): void
    {
        [$root, $car, $carpets, $rug] = $this->createAssetTree();
        $user = $this->assetUser($rug);

        $listing = new Asset\Listing();
        $listing->addConditionParam('parentId = ?', [$root->getId()]);
        $listing->filterAccessibleByUser($user, $root);

        $this->assertSame(1, count($listing->load()), 'accessible listing returns only "Carpets", not the prefix-sibling "Car"');
    }

    /**
     * Traversal direction for Documents (Document\Dao::getChildAmount).
     */
    public function testDocumentChildAmountIsBoundaryCorrect(): void
    {
        [$root, $car, $carpets, $rug] = $this->createDocumentTree();
        $user = $this->documentUser($rug);

        $this->assertSame(1, $root->getDao()->getChildAmount($user), 'only "Carpets" is listable, not the prefix-sibling "Car"');
    }

    // ------------------------------------------------------------------ fixtures

    /**
     * @return array{0: DataObject\Folder, 1: DataObject\Folder, 2: DataObject\Folder, 3: DataObject\Folder}
     */
    private function createObjectTree(): array
    {
        $root = $this->objectFolder('perm-test', 1);
        $car = $this->objectFolder('Car', $root->getId());
        $carpets = $this->objectFolder('Carpets', $root->getId());
        $rug = $this->objectFolder('rug', $carpets->getId());

        return [$root, $car, $carpets, $rug];
    }

    /**
     * @return array{0: Asset\Folder, 1: Asset\Folder, 2: Asset\Folder, 3: Asset\Folder}
     */
    private function createAssetTree(): array
    {
        $root = $this->assetFolder('perm-test', 1);
        $car = $this->assetFolder('Car', $root->getId());
        $carpets = $this->assetFolder('Carpets', $root->getId());
        $rug = $this->assetFolder('rug', $carpets->getId());

        return [$root, $car, $carpets, $rug];
    }

    /**
     * @return array{0: Document\Folder, 1: Document\Folder, 2: Document\Folder, 3: Document\Folder}
     */
    private function createDocumentTree(): array
    {
        $root = $this->documentFolder('perm-test', 1);
        $car = $this->documentFolder('Car', $root->getId());
        $carpets = $this->documentFolder('Carpets', $root->getId());
        $rug = $this->documentFolder('rug', $carpets->getId());

        return [$root, $car, $carpets, $rug];
    }

    private function objectFolder(string $key, int $parentId): DataObject\Folder
    {
        $folder = new DataObject\Folder();
        $folder->setParentId($parentId);
        $folder->setKey($key);
        $folder->save();

        return $folder;
    }

    private function assetFolder(string $key, int $parentId): Asset\Folder
    {
        $folder = new Asset\Folder();
        $folder->setParentId($parentId);
        $folder->setFilename($key);
        $folder->save();

        return $folder;
    }

    private function documentFolder(string $key, int $parentId): Document\Folder
    {
        $folder = new Document\Folder();
        $folder->setParentId($parentId);
        $folder->setKey($key);
        $folder->save();

        return $folder;
    }

    private function objectUser(DataObject\AbstractObject $target): User
    {
        return $this->makeUser('objects', function (User\Role $role) use ($target): void {
            $role->setWorkspacesObject([$this->workspace(new User\Workspace\DataObject(), $target)]);
        });
    }

    private function assetUser(Asset $target): User
    {
        return $this->makeUser('assets', function (User\Role $role) use ($target): void {
            $role->setWorkspacesAsset([$this->workspace(new User\Workspace\Asset(), $target)]);
        });
    }

    private function documentUser(Document $target): User
    {
        return $this->makeUser('documents', function (User\Role $role) use ($target): void {
            $role->setWorkspacesDocument([$this->workspace(new User\Workspace\Document(), $target)]);
        });
    }

    private function workspace(User\Workspace\AbstractWorkspace $workspace, AbstractElement $target): User\Workspace\AbstractWorkspace
    {
        $workspace->setCid($target->getId());
        $workspace->setCpath($target->getRealFullPath());
        $workspace->setList(true);
        $workspace->setView(true);

        return $workspace;
    }

    private function makeUser(string $permission, callable $assignWorkspace): User
    {
        $role = new User\Role();
        $role->setParentId(0);
        $role->setName('perm_boundary_role_' . uniqid());
        $role->setPermissions([$permission]);
        $assignWorkspace($role);
        $role->save();
        $this->createdRoles[] = $role;

        $user = new User();
        $user->setParentId(0);
        $user->setName('perm_boundary_user_' . uniqid());
        $user->setActive(true);
        $user->setAdmin(false);
        $user->setPermissions([$permission]);
        $user->setRoles([$role->getId()]);
        $user->save();
        $this->createdUsers[] = $user;

        return $user;
    }

    /**
     * @return int[]
     */
    private function userIds(User $user): array
    {
        return array_map(intval(...), array_merge($user->getRoles(), [$user->getId()]));
    }
}
