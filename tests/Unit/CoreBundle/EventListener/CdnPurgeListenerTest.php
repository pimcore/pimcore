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

namespace Pimcore\Tests\Unit\CoreBundle\EventListener;

use Pimcore\Bundle\CoreBundle\EventListener\CdnPurgeListener;
use Pimcore\Cdn\Message\PurgeCdnTagMessage;
use Pimcore\Event\Model\Asset\Image\Thumbnail\ConfigEvent as ImageThumbnailConfigEvent;
use Pimcore\Event\Model\Asset\Video\Thumbnail\ConfigEvent as VideoThumbnailConfigEvent;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail\Config as ImageThumbnailConfig;
use Pimcore\Model\Asset\Video\Thumbnail\Config as VideoThumbnailConfig;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class CdnPurgeListenerTest extends TestCase
{
    private function makeAsset(int $id, string $fullPath): Asset
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getId')->willReturn($id);
        $asset->method('getFullPath')->willReturn($fullPath);

        return $asset;
    }

    private function makeListener(MessageBusInterface $bus): CdnPurgeListener
    {
        return new CdnPurgeListener($bus);
    }

    /**
     * @return array{0: MessageBusInterface, 1: \ArrayObject<int, object>}
     */
    private function captureBusDispatches(): array
    {
        $dispatched = new \ArrayObject();
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')
            ->willReturnCallback(function (object $message) use ($dispatched) {
                $dispatched->append($message);

                return new Envelope($message);
            });

        return [$bus, $dispatched];
    }

    public function testPostUpdateDispatchesTwoMessages(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(42, '/products/image.jpg');
        $event = new AssetEvent($asset);

        $this->makeListener($bus)->onAssetUpdate($event);

        $this->assertCount(2, $dispatched);
    }

    public function testPostUpdateDispatchesAssetIdTag(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(42, '/products/image.jpg');
        $this->makeListener($bus)->onAssetUpdate(new AssetEvent($asset));

        $tags = array_map(fn (PurgeCdnTagMessage $m) => $m->tag, $dispatched->getArrayCopy());
        $this->assertContains('asset-42', $tags);
    }

    public function testPostUpdateDispatchesPathHashTag(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $fullPath = '/products/image.jpg';
        $asset = $this->makeAsset(42, $fullPath);
        $this->makeListener($bus)->onAssetUpdate(new AssetEvent($asset));

        $expectedHash = substr(hash('sha256', '/var/assets' . $fullPath), 0, 12);
        $tags = array_map(fn (PurgeCdnTagMessage $m) => $m->tag, $dispatched->getArrayCopy());
        $this->assertContains('asset-path-' . $expectedHash, $tags);
    }

    public function testPostDeleteAlsoDispatchesTwoMessages(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(7, '/docs/manual.pdf');
        $this->makeListener($bus)->onAssetDelete(new AssetEvent($asset));

        $this->assertCount(2, $dispatched);
        $tags = array_map(fn (PurgeCdnTagMessage $m) => $m->tag, $dispatched->getArrayCopy());
        $this->assertContains('asset-7', $tags);
    }

    public function testPathHashMatchesSurrogateKeyListenerHash(): void
    {
        // The hash computed here must be identical to what CdnSurrogateKeyListener computes
        // for a request to /var/assets/{fullPath} — both sides must stay in sync.
        $fullPath = '/some/nested/file.png';
        $requestPath = '/var/assets' . $fullPath;

        $expectedHash = substr(hash('sha256', $requestPath), 0, 12);

        [$bus, $dispatched] = $this->captureBusDispatches();
        $asset = $this->makeAsset(1, $fullPath);
        $this->makeListener($bus)->onAssetUpdate(new AssetEvent($asset));

        $tags = array_map(fn (PurgeCdnTagMessage $m) => $m->tag, $dispatched->getArrayCopy());
        $this->assertContains('asset-path-' . $expectedHash, $tags);
    }

    public function testAllDispatchedMessagesArePurgeCdnTagMessages(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(10, '/img.jpg');
        $this->makeListener($bus)->onAssetUpdate(new AssetEvent($asset));

        foreach ($dispatched as $message) {
            $this->assertInstanceOf(PurgeCdnTagMessage::class, $message);
        }
    }

    // -----------------------------------------------------------------------
    // Thumbnail config events
    // -----------------------------------------------------------------------

    private function makeImageConfig(string $name): ImageThumbnailConfig
    {
        $config = new ImageThumbnailConfig();
        $config->setName($name);

        return $config;
    }

    private function makeVideoConfig(string $name): VideoThumbnailConfig
    {
        $config = new VideoThumbnailConfig();
        $config->setName($name);

        return $config;
    }

    public function testImageThumbnailConfigUpdateDispatchesThumbConfigTag(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $event = new ImageThumbnailConfigEvent($this->makeImageConfig('product-hero'));
        $this->makeListener($bus)->onImageThumbnailConfigChange($event);

        $this->assertCount(1, $dispatched);
        $this->assertInstanceOf(PurgeCdnTagMessage::class, $dispatched[0]);
        $this->assertSame('thumb-product-hero', $dispatched[0]->tag);
    }

    public function testImageThumbnailConfigDeleteDispatchesThumbConfigTag(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        // Same handler covers POST_UPDATE and POST_DELETE for image configs.
        $event = new ImageThumbnailConfigEvent($this->makeImageConfig('banner'));
        $this->makeListener($bus)->onImageThumbnailConfigChange($event);

        $tags = array_map(fn (PurgeCdnTagMessage $m) => $m->tag, $dispatched->getArrayCopy());
        $this->assertSame(['thumb-banner'], $tags);
    }

    public function testVideoThumbnailConfigUpdateDispatchesThumbConfigTag(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $event = new VideoThumbnailConfigEvent($this->makeVideoConfig('hero-video'));
        $this->makeListener($bus)->onVideoThumbnailConfigChange($event);

        $this->assertCount(1, $dispatched);
        $this->assertSame('thumb-hero-video', $dispatched[0]->tag);
    }

    public function testVideoThumbnailConfigDeleteDispatchesThumbConfigTag(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $event = new VideoThumbnailConfigEvent($this->makeVideoConfig('preview'));
        $this->makeListener($bus)->onVideoThumbnailConfigChange($event);

        $tags = array_map(fn (PurgeCdnTagMessage $m) => $m->tag, $dispatched->getArrayCopy());
        $this->assertSame(['thumb-preview'], $tags);
    }

    public function testThumbnailConfigEventDoesNotDispatchAssetTags(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $event = new ImageThumbnailConfigEvent($this->makeImageConfig('hero'));
        $this->makeListener($bus)->onImageThumbnailConfigChange($event);

        $tags = array_map(fn (PurgeCdnTagMessage $m) => $m->tag, $dispatched->getArrayCopy());
        foreach ($tags as $tag) {
            $this->assertStringStartsNotWith('asset-', $tag);
        }
    }

    public function testListenerSubscribesToAllSixRelevantEvents(): void
    {
        $events = CdnPurgeListener::getSubscribedEvents();

        $this->assertArrayHasKey('pimcore.asset.postUpdate', $events);
        $this->assertArrayHasKey('pimcore.asset.postDelete', $events);
        $this->assertArrayHasKey('pimcore.asset.image.thumbnailConfig.postUpdate', $events);
        $this->assertArrayHasKey('pimcore.asset.image.thumbnailConfig.postDelete', $events);
        $this->assertArrayHasKey('pimcore.asset.video.thumbnailConfig.postUpdate', $events);
        $this->assertArrayHasKey('pimcore.asset.video.thumbnailConfig.postDelete', $events);
    }
}
