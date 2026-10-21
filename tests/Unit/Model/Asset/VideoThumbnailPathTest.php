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
use Pimcore\Config as PimcoreConfig;
use Pimcore\Event\FrontendEvents;
use Pimcore\Model\Asset\Video;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tool\Storage;
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

    private const PREFIX = 'https://cdn.example.com';

    private const MP4_PATH = '99/video-thumb__99__teaser/my clip.mp4';

    private const MEDIA_PATH = '99/video-thumb__99__teaser/my clip-small.mp4';

    private ?array $previousAssetsConfiguration = null;

    private ?string $storedThumbnail = null;

    protected function tearDown(): void
    {
        if ($this->previousAssetsConfiguration !== null) {
            PimcoreConfig::setSystemConfiguration($this->previousAssetsConfiguration, 'assets');
            $this->previousAssetsConfiguration = null;
        }

        if ($this->storedThumbnail !== null) {
            Storage::get('thumbnail')->delete($this->storedThumbnail);
            $this->storedThumbnail = null;
        }

        parent::tearDown();
    }

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

    public function testGetThumbnailForwardsFrontendToAllFormats(): void
    {
        // the processor checks for a video adapter before it looks at finished thumbnails
        if (!\Pimcore\Video::isAvailable()) {
            $this->markTestSkipped('No video adapter (ffmpeg) available');
        }

        $video = $this->createVideoWithFinishedThumbnail();

        $frontend = $video->getThumbnail($this->createThumbnailConfig(), [], true);
        $this->assertSame(self::PREFIX . '/my%20videos/99/video-thumb__99__teaser/my%20clip.mp4', $frontend['formats']['mp4']);
        $this->assertSame(
            self::PREFIX . '/my%20videos/99/video-thumb__99__teaser/my%20clip-small.mp4',
            $frontend['formats']['medias']['(max-width: 600px)']['mp4']
        );

        foreach ([null, false] as $storedPaths) {
            $thumbnail = $video->getThumbnail($this->createThumbnailConfig(), [], $storedPaths);
            $this->assertSame(self::MP4_PATH, $thumbnail['formats']['mp4']);
            $this->assertSame(self::MEDIA_PATH, $thumbnail['formats']['medias']['(max-width: 600px)']['mp4']);
        }
    }

    private function createThumbnailConfig(): Video\Thumbnail\Config
    {
        $config = new Video\Thumbnail\Config();
        $config->setName('teaser');

        return $config;
    }

    /**
     * A finished thumbnail makes the processor return without converting, so no ffmpeg run is needed
     */
    private function createVideoWithFinishedThumbnail(): Video
    {
        $assets = PimcoreConfig::getSystemConfiguration('assets');
        $this->previousAssetsConfiguration = $assets;
        $assets['frontend_prefixes']['thumbnail'] = self::PREFIX;
        PimcoreConfig::setSystemConfiguration($assets, 'assets');

        $video = new Video();
        $video->setId(99);
        $video->setPath('/my videos/');
        $video->setFilename('my clip.mp4');
        $video->setCustomSetting('thumbnails', [
            'teaser' => [
                'status' => 'finished',
                'formats' => [
                    'mp4' => self::MP4_PATH,
                    'medias' => ['(max-width: 600px)' => ['mp4' => self::MEDIA_PATH]],
                ],
            ],
        ]);

        // the processor checks that the file of every format exists on the thumbnail storage
        $this->storedThumbnail = '/my videos/' . self::MP4_PATH;
        Storage::get('thumbnail')->write($this->storedThumbnail, '');

        return $video;
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
