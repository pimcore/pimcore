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

namespace Pimcore\Tests\Unit\Messenger\Handler;

use Doctrine\Persistence\ConnectionRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Messenger\GenerateImageThumbnailsMessage;
use Pimcore\Messenger\Handler\GenerateImageThumbnailsHandler;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Asset\Image\Thumbnail\VariantResolver;
use Pimcore\Model\Asset\Image\ThumbnailInterface;
use Pimcore\Model\Exception\ThumbnailFormatNotSupportedException;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;
use RuntimeException;

/**
 * @internal
 */
class GenerateImageThumbnailsHandlerTest extends TestCase
{
    private const ASSET_ID = 987654322;

    private const THUMBNAIL_NAME = 'pre_generate_unittest';

    private const CHECKSUM = 'abc123';

    private LongRunningHelper $longRunningHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->longRunningHelper = new LongRunningHelper($this->createMock(ConnectionRegistry::class));

        $config = new Config();
        $config->setName(self::THUMBNAIL_NAME);
        $config->setFormat('PNG');
        $config->setItems([['method' => 'scaleByWidth', 'arguments' => ['width' => 400]]]);
        $config->setMedias([
            '(max-width: 600px)' => [['method' => 'scaleByWidth', 'arguments' => ['width' => 200]]],
        ]);
        RuntimeCache::set($this->configCacheKey(), $config);
    }

    protected function tearDown(): void
    {
        RuntimeCache::getInstance()->offsetUnset($this->assetCacheKey());
        RuntimeCache::getInstance()->offsetUnset($this->configCacheKey());

        parent::tearDown();
    }

    public function testEveryVariantIsGeneratedWithoutDeferring(): void
    {
        $image = $this->createImage(self::CHECKSUM);

        $options = [];
        $thumbnail = $this->createMock(ThumbnailInterface::class);
        $thumbnail->method('getPath')->willReturnCallback(function (array $args = []) use (&$options) {
            $options[] = $args;

            return '/thumbnail.png';
        });

        $variants = [];
        $image->method('getThumbnail')->willReturnCallback(function (Config $variant) use (&$variants, $thumbnail) {
            $variants[] = [$variant->getFilenameSuffix(), $variant->getHighResolution()];

            return $thumbnail;
        });

        $this->handle();

        // 2 = the default of pimcore.assets.image.thumbnails.max_srcset_dpi_factor
        $suffix = 'media--' . hash('crc32b', '(max-width: 600px)') . '--query';
        self::assertSame([[null, null], [null, 2.0], [$suffix, 1.0], [$suffix, 2.0]], $variants);
        self::assertSame(array_fill(0, 4, ['deferredAllowed' => false]), $options);
    }

    public function testVariantsBeyondTheLimitAreLeftToTheRequest(): void
    {
        $image = $this->createImage(self::CHECKSUM);
        $image->expects(self::exactly(3))
            ->method('getThumbnail')
            ->willReturn($this->createMock(ThumbnailInterface::class));

        $this->handle(maxVariantsPerConfig: 3);
    }

    public function testStaleMessageIsIgnored(): void
    {
        $image = $this->createImage('checksum-of-a-newer-binary');
        $image->expects(self::never())->method('getThumbnail');

        $this->handle();
    }

    public function testImageWithoutChecksumIsIgnored(): void
    {
        $image = $this->createImage(null);
        $image->expects(self::never())->method('getThumbnail');

        $this->handle();
    }

    public function testMissingImageIsIgnored(): void
    {
        $folder = $this->getMockBuilder(Asset\Folder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $folder->method('getId')->willReturn(self::ASSET_ID);
        RuntimeCache::set($this->assetCacheKey(), $folder);

        $this->handle();

        $this->addToAssertionCount(1);
    }

    public function testMissingThumbnailConfigurationIsIgnored(): void
    {
        $image = $this->createImage(self::CHECKSUM);
        $image->expects(self::never())->method('getThumbnail');

        $this->handle(thumbnailName: self::THUMBNAIL_NAME . '_missing');
    }

    public function testUnsupportedVariantDoesNotStopTheOthers(): void
    {
        $image = $this->createImage(self::CHECKSUM);

        $failing = $this->createMock(ThumbnailInterface::class);
        $failing->method('getPath')->willThrowException(new ThumbnailFormatNotSupportedException());

        $working = $this->createMock(ThumbnailInterface::class);
        $working->expects(self::exactly(3))->method('getPath')->willReturn('/thumbnail.png');

        $image->method('getThumbnail')->willReturnOnConsecutiveCalls($failing, $working, $working, $working);

        $this->handle();
    }

    public function testTemporaryFilesAreRemovedWhenGenerationFails(): void
    {
        $image = $this->createImage(self::CHECKSUM);

        $thumbnail = $this->createMock(ThumbnailInterface::class);
        $thumbnail->method('getPath')->willThrowException(new RuntimeException('storage unavailable'));
        $image->method('getThumbnail')->willReturn($thumbnail);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'pimcore-thumbnail-pre-generate-test');
        self::assertNotFalse($temporaryFile);
        $this->longRunningHelper->addTmpFilePath($temporaryFile);

        try {
            $this->handle();
            self::fail('An unexpected failure must reach Messenger, so that the message is retried');
        } catch (RuntimeException $e) {
            self::assertSame('storage unavailable', $e->getMessage());
        }

        self::assertFileDoesNotExist($temporaryFile);
    }

    /**
     * @return Asset\Image&MockObject
     */
    private function createImage(?string $checksum): Asset\Image
    {
        $image = $this->getMockBuilder(Asset\Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getThumbnail'])
            ->getMock();
        $image->method('getId')->willReturn(self::ASSET_ID);

        if ($checksum !== null) {
            $image->setCustomSetting('checksum', $checksum);
        }

        RuntimeCache::set($this->assetCacheKey(), $image);

        return $image;
    }

    private function handle(int $maxVariantsPerConfig = 25, string $thumbnailName = self::THUMBNAIL_NAME): void
    {
        $handler = new GenerateImageThumbnailsHandler(
            new NullLogger(),
            $this->longRunningHelper,
            new VariantResolver(),
            $maxVariantsPerConfig
        );
        $handler(new GenerateImageThumbnailsMessage(self::ASSET_ID, $thumbnailName, self::CHECKSUM));
    }

    private function assetCacheKey(): string
    {
        return (new ReflectionMethod(Asset::class, 'getCacheKey'))->invoke(null, self::ASSET_ID);
    }

    private function configCacheKey(): string
    {
        return (new ReflectionMethod(Config::class, 'getCacheKey'))->invoke(null, self::THUMBNAIL_NAME);
    }
}
