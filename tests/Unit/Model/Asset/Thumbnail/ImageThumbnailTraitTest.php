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

class ImageThumbnailTraitTest extends TestCase
{
    private const STORAGE_PATH = '/testimage/1/image-thumb__1__unittest/testimage.jpg';

    public function testGetStreamRethrowsWhenFileStillExists(): void
    {
        $storage = $this->createMock(FilesystemOperator::class);
        $storage->method('readStream')->willThrowException(UnableToReadFile::fromLocation(self::STORAGE_PATH));
        $storage->method('fileExists')->willReturn(true);

        $asset = $this->createMock(Asset\Image::class);
        $asset->expects($this->never())->method('getDao');

        $thumbnail = $this->createThumbnail($storage, $asset, $this->createConfig());

        $this->expectException(UnableToReadFile::class);
        $thumbnail->getStream();
    }

    public function testGetStreamRethrowsWhenExistenceCannotBeDetermined(): void
    {
        $storage = $this->createMock(FilesystemOperator::class);
        $storage->method('readStream')->willThrowException(UnableToReadFile::fromLocation(self::STORAGE_PATH));
        $storage->method('fileExists')->willThrowException(new UnableToCheckFileExistence('Unable to check file existence for: ' . self::STORAGE_PATH));

        $asset = $this->createMock(Asset\Image::class);
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
        $asset->method('getDao')->willReturn($dao);

        $thumbnail = $this->createThumbnail($storage, $asset, $this->createConfig());

        $this->assertNull($thumbnail->getStream());
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
        $asset->expects($this->never())->method('getDao');

        $thumbnail = new class($this->createConfig(), $storage, $asset, $owner, self::STORAGE_PATH) {
            use ImageThumbnailTrait;

            public function __construct(
                ?Config $config,
                private readonly FilesystemOperator $storage,
                ?Asset $asset,
                private readonly ?Asset $cacheOwner,
                string $storagePath
            ) {
                $this->asset = $asset;
                $this->config = $config;
                $this->pathReference = [
                    'type' => 'thumbnail',
                    'src' => $storagePath,
                    'storagePath' => $storagePath,
                ];
            }

            protected function getThumbnailStorage(): FilesystemOperator
            {
                return $this->storage;
            }

            protected function getThumbnailStatusCacheOwner(): ?Asset
            {
                return $this->cacheOwner;
            }
        };

        $this->assertNull($thumbnail->getStream());
    }

    private function createConfig(): Config
    {
        $config = new Config();
        $config->setName('unittest');

        return $config;
    }

    private function createThumbnail(FilesystemOperator $storage, Asset $asset, Config $config): object
    {
        return new class($storage, $asset, $config, self::STORAGE_PATH) {
            use ImageThumbnailTrait;

            public function __construct(
                private readonly FilesystemOperator $storage,
                ?Asset $asset,
                ?Config $config,
                string $storagePath
            ) {
                $this->asset = $asset;
                $this->config = $config;
                $this->pathReference = [
                    'type' => 'thumbnail',
                    'src' => $storagePath,
                    'storagePath' => $storagePath,
                ];
            }

            protected function getThumbnailStorage(): FilesystemOperator
            {
                return $this->storage;
            }
        };
    }
}
