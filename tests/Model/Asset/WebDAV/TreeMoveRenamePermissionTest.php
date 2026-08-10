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
use ReflectionProperty;
use Sabre\DAV\Exception\Forbidden;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Regression test for GHSA-3hv6-4774-vpmf: a WebDAV same-directory MOVE (rename) must be gated by
 * the "rename" workspace permission, not just "publish". A user granted publish-but-not-rename on
 * an asset must not be able to rename it by issuing a same-directory MOVE.
 *
 * @group model.asset.webdav
 */
class TreeMoveRenamePermissionTest extends ModelTestCase
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

    public function testSameDirectoryMoveIsForbiddenWithoutRenamePermission(): void
    {
        $asset = $this->createAsset('rename-source-forbidden.txt');
        $this->loginAs($this->createUserWithWorkspace($asset, publish: true, rename: false));

        try {
            $this->createTree()->move($asset->getFilename(), 'rename-target-forbidden.txt');
            $this->fail('Expected a Forbidden exception because the user lacks the "rename" permission.');
        } catch (Forbidden $e) {
            // expected
        }

        $reloaded = Asset::getById($asset->getId(), ['force' => true]);
        $this->assertSame(
            'rename-source-forbidden.txt',
            $reloaded->getFilename(),
            'a user with publish but not rename permission must not be able to rename the asset via WebDAV MOVE'
        );
    }

    public function testSameDirectoryMoveSucceedsWithRenamePermission(): void
    {
        $asset = $this->createAsset('rename-source-allowed.txt');
        $this->loginAs($this->createUserWithWorkspace($asset, publish: true, rename: true));

        $this->createTree()->move($asset->getFilename(), 'rename-target-allowed.txt');

        $reloaded = Asset::getById($asset->getId(), ['force' => true]);
        $this->assertSame(
            'rename-target-allowed.txt',
            $reloaded->getFilename(),
            'a user with both publish and rename permission must still be able to rename the asset via WebDAV MOVE'
        );
    }

    private function createAsset(string $filename): Asset
    {
        $asset = new Asset();
        $asset->setParent(Asset::getByPath('/'));
        $asset->setFilename($filename);
        $asset->setData('some content');
        $asset->save();

        return $asset;
    }

    private function createUserWithWorkspace(Asset $asset, bool $publish, bool $rename): User
    {
        $workspace = new AssetWorkspace();
        $workspace->setCid($asset->getId());
        $workspace->setCpath($asset->getRealFullPath());
        $workspace->setList(true);
        $workspace->setView(true);
        $workspace->setPublish($publish);
        $workspace->setRename($rename);

        $role = new User\Role();
        $role->setParentId(0);
        $role->setName('webdav_rename_role_' . uniqid());
        $role->setPermissions(['assets']);
        $role->setWorkspacesAsset([$workspace]);
        $role->save();
        $this->createdRole = $role;

        $user = new User();
        $user->setParentId(0);
        $user->setName('webdav_rename_user_' . uniqid());
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
