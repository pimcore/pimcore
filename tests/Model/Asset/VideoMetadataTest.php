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

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;
use Pimcore;
use Pimcore\Db;
use Pimcore\Event\AssetEvents;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Messenger\Handler\AssetUpdateTasksHandler;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Video;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Console;
use Pimcore\Video as VideoAdapterFactory;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\Lock\LockFactory;

/**
 * Regression tests for pimcore/platform-version#748.
 *
 * - Reading video metadata (getDuration(), getDimensions(), getWidth(), getHeight()) must not save the
 *   whole asset: no save events, no modificationDate change. The computed values are still cached.
 * - A missing video adapter (ffmpeg not available) is not a broken file and must not set the permanent
 *   processing-failed marker.
 *
 * @group model.asset.video-metadata
 */
final class VideoMetadataTest extends ModelTestCase
{
    private const OLD_MODIFICATION_DATE = 1000000000;

    private const METADATA_SETTINGS = [
        'duration',
        'videoWidth',
        'videoHeight',
        Asset::CUSTOM_SETTING_PROCESSING_FAILED,
    ];

    /** @var callable|null */
    private $postUpdateListener = null;

    private int $postUpdateEvents = 0;

    protected function tearDown(): void
    {
        if ($this->postUpdateListener !== null) {
            Pimcore::getEventDispatcher()->removeListener(AssetEvents::POST_UPDATE, $this->postUpdateListener);
            $this->postUpdateListener = null;
        }
        $this->enableVideoAdapter();

        parent::tearDown();
    }

    public function testReadingDimensionsAndDurationDoesNotSaveTheAsset(): void
    {
        $this->skipWithoutVideoAdapter();
        $video = $this->createVideoWithoutCachedMetadata();
        $this->countPostUpdateEvents();

        $this->assertGreaterThan(0, $video->getWidth());
        $this->assertGreaterThan(0, $video->getHeight());
        $this->assertGreaterThan(0, $video->getDuration());

        $this->assertSame(0, $this->postUpdateEvents, 'reading metadata must not save the asset');
        $this->assertSame(self::OLD_MODIFICATION_DATE, $this->fetchModificationDate($video));
    }

    public function testReadMetadataIsPersisted(): void
    {
        $this->skipWithoutVideoAdapter();
        $video = $this->createVideoWithoutCachedMetadata();

        $width = $video->getWidth();
        $height = $video->getHeight();
        $duration = $video->getDuration();

        $settings = $this->fetchCustomSettings($video);
        $this->assertSame($width, $settings['videoWidth'] ?? null);
        $this->assertSame($height, $settings['videoHeight'] ?? null);
        $this->assertEquals($duration, $settings['duration'] ?? null);

        $reloaded = Video::getById($video->getId(), ['force' => true]);
        $this->assertInstanceOf(Video::class, $reloaded);
        $this->assertSame($width, $reloaded->getCustomSetting('videoWidth'));
    }

