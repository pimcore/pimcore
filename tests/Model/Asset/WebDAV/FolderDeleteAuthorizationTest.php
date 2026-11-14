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

namespace Pimcore\Tests\Model\Asset\WebDAV;

use Pimcore;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\WebDAV\Folder;
use Pimcore\Model\Asset\WebDAV\Tree;
use Pimcore\Model\User;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionProperty;
use Sabre\DAV\Exception\Forbidden;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Asset::delete() recursively removes an entire subtree without checking each descendant's own
 * workspace permissions. Folder::delete() therefore has to verify every descendant's "delete"
 * permission itself, before any mutation happens, instead of only checking the requested node.
 *
 * @group model.asset.webdav
 */
class FolderDeleteAuthorizationTest extends ModelTestCase
{
    private ?User $createdUser = null;

    private ?User\Role $createdRole = null;

    private ?TokenInterface $originalToken = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalToken = $this->tokenStorage()->getToken();
    }

    protected function tearDown(): void
    {
        $this->tokenStorage()->setToken($this->originalToken);

        $this->createdUser?->delete();
        $this->createdRole?->delete();

        parent::tearDown();
    }

    public function testRecursiveDeleteIsForbiddenWhenANestedChildDeniesDeletePermission(): void
    {
        $parent = TestHelper::createAssetFolder('webdav-delete-parent-');
        $child = $this->createFolder($parent, 'child-' . uniqid());
        $nestedFile = $this->createFile($child, 'secret.png');

        $this->loginAs($this->createUserWithWorkspaces([
            ['asset' => $parent, 'delete' => true],
            ['asset' => $child, 'delete' => false],
        ]));

        try {
            $this->resolveNode($parent)->delete();
            $this->fail('Expected a Forbidden exception because a nested child denies delete permission.');
        } catch (Forbidden $e) {
            // expected
        }

        $this->assertNotNull(Asset::getById($parent->getId()));
        $this->assertNotNull(Asset::getById($child->getId()));
        $this->assertNotNull(Asset::getById($nestedFile->getId()));
    }

    public function testDirectDeleteOfTheDeniedChildIsForbidden(): void
    {
        $parent = TestHelper::createAssetFolder('webdav-delete-parent-');
        $child = $this->createFolder($parent, 'child-' . uniqid());

        $this->loginAs($this->createUserWithWorkspaces([
            ['asset' => $parent, 'delete' => true],
            ['asset' => $child, 'delete' => false],
        ]));

        try {
            $this->resolveNode($child)->delete();
            $this->fail('Expected a Forbidden exception because the child itself denies delete permission.');
        } catch (Forbidden $e) {
            // expected
        }

        $this->assertNotNull(Asset::getById($parent->getId()));
        $this->assertNotNull(Asset::getById($child->getId()));
    }

    public function testRecursiveDeleteSucceedsWhenTheEntireSubtreeIsDeletable(): void
    {
        $parent = TestHelper::createAssetFolder('webdav-delete-parent-');
        $child = $this->createFolder($parent, 'child-' . uniqid());
        $nestedFile = $this->createFile($child, 'ok.png');

        $this->loginAs($this->createUserWithWorkspaces([
            ['asset' => $parent, 'delete' => true],
            ['asset' => $child, 'delete' => true],
        ]));

        $parentId = $parent->getId();
        $childId = $child->getId();
        $nestedFileId = $nestedFile->getId();

        $this->resolveNode($parent)->delete();

        $this->assertNull(Asset::getById($parentId));
        $this->assertNull(Asset::getById($childId));
        $this->assertNull(Asset::getById($nestedFileId));
    }

    public function testRecursiveDeleteIsForbiddenWhenADeniedGrandchildExists(): void
    {
        $parent = TestHelper::createAssetFolder('webdav-delete-parent-');
        $child = $this->createFolder($parent, 'child-' . uniqid());
        $grandchild = $this->createFolder($child, 'grandchild-' . uniqid());
        $nestedFile = $this->createFile($grandchild, 'secret.png');

        $this->loginAs($this->createUserWithWorkspaces([
            ['asset' => $parent, 'delete' => true],
            ['asset' => $child, 'delete' => true],
            ['asset' => $grandchild, 'delete' => false],
        ]));

        try {
            $this->resolveNode($parent)->delete();
            $this->fail('Expected a Forbidden exception because a denied grandchild exists.');
        } catch (Forbidden $e) {
            // expected
        }

        $this->assertNotNull(Asset::getById($parent->getId()));
        $this->assertNotNull(Asset::getById($child->getId()));
        $this->assertNotNull(Asset::getById($grandchild->getId()));
        $this->assertNotNull(Asset::getById($nestedFile->getId()));
    }

    private function createFolder(Asset $parent, string $filename): Asset\Folder
    {
        $folder = new Asset\Folder();
        $folder->setParent($parent);
        $folder->setFilename($filename);
        $folder->save();

        return $folder;
    }

    private function createFile(Asset $parent, string $filename): Asset
    {
        $asset = new Asset();
        $asset->setParent($parent);
        $asset->setFilename($filename);
        $asset->setData('some content');
        $asset->save();

        return $asset;
    }

    /**
     * @param array<int, array{asset: Asset, delete: bool}> $workspaceSpecs
     */
    private function createUserWithWorkspaces(array $workspaceSpecs): User
    {
        $workspaces = [];
        foreach ($workspaceSpecs as $spec) {
            $workspace = new AssetWorkspace();
            $workspace->setCid($spec['asset']->getId());
            $workspace->setCpath($spec['asset']->getRealFullPath());
            $workspace->setList(true);
            $workspace->setView(true);
            $workspace->setDelete($spec['delete']);
            $workspaces[] = $workspace;
        }

        $role = new User\Role();
        $role->setParentId(0);
        $role->setName('webdav_delete_role_' . uniqid());
        $role->setPermissions(['assets']);
        $role->setWorkspacesAsset($workspaces);
        $role->save();
        $this->createdRole = $role;

        $user = new User();
        $user->setParentId(0);
        $user->setName('webdav_delete_user_' . uniqid());
        $user->setActive(true);
        $user->setAdmin(false);
        $user->setPermissions(['assets']);
        $user->setRoles([$role->getId()]);
        $user->save();
        $this->createdUser = $user;

        return $user;
    }

    private function resolveNode(Asset $asset): Folder
    {
        $tree = new Tree(new Folder(Asset::getById(1)));
        $node = $tree->getNodeForPath(ltrim($asset->getRealFullPath(), '/'));

        $this->assertInstanceOf(Folder::class, $node);

        return $node;
    }

    private function loginAs(User $user): void
    {
        $this->tokenStorage()->setToken(new UsernamePasswordToken(new SecurityUser($user), 'pimcore_admin'));
    }

    private function tokenStorage(): TokenStorageInterface
    {
        // security.token_storage is inlined out of the compiled container and cannot be fetched by
        // id. Asset::isAllowed() resolves the current user via Admin::getCurrentUser(), which reads
        // the token storage held by the public TokenStorageUserResolver service, so we reach the
        // same shared instance through that resolver rather than replacing the service.
        $resolver = Pimcore::getContainer()->get(TokenStorageUserResolver::class);

        return (new ReflectionProperty(TokenStorageUserResolver::class, 'tokenStorage'))->getValue($resolver);
    }
}
