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
use Pimcore\Model\Asset\WebDAV\Service;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionProperty;
use Sabre\DAV\Locks\Backend\BackendInterface;
use Sabre\DAV\Locks\LockInfo;
use Sabre\DAV\Locks\Plugin as LocksPlugin;
use Sabre\DAV\Server;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Regression test for GHSA-xj42-3gh9-g6jv at the HTTP level: some WebDAV methods never resolve a
 * node through Tree::getNodeForPath() - UNLOCK, for one, only talks to the lock backend. The
 * server built for the WebDAV controller must therefore reject anonymous requests before Sabre
 * dispatches any method.
 *
 * @group model.asset.webdav
 */
class ServerAuthenticationTest extends ModelTestCase
{
    private const BASE_URI = '/asset/webdav/';

    private ?User $createdUser = null;

    private ?TokenInterface $originalToken = null;

    private ?Asset $lockedAsset = null;

    private ?LockInfo $lock = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalToken = $this->tokenStorage()->getToken();
    }

    protected function tearDown(): void
    {
        if ($this->lockedAsset !== null && $this->lock !== null) {
            $this->lockBackend(Service::createServer(self::BASE_URI))
                ->unlock($this->lockedAsset->getFilename(), $this->lock);
        }

        $this->tokenStorage()->setToken($this->originalToken);

        $this->createdUser?->delete();

        parent::tearDown();
    }

    public function testUnlockIsForbiddenWithoutAuthentication(): void
    {
        $server = Service::createServer(self::BASE_URI);
        $path = $this->lockAsset($server, 'anonymous-unlock-target.txt');
        $this->tokenStorage()->setToken(null);

        $response = $this->dispatch($server, 'UNLOCK', $path, ['Lock-Token' => '<opaquelocktoken:' . $this->lock->token . '>']);

        $this->assertSame(403, $response->getStatus());
        $this->assertCount(1, $this->lockBackend($server)->getLocks($path, false), 'The lock must survive an anonymous UNLOCK.');
    }

    public function testPropfindIsForbiddenWithoutAuthentication(): void
    {
        $server = Service::createServer(self::BASE_URI);
        $this->tokenStorage()->setToken(null);

        $response = $this->dispatch($server, 'PROPFIND', '', ['Depth' => '0']);

        $this->assertSame(403, $response->getStatus());
    }

    public function testUnlockSucceedsWithAuthenticatedUser(): void
    {
        $server = Service::createServer(self::BASE_URI);
        $path = $this->lockAsset($server, 'authenticated-unlock-target.txt');
        $this->loginAs($this->createAuthenticatedUser());

        $response = $this->dispatch($server, 'UNLOCK', $path, ['Lock-Token' => '<opaquelocktoken:' . $this->lock->token . '>']);

        $this->assertSame(204, $response->getStatus());
        $this->assertCount(0, $this->lockBackend($server)->getLocks($path, false));
        $this->lock = null;
    }

    private function dispatch(Server $server, string $method, string $path, array $headers = []): Response
    {
        $request = new Request($method, self::BASE_URI . $path, $headers);
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

    private function lockAsset(Server $server, string $filename): string
    {
        $asset = new Asset();
        $asset->setParent(Asset::getByPath('/'));
        $asset->setFilename($filename);
        $asset->setData('some content');
        $asset->save();
        $this->lockedAsset = $asset;

        $lock = new LockInfo();
        $lock->token = 'test-' . uniqid();
        $lock->owner = 'test';
        $lock->timeout = 600;
        $lock->created = time();
        $lock->scope = LockInfo::EXCLUSIVE;
        $lock->depth = 0;
        $lock->uri = $asset->getFilename();

        $this->lockBackend($server)->lock($asset->getFilename(), $lock);
        $this->lock = $lock;

        return $asset->getFilename();
    }

    private function lockBackend(Server $server): BackendInterface
    {
        $plugin = $server->getPlugin('locks');
        $this->assertInstanceOf(LocksPlugin::class, $plugin);

        return (new ReflectionProperty(LocksPlugin::class, 'locksBackend'))->getValue($plugin);
    }

    private function createAuthenticatedUser(): User
    {
        $user = new User();
        $user->setParentId(0);
        $user->setName('webdav_server_auth_user_' . uniqid());
        $user->setActive(true);
        $user->setAdmin(true);
        $user->save();
        $this->createdUser = $user;

        return $user;
    }

    private function loginAs(User $user): void
    {
        $this->tokenStorage()->setToken(new UsernamePasswordToken(new SecurityUser($user), 'pimcore_admin'));
    }

    private function tokenStorage(): TokenStorageInterface
    {
        // see TreeGetNodeForPathAuthenticationTest::tokenStorage()
        $resolver = Pimcore::getContainer()->get(TokenStorageUserResolver::class);

        return (new ReflectionProperty(TokenStorageUserResolver::class, 'tokenStorage'))->getValue($resolver);
    }
}
