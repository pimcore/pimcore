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
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Messenger\GenerateImageThumbnailsMessage;
use Pimcore\Messenger\Handler\AssetUpdateTasksHandler;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\ThumbnailInterface;
use Pimcore\Model\Version;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use function sprintf;
use function strlen;
use function unserialize;

/**
 * @internal
 */
class AssetUpdateTasksHandlerThumbnailPreGenerationTest extends TestCase
{
    private const ASSET_ID = 987654323;

    /**
     * @var GenerateImageThumbnailsMessage[]
     */
    private array $dispatched = [];

    protected function setUp(): void
    {
        parent::setUp();

        Version::enable();
        $this->dispatched = [];
    }

    protected function tearDown(): void
    {
        Version::enable();
        RuntimeCache::getInstance()->offsetUnset($this->assetCacheKey());

        parent::tearDown();
    }

    public function testNothingIsDispatchedByDefault(): void
    {
        $this->createImage();

        $this->handle([]);

        self::assertSame([], $this->dispatched);
    }

    public function testOneMessagePerConfiguredThumbnailIsDispatchedAfterTheImageWasSaved(): void
    {
        $image = $this->createImage();

        $saved = false;
        $image->expects(self::once())->method('save')->willReturnCallback(function () use (&$saved, $image) {
            self::assertSame([], $this->dispatched, 'Messages must not be dispatched before the image is saved');
            $saved = true;

            return $image;
        });

        $this->handle(['hero', 'card', 'hero']);

        self::assertTrue($saved);
        self::assertSame(
            [[self::ASSET_ID, 'hero'], [self::ASSET_ID, 'card']],
            array_map(
                static fn (GenerateImageThumbnailsMessage $message) => [$message->getId(), $message->getThumbnailName()],
                $this->dispatched
            )
        );
    }

    /**
     * `pimcore:assets:add-to-update-task-queue` re-queues the update tasks of images whose binary
     * did not change, that must not warm the configured thumbnails for the whole catalog.
     */
    public function testNothingIsDispatchedForARequeuedUnchangedImage(): void
    {
        $this->createImage();

        $this->handle(['hero'], dataChanged: false);

        self::assertSame([], $this->dispatched);
    }

    public function testMessageSerializedBeforeTheMarkerExistedIsNotABinaryChange(): void
    {
        $legacyPayload = sprintf('O:%d:"%s":1:{s:5:"%sid";i:%d;}', strlen(AssetUpdateTasksMessage::class), AssetUpdateTasksMessage::class, "\0*\0", self::ASSET_ID);
        $message = unserialize($legacyPayload);

        self::assertInstanceOf(AssetUpdateTasksMessage::class, $message);
        self::assertSame(self::ASSET_ID, $message->getId());
        self::assertFalse($message->isDataChanged());
    }

    /**
     * @return Asset\Image&MockObject
     */
    private function createImage(): Asset\Image
    {
        $image = $this->getMockBuilder(Asset\Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getId',
                'getRealFullPath',
                'getDimensions',
                'handleEmbeddedMetaData',
                'markFieldDirty',
                'save',
                'getThumbnail',
                'generateLowQualityPreview',
            ])
            ->getMock();

        $image->method('getId')->willReturn(self::ASSET_ID);
        $image->method('getRealFullPath')->willReturn('/test.jpg');
        $image->method('getDimensions')->willReturn(['width' => 800, 'height' => 600]);
        $image->method('getThumbnail')->willReturn($this->createMock(ThumbnailInterface::class));

        RuntimeCache::set($this->assetCacheKey(), $image);

        return $image;
    }

    /**
     * @param string[] $preGenerateThumbnails
     */
    private function handle(array $preGenerateThumbnails, bool $dataChanged = true): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturnCallback(function (object $message) {
            $this->dispatched[] = $message;

            return new Envelope($message);
        });

        $handler = new AssetUpdateTasksHandler(
            new NullLogger(),
            new LongRunningHelper($this->createMock(ConnectionRegistry::class)),
            new LockFactory(new InMemoryStore()),
            $messageBus,
            $preGenerateThumbnails
        );
        $handler(new AssetUpdateTasksMessage(self::ASSET_ID, $dataChanged));
    }

    private function assetCacheKey(): string
    {
        return (new ReflectionMethod(Asset::class, 'getCacheKey'))->invoke(null, self::ASSET_ID);
    }
}
