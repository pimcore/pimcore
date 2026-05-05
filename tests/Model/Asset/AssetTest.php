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

use Exception;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToMoveFile;
use Pimcore;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;
use Psr\Container\ContainerInterface;

/**
 * Class AssetTest
 *
 * @package Pimcore\Tests\Model\Asset
 *
 * @group model.asset.asset
 */
class AssetTest extends ModelTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        TestHelper::clearThumbnailConfigurations();
    }

    protected Asset $testAsset;

    public function testCRUD(): void
    {
        // create
        $path = TestHelper::resolveFilePath('assets/images/image5.jpg');
        $expectedData = file_get_contents($path);
        $fileSize = strlen($expectedData);
        $this->assertTrue(strlen((string)$fileSize) > 0);

        $this->testAsset = TestHelper::createImageAsset('', null, true, 'assets/images/image5.jpg');
        $this->assertInstanceOf(Asset\Image::class, $this->testAsset);

        $this->reloadAsset();
        $data = $this->testAsset->getData();
        $this->assertEquals($data, $expectedData);

        // move and rename
        $newParent = Asset\Service::createFolderByPath(uniqid());
        $newPath = $newParent->getFullPath() . '/' . $this->testAsset->getKey() . '_new';

        $this->testAsset->setParentId($newParent->getId());
        $this->testAsset->setKey($this->testAsset->getKey() . '_new');
        $this->testAsset->save();
        $this->reloadAsset();

        $byPath = Asset::getByPath($newPath);
        $this->assertInstanceOf(Asset::class, $byPath);
        $this->assertEquals($this->testAsset->getId(), $byPath->getId());

        $this->reloadAsset();
        $data = $this->testAsset->getData();
        $this->assertEquals($data, $expectedData);

        $this->assertTrue($newParent->hasChildren());

        // change the data

        $path = TestHelper::resolveFilePath('assets/images/image4.jpg');
        $expectedData = file_get_contents($path);
        $fileSize = strlen($expectedData);
        $this->assertTrue(strlen((string)$fileSize) > 0);
        $this->testAsset->setData($expectedData);
        $this->testAsset->save();
        $this->reloadAsset();
        $data = $this->testAsset->getData();
        $this->assertEquals($data, $expectedData);

        // delete
        $this->testAsset->delete();
        $this->assertFalse($newParent->hasChildren());
    }

    /**
     * Parent ID of a new object cannot be 0
     */
    public function testParentIs0(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ParentID is mandatory and can´t be null. If you want to add the element as a child to the tree´s root node, consider setting ParentID to 1.');
        $savedObject = TestHelper::createImageAsset('', null, false);
        $this->assertNull($savedObject->getId());

        $savedObject->setParentId(0);
        $savedObject->save();
    }

    /**
     * Parent ID of a new object cannot be null
     */
    public function testParentIsNull(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ParentID is mandatory and can´t be null. If you want to add the element as a child to the tree´s root node, consider setting ParentID to 1.');
        $savedObject = TestHelper::createImageAsset('', null, false);
        $this->assertNull($savedObject->getId());

        $savedObject->setParentId(null);
        $savedObject->save();
    }

    /**
     * Verifies that an object with the same parent ID cannot be created.
     */
    public function testParentIdentical(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("ParentID and ID are identical, an element can't be the parent of itself in the tree.");
        $savedObject = TestHelper::createImageAsset();
        $this->assertTrue($savedObject->getId() > 0);

        $savedObject->setParentId($savedObject->getId());
        $savedObject->save();
    }

    /**
     * Parent ID must resolve to an existing element
     *
     * @group notfound
     */
    public function testParentNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ParentID not found.');
        $savedObject = TestHelper::createImageAsset('', null, false);
        $this->assertEquals(null, $savedObject->getId());

        $savedObject->setParentId(999999);
        $savedObject->save();
    }

    /**
     * Verifies that asset PHP API version note is saved
     */
    public function testSavingVersionNotes(): void
    {
        $versionNote = ['versionNote' => 'a new version of this asset'];
        $this->testAsset = TestHelper::createImageAsset('', null, true, 'assets/images/image1.jpg');
        $this->testAsset->save($versionNote);
        $this->assertEquals($this->testAsset->getLatestVersion(null, true)->getNote(), $versionNote['versionNote']);
    }

    public function testThumbnails(): void
    {
        $this->testAsset = TestHelper::createImageAsset('', null, true, 'assets/images/image1.jpg');
        $this->assertInstanceOf(Asset\Image::class, $this->testAsset);

        $this->reloadAsset();
        $this->assertEquals(1024, $this->testAsset->getWidth());
        $this->assertEquals(768, $this->testAsset->getHeight());

        // rotate 90°
        $config = TestHelper::createThumbnailConfigurationRotate();
        $thumbnail = $this->testAsset->getThumbnail($config->getName(), false);
        $this->assertEquals(768, $thumbnail->getWidth());
        $this->assertEquals(1024, $thumbnail->getHeight());

        // rotate 45°
        $config = TestHelper::createThumbnailConfigurationRotate(45);
        $thumbnail = $this->testAsset->getThumbnail($config->getName(), false);
        $this->assertTrue($thumbnail->getWidth() > 1024);
        $this->assertTrue($thumbnail->getHeight() > 768);

        // scale by width (shrink)
        $config = TestHelper::createThumbnailConfigurationScaleByWidth();
        $thumbnail = $this->testAsset->getThumbnail($config->getName(), false);
        $this->assertEquals(256, $thumbnail->getWidth());
        $this->assertEquals(192, $thumbnail->getHeight());

        // check if the thumbnail file is there
        $pathReference = $thumbnail->getPathReference(false);
        $stream = Storage::get($pathReference['type'])->readStream($pathReference['src']);
        $this->assertTrue(is_resource($stream));
        $thumbnailContent = stream_get_contents($stream);
        $fileSizeThumbnail = strlen($thumbnailContent);

        $path = TestHelper::resolveFilePath('assets/images/image1.jpg');
        $expectedData = file_get_contents($path);
        $fileSize = strlen($expectedData);

        $this->assertTrue($fileSizeThumbnail < $fileSize);

        $thumbnailimageSizeInfo = getimagesize($thumbnail->getLocalFile());
        $this->assertEquals(256, $thumbnailimageSizeInfo[0]);
        $this->assertEquals(192, $thumbnailimageSizeInfo[1]);

        // scale by width (factor 2x) without forceResize
        $config = TestHelper::createThumbnailConfigurationScaleByWidth(2048, false);
        $thumbnail = $this->testAsset->getThumbnail($config->getName(), false);
        $this->assertEquals(1024, $thumbnail->getWidth());
        $this->assertEquals(768, $thumbnail->getHeight());

        // scale by width (factor 2x) with forceResize
        $config = TestHelper::createThumbnailConfigurationScaleByWidth(2048, true);
        $thumbnail = $this->testAsset->getThumbnail($config->getName(), false);
        $this->assertEquals(2048, $thumbnail->getWidth());
        $this->assertEquals(1536, $thumbnail->getHeight());

        // test custom format thumbnails
        $webpThumbnail = $thumbnail->getAsFormat('webp');
        $jpgThumbnail = $thumbnail->getAsFormat('jpg');
        $pngThumbnail = $thumbnail->getAsFormat('png');

        $this->assertStringEndsWith('.webp', $webpThumbnail->getPath());
        $this->assertStringEndsWith('.jpg', $jpgThumbnail->getPath());
        $this->assertStringEndsWith('.png', $pngThumbnail->getPath());

        // clean the thumbnails
        try {
            $stream = $thumbnail->getStream();
        } catch (Exception $e) {
            $stream = null;
        }

        $this->assertTrue(is_resource($stream));

        $this->testAsset->clearThumbnails(true);

        try {
            $stream1 = $thumbnail->getStream();
        } catch (Exception $e) {
            $stream1 = null;
        }

        $this->assertFalse(is_resource($stream1));
    }

    public function reloadAsset(): void
    {
        $this->testAsset = Asset::getById($this->testAsset->getId(), ['force' => true]);
    }

    /**
     * Regression test for the updateChildPaths() fallback path:
     * when a single storage->move() of a folder fails with UnableToMoveFile,
     * the method must move all descendant files individually and then delete
     * the source directory — even when the folder tree contains nested
     * subdirectories (which listContents() returns as DirectoryAttributes that
     * must not be counted against the moved-file total).
     */
    public function testFolderMoveWithNestedSubdirectoriesFallback(): void
    {
        // Build:
        //   /src-root/sub/image.jpg   (file inside a nested subfolder)
        //   /src-root/empty/          (empty nested subfolder — no files)
        $srcRoot  = Asset\Service::createFolderByPath('/test-fallback-src-' . uniqid());
        $sub      = Asset\Service::createFolderByPath($srcRoot->getFullPath() . '/sub');
        $emptyDir = Asset\Service::createFolderByPath($srcRoot->getFullPath() . '/empty');
        $image    = TestHelper::createImageAsset('image', null, true, 'assets/images/image1.jpg');
        $image->setParentId($sub->getId());
        $image->save();

        $destParent          = Asset\Service::createFolderByPath('/test-fallback-dest-' . uniqid());
        $oldPath             = $srcRoot->getRealFullPath();
        $imageOldStoragePath = $image->getRealFullPath();

        // Wrap the real storage: throw UnableToMoveFile only for the top-level
        // folder move so that the file-by-file fallback in updateChildPaths() is
        // exercised. All other calls (listContents, individual file moves,
        // deleteDirectory) are delegated to the real storage.
        $realStorage = Storage::get('asset');

        $mockStorage = $this->createMock(FilesystemOperator::class);

        $mockStorage->method('move')
            ->willReturnCallback(
                function (string $source, string $dest) use ($realStorage, $oldPath): void {
                    if ($source === $oldPath) {
                        throw UnableToMoveFile::fromLocationTo($source, $dest);
                    }
                    $realStorage->move($source, $dest);
                }
            );

        $mockStorage->method('listContents')
            ->willReturnCallback(
                fn (string $path, bool $deep = false) => $realStorage->listContents($path, $deep)
            );

        $mockStorage->method('deleteDirectory')
            ->willReturnCallback(fn (string $path) => $realStorage->deleteDirectory($path));

        $mockStorage->method('createDirectory')
            ->willReturnCallback(fn (string $path) => $realStorage->createDirectory($path));

        $mockStorage->method('directoryExists')
            ->willReturnCallback(fn (string $path) => $realStorage->directoryExists($path));

        $mockStorage->method('fileExists')
            ->willReturnCallback(fn (string $path) => $realStorage->fileExists($path));

        // Inject mock storage via the Storage service's internal locator
        $storageService = Pimcore::getContainer()->get(Storage::class);
        $locatorProp    = new \ReflectionProperty(Storage::class, 'locator');
        $locatorProp->setAccessible(true);
        $realLocator = $locatorProp->getValue($storageService);

        $mockLocator = $this->createMock(ContainerInterface::class);
        $mockLocator->method('get')
            ->willReturnCallback(
                fn (string $id) => $id === 'pimcore.asset.storage'
                    ? $mockStorage
                    : $realLocator->get($id)
            );

        $locatorProp->setValue($storageService, $mockLocator);

        try {
            $srcRoot->setParentId($destParent->getId());
            $srcRoot->save();
        } finally {
            // Always restore the real locator
            $locatorProp->setValue($storageService, $realLocator);
        }

        $this->assertFalse(
            $realStorage->directoryExists($oldPath),
            'Source directory must be deleted after all files are moved via the fallback path.'
        );

        $newPath = $srcRoot->getRealFullPath();
        $this->assertTrue(
            $realStorage->directoryExists($newPath),
            'Destination directory must exist after the fallback move.'
        );

        $this->assertTrue(
            $realStorage->directoryExists($newPath . '/empty'),
            'Empty subdirectory must be recreated at the destination during the fallback move.'
        );

        // Verify that the nested asset file was actually relocated to the new
        // subtree and is no longer at its old path.  A path-mapping bug could
        // still pass the directory checks above while silently losing the file.
        $imageNewStoragePath = str_replace($oldPath, $newPath, $imageOldStoragePath);

        $this->assertFalse(
            $realStorage->fileExists($imageOldStoragePath),
            'Image file must no longer exist at the source path after the fallback move.'
        );

        $this->assertTrue(
            $realStorage->fileExists($imageNewStoragePath),
            'Image file must be present at the destination path after the fallback move.'
        );
    }

    /**
     * Verifies that the updateChildPaths() rollback correctly removes any
     * destination directories that were created before a mid-move failure.
     * Without this, a failed partial move leaves orphaned directories behind
     * at the destination while the source tree is still intact.
     *
     * The tree contains multiple nested subdirectory levels so that the
     * directory-rollback loop is exercised for more than one $createdDirs entry.
     */
    public function testFolderMoveRollbackCleansUpCreatedDirectories(): void
    {
        // Build:
        //   /src-root/sub/      (subfolder — createDirectory at dest must be rolled back)
        //   /src-root/sub/deep/ (nested subfolder — verifies the loop runs for multiple dirs)
        //   /src-root/file1.jpg (first file — moved to dest before the failure, must be moved back)
        //   /src-root/file2.jpg (second file — its move will throw, triggering rollback)
        $srcRoot = Asset\Service::createFolderByPath('/test-rollback-src-' . uniqid());
        Asset\Service::createFolderByPath($srcRoot->getFullPath() . '/sub');
        Asset\Service::createFolderByPath($srcRoot->getFullPath() . '/sub/deep');

        $file1 = TestHelper::createImageAsset('file1', null, true, 'assets/images/image1.jpg');
        $file1->setParentId($srcRoot->getId());
        $file1->save();

        $file2 = TestHelper::createImageAsset('file2', null, true, 'assets/images/image2.jpg');
        $file2->setParentId($srcRoot->getId());
        $file2->save();

        $destParent  = Asset\Service::createFolderByPath('/test-rollback-dest-' . uniqid());
        $oldPath     = $srcRoot->getRealFullPath();
        $newRootPath = $destParent->getRealFullPath() . '/' . $srcRoot->getKey();

        $realStorage = Storage::get('asset');

        $fallbackFileMoveCount = 0;
        $mockStorage = $this->createMock(FilesystemOperator::class);

        $mockStorage->method('move')
            ->willReturnCallback(
                function (string $source, string $dest) use ($realStorage, $oldPath, $newRootPath, &$fallbackFileMoveCount): void {
                    if ($source === $oldPath) {
                        // Force the file-by-file fallback path
                        throw UnableToMoveFile::fromLocationTo($source, $dest);
                    }

                    // Rollback moves go from destination back to source — always allow them
                    // through so the rollback loop itself is not disrupted by the mock.
                    if (str_starts_with($source, $newRootPath)) {
                        $realStorage->move($source, $dest);
                        return;
                    }

                    if ($fallbackFileMoveCount >= 1) {
                        // Fail on the second forward file move to trigger rollback
                        throw UnableToMoveFile::fromLocationTo($source, $dest);
                    }

                    $realStorage->move($source, $dest);
                    $fallbackFileMoveCount++;
                }
            );

        $mockStorage->method('listContents')
            ->willReturnCallback(
                fn (string $path, bool $deep = false) => $realStorage->listContents($path, $deep)
            );

        $mockStorage->method('deleteDirectory')
            ->willReturnCallback(fn (string $path) => $realStorage->deleteDirectory($path));

        $mockStorage->method('createDirectory')
            ->willReturnCallback(fn (string $path) => $realStorage->createDirectory($path));

        $mockStorage->method('directoryExists')
            ->willReturnCallback(fn (string $path) => $realStorage->directoryExists($path));

        $mockStorage->method('fileExists')
            ->willReturnCallback(fn (string $path) => $realStorage->fileExists($path));

        $storageService = Pimcore::getContainer()->get(Storage::class);
        $locatorProp    = new \ReflectionProperty(Storage::class, 'locator');
        $locatorProp->setAccessible(true);
        $realLocator = $locatorProp->getValue($storageService);

        $mockLocator = $this->createMock(ContainerInterface::class);
        $mockLocator->method('get')
            ->willReturnCallback(
                fn (string $id) => $id === 'pimcore.asset.storage'
                    ? $mockStorage
                    : $realLocator->get($id)
            );

        $locatorProp->setValue($storageService, $mockLocator);

        $caughtException = null;

        try {
            $srcRoot->setParentId($destParent->getId());
            $srcRoot->save();
        } catch (UnableToMoveFile $e) {
            $caughtException = $e;
        } finally {
            $locatorProp->setValue($storageService, $realLocator);
        }

        $this->assertInstanceOf(
            UnableToMoveFile::class,
            $caughtException,
            'Expected save() to throw UnableToMoveFile after a partial move failure.'
        );

        $this->assertFalse(
            $realStorage->directoryExists($newRootPath . '/sub'),
            'Destination directory "sub" created before the failure must be deleted by rollback.'
        );

        $this->assertFalse(
            $realStorage->directoryExists($newRootPath . '/sub/deep'),
            'Nested destination directory "sub/deep" created before the failure must be deleted by rollback.'
        );

        $this->assertTrue(
            $realStorage->directoryExists($oldPath),
            'Source directory must still exist after a rolled-back partial move.'
        );

        // Verify the file that was successfully moved before the failure was
        // restored to the source — and is no longer present at the destination.
        $file1StoragePath = $file1->getRealFullPath();
        $file1DestPath    = str_replace($oldPath, $newRootPath, $file1StoragePath);

        $this->assertTrue(
            $realStorage->fileExists($file1StoragePath),
            'File moved before the failure must be restored to the source by rollback.'
        );

        $this->assertFalse(
            $realStorage->fileExists($file1DestPath),
            'File moved before the failure must no longer exist at the destination after rollback.'
        );
    }

    /**
     * Verifies that an asset can be saved with custom user modification id.
     *
     */
    public function testCustomUserModification(): void
    {
        $userId = 101;
        $asset = TestHelper::createImageAsset();

        //custom user modification
        $asset->setUserModification($userId);
        $asset->save();
        $this->assertEquals($userId, $asset->getUserModification(), 'Expected custom user modification id');

        //auto generated user modification
        $asset = Asset::getById($asset->getId(), ['force' => true]);
        $asset->save();
        $this->assertEquals(0, $asset->getUserModification(), 'Expected auto assigned user modification id');
    }

    /**
     * Verifies that an asset can be saved with custom modification date.
     *
     */
    public function testCustomModificationDate(): void
    {
        $customDateTime = new \Carbon\Carbon();
        $customDateTime = $customDateTime->subHour();

        $asset = TestHelper::createDocumentAsset();

        //custom modification date
        $asset->setModificationDate($customDateTime->getTimestamp());
        $asset->save();
        $this->assertEquals($customDateTime->getTimestamp(), $asset->getModificationDate(), 'Expected custom modification date');

        //auto generated modification date
        $currentTime = time();
        $asset = Asset::getById($asset->getId(), ['force' => true]);
        $asset->save();
        $this->assertGreaterThanOrEqual($currentTime, $asset->getModificationDate(), 'Expected auto assigned modification date');
    }

    public function testForceReload(): void
    {
        $asset = TestHelper::createImageAsset();

        $this->assertTrue(Asset::getById($asset->getId()) === Asset::getById($asset->getId()));
        $this->assertFalse(Asset::getById($asset->getId()) === Asset::getById($asset->getId(), ['force' => true]));
    }

    public function testAssetFullPath(): void
    {
        $asset = TestHelper::createImageAsset();

        $thumbnailConfig = TestHelper::createThumbnailConfigurationScaleByWidth();

        $this->assertMatchesRegularExpression('@^(https?|data):@', $asset->getFrontendPath());
        $this->assertStringContainsString($asset->getFullPath(), $asset->getFrontendPath());

        $thumbnail = $asset->getThumbnail($thumbnailConfig->getName());

        $thumbnailFullUrl = $thumbnail->getFrontendPath();

        $this->assertMatchesRegularExpression('@^(https?|data):@', $thumbnailFullUrl);
        $this->assertStringContainsString($thumbnail->getPath(), $thumbnailFullUrl);
    }

    public function testMimeTypeFromStream(): void
    {
        $asset = Asset::create(
            1,
            [
                'stream' => fopen(
                    TestHelper::resolveFilePath('assets/images/image1.jpg'),
                    'rb'
                ),
                'filename' => 'image1_from_stream.jpg',
            ]
        );

        $this->assertEquals('image/jpeg', $asset->getMimeType());
    }

    /**
     * @throws Exception
     */
    public function testMimeTypeFromFile(): void
    {
        $asset = TestHelper::createImageAsset(
            '',
            null,
            true,
            'assets/images/image1.jpg'
        );

        $this->assertEquals('image/jpeg', $asset->getMimeType());
    }

    public function testMimeTypeFromContent(): void
    {
        $fileName = 'image1_from_content';
        $assetData = @file_get_contents(
            TestHelper::resolveFilePath('assets/images/image1.jpg'),
            false
        );
        $data = [
            'data' => $assetData,
            'key' => $fileName,
            'filename' => $fileName,
        ];
        $asset = Asset::create(1, $data);

        $this->assertEquals('image/jpeg', $asset->getMimeType());
    }
}
