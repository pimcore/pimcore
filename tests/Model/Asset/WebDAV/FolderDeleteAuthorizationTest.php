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
use Pimcore\Event\AssetEvents;
use Pimcore\Event\ElementEvents;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\ElementEvent;
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
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\Forbidden;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Asset::delete() recursively removes an entire subtree without checking each descendant's own
 * workspace permissions. A WebDAV delete therefore removes the descendants one by one, deepest first,
 * each after its own "delete" permission check - like the Studio API - instead of only checking the
 * requested node.
 *
 * @group model.asset.webdav
 */
class FolderDeleteAuthorizationTest extends ModelTestCase
{
    private ?User $createdUser = null;

    private ?User\Role $createdRole = null;

    private ?TokenInterface $originalToken = null;

    private ?int $lateAssetId = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalToken = $this->tokenStorage()->getToken();
        $this->lateAssetId = null;
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

    public function testDenialKeepsTheDeniedAssetAndItsAncestorsAndDeletesDeeperAssetsFirst(): void
    {
        $parent = TestHelper::createAssetFolder('webdav-delete-parent-');
        $allowedBranch = $this->createFolder($parent, 'allowed-' . uniqid());
        $deepFile = $this->createFile($allowedBranch, 'deep.png');
        $denied = $this->createFolder($parent, 'denied-' . uniqid());

        $this->loginAs($this->createUserWithWorkspaces([
            ['asset' => $parent, 'delete' => true],
            ['asset' => $denied, 'delete' => false],
        ]));

        $deepFileId = $deepFile->getId();

        try {
            $this->resolveNode($parent)->delete();
            $this->fail('Expected a Forbidden exception because a descendant denies delete permission.');
        } catch (Forbidden $e) {
            // expected
        }

        // the deepest asset is processed first and is gone; the denied asset and its ancestors are kept
        $this->assertNull(Asset::getById($deepFileId, ['force' => true]));
        $this->assertNotNull(Asset::getById($denied->getId(), ['force' => true]));
        $this->assertNotNull(Asset::getById($parent->getId(), ['force' => true]));
    }

    public function testFolderThatGainsAChildWhileItIsDeletedIsKept(): void
    {
        $parent = TestHelper::createAssetFolder('webdav-delete-parent-');
        $child = $this->createFolder($parent, 'child-' . uniqid());

        $this->loginAs($this->createUserWithWorkspaces([
            ['asset' => $parent, 'delete' => true],
        ]));

        $dispatcher = Pimcore::getEventDispatcher();
        // a child shows up in the parent after the descendants were listed: it was never checked
        $listener = $this->addChildWhenPermissionIsChecked($parent, 'child-');

        $childId = $child->getId();

        try {
            $this->resolveNode($parent)->delete();
            $this->fail('Expected a Conflict exception because the folder gained a child that was not checked.');
        } catch (Conflict $e) {
            // expected
        } finally {
            $dispatcher->removeListener(ElementEvents::ELEMENT_PERMISSION_IS_ALLOWED, $listener);
        }

        $this->assertNotNull($this->lateAssetId);
        $this->assertNull(Asset::getById($childId, ['force' => true]));
        $this->assertNotNull(Asset::getById($parent->getId(), ['force' => true]));
        $this->assertNotNull(Asset::getById($this->lateAssetId, ['force' => true]));
    }

    public function testTheRootAssetIsRejectedBeforeAnythingIsDeleted(): void
    {
        $asset = TestHelper::createAssetFolder('webdav-delete-root-');
        $root = Asset::getById(1);

        $this->loginAs($this->createUserWithWorkspaces([
            ['asset' => $root, 'delete' => true],
        ]));

        try {
            (new Folder($root))->delete();
            $this->fail('Expected a Forbidden exception because the root asset cannot be deleted.');
        } catch (Forbidden $e) {
            // expected
        }

        $this->assertNotNull(Asset::getById($asset->getId(), ['force' => true]));
    }

    public function testChildThatAppearsWhileTheFolderIsBeingDeletedIsNotRemoved(): void
    {
        $parent = TestHelper::createAssetFolder('webdav-delete-parent-');

        $this->loginAs($this->createUserWithWorkspaces([
            ['asset' => $parent, 'delete' => true],
        ]));

        // the folder has no children when it is checked; one appears while Asset::delete() starts
        $dispatcher = Pimcore::getEventDispatcher();
        $addChild = function (AssetEvent $event) use ($parent): void {
            if ($this->lateAssetId !== null || $event->getAsset()->getId() !== $parent->getId()) {
                return;
            }

            $this->lateAssetId = $this->createFolder($parent, 'late-' . uniqid())->getId();
            $event->getAsset()->setChildren(null);
        };
        $dispatcher->addListener(AssetEvents::PRE_DELETE, $addChild);

        try {
            $this->resolveNode($parent)->delete();
            $this->fail('Expected a Conflict exception because a child appeared that was not checked.');
        } catch (Conflict $e) {
            // expected
        } finally {
            $dispatcher->removeListener(AssetEvents::PRE_DELETE, $addChild);
        }

        $this->assertNotNull($this->lateAssetId);
        $this->assertNotNull(Asset::getById($parent->getId(), ['force' => true]));
        $this->assertNotNull(Asset::getById($this->lateAssetId, ['force' => true]));
    }

