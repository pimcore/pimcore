<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Asset\Video\Thumbnail;

use Exception;
use Pimcore;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Messenger\VideoConvertMessage;
use Pimcore\Model;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Tool\Storage;
use Pimcore\Video\Adapter;
use Symfony\Component\Lock\LockFactory;

/**
 * @internal
 */
class Processor
{
    protected static array $argumentMapping = [
        'resize'            => ['width', 'height'],
        'scaleByWidth'      => ['width'],
        'scaleByHeight'     => ['height'],
        'cut'               => ['start', 'duration'],
        'setFramerate'      => ['fps'],
        'colorChannelMixer' => ['effect'],
        'mute'              => [],
    ];

    /**
     * @var \Pimcore\Video\Adapter[]
     */
    protected array $queue = [];

    protected string $processId;

    protected int $assetId;

    protected Config $config;

    protected int $status;

    /**
     *
     *
     * @throws Exception
     */
    public static function process(Model\Asset\Video $asset, Config $config, array $onlyFormats = []): ?Processor
    {
        if (!\Pimcore\Video::isAvailable()) {
            throw new Exception('No ffmpeg executable found, please configure the correct path in the system settings');
        }

        $storage = Storage::get('thumbnail');

        $instance = new self();
        $formats = empty($onlyFormats) ? ['mp4'] : $onlyFormats;
        $instance->setProcessId(uniqid());
        $instance->setAssetId($asset->getId());
        $instance->setConfig($config);

        //create dash file(.mpd), if medias exists
        $medias = $config->getMedias();
        if (count($medias) > 0) {
            $formats[] = 'mpd';
        }

        // check for running or already created thumbnails
        $customSetting = $asset->getCustomSetting('thumbnails');
        $existingFormats = [];
        if (is_array($customSetting) && array_key_exists($config->getName(), $customSetting)) {
            if ($customSetting[$config->getName()]['status'] == 'inprogress') {
                if (TmpStore::get($instance->getJobStoreId($customSetting[$config->getName()]['processId']))) {
                    return null;
                }
            } elseif ($customSetting[$config->getName()]['status'] == 'finished') {
                // check if the files are there
                $formatsToConvert = [];
                foreach ($formats as $f) {
                    $format = $customSetting[$config->getName()]['formats'][$f] ?? null;
                    if (!$storage->fileExists($asset->getRealPath() . $format)) {
                        $formatsToConvert[] = $f;
                    } else {
                        $existingFormats[$f] = $customSetting[$config->getName()]['formats'][$f];
                    }
                }

                if (!empty($formatsToConvert)) {
                    $formats = $formatsToConvert;
                } else {
                    return null;
                }
            } elseif ($customSetting[$config->getName()]['status'] == 'error') {
                throw new Exception('Unable to convert video, see logs for details.');
            }
        }

        foreach ($formats as $format) {
            $thumbDir = $asset->getRealPath().'/'.$asset->getId().'/video-thumb__'.$asset->getId().'__'.$config->getName();
            $filename = preg_replace("/\." . preg_quote(pathinfo($asset->getFilename(), PATHINFO_EXTENSION), '/') . '/', '', $asset->getFilename()) . '.' . $format;
            $storagePath = $thumbDir . '/' . $filename;
            $tmpPath = File::getLocalTempFilePath($format);

            if ($converter = \Pimcore\Video::getInstance()) {
                $converter->setAudioBitrate($config->getAudioBitrate());
                $converter->setVideoBitrate($config->getVideoBitrate());
                $converter->setFormat($format);
                $converter->setDestinationFile($tmpPath);
                $converter->setStorageFile($storagePath);

                //add media queries for mpd file generation
                if ($format == 'mpd') {
                    $medias = $config->getMedias();
                    foreach ($medias as $media => $transformations) {
                        //used just to generate arguments for medias
                        if ($subConverter = \Pimcore\Video::getInstance()) {
                            self::applyTransformations($subConverter, $transformations);
                            $medias[$media]['converter'] = $subConverter;
                        }
                    }
                    $converter->setMedias($medias);
                }

                $transformations = $config->getItems();
                self::applyTransformations($converter, $transformations);

                $instance->queue[] = $converter;
            }
        }

        $customSetting = $asset->getCustomSetting('thumbnails');
        $customSetting = is_array($customSetting) ? $customSetting : [];
        $customSetting[$config->getName()] = [
            'status' => 'inprogress',
            'formats' => $existingFormats,
            'processId' => $instance->getProcessId(),
        ];
        $asset->setCustomSetting('thumbnails', $customSetting);

        Model\Version::disable();
        $asset->save();
        Model\Version::enable();

        $instance->save();

        Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
            new VideoConvertMessage($instance->getProcessId())
        );

