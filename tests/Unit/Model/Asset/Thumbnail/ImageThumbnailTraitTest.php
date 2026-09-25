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

namespace Pimcore\Tests\Unit\Model\Asset\Thumbnail;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToReadFile;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Asset\Thumbnail\ImageThumbnailTrait;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
class ImageThumbnailTraitTest extends TestCase
{
    private const STORAGE_PATH = '/testimage/1/image-thumb__1__unittest/testimage.jpg';

    public function testGetStreamRethrowsWhenFileStillExists(): void
    {
        $storage = $this->createMock(FilesystemOperator::class);
        $storage->method('readStream')->willThrowException(UnableToReadFile::fromLocation(self::STORAGE_PATH));
        $storage->method('fileExists')->willReturn(true);

        // a permission, I/O or backend availability problem is not a stale reference,
        // so the status cache entry must survive and the instance keeps its state
        $asset = $this->createMock(Asset\Image::class);
        $asset->method('getFilename')->willReturn('testimage.jpg');
        $asset->expects($this->never())->method('getDao');

        $thumbnail = $this->createThumbnail($storage, $asset, $this->createConfig());

        try {
            $thumbnail->getStream();
            $this->fail('Expected ' . UnableToReadFile::class . ' to be thrown');
        } catch (UnableToReadFile $e) {
            $this->assertSame(self::STORAGE_PATH, $thumbnail->getPathReference(true)['storagePath']);
            $this->assertSame(0, $thumbnail->generateCalls);
        }
    }

    public function testGetStreamRethrowsWhenExistenceCannotBeDetermined(): void
    {
        $storage = $this->createMock(FilesystemOperator::class);
        $storage->method('readStream')->willThrowException(UnableToReadFile::fromLocation(self::STORAGE_PATH));
        $storage->method('fileExists')->willThrowException(UnableToCheckFileExistence::forLocation(self::STORAGE_PATH));

        $asset = $this->createMock(Asset\Image::class);
        $asset->method('getFilename')->willReturn('testimage.jpg');
        $asset->expects($this->never())->method('getDao');

        $thumbnail = $this->createThumbnail($storage, $asset, $this->createConfig());

        $this->expectException(UnableToReadFile::class);
        $thumbnail->getStream();
    }

    public function testGetStreamInvalidatesStatusCacheWhenFileIsMissing(): void
    {
        $storage = $this->createMock(FilesystemOperator::class);
        $storage->method('readStream')->willThrowException(UnableToReadFile::fromLocation(self::STORAGE_PATH));
        $storage->method('fileExists')->willReturn(false);

        $dao = $this->createMock(Asset\Dao::class);
        $dao->expects($this->once())
            ->method('deleteFromThumbnailCache')
            ->with('unittest', basename(self::STORAGE_PATH));

        $asset = $this->createMock(Asset\Image::class);
        $asset->method('getFilename')->willReturn('testimage.jpg');
        $asset->method('getDao')->willReturn($dao);

        $thumbnail = $this->createThumbnail($storage, $asset, $this->createConfig());

        $this->assertNull($thumbnail->getStream());

        // the memoized path reference is discarded as well, so a later call on this same
        // instance re-resolves instead of pointing at the file that is no longer there
        $this->assertSame(0, $thumbnail->generateCalls);
        $thumbnail->getPathReference(true);
        $this->assertSame(1, $thumbnail->generateCalls);
    }

    public function testGetStreamInvalidatesStatusCacheOfDelegatedOwner(): void
    {
        $storage = $this->createMock(FilesystemOperator::class);
        $storage->method('readStream')->willThrowException(UnableToReadFile::fromLocation(self::STORAGE_PATH));
        $storage->method('fileExists')->willReturn(false);

        // e.g. a video thumbnail delegating its path reference to a poster image asset:
        // the stale status cache entry belongs to the delegated asset, not the thumbnail's own asset
        $ownerDao = $this->createMock(Asset\Dao::class);
        $ownerDao->expects($this->once())
            ->method('deleteFromThumbnailCache')
            ->with('unittest', basename(self::STORAGE_PATH));

        $owner = $this->createMock(Asset\Image::class);
        $owner->method('getDao')->willReturn($ownerDao);

        $asset = $this->createMock(Asset\Image::class);
        $asset->method('getFilename')->willReturn('testimage.jpg');
        $asset->expects($this->never())->method('getDao');

        $thumbnail = $this->createThumbnail($storage, $asset, $this->createConfig(), $owner);

        $this->assertNull($thumbnail->getStream());
    }

    private function createConfig(): Config
    {
        $config = new Config();
        $config->setName('unittest');

        return $config;
    }

    private function createThumbnail(
        FilesystemOperator $storage,
        Asset $asset,
        Config $config,
        ?Asset $cacheOwner = null
    ): object {
        return new class($storage, $asset, $config, $cacheOwner, self::STORAGE_PATH) {
            use ImageThumbnailTrait;

            public int $generateCalls = 0;

            public function __construct(
                private readonly FilesystemOperator $storage,
                ?Asset $asset,
                ?Config $config,
                private readonly ?Asset $cacheOwner,
                private readonly string $storagePath
            ) {
                $this->asset = $asset;
                $this->config = $config;
                $this->pathReference = $this->buildPathReference();
            }

            public function generate(bool $deferredAllowed = true): void
            {
                $this->generateCalls++;
                $this->pathReference = $this->buildPathReference();
            }

            protected function getThumbnailStorage(): FilesystemOperator
            {
                return $this->storage;
            }

            protected function getThumbnailStatusCacheOwner(): ?Asset
            {
                return $this->cacheOwner ?? $this->asset;
            }

            private function buildPathReference(): array
            {
                return [
                    'type' => 'thumbnail',
                    'src' => $this->storagePath,
                    'storagePath' => $this->storagePath,
                ];
            }
        };
    }
}
