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
use ReflectionProperty;
use Sabre\DAV\Exception\Forbidden;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The delete-log restore branch of Tree::move() re-creates a destination that was deleted moments
 * earlier. Its own users_workspaces_asset rows went with it (ON DELETE CASCADE on
 * users_workspaces_asset.cid) and are only re-inserted after the save, so Asset::isAllowed()
 * resolves nothing but the inherited rules at the point the overwrite is authorized.
 *
 * Without an extra check a user holding an explicit deny on the destination (delete = 1,
 * publish = 0) plus an inherited publish grant on the folder could therefore delete the
 * destination and then replace it through the restore path - a write that an in-place overwrite
 * refuses. Tree::move() decides that overwrite against the workspace rows captured in the delete
 * log instead, which is what these tests pin down.
 *
 * @group model.asset.webdav
 */
class TreeMoveRestoreWorkspacePermissionTest extends ModelTestCase
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

        if (file_exists(Service::getDeleteLogFile())) {
            unlink(Service::getDeleteLogFile());
        }

        parent::tearDown();
    }

    public function testRestoreIsForbiddenWhenTheDeletedDestinationDeniedPublish(): void
    {
        $destination = $this->createAsset('restore-denied-target.txt', 'destination content');
        $destinationId = $destination->getId();
        $destinationPath = $destination->getFilename();
        $source = $this->createAsset('restore-denied-source.txt', 'source content');
        $sourceId = $source->getId();

        $this->loginAs($this->createUserWithWorkspaces($destination, destinationPublish: false));

        // allowed: the destination's own rule grants delete. This also cascade-removes that rule,
        // which is exactly what leaves the follow-up MOVE looking at the inherited grant only.
        (new File($destination))->delete();
        $this->assertNull(Asset::getById($destinationId, ['force' => true]));

        try {
            $this->createTree()->move($source->getFilename(), $destinationPath);
            $this->fail('Expected a Forbidden exception: the deleted destination denied "publish".');
        } catch (Forbidden $e) {
            // expected
        }

        $this->assertNull(
            Asset::getById($destinationId, ['force' => true]),
            'a denied restore must not re-create the destination'
        );
        $this->assertNotNull(
            Asset::getById($sourceId, ['force' => true]),
            'a denied restore must leave the source asset untouched'
        );
    }

    public function testRestoreSucceedsWhenTheDeletedDestinationAllowedPublish(): void
    {
        $destination = $this->createAsset('restore-allowed-target.txt', 'destination content');
        $destinationId = $destination->getId();
        $destinationPath = $destination->getFilename();
        $source = $this->createAsset('restore-allowed-source.txt', 'source content');
        $sourceId = $source->getId();

        $this->loginAs($this->createUserWithWorkspaces($destination, destinationPublish: true));

        (new File($destination))->delete();

        $this->createTree()->move($source->getFilename(), $destinationPath);

        $restored = Asset::getById($destinationId, ['force' => true]);
        $this->assertNotNull(
            $restored,
            'the captured rule grants publish, so the restore must still re-create the destination under its original id'
        );
        $this->assertSame('source content', $restored->getData());
        $this->assertNull(
            Asset::getById($sourceId, ['force' => true]),
            'the source asset must be removed after the restore'
        );
    }

    private function createAsset(string $filename, string $data): Asset
    {
        $asset = new Asset();
        $asset->setParent(Asset::getByPath('/'));
        $asset->setFilename($filename);
        $asset->setData($data);
        $asset->save();

        return $asset;
    }

    /**
     * Grants everything the MOVE needs on the asset root and puts a second, deeper rule on the
     * destination itself - the shape that makes the deepest-cpath resolution in
     * Asset\Dao::isAllowed() decide the overwrite. Both rules sit on a role, so the role branch of
     * the snapshot resolution is exercised rather than the direct-user one.
     */
    private function createUserWithWorkspaces(Asset $destination, bool $destinationPublish): User
    {
        $inherited = new AssetWorkspace();
        $inherited->setCid(1);
        $inherited->setCpath(Asset::getById(1)->getRealFullPath());
        $inherited->setList(true);
        $inherited->setView(true);
        $inherited->setPublish(true);
        $inherited->setDelete(true);
        $inherited->setRename(true);
        $inherited->setCreate(true);

        $own = new AssetWorkspace();
        $own->setCid($destination->getId());
        $own->setCpath($destination->getRealFullPath());
        $own->setList(true);
        $own->setView(true);
        $own->setPublish($destinationPublish);
        $own->setDelete(true);

        $role = new User\Role();
        $role->setParentId(0);
        $role->setName('webdav_restore_role_' . uniqid());
        $role->setPermissions(['assets']);
        $role->setWorkspacesAsset([$inherited, $own]);
        $role->save();
        $this->createdRole = $role;

        $user = new User();
        $user->setParentId(0);
        $user->setName('webdav_restore_user_' . uniqid());
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
        // see TreeMoveRenamePermissionTest::tokenStorage()
        $resolver = Pimcore::getContainer()->get(TokenStorageUserResolver::class);

        return (new ReflectionProperty(TokenStorageUserResolver::class, 'tokenStorage'))->getValue($resolver);
    }
}
