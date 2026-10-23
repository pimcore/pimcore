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
use Pimcore\Model\Asset\WebDAV\File;
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
 * Regression test for GHSA-5v9m-v6wp-7whc: Folder::getChild() is the direct node resolution that
 * PROPFIND and LOCK go through via Tree::getNodeForPath() (see that class's docblock), and it only
 * checked authentication, not per-asset authorization. An authenticated user whose workspace is
 * confined to one folder could still PROPFIND/LOCK a path outside it and learn the asset exists plus
 * read its metadata, even though Folder::getChildren() (folder listings) and File::get() (downloads)
 * already enforced "list"/"view" there.
 *
 * @group model.asset.webdav
 */
class FolderGetChildAuthorizationTest extends ModelTestCase
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

    public function testGetNodeForPathIsForbiddenForAFileOutsideTheUsersWorkspace(): void
    {
        $workspaceFolder = TestHelper::createAssetFolder('webdav-authz-workspace-');
        $outsideFile = $this->createFile(Asset::getByPath('/'), 'outside-file-' . uniqid() . '.txt');
        $this->loginAs($this->createUserWithWorkspace($workspaceFolder));

        try {
            $this->createTree()->getNodeForPath(ltrim($outsideFile->getRealFullPath(), '/'));
            $this->fail('Expected a Forbidden exception because the file is outside the user\'s workspace.');
        } catch (Forbidden $e) {
            // expected
        }
    }

    public function testGetNodeForPathIsForbiddenForAFolderOutsideTheUsersWorkspace(): void
    {
        $workspaceFolder = TestHelper::createAssetFolder('webdav-authz-workspace-');
        $outsideFolder = TestHelper::createAssetFolder('webdav-authz-outside-');
        $this->loginAs($this->createUserWithWorkspace($workspaceFolder));

        try {
            $this->createTree()->getNodeForPath(ltrim($outsideFolder->getRealFullPath(), '/'));
            $this->fail('Expected a Forbidden exception because the folder is outside the user\'s workspace.');
        } catch (Forbidden $e) {
            // expected
        }
    }

    public function testGetNodeForPathSucceedsForAFileInsideTheUsersWorkspace(): void
    {
        $workspaceFolder = TestHelper::createAssetFolder('webdav-authz-workspace-');
        $insideFile = $this->createFile($workspaceFolder, 'inside.txt');
        $this->loginAs($this->createUserWithWorkspace($workspaceFolder));

        $node = $this->createTree()->getNodeForPath(ltrim($insideFile->getRealFullPath(), '/'));

        $this->assertInstanceOf(File::class, $node);
    }

    public function testGetNodeForPathSucceedsForTheWorkspaceFolderItself(): void
    {
        $workspaceFolder = TestHelper::createAssetFolder('webdav-authz-workspace-');
        $this->loginAs($this->createUserWithWorkspace($workspaceFolder));

        $node = $this->createTree()->getNodeForPath(ltrim($workspaceFolder->getRealFullPath(), '/'));

        $this->assertInstanceOf(Folder::class, $node);
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

    private function createUserWithWorkspace(Asset $asset): User
    {
        $workspace = new AssetWorkspace();
        $workspace->setCid($asset->getId());
        $workspace->setCpath($asset->getRealFullPath());
        $workspace->setList(true);
        $workspace->setView(true);

        $role = new User\Role();
        $role->setParentId(0);
        $role->setName('webdav_authz_role_' . uniqid());
        $role->setPermissions(['assets']);
        $role->setWorkspacesAsset([$workspace]);
        $role->save();
        $this->createdRole = $role;

        $user = new User();
        $user->setParentId(0);
        $user->setName('webdav_authz_user_' . uniqid());
        $user->setActive(true);
        $user->setAdmin(false);
        $user->setPermissions(['assets']);
        $user->setRoles([$role->getId()]);
        $user->save();
        $this->createdUser = $user;

        return $user;
    }

    private function createTree(): Tree
    {
        return new Tree(new Folder(Asset::getById(1)));
    }

    private function loginAs(User $user): void
    {
        $this->tokenStorage()->setToken(new UsernamePasswordToken(new SecurityUser($user), 'pimcore_admin'));
    }

    private function tokenStorage(): TokenStorageInterface
    {
        // security.token_storage is inlined out of the compiled container and cannot be fetched by
        // id. Tree::getNodeForPath() resolves the current user via Admin::getCurrentUser(), which
        // reads the token storage held by the public TokenStorageUserResolver service, so we reach
        // the same shared instance through that resolver rather than replacing the service.
        $resolver = Pimcore::getContainer()->get(TokenStorageUserResolver::class);

        return (new ReflectionProperty(TokenStorageUserResolver::class, 'tokenStorage'))->getValue($resolver);
    }
}
