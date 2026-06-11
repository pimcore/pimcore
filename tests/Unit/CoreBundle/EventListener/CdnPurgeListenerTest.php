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

use ArrayObject;
use Pimcore\Bundle\CoreBundle\EventListener\CdnPurgeListener;
use Pimcore\Cdn\AssetWebPath;
use Pimcore\Cdn\CdnAssetTag;
use Pimcore\Cdn\Message\PurgeCdnAssetTreeMessage;
use Pimcore\Cdn\Message\PurgeCdnTagMessage;
use Pimcore\Cdn\Message\PurgeCdnUrlMessage;
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
        // The listener must use getRealFullPath() (raw tree path); getFullPath() returns an
        // encoded/prefixed variant during frontend requests and must not be called.
        $asset->method('getRealFullPath')->willReturn($fullPath);
        $asset->expects($this->never())->method('getFullPath');

        return $asset;
    }

    private function makeFolder(int $id, string $fullPath): Asset\Folder
    {
        $folder = $this->createMock(Asset\Folder::class);
        $folder->method('getId')->willReturn($id);
        $folder->method('getRealFullPath')->willReturn($fullPath);
        $folder->expects($this->never())->method('getFullPath');

        return $folder;
    }

    private function makeListener(MessageBusInterface $bus, string $provider = 'fastly', string $cdnBaseUrl = ''): CdnPurgeListener
    {
        return new CdnPurgeListener($bus, new CdnAssetTag(), new AssetWebPath(), $provider, $cdnBaseUrl);
    }

    /**
     * @return array{0: MessageBusInterface, 1: ArrayObject<int, object>}
     */
    private function captureBusDispatches(): array
    {
        $dispatched = new ArrayObject();
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

    public function testOnAssetUpdateAlsoPurgesOldPathWhenAssetWasRenamed(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $newPath = '/products/new-name.jpg';
        $oldPath = '/products/old-name.jpg';
        $asset = $this->makeAsset(42, $newPath);

        $event = new AssetEvent($asset);
        $event->setArgument('oldPath', $oldPath);

        $this->makeListener($bus)->onAssetUpdate($event);

        $newPathHash = substr(hash('sha256', '/var/assets' . $newPath), 0, 12);
        $oldPathHash = substr(hash('sha256', '/var/assets' . $oldPath), 0, 12);

        $tags = array_map(fn (PurgeCdnTagMessage $m) => $m->tag, $dispatched->getArrayCopy());
        $this->assertEqualsCanonicalizing(
            ['asset-42', 'asset-path-' . $newPathHash, 'asset-path-' . $oldPathHash],
            $tags
        );
    }

    public function testOnAssetUpdateDoesNotPurgeOldPathWhenIdenticalToCurrentPath(): void
    {
        // Defensive: a non-rename POST_UPDATE may still carry an oldPath argument equal
        // to the new path. The listener must not dispatch a redundant third message.
        [$bus, $dispatched] = $this->captureBusDispatches();

        $path = '/products/image.jpg';
        $asset = $this->makeAsset(42, $path);

        $event = new AssetEvent($asset);
        $event->setArgument('oldPath', $path);

        $this->makeListener($bus)->onAssetUpdate($event);

        $pathHash = substr(hash('sha256', '/var/assets' . $path), 0, 12);

        $tags = array_map(fn (PurgeCdnTagMessage $m) => $m->tag, $dispatched->getArrayCopy());
        $this->assertEqualsCanonicalizing(
            ['asset-42', 'asset-path-' . $pathHash],
            $tags
        );
    }

    public function testOnAssetUpdateIgnoresEmptyOldPath(): void
    {
        // Defensive: a buggy event payload may set oldPath to '' — must not be hashed
        // (would produce a meaningless asset-path-{hash} for /var/assets alone).
        [$bus, $dispatched] = $this->captureBusDispatches();

        $path = '/products/image.jpg';
        $asset = $this->makeAsset(42, $path);

        $event = new AssetEvent($asset);
        $event->setArgument('oldPath', '');

        $this->makeListener($bus)->onAssetUpdate($event);

        $pathHash = substr(hash('sha256', '/var/assets' . $path), 0, 12);

        $tags = array_map(fn (PurgeCdnTagMessage $m) => $m->tag, $dispatched->getArrayCopy());
        $this->assertEqualsCanonicalizing(
            ['asset-42', 'asset-path-' . $pathHash],
            $tags
        );
    }

    public function testFolderRenameDispatchesAssetTreePurgeMessage(): void
    {
        // Descendants are repathed via a single SQL UPDATE with no per-child events —
        // a renamed/moved folder must additionally enqueue a subtree purge.
        [$bus, $dispatched] = $this->captureBusDispatches();

        $folder = $this->makeFolder(5, '/catalog');
        $event = new AssetEvent($folder);
        $event->setArgument('oldPath', '/products');

        $this->makeListener($bus)->onAssetUpdate($event);

        $treeMessages = array_values(array_filter(
            $dispatched->getArrayCopy(),
            fn (object $m) => $m instanceof PurgeCdnAssetTreeMessage
        ));

        $this->assertCount(1, $treeMessages);
        $this->assertSame('/products', $treeMessages[0]->oldPath);
        $this->assertSame('/catalog', $treeMessages[0]->newPath);
    }

    public function testNonFolderRenameDoesNotDispatchAssetTreePurge(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(42, '/products/new-name.jpg');
        $event = new AssetEvent($asset);
        $event->setArgument('oldPath', '/products/old-name.jpg');

        $this->makeListener($bus)->onAssetUpdate($event);

        $treeMessages = array_filter(
            $dispatched->getArrayCopy(),
            fn (object $m) => $m instanceof PurgeCdnAssetTreeMessage
        );

        $this->assertCount(0, $treeMessages);
    }

    public function testFolderUpdateWithoutRenameDoesNotDispatchAssetTreePurge(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $folder = $this->makeFolder(5, '/catalog');

        $this->makeListener($bus)->onAssetUpdate(new AssetEvent($folder));

        $treeMessages = array_filter(
            $dispatched->getArrayCopy(),
            fn (object $m) => $m instanceof PurgeCdnAssetTreeMessage
        );

        $this->assertCount(0, $treeMessages);
    }

    public function testVersionOnlySaveDoesNotDispatchAnyPurge(): void
    {
        // Asset::saveVersion() (editor autosave / "save only new version") dispatches
        // POST_UPDATE with saveVersionOnly=true while the published binary is unchanged —
        // purging would evict valid CDN objects on every autosave.
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(42, '/products/image.jpg');
        $event = new AssetEvent($asset, ['saveVersionOnly' => true]);

        $this->makeListener($bus)->onAssetUpdate($event);

        $this->assertCount(0, $dispatched);
    }

    public function testAutoSaveDoesNotDispatchAnyPurge(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(42, '/products/image.jpg');
        $event = new AssetEvent($asset, ['autoSave' => true]);

        $this->makeListener($bus)->onAssetUpdate($event);

        $this->assertCount(0, $dispatched);
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

    // -----------------------------------------------------------------------
    // CDN-disabled gating: when CDN_PROVIDER is empty, the listener must
    // not enqueue any messages — otherwise the doctrine messenger transport
    // would accumulate no-op purges that nobody consumes.
    // -----------------------------------------------------------------------

    public function testAssetUpdateDoesNotDispatchWhenCdnDisabled(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(42, '/products/image.jpg');
        $this->makeListener($bus, '')->onAssetUpdate(new AssetEvent($asset));

        $this->assertCount(0, $dispatched);
    }

    public function testAssetDeleteDoesNotDispatchWhenCdnDisabled(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(42, '/products/image.jpg');
        $this->makeListener($bus, '')->onAssetDelete(new AssetEvent($asset));

        $this->assertCount(0, $dispatched);
    }

    public function testImageThumbnailConfigChangeDoesNotDispatchWhenCdnDisabled(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $event = new ImageThumbnailConfigEvent($this->makeImageConfig('hero'));
        $this->makeListener($bus, '')->onImageThumbnailConfigChange($event);

        $this->assertCount(0, $dispatched);
    }

    public function testVideoThumbnailConfigChangeDoesNotDispatchWhenCdnDisabled(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $event = new VideoThumbnailConfigEvent($this->makeVideoConfig('preview'));
        $this->makeListener($bus, '')->onVideoThumbnailConfigChange($event);

        $this->assertCount(0, $dispatched);
    }

    // -----------------------------------------------------------------------
    // URL-based purges for original assets (nginx serves /var/assets/* directly
    // off disk, so PHP never emits Cache-Tag for them and tag-purge cannot
    // reach them). When CDN_BASE_URL is configured, the listener must also
    // dispatch PurgeCdnUrlMessage for the absolute URL of the original asset.
    // -----------------------------------------------------------------------

    public function testOnAssetUpdateDispatchesUrlPurgeForOriginalAssetWhenCdnBaseUrlConfigured(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(42, '/products/photo.jpg');
        $this->makeListener($bus, 'fastly', 'https://cdn.example.com')
            ->onAssetUpdate(new AssetEvent($asset));

        $urlMessages = array_values(array_filter(
            $dispatched->getArrayCopy(),
            fn (object $m) => $m instanceof PurgeCdnUrlMessage
        ));

        $this->assertCount(1, $urlMessages);
        $this->assertSame('https://cdn.example.com/var/assets/products/photo.jpg', $urlMessages[0]->url);
    }

    public function testOnAssetUpdateDispatchesUrlPurgeForBothPathsOnRenameWhenCdnBaseUrlConfigured(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(42, '/products/new.jpg');
        $event = new AssetEvent($asset);
        $event->setArgument('oldPath', '/products/old.jpg');

        $this->makeListener($bus, 'fastly', 'https://cdn.example.com')->onAssetUpdate($event);

        $urls = array_map(
            fn (PurgeCdnUrlMessage $m) => $m->url,
            array_values(array_filter(
                $dispatched->getArrayCopy(),
                fn (object $m) => $m instanceof PurgeCdnUrlMessage
            ))
        );
        sort($urls);

        $this->assertSame(
            [
                'https://cdn.example.com/var/assets/products/new.jpg',
                'https://cdn.example.com/var/assets/products/old.jpg',
            ],
            $urls
        );
    }

    public function testOnAssetUpdateDoesNotDispatchUrlPurgeWhenCdnBaseUrlEmpty(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(42, '/products/photo.jpg');
        $this->makeListener($bus, 'fastly', '')->onAssetUpdate(new AssetEvent($asset));

        $urlMessages = array_filter(
            $dispatched->getArrayCopy(),
            fn (object $m) => $m instanceof PurgeCdnUrlMessage
        );

        $this->assertCount(0, $urlMessages);
        // Tag dispatches still happen (asset-id + path-hash).
        $this->assertCount(2, $dispatched);
    }

    public function testOnAssetDeleteDispatchesUrlPurgeForOriginalAsset(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(42, '/products/gone.jpg');
        $this->makeListener($bus, 'fastly', 'https://cdn.example.com')
            ->onAssetDelete(new AssetEvent($asset));

        $urlMessages = array_values(array_filter(
            $dispatched->getArrayCopy(),
            fn (object $m) => $m instanceof PurgeCdnUrlMessage
        ));

        $this->assertCount(1, $urlMessages);
        $this->assertSame('https://cdn.example.com/var/assets/products/gone.jpg', $urlMessages[0]->url);
    }

    // -----------------------------------------------------------------------
    // Asset paths may legitimately contain spaces, unicode characters, and
    // other non-URL-safe characters (Pimcore allows them as filenames). When
    // building the absolute purge URL, each path segment must be percent-
    // encoded so the URL sent to the CDN matches the cache key the CDN
    // stored when the browser-encoded GET request hit the edge.
    // -----------------------------------------------------------------------

    public function testOnAssetUpdateUrlPurgeEncodesPathSegmentsForOriginalAsset(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        // Path containing a space and a non-ASCII character (umlaut).
        $asset = $this->makeAsset(42, '/Car Images/Mötley.jpg');
        $this->makeListener($bus, 'fastly', 'https://cdn.example.com')
            ->onAssetUpdate(new AssetEvent($asset));

        $urlMessages = array_values(array_filter(
            $dispatched->getArrayCopy(),
            fn (object $m) => $m instanceof PurgeCdnUrlMessage
        ));

        $this->assertCount(1, $urlMessages);
        $this->assertSame(
            'https://cdn.example.com/var/assets/Car%20Images/M%C3%B6tley.jpg',
            $urlMessages[0]->url
        );
    }

    public function testOnAssetUpdateUrlPurgeEncodesBothPathsOnRename(): void
    {
        [$bus, $dispatched] = $this->captureBusDispatches();

        $asset = $this->makeAsset(42, '/Car Images/Mötley.jpg');
        $event = new AssetEvent($asset);
        $event->setArgument('oldPath', '/Old Folder/Lého.jpg');

        $this->makeListener($bus, 'fastly', 'https://cdn.example.com')->onAssetUpdate($event);

        $urls = array_map(
            fn (PurgeCdnUrlMessage $m) => $m->url,
            array_values(array_filter(
                $dispatched->getArrayCopy(),
                fn (object $m) => $m instanceof PurgeCdnUrlMessage
            ))
        );
        sort($urls);

        $this->assertSame(
            [
                'https://cdn.example.com/var/assets/Car%20Images/M%C3%B6tley.jpg',
                'https://cdn.example.com/var/assets/Old%20Folder/L%C3%A9ho.jpg',
            ],
            $urls
        );
    }
}