    public function testMoveOverwriteIsForbiddenWhenTheSourceHasADeniedDescendant(): void
    {
        $parent = TestHelper::createAssetFolder('webdav-move-parent-');
        $source = $this->createFolder($parent, 'source-' . uniqid());
        $denied = $this->createFolder($source, 'denied-' . uniqid());
        $destination = $this->createFolder($parent, 'destination-' . uniqid());

        $this->loginAs($this->createUserWithWorkspaces([
            ['asset' => $parent, 'delete' => true, 'publish' => true],
            ['asset' => $denied, 'delete' => false, 'publish' => true],
        ]));

        try {
            $this->moveOver($source, $destination);
            $this->fail('Expected a Forbidden exception because a descendant of the moved source denies delete permission.');
        } catch (Forbidden $e) {
            // expected
        }

        $this->assertNotNull(Asset::getById($source->getId()));
        $this->assertNotNull(Asset::getById($denied->getId()));
        $this->assertNotNull(Asset::getById($destination->getId()));
    }

    public function testMoveOverwriteLeavesTheDestinationUntouchedWhenTheSourceGainsAChildMeanwhile(): void
    {
        $parent = TestHelper::createAssetFolder('webdav-move-parent-');
        $source = $this->createFolder($parent, 'source-' . uniqid());
        $child = $this->createFolder($source, 'child-' . uniqid());
        $destination = $this->createFolder($parent, 'destination-' . uniqid());

        $user = $this->createUserWithWorkspaces([
            ['asset' => $parent, 'delete' => true, 'publish' => true],
        ]);
        $this->loginAs($user);

        $modifiedByBefore = Asset::getById($destination->getId(), ['force' => true])->getUserModification();
        $this->assertNotSame($user->getId(), $modifiedByBefore);

        $dispatcher = Pimcore::getEventDispatcher();
        $listener = $this->addChildWhenPermissionIsChecked($source, 'child-');

        try {
            $this->moveOver($source, $destination);
            $this->fail('Expected a Conflict exception because the source gained a child that was not checked.');
        } catch (Conflict $e) {
            // expected
        } finally {
            $dispatcher->removeListener(ElementEvents::ELEMENT_PERMISSION_IS_ALLOWED, $listener);
        }

        $this->assertNotNull($this->lateAssetId);
        $this->assertNotNull(Asset::getById($source->getId(), ['force' => true]));
        $this->assertNotNull(Asset::getById($this->lateAssetId, ['force' => true]));
        $this->assertSame(
            $modifiedByBefore,
            Asset::getById($destination->getId(), ['force' => true])->getUserModification(),
            'The destination must not be modified when the source deletion is refused'
        );
    }

    public function testMoveOverwriteSucceedsWhenTheEntireSourceSubtreeIsDeletable(): void
    {
        $parent = TestHelper::createAssetFolder('webdav-move-parent-');
        $source = $this->createFolder($parent, 'source-' . uniqid());
        $child = $this->createFolder($source, 'child-' . uniqid());
        $destination = $this->createFolder($parent, 'destination-' . uniqid());

        $this->loginAs($this->createUserWithWorkspaces([
            ['asset' => $parent, 'delete' => true, 'publish' => true],
            ['asset' => $child, 'delete' => true, 'publish' => true],
        ]));

        $sourceId = $source->getId();
        $childId = $child->getId();

        $this->moveOver($source, $destination);

        $this->assertNull(Asset::getById($sourceId));
        $this->assertNull(Asset::getById($childId));
        $this->assertNotNull(Asset::getById($destination->getId()));
    }

    /**
     * Adds a child to $parent the first time the permission of an asset whose key starts with $keyPrefix is
     * checked, which simulates a concurrent change between the steps of a deletion.
     */
    private function addChildWhenPermissionIsChecked(Asset $parent, string $keyPrefix): callable
    {
        $listener = function (ElementEvent $event) use ($parent, $keyPrefix): void {
            if ($this->lateAssetId !== null || !str_starts_with($event->getElement()->getKey(), $keyPrefix)) {
                return;
            }

            $this->lateAssetId = $this->createFolder($parent, 'late-' . uniqid())->getId();
        };

        Pimcore::getEventDispatcher()->addListener(ElementEvents::ELEMENT_PERMISSION_IS_ALLOWED, $listener);

        return $listener;
    }

    private function moveOver(Asset $source, Asset $destination): void
    {
        $tree = new Tree(new Folder(Asset::getById(1)));
        $tree->move(ltrim($source->getRealFullPath(), '/'), ltrim($destination->getRealFullPath(), '/'));
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
     * @param array<int, array{asset: Asset, delete: bool, publish?: bool}> $workspaceSpecs
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
            $workspace->setPublish($spec['publish'] ?? false);
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
