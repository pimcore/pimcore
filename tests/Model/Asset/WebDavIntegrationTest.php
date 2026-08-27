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
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Security\User\User as SecurityUser;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionProperty;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
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
        $this->getTokenStorage()->setToken($token);
    }

    private function clearCurrentUser(): void
    {
        $this->getTokenStorage()->setToken(null);
    }

    /**
     * security.token_storage is a private service, so reach the instance through the public
     * TokenStorageUserResolver - the exact object Pimcore\Tool\Admin::getCurrentUser() reads from.
     */
    private function getTokenStorage(): TokenStorageInterface
    {
        $resolver = Pimcore::getContainer()->get(TokenStorageUserResolver::class);

        $property = new ReflectionProperty($resolver, 'tokenStorage');
        $tokenStorage = $property->getValue($resolver);

        if (!$tokenStorage instanceof TokenStorageInterface) {
            $this->fail('Unable to resolve the security token storage');
        }

        return $tokenStorage;
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

    /**
     * The delete-log restore must also bring back the deleted destination's own properties,
     * metadata, owner and creation date (captured as a scalar snapshot), not just its id.
     */
    public function testMoveRestoresDestinationMetadataAndProperties(): void
    {
        $originalCreationDate = time() - 86400;

        $dest = $this->createFileAssetIn($this->root, 'meta-target.txt', 'OLD');
        $dest->setProperty('reviewed', 'text', 'yes');
        $dest->addMetadata('copyright', 'input', 'ACME');
        // distinct values a freshly rebuilt asset would not get on its own
        $dest->setUserOwner(12345);
        $dest->setCreationDate($originalCreationDate);
        $dest->save();
        $destId = $dest->getId();
        $destPath = $dest->getRealFullPath();

        (new WebDavFile($dest))->delete();

        $source = $this->createFileAssetIn($this->root, 'meta-source.txt', 'NEW');

        $this->newTree()->move(
            $this->davPath($source),
            ltrim($destPath, '/')
        );

        $restored = Asset::getById($destId, ['force' => true]);
        $this->assertInstanceOf(Asset::class, $restored);
        $this->assertSame($destPath, $restored->getRealFullPath());
        $this->assertSame('NEW', $restored->getData());
        $this->assertSame('yes', $restored->getProperty('reviewed'));
        $this->assertSame('ACME', $restored->getMetadata('copyright'));
    }

    /**
     * A node still running the previous release writes the legacy entry shape - id, timestamp and
     * a serialized Asset under 'data'. During a rolling deploy such an entry can be read by a node
     * already running this code, so it must restore from the scalar id and ignore the payload
     * entirely: no object is ever reconstructed from the log. Replaces the round-trip coverage of
     * the object payload that WebDavDeleteLogTest held before the format change.
     */
    public function testMoveIgnoresLegacySerializedPayloadInDeleteLog(): void
    {
        $dest = $this->createFileAssetIn($this->root, 'legacy-target.txt', 'OLD');
        $destId = $dest->getId();
        $destPath = $dest->getRealFullPath();

        $dest->delete();

        // exactly what the previous release persisted, payload included
        Service::saveDeleteLog([
            $destPath => [
                'id' => $destId,
                'timestamp' => time(),
                'data' => 'O:24:"Pimcore\\Model\\Asset\\Text":0:{}',
            ],
        ]);

        $source = $this->createFileAssetIn($this->root, 'legacy-source.txt', 'NEW');

        $this->newTree()->move(
            $this->davPath($source),
            ltrim($destPath, '/')
        );

        $restored = Asset::getByPath($destPath);
        $this->assertInstanceOf(Asset::class, $restored);
        $this->assertSame($destId, $restored->getId(), 'a legacy entry must still restore the destination id');
        $this->assertSame('NEW', $restored->getData());
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
