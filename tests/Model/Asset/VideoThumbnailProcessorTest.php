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

use Pimcore;
use Pimcore\File;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Video\Thumbnail\Config;
use Pimcore\Model\Asset\Video\Thumbnail\Processor;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\FakeVideoConverter;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;
use Pimcore\Video\Adapter\Ffmpeg;

/**
 * Regression tests for pimcore/platform-version#790.
 *
 * The destination file of a video conversion is allocated by Processor::process(), usually in a web
 * request, but written by Processor::execute() in a messenger worker. It used to be registered for
 * removal at the end of the allocating process, so a request that ended while the worker was still
 * converting deleted the file under FFmpeg. The file now outlives the allocating process, and execute()
 * removes it itself, whatever the outcome of the conversion.
 *
 * @group model.asset.video-thumbnail-processor
 */
final class VideoThumbnailProcessorTest extends ModelTestCase
{
    private const CONFIG_NAME = 'assettest_video_processor';

    private const PROCESS_ID = 'assettest-video-processor';

    /**
     * @var string[]
     */
    private array $tmpFiles = [];

    /**
     * @var string[]
     */
    private array $jobStoreIds = [];

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $tmpFile) {
            if (is_file($tmpFile)) {
                unlink($tmpFile);
            }
        }

        foreach ($this->jobStoreIds as $jobStoreId) {
            TmpStore::delete($jobStoreId);
        }

        parent::tearDown();
    }

    public function testExecuteStoresThumbnailAndRemovesDestinationFile(): void
    {
        $asset = TestHelper::createVideoAsset();
        $converter = $this->queueConversion($asset, FakeVideoConverter::OUTCOME_SUCCESS);

        Processor::execute(self::PROCESS_ID, $asset->getId());

        $this->assertFileDoesNotExist($converter->getDestinationFile());
        $this->assertSame(
            FakeVideoConverter::CONTENT,
            Storage::get('thumbnail')->read($converter->getStorageFile())
        );

        $thumbnail = $this->getThumbnailSetting($asset);
        $this->assertSame('finished', $thumbnail['status']);
        $this->assertArrayHasKey('mp4', $thumbnail['formats']);
    }

    public function testExecuteRemovesDestinationFileWhenConversionFails(): void
    {
        $asset = TestHelper::createVideoAsset();
        $converter = $this->queueConversion($asset, FakeVideoConverter::OUTCOME_FAILURE);

        Processor::execute(self::PROCESS_ID, $asset->getId());

        $this->assertFileDoesNotExist($converter->getDestinationFile());
        $this->assertFalse(Storage::get('thumbnail')->fileExists($converter->getStorageFile()));

        $thumbnail = $this->getThumbnailSetting($asset);
        $this->assertSame('error', $thumbnail['status']);
        $this->assertSame([], $thumbnail['formats']);
    }

    public function testExecuteRemovesDestinationFileWhenConversionThrows(): void
    {
        $asset = TestHelper::createVideoAsset();
        $converter = $this->queueConversion($asset, FakeVideoConverter::OUTCOME_EXCEPTION);

        Processor::execute(self::PROCESS_ID, $asset->getId());

        $this->assertFileDoesNotExist($converter->getDestinationFile());
        $this->assertFalse(Storage::get('thumbnail')->fileExists($converter->getStorageFile()));
        $this->assertSame([], $this->getThumbnailSetting($asset)['formats']);
    }

    /**
     * The end of the process that called process() is simulated by the clean-up that runs after every
     * request and every handled message; the shutdown function registered next to it cannot be triggered
     * from within a test.
     */
    public function testDestinationFileOutlivesTheProcessThatQueuedTheConversion(): void
    {
        if (!(new Ffmpeg())->isAvailable()) {
            $this->markTestSkipped('FFmpeg is not available');
        }

        $asset = TestHelper::createVideoAsset();

        $processor = Processor::process($asset, $this->createConfig());

        $this->assertNotNull($processor);
        $this->jobStoreIds[] = 'video-job-' . $processor->getProcessId();

        $queue = $processor->getQueue();
        $this->assertCount(1, $queue);

        // the worker has started to write the file ...
        $destinationFile = $queue[0]->getDestinationFile();
        $this->tmpFiles[] = $destinationFile;
        file_put_contents($destinationFile, FakeVideoConverter::CONTENT);

        // ... when the process that queued the conversion ends
        Pimcore::getContainer()->get(LongRunningHelper::class)->deleteTemporaryFiles();

        $this->assertFileExists($destinationFile);
    }

    /**
     * Stores a conversion job the way process() does, but with a converter that does not need FFmpeg.
     */
    private function queueConversion(Asset\Video $asset, string $outcome): FakeVideoConverter
    {
        $converter = new FakeVideoConverter();
        $converter->outcome = $outcome;
        $converter->setFormat('mp4');
        $converter->setDestinationFile(File::getLocalTempFilePath('mp4', true));
        $converter->setStorageFile(
            $asset->getRealPath() . $asset->getId() . '/video-thumb__' . $asset->getId() . '__' . self::CONFIG_NAME . '/example.mp4'
        );
        $this->tmpFiles[] = $converter->getDestinationFile();

        $asset->setCustomSetting('thumbnails', [
            self::CONFIG_NAME => [
                'status' => 'inprogress',
                'formats' => [],
                'processId' => self::PROCESS_ID,
            ],
        ]);
        $asset->save();

        $processor = new Processor();
        $processor->setProcessId(self::PROCESS_ID);
        $processor->setAssetId($asset->getId());
        $processor->setConfig($this->createConfig());
        $processor->setQueue([$converter]);
        $processor->save();
        $this->jobStoreIds[] = 'video-job-' . self::PROCESS_ID;

        return $converter;
    }

    private function createConfig(): Config
    {
        $config = new Config();
        $config->setName(self::CONFIG_NAME);
        $config->setAudioBitrate(128);
        $config->setVideoBitrate(400);

        return $config;
    }

    /**
     * @return array{status: string, formats: array<string, string>}
     */
    private function getThumbnailSetting(Asset\Video $asset): array
    {
        $asset = Asset\Video::getById($asset->getId(), ['force' => true]);

        return $asset->getCustomSetting('thumbnails')[self::CONFIG_NAME];
    }
}
