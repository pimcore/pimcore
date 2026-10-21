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

namespace Pimcore\Tests\Unit\Model\Asset;

use Pimcore;
use Pimcore\Event\FrontendEvents;
use Pimcore\Model\Asset\Video;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @internal
 */
class VideoThumbnailPathTest extends TestCase
{
    // as stored in the asset's thumbnail settings, relative to the asset's path
    private const THUMBNAIL_PATH = '/video-thumb__1__teaser/my clip.mp4';

    private const STORAGE_PATH = '/my videos/video-thumb__1__teaser/my clip.mp4';

    private const ENCODED_PATH = '/my%20videos/video-thumb__1__teaser/my%20clip.mp4';

    public function testGetThumbnailOffersFrontendParameter(): void
    {
        $parameters = (new ReflectionMethod(Video::class, 'getThumbnail'))->getParameters();
        $frontend = end($parameters);

        $this->assertSame('frontend', $frontend->getName());
        $this->assertTrue($frontend->isOptional());
        $this->assertNull($frontend->getDefaultValue());
        $this->assertTrue($frontend->allowsNull());
    }

    public function testDefaultOutsideFrontendRequestKeepsStoredPath(): void
    {
        // no request in a unit test = CLI/cron/messenger context, Tool::isFrontend() is false
        $this->assertSame(self::THUMBNAIL_PATH, $this->enrichThumbnailPath());
    }

    public function testExplicitFrontendEncodesPathOutsideFrontendRequest(): void
    {
        $this->assertSame(self::ENCODED_PATH, $this->enrichThumbnailPath(true));
    }

    public function testExplicitNoFrontendKeepsStoredPath(): void
    {
        $this->assertSame(self::THUMBNAIL_PATH, $this->enrichThumbnailPath(false));
    }

    public function testEventIsDispatchedWithBothPathsIfFrontendIsForced(): void
    {
        $event = $this->captureEvent(true);

        $this->assertSame(self::STORAGE_PATH, $event->getArgument('filesystemPath'));
        $this->assertSame(self::ENCODED_PATH, $event->getArgument('frontendPath'));
    }

    public function testEventIsDispatchedIfFrontendIsNotForced(): void
    {
        $event = $this->captureEvent(null);

        $this->assertSame(self::STORAGE_PATH, $event->getArgument('filesystemPath'));
        $this->assertSame(self::THUMBNAIL_PATH, $event->getArgument('frontendPath'));
    }

    private function enrichThumbnailPath(?bool $frontend = null): string
    {
        $video = new Video();
        $video->setPath('/my videos/');

        $method = new ReflectionMethod(Video::class, 'enrichThumbnailPath');

        // the frontend argument is only passed when set, so the default is exercised as well
        return $frontend === null
            ? $method->invoke($video, self::THUMBNAIL_PATH)
            : $method->invoke($video, self::THUMBNAIL_PATH, $frontend);
    }

    private function captureEvent(?bool $frontend): GenericEvent
    {
        $captured = null;
        $listener = static function (GenericEvent $event) use (&$captured): void {
            $captured = $event;
        };

        $dispatcher = Pimcore::getEventDispatcher();
        $dispatcher->addListener(FrontendEvents::ASSET_VIDEO_THUMBNAIL, $listener);

        try {
            $this->enrichThumbnailPath($frontend);
        } finally {
            $dispatcher->removeListener(FrontendEvents::ASSET_VIDEO_THUMBNAIL, $listener);
        }

        $this->assertInstanceOf(GenericEvent::class, $captured);

        return $captured;
    }
}
