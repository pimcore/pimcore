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
    private function image(string $fullPath, bool $vector = false): Image
    {
        $image = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRealFullPath', 'isVectorGraphic'])
            ->getMock();
        $image->method('getRealFullPath')->willReturn($fullPath);
        $image->method('isVectorGraphic')->willReturn($vector);

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

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly');
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

        $listener = new CdnImageThumbnailUrlListener($adapter, $this->createMock(ThumbnailTransformResolver::class), '');
        $event = $this->event($this->image('/folder/photo.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testDoesNotRewriteVectorGraphic(): void
    {
        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $listener = new CdnImageThumbnailUrlListener($adapter, $this->createMock(ThumbnailTransformResolver::class), 'fastly');
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

        $listener = new CdnImageThumbnailUrlListener($adapter, $this->createMock(ThumbnailTransformResolver::class), 'fastly');
        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
    }

    public function testDoesNotRewriteWhenConfigNotTranslatable(): void
    {
        $resolver = $this->createMock(ThumbnailTransformResolver::class);
        $resolver->method('resolve')->willReturn(null);

        $adapter = $this->createMock(ImageTransformAdapterInterface::class);
        $adapter->expects(self::never())->method('buildUrl');

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly');
        $event = $this->event($this->image('/folder/photo.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame('/var/tmp/thumbnails/image-thumb__1__cfg/x.jpg', $event->getArgument('frontendPath'));
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

        $listener = new CdnImageThumbnailUrlListener($adapter, $resolver, 'fastly');
        $event = $this->event($this->image('/Car Images/Mötley.jpg'), new Config());

        $listener->onThumbnailPath($event);

        self::assertSame(
            'https://cdn.example.com/var/assets/Car%20Images/M%C3%B6tley.jpg?width=100',
            $event->getArgument('frontendPath'),
        );
    }
}
