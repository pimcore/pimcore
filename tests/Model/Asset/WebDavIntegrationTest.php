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

namespace Pimcore\Tests\Model\Asset;

use Pimcore;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\WebDAV\File as WebDavFile;
use Pimcore\Model\Asset\WebDAV\Folder as WebDavFolder;
use Pimcore\Model\Asset\WebDAV\Service;
use Pimcore\Model\Asset\WebDAV\Tree;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Best-effort end-to-end coverage for the Sabre/DAV asset nodes. These drive the real
 * Asset\WebDAV\{Tree,Folder,File} classes, which require an authenticated admin user in the
 * security token storage (Admin::getCurrentUser()).
 *
 * @group model.asset.webdav
 */
class WebDavIntegrationTest extends ModelTestCase
{
    private User $user;

    private Asset\Folder $root;

    protected function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->seatAdminUser();
        $this->root = TestHelper::createAssetFolder();
    }

    protected function tearDown(): void
    {
        $this->clearCurrentUser();

        if (file_exists(Service::getDeleteLogFile())) {
            unlink(Service::getDeleteLogFile());
        }

        TestHelper::cleanUp();
        parent::tearDown();
    }

    // ---- helpers -------------------------------------------------------------------------

    private function seatAdminUser(): void
    {
        if (!$user = User::getByName('webdav-test-user')) {
            $user = new User();
            $user->setAdmin(true);
            $user->setName('webdav-test-user');
            $user->save();
        }

        $this->user = $user;

        $token = new UsernamePasswordToken(new SecurityUser($user), 'pimcore_admin');
        Pimcore::getContainer()->get('security.token_storage')->setToken($token);
    }

    private function clearCurrentUser(): void
    {
        Pimcore::getContainer()->get('security.token_storage')->setToken(null);
    }

    /**
     * Creates a text asset directly under the given folder.
     */
    private function createFileAssetIn(Asset\Folder $parent, string $filename, string $content): Asset
    {
        return Asset::create($parent->getId(), [
            'filename' => $filename,
            'data' => $content,
            'type' => 'text',
            'userOwner' => $this->user->getId(),
            'userModification' => $this->user->getId(),
        ]);
    }

    /**
     * The path a Sabre node uses is the asset's real full path without the leading slash.
     */
    private function davPath(Asset $asset): string
    {
        return ltrim($asset->getRealFullPath(), '/');
    }

    private function newTree(): Tree
    {
        return new Tree(new WebDavFolder(Asset::getById(1)));
    }

    // ---- Folder --------------------------------------------------------------------------

    public function testCreateFileThroughFolderNode(): void
    {
        $rootNode = new WebDavFolder($this->root);
        $rootNode->createFile('hello.txt', 'hello world');

        $created = Asset::getByPath($this->root->getRealFullPath() . '/hello.txt');

        $this->assertInstanceOf(Asset::class, $created);
        $this->assertSame('hello world', $created->getData());
    }

    public function testCreateDirectoryThroughFolderNode(): void
    {
        $rootNode = new WebDavFolder($this->root);
        $rootNode->createDirectory('subfolder');

        $created = Asset::getByPath($this->root->getRealFullPath() . '/subfolder');

        $this->assertInstanceOf(Asset\Folder::class, $created);
    }

    public function testCreateFileWithoutAuthenticatedUserIsForbidden(): void
    {
        $this->clearCurrentUser();

        $rootNode = new WebDavFolder($this->root);

        $this->expectException(Forbidden::class);
        $rootNode->createFile('nope.txt', 'data');
    }

    // ---- File ----------------------------------------------------------------------------

    public function testPutUpdatesContent(): void
    {
        $asset = $this->createFileAssetIn($this->root, 'put.txt', 'original');

        $node = new WebDavFile($asset);
        $node->put('updated');

        $reloaded = Asset::getById($asset->getId(), ['force' => true]);
        $this->assertSame('updated', $reloaded->getData());
    }

    public function testSetNameRenamesAsset(): void
    {
        $asset = $this->createFileAssetIn($this->root, 'before.txt', 'x');

        $node = new WebDavFile($asset);
        $node->setName('after.txt');

        $this->assertNull(Asset::getByPath($this->root->getRealFullPath() . '/before.txt'));
        $this->assertInstanceOf(Asset::class, Asset::getByPath($this->root->getRealFullPath() . '/after.txt'));
    }

    public function testDeleteRemovesAssetAndWritesDeleteLog(): void
    {
        $asset = $this->createFileAssetIn($this->root, 'delete-me.txt', 'x');
        $id = $asset->getId();
        $path = $asset->getRealFullPath();

        (new WebDavFile($asset))->delete();

        $this->assertNull(Asset::getById($id, ['force' => true]), 'asset should be deleted');
        $this->assertArrayHasKey($path, Service::getDeleteLog(), 'delete log should record the removed asset');
    }

    // ---- Tree::move ----------------------------------------------------------------------

    public function testMoveRenamesWithinSameDirectory(): void
    {
        $asset = $this->createFileAssetIn($this->root, 'src-rename.txt', 'content');
        $id = $asset->getId();

        $this->newTree()->move(
            $this->davPath($asset),
            ltrim($this->root->getRealFullPath() . '/dst-rename.txt', '/')
        );

        $this->assertNull(Asset::getByPath($this->root->getRealFullPath() . '/src-rename.txt'));
        $moved = Asset::getByPath($this->root->getRealFullPath() . '/dst-rename.txt');
        $this->assertInstanceOf(Asset::class, $moved);
        $this->assertSame($id, $moved->getId(), 'rename must preserve the asset id');
    }

    public function testMoveAcrossDirectories(): void
    {
        $target = TestHelper::createAssetFolder();
        $asset = $this->createFileAssetIn($this->root, 'cross.txt', 'content');
        $id = $asset->getId();

        $this->newTree()->move(
            $this->davPath($asset),
            ltrim($target->getRealFullPath() . '/cross.txt', '/')
        );

        $moved = Asset::getByPath($target->getRealFullPath() . '/cross.txt');
        $this->assertInstanceOf(Asset::class, $moved);
        $this->assertSame($id, $moved->getId());
        $this->assertSame($target->getId(), $moved->getParentId());
        $this->assertNull(Asset::getByPath($this->root->getRealFullPath() . '/cross.txt'));
    }

    public function testMoveOverwritesExistingDestination(): void
    {
        $source = $this->createFileAssetIn($this->root, 'ow-source.txt', 'SOURCE');
        $dest = $this->createFileAssetIn($this->root, 'ow-dest.txt', 'DEST');
        $destId = $dest->getId();

        $this->newTree()->move(
            $this->davPath($source),
            ltrim($this->root->getRealFullPath() . '/ow-dest.txt', '/')
        );

        // source is consumed, destination keeps its id but takes the source content
        $this->assertNull(Asset::getByPath($this->root->getRealFullPath() . '/ow-source.txt'));

        $result = Asset::getByPath($this->root->getRealFullPath() . '/ow-dest.txt');
        $this->assertInstanceOf(Asset::class, $result);
        $this->assertSame($destId, $result->getId(), 'overwrite must preserve the destination asset id/history');
        $this->assertSame('SOURCE', $result->getData());
    }

    /**
     * End-to-end regression for the delete-log restore (the bug where the payload was
     * unserialized with allowedClasses=false). A client that deletes the destination and then
     * moves a new file onto that path must have the destination restored, preserving its id.
     */
    public function testMoveRestoresDestinationFromDeleteLog(): void
    {
        $dest = $this->createFileAssetIn($this->root, 'restore-target.txt', 'OLD');
        $destId = $dest->getId();
        $destPath = $dest->getRealFullPath();

        // client deletes the destination first (records it in the delete log)
        (new WebDavFile($dest))->delete();
        $this->assertNull(Asset::getByPath($destPath), 'destination should be gone before the move');

        $source = $this->createFileAssetIn($this->root, 'restore-source.txt', 'NEW');

        $this->newTree()->move(
            $this->davPath($source),
            ltrim($destPath, '/')
        );

        $restored = Asset::getByPath($destPath);
        $this->assertInstanceOf(Asset::class, $restored);
        $this->assertSame($destId, $restored->getId(), 'delete-log restore must reuse the original destination id');
        $this->assertSame('NEW', $restored->getData());
        $this->assertNull(Asset::getByPath($this->root->getRealFullPath() . '/restore-source.txt'));
    }

    public function testMoveWithMissingSourceThrowsNotFound(): void
    {
        $this->expectException(NotFound::class);

        $this->newTree()->move(
            ltrim($this->root->getRealFullPath() . '/missing.txt', '/'),
            ltrim($this->root->getRealFullPath() . '/whatever.txt', '/')
        );
    }
}
