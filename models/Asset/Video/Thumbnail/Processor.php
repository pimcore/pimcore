<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\Video\Thumbnail;

use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Tool\Console;
use Pimcore\Tool\Storage;
use Symfony\Component\Lock\LockFactory;

/**
 * @internal
 */
class Processor
{
    /**
     * @var array
     */
    protected static $argumentMapping = [
        'resize' => ['width', 'height'],
        'scaleByWidth' => ['width'],
        'scaleByHeight' => ['height'],
    ];

    /**
     * @var \Pimcore\Video\Adapter[]
     */
    protected $queue = [];

    /**
     * @var string
     */
    protected $processId;

    /**
     * @var int
     */
    protected $assetId;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var null|string
     */
    protected $deleteSourceAfterFinished;

    /**
     * @param Model\Asset\Video $asset
     * @param Config $config
     * @param array $onlyFormats
     *
     * @return Processor|null
     *
     * @throws \Exception
     */
    public static function process(Model\Asset\Video $asset, $config, $onlyFormats = [])
    {
        if (!\Pimcore\Video::isAvailable()) {
            throw new \Exception('No ffmpeg executable found, please configure the correct path in the system settings');
        }

        $storage = Storage::get('thumbnail');
        $sourceFile = $asset->getTemporaryFile(true);

        $instance = new self();
        $formats = empty($onlyFormats) ? ['mp4'] : $onlyFormats;
        $instance->setProcessId(uniqid());
        $instance->setAssetId($asset->getId());
        $instance->setConfig($config);
        $instance->setDeleteSourceAfterFinished($sourceFile);

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
                throw new \Exception('Unable to convert video, see logs for details.');
            }
        }

        foreach ($formats as $format) {
            $thumbDir = $asset->getRealPath() . '/video-thumb__' . $asset->getId() . '__' . $config->getName();
            $filename = preg_replace("/\." . preg_quote(File::getFileExtension($asset->getFilename()), '/') . '/', '', $asset->getFilename()) . '.' . $format;
            $storagePath = $thumbDir . '/' . $filename;
            $tmpPath = File::getLocalTempFilePath($format);

            $converter = \Pimcore\Video::getInstance();
            $converter->load($sourceFile, ['asset' => $asset]);
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
                    $subConverter = \Pimcore\Video::getInstance();
                    self::applyTransformations($subConverter, $transformations);
                    $medias[$media]['converter'] = $subConverter;
                }
                $converter->setMedias($medias);
            }

            $transformations = $config->getItems();
            self::applyTransformations($converter, $transformations);

            $instance->queue[] = $converter;
        }

        $customSetting = $asset->getCustomSetting('thumbnails');
        $customSetting = is_array($customSetting) ? $customSetting : [];
        $customSetting[$config->getName()] = [
            'status' => 'inprogress',
            'formats' => $existingFormats,
            'processId' => $instance->getProcessId(),
        ];
        $asset->setCustomSetting('thumbnails', $customSetting);
        $asset->save();

        $instance->convert();

        return $instance;
    }

    private static function applyTransformations($converter, $transformations)
    {
        if (is_array($transformations) && count($transformations) > 0) {
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
    }

    /**
     * @param string $processId
     */
    public static function execute($processId)
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
        $lock = \Pimcore::getContainer()->get(LockFactory::class)->createLock('video-transcoding', 7200);
        $lock->acquire(true);

        $asset = Model\Asset::getById($instance->getAssetId());

        // start converting
        foreach ($instance->queue as $converter) {
            try {
                Logger::info('start video ' . $converter->getFormat() . ' to ' . $converter->getDestinationFile());
                $success = $converter->save();
                Logger::info('finished video ' . $converter->getFormat() . ' to ' . $converter->getDestinationFile());

                $source = fopen($converter->getDestinationFile(), 'rb');
                Storage::get('thumbnail')->writeStream($converter->getStorageFile(), $source);
                fclose($source);
                unlink($converter->getDestinationFile());

                if ($converter->getFormat() === 'mpd') {
                    $streamFilesPath = str_replace('.mpd', '-stream*.mp4', $converter->getDestinationFile());
                    $streams = glob($streamFilesPath);
                    $parentPath = dirname($converter->getStorageFile());

                    foreach ($streams as $steam) {
                        $storagePath = $parentPath . '/' . basename($steam);
                        $source = fopen($steam, 'rb');
                        Storage::get('thumbnail')->writeStream($storagePath, $source);
                        fclose($source);
                        unlink($steam);

                        // set proper permissions
                        @chmod($storagePath, File::getDefaultMode());
                    }
                }

                if ($success) {
                    $formats[$converter->getFormat()] = str_replace($asset->getRealPath(), '', $converter->getStorageFile());
                } else {
                    $conversionStatus = 'error';
                }

                $converter->destroy();
            } catch (\Exception $e) {
                Logger::error($e);
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
            $asset->save();
        }

        if ($instance->getDeleteSourceAfterFinished()) {
            @unlink($instance->getDeleteSourceAfterFinished());
        }

        TmpStore::delete($instance->getJobStoreId());
    }

    /**
     * @return string|null
     */
    public function getDeleteSourceAfterFinished(): ?string
    {
        return $this->deleteSourceAfterFinished;
    }

    /**
     * @param string|null $deleteSourceAfterFinished
     */
    public function setDeleteSourceAfterFinished(?string $deleteSourceAfterFinished): void
    {
        $this->deleteSourceAfterFinished = $deleteSourceAfterFinished;
    }

    public function convert()
    {
        $this->save();
        Console::runPhpScriptInBackground(realpath(PIMCORE_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console'), [
            'internal:video-converter',
            $this->getProcessId(),
        ]);
    }

    /**
     * @return bool
     */
    public function save()
    {
        TmpStore::add($this->getJobStoreId(), $this, 'video-job');

        return true;
    }

    /**
     * @param string $processId
     *
     * @return string
     */
    protected function getJobStoreId($processId = null)
    {
        if (!$processId) {
            $processId = $this->getProcessId();
        }

        return 'video-job-' . $processId;
    }

    /**
     * @param string $processId
     *
     * @return $this
     */
    public function setProcessId($processId)
    {
        $this->processId = $processId;

        return $this;
    }

    /**
     * @return string
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * @param int $assetId
     *
     * @return $this
     */
    public function setAssetId($assetId)
    {
        $this->assetId = $assetId;

        return $this;
    }

    /**
     * @return int
     */
    public function getAssetId()
    {
        return $this->assetId;
    }

    /**
     * @param Config $config
     *
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $queue
     *
     * @return $this
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @return array
     */
    public function getQueue()
    {
        return $this->queue;
    }
}
