<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Asset
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset;

use Pimcore\Event\FrontendEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Video extends Model\Asset
{
    use Model\Asset\MetaData\EmbeddedMetaDataTrait;
    /**
     * @var string
     */
    protected $type = 'video';

    /**
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws \Exception
     */
    protected function update($params = [])
    {
        if ($this->getDataChanged() || !$this->getCustomSetting('duration') || !$this->getCustomSetting('embeddedMetaDataExtracted') || !$this->getCustomSetting('videoWidth') || !$this->getCustomSetting('videoHeight')) {
            // save the current data into a tmp file to calculate the dimensions, otherwise updates wouldn't be updated
            // because the file is written in parent::update();
            $tmpFile = $this->getTemporaryFile();

            if ($this->getDataChanged() || !$this->getCustomSetting('duration')) {
                try {
                    $this->setCustomSetting('duration', $this->getDurationFromBackend($tmpFile));
                } catch (\Exception $e) {
                    Logger::err('Unable to get duration of video: ' . $this->getId());
                }
            }

            if ($this->getDataChanged() || !$this->getCustomSetting('videoWidth') || !$this->getCustomSetting('videoHeight')) {
                try {
                    $dimensions = $this->getDimensionsFromBackend();
                    if ($dimensions) {
                        $this->setCustomSetting('videoWidth', $dimensions['width']);
                        $this->setCustomSetting('videoHeight', $dimensions['height']);
                    } else {
                        $this->removeCustomSetting('videoWidth');
                        $this->removeCustomSetting('videoHeight');
                    }
                } catch (\Exception $e) {
                    Logger::err('Unable to get dimensions of video: ' . $this->getId());
                }
            }

            $this->handleEmbeddedMetaData(true, $tmpFile);
        }

        $this->clearThumbnails();
        parent::update($params);
    }

    /**
     * @inheritdoc
     */
    public function delete(bool $isNested = false)
    {
        parent::delete($isNested);
        $this->clearThumbnails(true);
    }

    /**
     * @param bool $force
     */
    public function clearThumbnails($force = false)
    {
        if ($this->_dataChanged || $force) {
            // clear the thumbnail custom settings
            $this->setCustomSetting('thumbnails', null);

            if (is_dir($this->getImageThumbnailSavePath())) {
                $directoryIterator = new \DirectoryIterator($this->getImageThumbnailSavePath());
                $filterIterator = new \CallbackFilterIterator($directoryIterator, function (\SplFileInfo $fileInfo) {
                    return strpos($fileInfo->getFilename(), 'image-thumb__' . $this->getId()) === 0 || strpos($fileInfo->getFilename(), 'video-image-cache__' . $this->getId() . '__thumbnail_') === 0;
                });
                /** @var \SplFileInfo $fileInfo */
                foreach ($filterIterator as $fileInfo) {
                    recursiveDelete($fileInfo->getPathname());
                }
            }

            if (is_dir($this->getVideoThumbnailSavePath())) {
                $directoryIterator = new \DirectoryIterator($this->getVideoThumbnailSavePath());
                $filterIterator = new \CallbackFilterIterator($directoryIterator, function (\SplFileInfo $fileInfo) {
                    return strpos($fileInfo->getFilename(), 'video-thumb__' . $this->getId()) === 0;
                });
                /** @var \SplFileInfo $fileInfo */
                foreach ($filterIterator as $fileInfo) {
                    recursiveDelete($fileInfo->getPathname());
                }
            }
        }
    }

    /**
     * @param string|Video\Thumbnail\Config $config
     *
     * @return Video\Thumbnail\Config|null
     */
    public function getThumbnailConfig($config)
    {
        $thumbnail = null;

        if (is_string($config)) {
            $thumbnail = Video\Thumbnail\Config::getByName($config);
        } elseif ($config instanceof Video\Thumbnail\Config) {
            $thumbnail = $config;
        }

        return $thumbnail;
    }

    /**
     * Returns a path to a given thumbnail or an thumbnail configuration
     *
     * @param string|Video\Thumbnail\Config $thumbnailName
     * @param array $onlyFormats
     *
     * @return array|null
     */
    public function getThumbnail($thumbnailName, $onlyFormats = [])
    {
        $thumbnail = $this->getThumbnailConfig($thumbnailName);

        if ($thumbnail) {
            try {
                Video\Thumbnail\Processor::process($this, $thumbnail, $onlyFormats);

                // check for existing videos
                $customSetting = $this->getCustomSetting('thumbnails');
                if (is_array($customSetting) && array_key_exists($thumbnail->getName(), $customSetting)) {
                    foreach ($customSetting[$thumbnail->getName()]['formats'] as &$path) {
                        $fullPath = $this->getVideoThumbnailSavePath() . $path;
                        $path = str_replace(PIMCORE_TEMPORARY_DIRECTORY . '/video-thumbnails', '', $fullPath);
                        $path = urlencode_ignore_slash($path);

                        $event = new GenericEvent($this, [
                            'filesystemPath' => $fullPath,
                            'frontendPath' => $path,
                        ]);
                        \Pimcore::getEventDispatcher()->dispatch(FrontendEvents::ASSET_VIDEO_THUMBNAIL, $event);
                        $path = $event->getArgument('frontendPath');
                    }

                    return $customSetting[$thumbnail->getName()];
                }
            } catch (\Exception $e) {
                Logger::error("Couldn't create thumbnail of video " . $this->getRealFullPath());
                Logger::error($e);
            }
        }

        return null;
    }

    /**
     * @param string|array|Image\Thumbnail\Config $thumbnailName
     * @param int|null $timeOffset
     * @param Image|null $imageAsset
     *
     * @return Video\ImageThumbnail
     */
    public function getImageThumbnail($thumbnailName, $timeOffset = null, $imageAsset = null)
    {
        if (!\Pimcore\Video::isAvailable()) {
            Logger::error("Couldn't create image-thumbnail of video " . $this->getRealFullPath() . ' no video adapter is available');

            return new Video\ImageThumbnail(null); // returns error image
        }

        return new Video\ImageThumbnail($this, $thumbnailName, $timeOffset, $imageAsset);
    }

    /**
     * @param string|null $filePath
     *
     * @return float|null
     *
     * @throws \Exception
     */
    protected function getDurationFromBackend(?string $filePath = null)
    {
        if (\Pimcore\Video::isAvailable()) {
            if (!$filePath) {
                $filePath = $this->getFileSystemPath();
            }

            $converter = \Pimcore\Video::getInstance();
            $converter->load($filePath, ['asset' => $this]);

            return $converter->getDuration();
        }

        return null;
    }

    /**
     * @return array|null
     *
     * @throws \Exception
     */
    protected function getDimensionsFromBackend()
    {
        if (\Pimcore\Video::isAvailable()) {
            $converter = \Pimcore\Video::getInstance();
            $converter->load($this->getFileSystemPath(), ['asset' => $this]);

            return $converter->getDimensions();
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getDuration()
    {
        $duration = $this->getCustomSetting('duration');
        if (!$duration) {
            $duration = $this->getDurationFromBackend();
            if ($duration) {
                $this->setCustomSetting('duration', $duration);

                Model\Version::disable();
                $this->save(); // auto save
                Model\Version::enable();
            }
        }

        return $duration;
    }

    /**
     * @return array|null
     */
    public function getDimensions()
    {
        $dimensions = null;
        $width = $this->getCustomSetting('videoWidth');
        $height = $this->getCustomSetting('videoHeight');
        if (!$width || !$height) {
            $dimensions = $this->getDimensionsFromBackend();
            if ($dimensions) {
                $this->setCustomSetting('videoWidth', $dimensions['width']);
                $this->setCustomSetting('videoHeight', $dimensions['height']);

                Model\Version::disable();
                $this->save(); // auto save
                Model\Version::enable();
            }
        } else {
            $dimensions = [
                'width' => $width,
                'height' => $height,
            ];
        }

        return $dimensions;
    }

    /**
     * @return int|null
     */
    public function getWidth()
    {
        $dimensions = $this->getDimensions();
        if ($dimensions) {
            return $dimensions['width'];
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getHeight()
    {
        $dimensions = $this->getDimensions();
        if ($dimensions) {
            return $dimensions['height'];
        }

        return null;
    }

    public function getSphericalMetaData()
    {
        $data = [];

        if (in_array(File::getFileExtension($this->getFilename()), ['mp4', 'webm'])) {
            $chunkSize = 1024;
            if (!is_int($chunkSize)) {
                throw new \RuntimeException('Expected integer value for argument #2 (chunkSize)');
            }

            if ($chunkSize < 12) {
                throw new \RuntimeException('Chunk size cannot be less than 12 argument #2 (chunkSize)');
            }

            if (($file_pointer = fopen($this->getFileSystemPath(), 'rb')) === false) {
                throw new \RuntimeException('Could not open file for reading');
            }

            $tag = '<rdf:SphericalVideo';
            $tagLength = strlen($tag);
            $buffer = false;

            // find open tag
            while ($buffer === false && ($chunk = fread($file_pointer, $chunkSize)) !== false) {
                if (strlen($chunk) <= $tagLength) {
                    break;
                }
                if (($position = strpos($chunk, $tag)) === false) {
                    // if open tag not found, back up just in case the open tag is on the split.
                    fseek($file_pointer, $tagLength * -1, SEEK_CUR);
                } else {
                    $buffer = substr($chunk, $position);
                }
            }

            if ($buffer !== false) {
                $tag = '</rdf:SphericalVideo>';
                $tagLength = strlen($tag);
                $offset = 0;
                while (($position = strpos($buffer, $tag, $offset)) === false && ($chunk = fread($file_pointer,
                        $chunkSize)) !== false && !empty($chunk)) {
                    $offset = strlen($buffer) - $tagLength; // subtract the tag size just in case it's split between chunks.
                    $buffer .= $chunk;
                }

                if ($position === false) {
                    // this would mean the open tag was found, but the close tag was not.  Maybe file corruption?
                    throw new \RuntimeException('No close tag found.  Possibly corrupted file.');
                } else {
                    $buffer = substr($buffer, 0, $position + $tagLength);
                }

                $buffer = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $buffer);
                $buffer = preg_replace('@<(/)?([a-zA-Z]+):([a-zA-Z]+)@', '<$1$2____$3', $buffer);

                $xml = @simplexml_load_string($buffer);
                $data = object2array($xml);
            }

            fclose($file_pointer);
        }

        // remove namespace prefixes if possible
        $resultData = [];
        array_walk($data, function ($value, $key) use (&$resultData) {
            $parts = explode('____', $key);
            $length = count($parts);
            if ($length > 1) {
                $name = $parts[$length - 1];
                if (!isset($resultData[$name])) {
                    $key = $name;
                }
            }

            $resultData[$key] = $value;
        });

        return $resultData;
    }
}
