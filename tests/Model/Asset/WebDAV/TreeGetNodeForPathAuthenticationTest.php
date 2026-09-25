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
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\ModelTestCase;
use ReflectionProperty;
use Sabre\DAV\Exception\Forbidden;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Regression test for GHSA-xj42-3gh9-g6jv: Sabre resolves the node-based WebDAV operations -
 * including PROPFIND (metadata) and LOCK, which never call any of the permission-gated File/Folder
 * methods themselves - through Tree::getNodeForPath(). Without a check there, an unauthenticated
 * request can enumerate asset existence/metadata and place locks.
 *
 * Operations that don't resolve a node, like UNLOCK, are covered at the server level by
 * ServerAuthenticationTest.
 *
 * @group model.asset.webdav
 */
class TreeGetNodeForPathAuthenticationTest extends ModelTestCase
{
    private ?User $createdUser = null;

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

        parent::tearDown();
    }

    public function testGetNodeForPathOnRootIsForbiddenWithoutAuthentication(): void
    {
        $this->tokenStorage()->setToken(null);

        try {
            $this->createTree()->getNodeForPath('');
            $this->fail('Expected a Forbidden exception because no user is authenticated.');
        } catch (Forbidden $e) {
            // expected
        }
    }

    public function testGetNodeForPathOnAssetIsForbiddenWithoutAuthentication(): void
    {
        $asset = $this->createAsset('unauthenticated-propfind-target.txt');
        $this->tokenStorage()->setToken(null);

        try {
            $this->createTree()->getNodeForPath($asset->getFilename());
            $this->fail('Expected a Forbidden exception because no user is authenticated.');
        } catch (Forbidden $e) {
            // expected
        }
    }

    public function testGetNodeForPathOnRootSucceedsWithAuthenticatedUser(): void
    {
        $this->loginAs($this->createAuthenticatedUser());

        $node = $this->createTree()->getNodeForPath('');

        $this->assertInstanceOf(Folder::class, $node);
    }

    public function testGetNodeForPathOnAssetSucceedsWithAuthenticatedUser(): void
    {
        $asset = $this->createAsset('authenticated-propfind-target.txt');
        $this->loginAs($this->createAuthenticatedUser());

        $node = $this->createTree()->getNodeForPath($asset->getFilename());

        $this->assertInstanceOf(File::class, $node);
    }

    private function createAsset(string $filename, string $data = 'some content'): Asset
    {
        $asset = new Asset();
        $asset->setParent(Asset::getByPath('/'));
        $asset->setFilename($filename);
        $asset->setData($data);
        $asset->save();

        return $asset;
    }

    private function createAuthenticatedUser(): User
    {
        $user = new User();
        $user->setParentId(0);
        $user->setName('webdav_auth_user_' . uniqid());
        $user->setActive(true);
        $user->setAdmin(true);
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
        // id. Tree::getNodeForPath()/move() resolve the current user via Admin::getCurrentUser(),
        // which reads the token storage held by the public TokenStorageUserResolver service, so we
        // reach the same shared instance through that resolver rather than replacing the service.
        $resolver = Pimcore::getContainer()->get(TokenStorageUserResolver::class);

        return (new ReflectionProperty(TokenStorageUserResolver::class, 'tokenStorage'))->getValue($resolver);
    }
}
