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
use Pimcore\Model\Asset\WebDAV\Service;
use Pimcore\Model\Asset\WebDAV\Tree;
use Pimcore\Model\User;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionProperty;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Folder::getChild() is the direct node resolution that PROPFIND, LOCK and the other node based
 * WebDAV methods go through via Tree::getNodeForPath() (see that class's docblock). It has to
 * enforce the same per-asset permissions as Folder::getChildren() (folder listings) and File::get()
 * (downloads): "list" for folders - which keeps traversal through list-only ancestors of a
 * workspace working - and "view" for files.
 *
 * @group model.asset.webdav
 */
class FolderGetChildAuthorizationTest extends ModelTestCase
{
    private const BASE_URI = '/asset/webdav/';

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

    public function testGetNodeForPathIsForbiddenForAFileWithoutViewPermission(): void
    {
        $workspaceFolder = TestHelper::createAssetFolder('webdav-authz-workspace-');
        $listOnlyFile = $this->createFile($workspaceFolder, 'list-only.txt');
        $this->loginAs($this->createUserWithWorkspace($workspaceFolder, view: false));

        $tree = $this->createTree();
        $this->assertInstanceOf(Folder::class, $tree->getNodeForPath(ltrim($workspaceFolder->getRealFullPath(), '/')));

        $this->expectException(Forbidden::class);
        $tree->getNodeForPath(ltrim($listOnlyFile->getRealFullPath(), '/'));
    }

    public function testGetNodeForPathTraversesFoldersAboveANestedWorkspace(): void
    {
        $ancestor = TestHelper::createAssetFolder('webdav-authz-ancestor-');
        $workspaceFolder = $this->createFolder($ancestor, 'webdav-authz-nested-' . uniqid());
        $insideFile = $this->createFile($workspaceFolder, 'nested-inside.txt');
        $this->loginAs($this->createUserWithWorkspace($workspaceFolder));

        $tree = $this->createTree();

        // the ancestor is only listable because a workspace lies below it, it carries no "view" itself
        $this->assertFalse($ancestor->isAllowed('view'));
        $this->assertInstanceOf(Folder::class, $tree->getNodeForPath(ltrim($ancestor->getRealFullPath(), '/')));
        $this->assertInstanceOf(Folder::class, $tree->getNodeForPath(ltrim($workspaceFolder->getRealFullPath(), '/')));
        $this->assertInstanceOf(File::class, $tree->getNodeForPath(ltrim($insideFile->getRealFullPath(), '/')));
    }

    public function testPropfindAndLockAreForbiddenOutsideTheUsersWorkspace(): void
    {
        $workspaceFolder = TestHelper::createAssetFolder('webdav-authz-workspace-');
        $insideFile = $this->createFile($workspaceFolder, 'inside.txt');
        $outsideFile = $this->createFile(Asset::getByPath('/'), 'outside-file-' . uniqid() . '.txt');
        $this->loginAs($this->createUserWithWorkspace($workspaceFolder));

        $server = Service::createServer(self::BASE_URI);
        $lockBody = '<?xml version="1.0"?><D:lockinfo xmlns:D="DAV:"><D:lockscope><D:exclusive/></D:lockscope>'
            . '<D:locktype><D:write/></D:locktype></D:lockinfo>';

        $outsidePath = ltrim($outsideFile->getRealFullPath(), '/');
        $this->assertSame(403, $this->dispatch($server, 'PROPFIND', $outsidePath, ['Depth' => '0'])->getStatus());
        $this->assertSame(403, $this->dispatch($server, 'LOCK', $outsidePath, ['Content-Type' => 'application/xml'], $lockBody)->getStatus());

        $insidePath = ltrim($insideFile->getRealFullPath(), '/');
        $this->assertSame(207, $this->dispatch($server, 'PROPFIND', $insidePath, ['Depth' => '0'])->getStatus());
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

    private function createUserWithWorkspace(Asset $asset, bool $view = true): User
    {
        $workspace = new AssetWorkspace();
        $workspace->setCid($asset->getId());
        $workspace->setCpath($asset->getRealFullPath());
        $workspace->setList(true);
        $workspace->setView($view);

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

    private function dispatch(Server $server, string $method, string $path, array $headers = [], ?string $body = null): Response
    {
        $request = new Request($method, self::BASE_URI . $path, $headers, $body);
        $request->setBaseUrl($server->getBaseUri());
        $response = new Response();

        $server->httpRequest = $request;
        $server->httpResponse = $response;

        // mirrors the request setup and exception handling of Server::start(), minus sending the response
        try {
            $server->invokeMethod($request, $response, false);
        } catch (\Sabre\DAV\Exception $e) {
            $response->setStatus($e->getHTTPCode());
        }

        return $response;
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