        return $instance;
    }

    private static function applyTransformations(Adapter $converter, array $transformations): void
    {
        foreach ($transformations as $transformation) {
            if (!empty($transformation)) {
                $arguments = [];
                $mapping = self::$argumentMapping[$transformation['method']];

                if (is_array($transformation['arguments'])) {
                    foreach ($transformation['arguments'] as $key => $value) {
                        $position = array_search($key, $mapping);
                        if ($position !== false) {
                            $arguments[$position] = $value;
                        }
                    }
                }

                ksort($arguments);
                if (count($mapping) == count($arguments)) {
                    call_user_func_array([$converter, $transformation['method']], $arguments);
                } else {
                    $message = 'Video Transform failed: cannot call method `' . $transformation['method'] . '´ with arguments `' . implode(',', $arguments) . '´ because there are too few arguments';
                    Logger::error($message);
                }
            }
        }
    }

    public static function execute(string $processId): void
    {
        $instance = new self();
        $instance->setProcessId($processId);

        $instanceItem = TmpStore::get($instance->getJobStoreId($processId));
        /**
         * @var self $instance
         */
        $instance = $instanceItem->getData();

        $formats = [];
        $conversionStatus = 'finished';

        // check if there is already a transcoding process running, wait if so ...
        $lock = Pimcore::getContainer()->get(LockFactory::class)->createLock('video-transcoding', 7200);
        $lock->acquire(true);

        $asset = Model\Asset::getById($instance->getAssetId());
        $workerSourceFile = $asset->getTemporaryFile();

        // start converting
        foreach ($instance->queue as $converter) {
            try {
                $converter->load($workerSourceFile, ['asset' => $asset]);

                Logger::info('start video ' . $converter->getFormat() . ' to ' . $converter->getDestinationFile());
                $success = $converter->save();
                Logger::info('finished video ' . $converter->getFormat() . ' to ' . $converter->getDestinationFile());

                if ($success) {
                    $source = fopen($converter->getDestinationFile(), 'rb');
                    if (false === $source) {
                        $conversionStatus = 'error';
                        Logger::info('could not open stream resource at path "' . $converter->getDestinationFile() . '" for Video conversion.');

                        continue;
                    }
                    Storage::get('thumbnail')->writeStream($converter->getStorageFile(), $source);

                    fclose($source);

                    unlink($converter->getDestinationFile());

                    if ($converter->getFormat() === 'mpd') {
                        $streamFilesPath = str_replace('.mpd', '-stream*.mp4', $converter->getDestinationFile());
                        $streams = glob($streamFilesPath);
                        $parentPath = dirname($converter->getStorageFile());

                        foreach ($streams as $steam) {
                            $storagePath = $parentPath.'/'.basename($steam);
                            $source = fopen($steam, 'rb');
                            Storage::get('thumbnail')->writeStream($storagePath, $source);

                            if (is_resource($source)) {
                                fclose($source);
                            }
                        }
                    }

                    $formats[$converter->getFormat()] = preg_replace(
                        '/'.preg_quote($asset->getRealPath(), '/').'/',
                        '',
                        $converter->getStorageFile(),
                        1
                    );
                } else {
                    $conversionStatus = 'error';
                }

                $converter->destroy();
            } catch (Exception $e) {
                Logger::error((string) $e);
            }
        }

        $lock->release();

        if ($asset) {
            $customSetting = $asset->getCustomSetting('thumbnails');
            $customSetting = is_array($customSetting) ? $customSetting : [];

            if (array_key_exists($instance->getConfig()->getName(), $customSetting)
                && array_key_exists('formats', $customSetting[$instance->getConfig()->getName()])
                && is_array($customSetting[$instance->getConfig()->getName()]['formats'])) {
                $formats = array_merge($customSetting[$instance->getConfig()->getName()]['formats'], $formats);
            }

            $customSetting[$instance->getConfig()->getName()] = [
                'status' => $conversionStatus,
                'formats' => $formats,
            ];
            $asset->setCustomSetting('thumbnails', $customSetting);

            Model\Version::disable();
            $asset->save();
            Model\Version::enable();
        }

        @unlink($workerSourceFile);

        TmpStore::delete($instance->getJobStoreId());
    }

    public function save(): bool
    {
        TmpStore::add($this->getJobStoreId(), $this, 'video-job');

        return true;
    }

    protected function getJobStoreId(string $processId = null): string
    {
        if (!$processId) {
            $processId = $this->getProcessId();
        }

        return 'video-job-' . $processId;
    }

    public function setProcessId(string $processId): static
    {
        $this->processId = $processId;

        return $this;
    }

    public function getProcessId(): string
    {
        return $this->processId;
    }

    public function setAssetId(int $assetId): static
    {
        $this->assetId = $assetId;

        return $this;
    }

    public function getAssetId(): int
    {
        return $this->assetId;
    }

    public function setConfig(Config $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function setQueue(array $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function getQueue(): array
    {
        return $this->queue;
    }
}