    public function testMissingVideoAdapterDoesNotMarkProcessingAsFailed(): void
    {
        $video = $this->createVideoWithoutCachedMetadata();
        $this->countPostUpdateEvents();
        $this->disableVideoAdapter();

        $this->assertNull($video->getWidth());
        $this->assertNull($video->getHeight());
        $this->assertNull($video->getDuration());

        $this->assertNull($video->getCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED));
        $this->assertArrayNotHasKey(Asset::CUSTOM_SETTING_PROCESSING_FAILED, $this->fetchCustomSettings($video));
        $this->assertSame(0, $this->postUpdateEvents);
        $this->assertSame(self::OLD_MODIFICATION_DATE, $this->fetchModificationDate($video));
    }

    public function testMissingVideoAdapterIsLoggedOncePerProcess(): void
    {
        $first = $this->createVideoWithoutCachedMetadata();
        $second = $this->createVideoWithoutCachedMetadata();
        $this->disableVideoAdapter();
        $this->setStaticProperty(Video::class, 'missingVideoAdapterLogged', false);

        /** @var MonologLogger $logger */
        $logger = Pimcore::getContainer()->get('monolog.logger.pimcore');
        $records = new TestHandler(Level::Warning);
        $logger->pushHandler($records);

        try {
            foreach ([$first, $second] as $video) {
                $this->assertNull($video->getWidth());
                $this->assertNull($video->getHeight());
            }
        } finally {
            $logger->popHandler();
        }

        // the consequence (logged by Video) and the cause (logged by the adapter's availability probe) - once each
        foreach (['no video adapter', "No 'ffmpeg' executable found"] as $message) {
            $warnings = array_filter(
                $records->getRecords(),
                static fn (LogRecord $record): bool => str_contains($record->message, $message)
            );
            $this->assertCount(1, $warnings, $message);
        }
    }

    public function testMetadataIsReadOnceTheVideoAdapterIsAvailableAgain(): void
    {
        $this->skipWithoutVideoAdapter();
        $video = $this->createVideoWithoutCachedMetadata();

        $this->disableVideoAdapter();
        $this->assertNull($video->getWidth());

        $this->enableVideoAdapter();
        $this->assertGreaterThan(0, $video->getWidth());
    }

    public function testUnreadableVideoIsStillMarkedAsFailed(): void
    {
        $this->skipWithoutVideoAdapter();
        $video = $this->createVideoWithoutCachedMetadata('this is not a video');
        $this->countPostUpdateEvents();

        $this->assertNull($video->getWidth());

        $this->assertTrue($video->getCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED));
        $settings = $this->fetchCustomSettings($video);
        $this->assertTrue($settings[Asset::CUSTOM_SETTING_PROCESSING_FAILED] ?? null);
        $this->assertSame(0, $this->postUpdateEvents);
    }

    /**
     * The getters read the unsaved replacement data, so whatever they find (here: a failure) belongs to that
     * data - it must not be persisted against the file that is still stored.
     */
    public function testMetadataOfUnsavedReplacementDataIsNotPersisted(): void
    {
        $this->skipWithoutVideoAdapter();
        $video = $this->createVideoWithoutCachedMetadata();
        $video->setData('this is not a video');

        $this->assertNull($video->getWidth());
        $this->assertNull($video->getDuration());

        $settings = $this->fetchCustomSettings($video);
        foreach (self::METADATA_SETTINGS as $key) {
            $this->assertArrayNotHasKey($key, $settings);
        }
    }

    public function testUpdateTaskWithoutVideoAdapterDoesNotMarkProcessingAsFailed(): void
    {
        $video = $this->createVideoWithoutCachedMetadata();
        $this->disableVideoAdapter();

        $handler = new AssetUpdateTasksHandler(
            new NullLogger(),
            Pimcore::getContainer()->get(LongRunningHelper::class),
            Pimcore::getContainer()->get(LockFactory::class)
        );
        $handler(new AssetUpdateTasksMessage($video->getId()));

        $this->assertArrayNotHasKey(Asset::CUSTOM_SETTING_PROCESSING_FAILED, $this->fetchCustomSettings($video));
    }

    private function createVideoWithoutCachedMetadata(?string $data = null): Video
    {
        $video = TestHelper::createVideoAsset('', $data);

        // the update task (if it ran on save) may already have cached the metadata - start from a clean state
        foreach (self::METADATA_SETTINGS as $key) {
            $video->removeCustomSetting($key);
        }
        $video->getDao()->updateCustomSettings();
        Db::get()->update(
            'assets',
            ['modificationDate' => self::OLD_MODIFICATION_DATE],
            ['id' => $video->getId()]
        );

        $reloaded = Video::getById($video->getId(), ['force' => true]);
        $this->assertInstanceOf(Video::class, $reloaded);

        return $reloaded;
    }

    private function countPostUpdateEvents(): void
    {
        $this->postUpdateEvents = 0;
        $this->postUpdateListener = function (): void {
            $this->postUpdateEvents++;
        };
        Pimcore::getEventDispatcher()->addListener(AssetEvents::POST_UPDATE, $this->postUpdateListener);
    }

    private function fetchModificationDate(Video $video): int
    {
        return (int) Db::get()->fetchOne('SELECT modificationDate FROM assets WHERE id = ?', [$video->getId()]);
    }

    private function fetchCustomSettings(Video $video): array
    {
        $json = Db::get()->fetchOne('SELECT customSettings FROM assets WHERE id = ?', [$video->getId()]);

        return json_decode((string) $json, true) ?: [];
    }

    private function skipWithoutVideoAdapter(): void
    {
        if (VideoAdapterFactory::getInstance() === null) {
            $this->markTestSkipped('No video adapter (ffmpeg) available in this environment.');
        }
    }

    /**
     * Simulates an environment without ffmpeg: the executable lookup is cached as "not found" and the
     * cached default adapter is dropped, so Pimcore\Video::getInstance() returns null.
     */
    private function disableVideoAdapter(): void
    {
        $this->resetVideoAdapterFactory();
        $cache = (new ReflectionProperty(Console::class, 'executableCache'))->getValue();
        $cache['ffmpeg'] = false;
        $this->setStaticProperty(Console::class, 'executableCache', $cache);
    }

    private function enableVideoAdapter(): void
    {
        $cache = (new ReflectionProperty(Console::class, 'executableCache'))->getValue();
        unset($cache['ffmpeg']);
        $this->setStaticProperty(Console::class, 'executableCache', $cache);
        $this->resetVideoAdapterFactory();
    }

    private function resetVideoAdapterFactory(): void
    {
        $this->setStaticProperty(VideoAdapterFactory::class, 'defaultAdapterInstance', null);
        $this->setStaticProperty(VideoAdapterFactory::class, 'defaultAdapterUnavailable', false);
    }

    private function setStaticProperty(string $class, string $property, mixed $value): void
    {
        (new ReflectionProperty($class, $property))->setValue(null, $value);
    }
}
