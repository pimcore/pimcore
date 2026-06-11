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

use Pimcore\Bundle\CoreBundle\EventListener\CdnImageThumbnailUrlListener;
use Pimcore\Cdn\AssetWebPath;
use Pimcore\Cdn\ImageTransformAdapterInterface;
use Pimcore\Cdn\ThumbnailTransform;
use Pimcore\Cdn\ThumbnailTransformResolver;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class CdnImageThumbnailUrlListenerTest extends TestCase
{
    private const SOURCE_FORMATS = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private function image(string $fullPath, bool $vector = false, string $mime = 'image/jpeg', ?int $focalPointX = null): Image
    {
        $image = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRealFullPath', 'isVectorGraphic', 'getMimeType', 'getCustomSetting'])
            ->getMock();
        $image->method('getRealFullPath')->willReturn($fullPath);
        $image->method('isVectorGraphic')->willReturn($vector);
        $image->method('getMimeType')->willReturn($mime);
        $image->method('getCustomSetting')->willReturnCallback(
            static fn (string $key) => $key === 'focalPointX' ? $focalPointX : null,
        );

        return $image;
    }

    private function event(Image $asset, Config $config): GenericEvent
    {
        return new GenericEvent($this, [
            'frontendPath' => '/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg',
            'asset' => $asset,
            'config' => $config,
        ]);
    }

    public function testRewritesEligibleImageThumbnail(): void
    {
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(new ThumbnailTransform(400, 300));

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::once())
            ->method('buildUrl')
            ->with('/var/assets/folder/photo.jpg', new ThumbnailTransform(400, 300))
            ->willReturn('https://cdn.example.com/var/assets/folder/photo.jpg?width=400&height=300');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS, new AssetWebPath());
        $event = $this->event($this->image('/folder/photo.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame(
            'https://cdn.example.com/var/assets/folder/photo.jpg?width=400&height=300',
            $event->getArgument('frontendPath'),
        );
    }

    public function testDoesNotRewriteWhenOptimizerDisabled(): void
    {
        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $listener = new CdnImageThumbnailUrlListener($adapter, $this->createMock(ThumbnailTransformResolver::class), '', self::SOURCE_FORMATS, new AssetWebPath());
        $event = $this->event($this->image('/folder/photo.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testDoesNotRewriteVectorGraphic(): void
    {
        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $listener = new CdnImageThumbnailUrlListener($adapter, $this->createMock(ThumbnailTransformResolver::class), 'fastly', self::SOURCE_FORMATS, new AssetWebPath());
        $event = $this->event($this->image('/folder/logo.svg', true), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testDoesNotRewriteWhenAssetIsNotAnImage(): void
    {
        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $asset = $this->getMockBuilder(\Pimcore\Model\Asset::class)->disableOriginalConstructor()->getMock();

        $event = new GenericEvent($this, [
            'frontendPath' => '/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg',
            'asset' => $asset,
            'config' => new Config(),
        ]);

        $listener = new CdnImageThumbnailUrlListener($adapter, $this->createMock(ThumbnailTransformResolver::class), 'fastly', self::SOURCE_FORMATS, new AssetWebPath());
        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testIgnoresEventWithoutAssetAndConfigArguments(): void
    {
        // Asset\Image::getLowQualityPreviewPath() dispatches ASSET_IMAGE_THUMBNAIL with only
        // storagePath/frontendPath arguments — the listener must skip it, not throw on
        // GenericEvent::getArgument('asset').
        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $event = new GenericEvent($this, [
            'storagePath' => '/some/image/low-quality-preview.svg',
            'frontendPath' => '/some/image/low-quality-preview.svg',
        ]);

        $listener = new CdnImageThumbnailUrlListener($adapter, $this->createMock(ThumbnailTransformResolver::class), 'fastly', self::SOURCE_FORMATS, new AssetWebPath());
        $listener->onThumbnailPath($event);

        self::assertSame('/some/image/low-quality-preview.svg', $event->getArgument('frontendPath'));
    }

    public function testDoesNotRewriteCoverWithFocalPoint(): void
    {
        // Pimcore's cover crop honors the asset's focal point when one is set; the CDN cover
        // transform cannot reproduce it, so the listener must fall back to Pimcore generation.
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(new ThumbnailTransform(200, 200, 'cover'));

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS, new AssetWebPath());
        $event = $this->event($this->image('/folder/photo.jpg', focalPointX: 50), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testRewritesCoverWithoutFocalPoint(): void
    {
        // A cover transform on an asset without a focal point maps cleanly and is rewritten.
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(new ThumbnailTransform(200, 200, 'cover'));

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::once())
            ->method('buildUrl')
            ->willReturn('https://cdn.example.com/var/assets/folder/photo.jpg?width=200&height=200&fit=cover');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS, new AssetWebPath());
        $event = $this->event($this->image('/folder/photo.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame(
            'https://cdn.example.com/var/assets/folder/photo.jpg?width=200&height=200&fit=cover',
            $event->getArgument('frontendPath'),
        );
    }

    public function testDoesNotRewriteWhenConfigNotTranslatable(): void
    {
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(null);

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS, new AssetWebPath());
        $event = $this->event($this->image('/folder/photo.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testDoesNotRewriteNonIngestibleRaster(): void
    {
        // A raster format Fastly IO cannot ingest (TIFF) must fall back to Pimcore,
        // even though it is not a vector graphic and the config is translatable.
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(new ThumbnailTransform(100));

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS, new AssetWebPath());
        $event = $this->event($this->image('/folder/scan.tif', mime: 'image/tiff'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testDoesNotRewritePhotoshop(): void
    {
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(new ThumbnailTransform(100));

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS, new AssetWebPath());
        $event = $this->event($this->image('/folder/clipping.psd', mime: 'image/x-photoshop'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testRewritesWhenFormatAddedToAllowlist(): void
    {
        // The allowlist is configurable: adding image/tiff makes a TIFF eligible.
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(new ThumbnailTransform(100));

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::once())
            ->method('buildUrl')
            ->with('/var/assets/folder/scan.tif', new ThumbnailTransform(100))
            ->willReturn('https://cdn.example.com/var/assets/folder/scan.tif?width=100');

        $formats = [...self::SOURCE_FORMATS, 'image/tiff'];
        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', $formats, new AssetWebPath());
        $event = $this->event($this->image('/folder/scan.tif', mime: 'image/tiff'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame(
            'https://cdn.example.com/var/assets/folder/scan.tif?width=100',
            $event->getArgument('frontendPath'),
        );
    }

    public function testMatchesMimeTypeCaseInsensitively(): void
    {
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(new ThumbnailTransform(100));

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::once())
            ->method('buildUrl')
            ->willReturn('https://cdn.example.com/var/assets/folder/photo.jpg?width=100');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS, new AssetWebPath());
        $event = $this->event($this->image('/folder/photo.jpg', mime: 'IMAGE/JPEG'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame(
            'https://cdn.example.com/var/assets/folder/photo.jpg?width=100',
            $event->getArgument('frontendPath'),
        );
    }

    public function testDoesNotOverwriteWhenAdapterReturnsOriginalUnchanged(): void
    {
        // Misconfigured CDN_IMAGE_OPTIMIZER → registry falls back to NullImageTransformAdapter,
        // which returns the original path unchanged. The listener must keep the Pimcore thumbnail
        // rather than overwriting frontendPath with the full-size original URL.
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(new ThumbnailTransform(100));

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->method('buildUrl')->willReturn('/var/assets/folder/photo.jpg');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS, new AssetWebPath());
        $event = $this->event($this->image('/folder/photo.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testMatchesAllowlistCaseInsensitively(): void
    {
        // A mixed-case configured allowlist entry must still match a lowercase asset MIME type.
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(new ThumbnailTransform(100));

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::once())
            ->method('buildUrl')
            ->willReturn('https://cdn.example.com/var/assets/folder/photo.jpg?width=100');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', ['IMAGE/JPEG'], new AssetWebPath());
        $event = $this->event($this->image('/folder/photo.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame(
            'https://cdn.example.com/var/assets/folder/photo.jpg?width=100',
            $event->getArgument('frontendPath'),
        );
    }

    public function testPassesUnencodedRealPathForSpecialCharFilenames(): void
    {
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(new ThumbnailTransform(100));

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::once())
            ->method('buildUrl')
            ->with('/var/assets/Car Images/Mötley.jpg', new ThumbnailTransform(100))
            ->willReturn('https://cdn.example.com/var/assets/Car%20Images/M%C3%B6tley.jpg?width=100');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS, new AssetWebPath());
        $event = $this->event($this->image('/Car Images/Mötley.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame(
            'https://cdn.example.com/var/assets/Car%20Images/M%C3%B6tley.jpg?width=100',
            $event->getArgument('frontendPath'),
        );
    }
}
