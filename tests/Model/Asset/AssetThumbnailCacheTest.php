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

use League\Flysystem\FilesystemOperator;
use Pimcore;
use Pimcore\Bundle\CoreBundle\Controller\PublicServicesController;
use Pimcore\Config;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetThumbnailCacheTest extends TestCase
{
    protected Asset $testAsset;

    protected string $thumbnailName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testAsset = TestHelper::createImageAsset('', null, true, 'assets/images/image1.jpg');
        $this->assertInstanceOf(Asset\Image::class, $this->testAsset);

        $thumbnailConfig = TestHelper::createThumbnailConfigurationScaleByWidth();
        $this->thumbnailName = $thumbnailConfig->getName();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        TestHelper::clearThumbnailConfigurations();
    }

    protected function needsDb(): bool
    {
        return true;
    }

    public function testThumbnailCache(): void
    {
        $asset = $this->testAsset;
        $thumbnailName = $this->thumbnailName;

        /** @var Asset\Image $asset * */
        $thumbConfig = $asset->getThumbnail($thumbnailName);
        $asset->clearThumbnails(true);

        $thumbnailStorage = Storage::get('thumbnail');
        $this->assertNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));

        //check if thumbnail exists after getting path reference deferred
        $pathReference = $thumbConfig->getPathReference(true);
        $this->assertFalse($thumbnailStorage->fileExists($pathReference['storagePath']));
        $this->assertNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));

        //create thumbnail
        $thumbConfig->getPath(['deferredAllowed' => false]);

        //recheck if thumbnail exists
        $this->assertTrue($thumbnailStorage->fileExists($pathReference['storagePath']));
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));

        //update asset
        $asset->setData(file_get_contents(TestHelper::resolveFilePath('assets/images/image2.jpg')));
        $asset->save();

        //check if cache is cleared
        $this->assertNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));
        $this->assertFalse($thumbnailStorage->fileExists($pathReference['storagePath']));

        //fetch config again as the asset checksum changed
        $thumbConfig = $asset->getThumbnail($thumbnailName);
        $pathReference = $thumbConfig->getPathReference(true);

        //load asset via public service controller
        $controller = new PublicServicesController();
        $subRequest = new Request(attributes: [
            'assetId' => $asset->getId(),
            'thumbnailName' => $thumbnailName,
            'filename' => $thumbConfig->getFilename(),
            'type' => 'image',
            'prefix' => '',
        ]);
        $response = $controller->thumbnailAction($subRequest);
        $response->sendContent(); // calls getStream() in order to generate the thumbnail file

        //check if cache is filled
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));
        $this->assertTrue($thumbnailStorage->fileExists($pathReference['storagePath']));

        //delete just file on file system
        //check if cache cleared - expected to not be cleared
        $thumbnailStorage->delete($pathReference['storagePath']);
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));
        $this->assertFalse($thumbnailStorage->fileExists($pathReference['storagePath']));

        //check via controller
        //check if thumbnail is regenerated and cache is filled
        $subRequest = new Request(attributes: [
            'assetId' => $asset->getId(),
            'thumbnailName' => $thumbnailName,
            'filename' => $thumbConfig->getFilename(),
            'type' => 'image',
            'prefix' => '',
        ]);
        $response = $controller->thumbnailAction($subRequest);
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));
        $this->assertTrue($thumbnailStorage->fileExists($pathReference['storagePath']));

        //delete again from file system
        $thumbnailStorage->delete($pathReference['storagePath']);
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $thumbConfig->getFilename()));
        $this->assertFalse($thumbnailStorage->fileExists($pathReference['storagePath']));
    }

    public function testThumbnailCacheControlHeaders(): void
    {
        $customLifetime = 3600;

        $assetsConfig = Config::getSystemConfiguration('assets');
        $originalLifetime = $assetsConfig['thumbnails']['cache_lifetime'];
        $assetsConfig['thumbnails']['cache_lifetime'] = $customLifetime;
        Config::setSystemConfiguration($assetsConfig, 'assets');

        try {
            $asset = $this->testAsset;
            $thumbnailName = $this->thumbnailName;

            /** @var Asset\Image $asset */
            $thumbConfig = $asset->getThumbnail($thumbnailName);
            $asset->clearThumbnails(true);

            $thumbnailStorage = Storage::get('thumbnail');

            // Generate the thumbnail so it exists in storage
            $thumbConfig->getPath(['deferredAllowed' => false]);
            $pathReference = $thumbConfig->getPathReference(true);
            $this->assertTrue($thumbnailStorage->fileExists($pathReference['storagePath']));

            $controller = new PublicServicesController();

            // Branch 1: existing file – the storage path is used as the request URI so
            // getStreamedResponseForThumbnail() serves it directly without re-generating.
            $storagePath = $pathReference['storagePath'];
            $existingFileRequest = Request::create($storagePath);
            $existingFileRequest->attributes->set('assetId', $asset->getId());
            $existingFileRequest->attributes->set('thumbnailName', $thumbnailName);
            $existingFileRequest->attributes->set('filename', $thumbConfig->getFilename());
            $existingFileRequest->attributes->set('type', 'image');
            $existingFileRequest->attributes->set('prefix', '');

            $timeBefore = time();
            $response = $controller->thumbnailAction($existingFileRequest);
            $timeAfter = time();

            $this->assertInstanceOf(StreamedResponse::class, $response);
            $this->assertTrue($response->headers->hasCacheControlDirective('public'));
            $this->assertSame($customLifetime, (int) $response->headers->getCacheControlDirective('max-age'));
            $expiresTimestamp = strtotime($response->headers->get('Expires'));
            $this->assertGreaterThanOrEqual($timeBefore + $customLifetime, $expiresTimestamp);
            $this->assertLessThanOrEqual($timeAfter + $customLifetime, $expiresTimestamp);

            // Branch 2: on-demand generation – delete the file so it must be re-generated.
            $thumbnailStorage->delete($storagePath);
            $this->assertFalse($thumbnailStorage->fileExists($storagePath));

            $onDemandRequest = new Request(attributes: [
                'assetId' => $asset->getId(),
                'thumbnailName' => $thumbnailName,
                'filename' => $thumbConfig->getFilename(),
                'type' => 'image',
                'prefix' => '',
            ]);

            $timeBefore2 = time();
            $response2 = $controller->thumbnailAction($onDemandRequest);
            $timeAfter2 = time();

            $this->assertInstanceOf(StreamedResponse::class, $response2);
            $this->assertTrue($response2->headers->hasCacheControlDirective('public'));
            $this->assertSame($customLifetime, (int) $response2->headers->getCacheControlDirective('max-age'));
            $expiresTimestamp2 = strtotime($response2->headers->get('Expires'));
            $this->assertGreaterThanOrEqual($timeBefore2 + $customLifetime, $expiresTimestamp2);
            $this->assertLessThanOrEqual($timeAfter2 + $customLifetime, $expiresTimestamp2);
        } finally {
            $assetsConfig['thumbnails']['cache_lifetime'] = $originalLifetime;
            Config::setSystemConfiguration($assetsConfig, 'assets');
        }
    }

    /**
     * Regression test for #18317: thumbnails generated by Pimcore 12.3.0-12.3.11
     * for configs without a crop box live under a hash that included the (false)
     * crop box flag. After the hash fix they must be reused - file AND status
     * cache row moved to the current name - instead of being regenerated.
     */
    public function testCropBoxCompatThumbnailIsMigratedNotRegenerated(): void
    {
        /** @var Asset\Image $asset */
        $asset = $this->testAsset;
        $thumbnailName = $this->thumbnailName;

        $thumb = $asset->getThumbnail($thumbnailName);
        $asset->clearThumbnails(true);

        $storage = Storage::get('thumbnail');
        $config = $thumb->getConfig();
        $this->assertFalse($config->isUseCropBox());

        // generate the thumbnail under the current (fixed) hash
        $thumb->getPath(['deferredAllowed' => false]);
        $storagePath = $thumb->getPathReference(true)['storagePath'];
        $filename = $thumb->getFilename();
        $this->assertTrue($storage->fileExists($storagePath));
        $this->assertNotNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $filename));

        // simulate the 12.3.0-12.3.11 on-disk state: same file and cache row, but
        // under the hash that always included the crop box flag
        $currentHash = $config->getHash([$asset->getChecksum()]);
        $compatHash = $config->getCropBoxCompatHash([$asset->getChecksum()]);
        $this->assertNotSame($currentHash, $compatHash);

        $compatFilename = str_replace('.' . $currentHash . '.', '.' . $compatHash . '.', $filename);
        $compatStoragePath = str_replace('.' . $currentHash . '.', '.' . $compatHash . '.', $storagePath);

        $storage->move($storagePath, $compatStoragePath);
        $asset->getDao()->moveThumbnailCache($thumbnailName, $filename, $compatFilename);

        // precondition: nothing under the current name, everything under the old one
        $this->assertFalse($storage->fileExists($storagePath));
        $this->assertNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $filename));
        $this->assertTrue($storage->fileExists($compatStoragePath));
        $compatModificationDate = $asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $compatFilename);
        $this->assertNotNull($compatModificationDate);

        // request the thumbnail again - the fallback must adopt the old file
        $asset->getThumbnail($thumbnailName)->getPath(['deferredAllowed' => false]);

        // the old file and cache row are gone, the current ones are restored ...
        $this->assertTrue($storage->fileExists($storagePath));
        $this->assertFalse($storage->fileExists($compatStoragePath));
        $this->assertNull($asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $compatFilename));

        // ... and the modification date is preserved, proving the file was moved
        // and the cache row migrated rather than the thumbnail regenerated
        $this->assertSame(
            $compatModificationDate,
            $asset->getDao()->getCachedThumbnailModificationDate($thumbnailName, $filename),
        );
    }

    /**
     * A cached thumbnail is streamed straight from storage. The existence check that used to
     * precede readStream() is gone, so remote adapters no longer pay for an extra HEAD request
     * on every delivery.
     */
    public function testDeliveringACachedThumbnailDoesNotCheckFileExistence(): void
    {
        $asset = $this->testAsset;
        $thumbnailName = $this->thumbnailName;

        /** @var Asset\Image $asset */
        $thumbConfig = $asset->getThumbnail($thumbnailName);
        $asset->clearThumbnails(true);

        // generate the thumbnail so the delivery below is a cache hit
        $thumbConfig->getPath(['deferredAllowed' => false]);
        $pathReference = $thumbConfig->getPathReference(true);
        $storagePath = $pathReference['storagePath'];

        $contents = 'cached-thumbnail-contents';
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $storage = $this->createMock(FilesystemOperator::class);
        $storage->expects($this->never())->method('fileExists');
        $storage->method('readStream')->willReturn($stream);
        $storage->method('mimeType')->willReturn('image/jpeg');
        $storage->method('fileSize')->willReturn(strlen($contents));

        $response = $this->withThumbnailStorage(
            $storage,
            fn () => Asset\Service::getStreamedResponseForThumbnail(
                $this->buildThumbnailConfig($asset, $thumbConfig->getFilename()),
                $storagePath,
            ),
        );

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
        $this->assertSame((string) strlen($contents), $response->headers->get('Content-Length'));
    }

    /**
     * A request URI that denotes a directory rather than a file must fall back to thumbnail
     * generation. readStream() cannot be used to rule this out: the local adapter opens
     * directories successfully, so without an explicit guard the storage root would be streamed
     * and the subsequent mimeType() call would throw.
     */
    public function testDirectoryShapedUriFallsBackToThumbnailGeneration(): void
    {
        $asset = $this->testAsset;
        $thumbnailName = $this->thumbnailName;

        /** @var Asset\Image $asset */
        $thumbConfig = $asset->getThumbnail($thumbnailName);
        $asset->clearThumbnails(true);

        $thumbnailStorage = Storage::get('thumbnail');
        $pathReference = $thumbConfig->getPathReference(true);
        $storagePath = $pathReference['storagePath'];
        $config = $this->buildThumbnailConfig($asset, $thumbConfig->getFilename());

        // '/' is what PublicServicesController passes when the request carries no REQUEST_URI,
        // and it normalizes to the root of the thumbnail storage
        foreach (['/', '/directory-shaped-path/'] as $uri) {
            if ($thumbnailStorage->fileExists($storagePath)) {
                $thumbnailStorage->delete($storagePath);
            }

            $response = Asset\Service::getStreamedResponseForThumbnail($config, $uri);

            $this->assertInstanceOf(
                StreamedResponse::class,
                $response,
                sprintf('URI "%s" should fall back to thumbnail generation.', $uri),
            );

            ob_start();
            $response->sendContent();
            ob_end_clean();

            $this->assertTrue(
                $thumbnailStorage->fileExists($storagePath),
                sprintf('URI "%s" should have re-generated the thumbnail.', $uri),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildThumbnailConfig(Asset $asset, string $filename): array
    {
        return [
            'prefix' => '',
            'type' => 'image',
            'asset_id' => $asset->getId(),
            'thumbnail_name' => $this->thumbnailName,
            'filename' => $filename,
            'file_extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
        ];
    }

    /**
     * Runs $callback with the thumbnail storage replaced by $storage.
     *
     * Pimcore\Tool\Storage resolves each storage from a tagged service locator, so the whole
     * service is swapped for one backed by a locator that returns $storage for the thumbnail
     * storage and delegates everything else to the original.
     */
    private function withThumbnailStorage(FilesystemOperator $storage, callable $callback): mixed
    {
        $storageService = Pimcore::getContainer()->get(Storage::class);

        // the container refuses to replace an already initialized service, and Storage is
        // initialized long before a test runs, so swap the locator it resolves each storage from
        $property = new ReflectionProperty(Storage::class, 'locator');
        $originalLocator = $property->getValue($storageService);

        $property->setValue($storageService, new class($storage, $originalLocator) implements ContainerInterface {
            public function __construct(
                private FilesystemOperator $thumbnailStorage,
                private ContainerInterface $original,
            ) {
            }

            public function has(string $id): bool
            {
                return $id === 'pimcore.thumbnail.storage' || $this->original->has($id);
            }

            public function get(string $id): mixed
            {
                return $id === 'pimcore.thumbnail.storage'
                    ? $this->thumbnailStorage
                    : $this->original->get($id);
            }
        });

        try {
            return $callback();
        } finally {
            $property->setValue($storageService, $originalLocator);
        }
    }
}
