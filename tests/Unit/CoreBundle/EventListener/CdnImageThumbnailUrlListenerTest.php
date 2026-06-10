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
use Pimcore\Cdn\ImageTransformAdapterInterface;
use Pimcore\Cdn\ThumbnailTransformResolver;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class CdnImageThumbnailUrlListenerTest extends TestCase
{
    private const SOURCE_FORMATS = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private function image(string $fullPath, bool $vector = false, string $mime = 'image/jpeg'): Image
    {
        $image = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRealFullPath', 'isVectorGraphic', 'getMimeType'])
            ->getMock();
        $image->method('getRealFullPath')->willReturn($fullPath);
        $image->method('isVectorGraphic')->willReturn($vector);
        $image->method('getMimeType')->willReturn($mime);

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
        $resolver->method('resolve')->willReturn(['width' => 400, 'height' => 300]);

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::once())
            ->method('buildUrl')
            ->with('/var/assets/folder/photo.jpg', ['width' => 400, 'height' => 300])
            ->willReturn('https://cdn.example.com/var/assets/folder/photo.jpg?width=400&height=300');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS);
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

        $listener = new CdnImageThumbnailUrlListener($adapter, $this->createMock(ThumbnailTransformResolver::class), '', self::SOURCE_FORMATS);
        $event = $this->event($this->image('/folder/photo.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testDoesNotRewriteVectorGraphic(): void
    {
        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $listener = new CdnImageThumbnailUrlListener($adapter, $this->createMock(ThumbnailTransformResolver::class), 'fastly', self::SOURCE_FORMATS);
        $event = $this->event($this->image('/folder/logo.svg', vector: true), new Config());

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

        $listener = new CdnImageThumbnailUrlListener($adapter, $this->createMock(ThumbnailTransformResolver::class), 'fastly', self::SOURCE_FORMATS);
        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testDoesNotRewriteWhenConfigNotTranslatable(): void
    {
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(null);

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS);
        $event = $this->event($this->image('/folder/photo.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testDoesNotRewriteNonIngestibleRaster(): void
    {
        // A raster format Fastly IO cannot ingest (TIFF) must fall back to Pimcore,
        // even though it is not a vector graphic and the config is translatable.
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(['width' => 100]);

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS);
        $event = $this->event($this->image('/folder/scan.tif', mime: 'image/tiff'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testDoesNotRewritePhotoshop(): void
    {
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(['width' => 100]);

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS);
        $event = $this->event($this->image('/folder/clipping.psd', mime: 'image/x-photoshop'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testRewritesWhenFormatAddedToAllowlist(): void
    {
        // The allowlist is configurable: adding image/tiff makes a TIFF eligible.
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(['width' => 100]);

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::once())
            ->method('buildUrl')
            ->with('/var/assets/folder/scan.tif', ['width' => 100])
            ->willReturn('https://cdn.example.com/var/assets/folder/scan.tif?width=100');

        $formats = [...self::SOURCE_FORMATS, 'image/tiff'];
        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', $formats);
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
        $resolver->method('resolve')->willReturn(['width' => 100]);

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::once())
            ->method('buildUrl')
            ->willReturn('https://cdn.example.com/var/assets/folder/photo.jpg?width=100');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS);
        $event = $this->event($this->image('/folder/photo.jpg', mime: 'IMAGE/JPEG'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame(
            'https://cdn.example.com/var/assets/folder/photo.jpg?width=100',
            $event->getArgument('frontendPath'),
        );
    }

    public function testPassesUnencodedRealPathForSpecialCharFilenames(): void
    {
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(['width' => 100]);

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::once())
            ->method('buildUrl')
            ->with('/var/assets/Car Images/Mötley.jpg', ['width' => 100])
            ->willReturn('https://cdn.example.com/var/assets/Car%20Images/M%C3%B6tley.jpg?width=100');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly', self::SOURCE_FORMATS);
        $event = $this->event($this->image('/Car Images/Mötley.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame(
            'https://cdn.example.com/var/assets/Car%20Images/M%C3%B6tley.jpg?width=100',
            $event->getArgument('frontendPath'),
        );
    }
}
