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
use Pimcore\Model\Asset\Folder as AssetFolder;
use Pimcore\Model\Asset\WebDAV\Folder;
use Pimcore\Model\Asset\WebDAV\Tree;
use Pimcore\Model\User;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionProperty;
use Sabre\DAV\Exception\Forbidden;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Regression test for GHSA-48m3-c7gg-xw8p: a WebDAV cross-directory MOVE must authorize the source
 * asset against its original (pre-move) parent chain before its path/parent are mutated. Without
 * this, a user who is denied view/delete on the source folder but holds create/view/publish on the
 * destination folder could relocate the asset and then read and replace it under the destination's
 * permissions.
 *
 * @group model.asset.webdav
 */
class TreeMoveCrossDirectoryPermissionTest extends ModelTestCase
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

    public function testCrossDirectoryMoveIsForbiddenWhenSourceDeniesViewAndDelete(): void
    {
        $sourceFolder = $this->createFolder('cross-move-private-');
        $destinationFolder = $this->createFolder('cross-move-workspace-');
        $asset = $this->createFile($sourceFolder, 'secret.txt', 'confidential-bytes');

        $this->loginAs($this->createUserWithWorkspaces([
            $this->workspace($sourceFolder, view: false, delete: false, publish: false),
            $this->workspace($destinationFolder, create: true, view: true, publish: true),
        ]));

        try {
            $this->createTree()->move(
                ltrim($asset->getRealFullPath(), '/'),
                ltrim($destinationFolder->getRealFullPath(), '/') . '/' . $asset->getFilename()
            );
            $this->fail('Expected a Forbidden exception because the user lacks view/delete on the source folder.');
        } catch (Forbidden $e) {
            // expected
        }

        $reloaded = Asset::getById($asset->getId(), ['force' => true]);
        $this->assertSame(
            $sourceFolder->getId(),
            $reloaded->getParentId(),
            'the asset must stay in its original folder when the source-side permission check fails'
        );
        $this->assertSame('confidential-bytes', $reloaded->getData());
    }

    public function testCrossDirectoryMoveIsForbiddenWhenDestinationDeniesPublish(): void
    {
        $sourceFolder = $this->createFolder('cross-move-source-');
        $destinationFolder = $this->createFolder('cross-move-no-publish-');
        $asset = $this->createFile($sourceFolder, 'document.txt', 'some content');

        $this->loginAs($this->createUserWithWorkspaces([
            $this->workspace($sourceFolder, view: true, delete: true),
            $this->workspace($destinationFolder, create: true, publish: false),
        ]));

        try {
            $this->createTree()->move(
                ltrim($asset->getRealFullPath(), '/'),
                ltrim($destinationFolder->getRealFullPath(), '/') . '/' . $asset->getFilename()
            );
            $this->fail('Expected a Forbidden exception because the user lacks publish on the destination.');
        } catch (Forbidden $e) {
            // expected
        }

        $reloaded = Asset::getById($asset->getId(), ['force' => true]);
        $this->assertSame(
            $sourceFolder->getId(),
            $reloaded->getParentId(),
            'the asset must stay in its original folder when the destination publish check fails'
        );
    }

    public function testCrossDirectoryMoveSucceedsWithSourceAndDestinationPermissionsGranted(): void
    {
        $sourceFolder = $this->createFolder('cross-move-allowed-source-');
        $destinationFolder = $this->createFolder('cross-move-allowed-destination-');
        $asset = $this->createFile($sourceFolder, 'document.txt', 'some content');

        $this->loginAs($this->createUserWithWorkspaces([
            $this->workspace($sourceFolder, view: true, delete: true),
            $this->workspace($destinationFolder, create: true, publish: true),
        ]));

        $this->createTree()->move(
            ltrim($asset->getRealFullPath(), '/'),
            ltrim($destinationFolder->getRealFullPath(), '/') . '/' . $asset->getFilename()
        );

        $reloaded = Asset::getById($asset->getId(), ['force' => true]);
        $this->assertSame(
            $destinationFolder->getId(),
            $reloaded->getParentId(),
            'a user with view/delete on the source and create/publish on the destination must still be able to move the asset'
        );
        $this->assertSame('some content', $reloaded->getData());
    }

    private function createFolder(string $prefix): AssetFolder
    {
        $folder = new AssetFolder();
        $folder->setParent(Asset::getByPath('/'));
        $folder->setFilename($prefix . uniqid());
        $folder->save();

        return $folder;
    }

    private function createFile(Asset $parent, string $filename, string $data): Asset
    {
        $asset = new Asset();
        $asset->setParent($parent);
        $asset->setFilename($filename);
        $asset->setData($data);
        $asset->save();

        return $asset;
    }

    private function workspace(
        Asset $asset,
        bool $list = true,
        bool $view = false,
        bool $publish = false,
        bool $delete = false,
        bool $create = false
    ): AssetWorkspace {
        $workspace = new AssetWorkspace();
        $workspace->setCid($asset->getId());
        $workspace->setCpath($asset->getRealFullPath());
        $workspace->setList($list);
        $workspace->setView($view);
        $workspace->setPublish($publish);
        $workspace->setDelete($delete);
        $workspace->setCreate($create);

        return $workspace;
    }

    /**
     * @param AssetWorkspace[] $workspaces
     */
    private function createUserWithWorkspaces(array $workspaces): User
    {
        $role = new User\Role();
        $role->setParentId(0);
        $role->setName('webdav_cross_move_role_' . uniqid());
        $role->setPermissions(['assets']);
        $role->setWorkspacesAsset($workspaces);
        $role->save();
        $this->createdRole = $role;

        $user = new User();
        $user->setParentId(0);
        $user->setName('webdav_cross_move_user_' . uniqid());
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
        // id. Tree::move() resolves the current user via Admin::getCurrentUser(), which reads the
        // token storage held by the public TokenStorageUserResolver service, so we reach the same
        // shared instance through that resolver rather than replacing the service.
        $resolver = Pimcore::getContainer()->get(TokenStorageUserResolver::class);

        return (new ReflectionProperty(TokenStorageUserResolver::class, 'tokenStorage'))->getValue($resolver);
    }
}
