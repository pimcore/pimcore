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

namespace Pimcore\Model\Asset;

use Exception;
use Pimcore;
use Pimcore\Config;
use Pimcore\Event\FrontendEvents;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Tool;
use RuntimeException;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Video extends Model\Asset
{
    use Model\Asset\MetaData\EmbeddedMetaDataTrait;

    protected string $type = 'video';

    protected function update(array $params = []): void
    {
        if ($this->getDataChanged()) {
            foreach (['duration', 'videoWidth', 'videoHeight'] as $key) {
                $this->removeCustomSetting($key);
            }
        }

        if ($params['isUpdate']) {
            $this->clearThumbnails();
        }

        parent::update($params);
    }

    public function clearThumbnails(bool $force = false): void
    {
        if ($this->getDataChanged() || $force) {
            // clear the thumbnail custom settings
            $this->setCustomSetting('thumbnails', null);
            parent::clearThumbnails($force);
        }
    }

    /**
     * @internal
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getThumbnailConfig(null|string|Video\Thumbnail\Config $config): ?Video\Thumbnail\Config
    {
        $thumbnail = null;

        if (is_string($config)) {
            $thumbnail = Video\Thumbnail\Config::getByName($config);

            if ($thumbnail === null) {
                throw new Model\Exception\NotFoundException('Video Thumbnail definition "' . $config . '" does not exist');
            }
        } elseif ($config instanceof Video\Thumbnail\Config) {
            $thumbnail = $config;
        }

        return $thumbnail;
    }

    /**
     * Returns a path to a given thumbnail or an thumbnail configuration
     *
     *
     */
    public function getThumbnail(string|Video\Thumbnail\Config $thumbnailName, array $onlyFormats = []): ?array
    {
        $thumbnail = $this->getThumbnailConfig($thumbnailName);

        if ($thumbnail) {
            try {
                Video\Thumbnail\Processor::process($this, $thumbnail, $onlyFormats);

                // check for existing videos
                $customSetting = $this->getCustomSetting('thumbnails');
                if (is_array($customSetting) && array_key_exists($thumbnail->getName(), $customSetting)) {
                    foreach ($customSetting[$thumbnail->getName()]['formats'] as $pathKey => &$path) {
                        if ($pathKey == 'medias') {
                            foreach ($path as &$format) {
                                foreach ($format as &$f) {
                                    $f = $this->enrichThumbnailPath($f);
                                }
                            }
                        } else {
                            $path = $this->enrichThumbnailPath($path);
                        }
                    }

                    return $customSetting[$thumbnail->getName()];
                }
            } catch (Exception $e) {
                Logger::error("Couldn't create thumbnail of video " . $this->getRealFullPath() . ': ' . $e);
            }
        }

        return null;
    }

    private function enrichThumbnailPath(string $path): string
    {
        $fullPath = rtrim($this->getRealPath(), '/') . '/' . ltrim($path, '/');

        if (Tool::isFrontend()) {
            $path = urlencode_ignore_slash($fullPath);
            $prefix = Config::getSystemConfiguration('assets')['frontend_prefixes']['thumbnail'];
            $path = $prefix . $path;
        }

        $event = new GenericEvent($this, [
            'filesystemPath' => $fullPath,
            'frontendPath' => $path,
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::ASSET_VIDEO_THUMBNAIL);

        return $event->getArgument('frontendPath');
    }

    public function getImageThumbnail(array|string|Image\Thumbnail\Config $thumbnailName, int $timeOffset = null, Image $imageAsset = null): Video\ImageThumbnailInterface
    {
        if (!\Pimcore\Video::isAvailable()) {
            Logger::error("Couldn't create image-thumbnail of video " . $this->getRealFullPath() . ' no video adapter is available');

            return new Video\ImageThumbnail(null); // returns error image
        }

        if (!$this->getCustomSetting('videoWidth') || !$this->getCustomSetting('videoHeight')) {
            Logger::info('Image thumbnail not yet available, processing is done asynchronously.');
            $this->addToUpdateTaskQueue();

            return new Video\ImageThumbnail(null); // returns error image
        }

        return new Video\ImageThumbnail($this, $thumbnailName, $timeOffset, $imageAsset);
    }

    /**
     * @internal
     *
     *
     */
    public function getDurationFromBackend(?string $filePath = null): ?float
    {
        if (\Pimcore\Video::isAvailable()) {
            if (!$filePath) {
                $filePath = $this->getLocalFile();
            }

            $converter = \Pimcore\Video::getInstance();
            $converter->load($filePath, ['asset' => $this]);

            return $converter->getDuration();
        }

        return null;
    }

    /**
     * @internal
     *
     */
    public function getDimensionsFromBackend(): ?array
    {
        if (\Pimcore\Video::isAvailable()) {
            $converter = \Pimcore\Video::getInstance();
            $converter->load($this->getLocalFile(), ['asset' => $this]);

            return $converter->getDimensions();
        }

        return null;
    }

    /**
     *
     * @throws Exception
     */
    public function getDuration(): float|int|null
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

    public function getDimensions(): ?array
    {
        $width = $this->getCustomSetting('videoWidth');
        $height = $this->getCustomSetting('videoHeight');
        if (!$width || !$height) {
            $dimensions = $this->getDimensionsFromBackend();
            if ($dimensions) {
                $this->setCustomSetting('videoWidth', (int) $dimensions['width']);
                $this->setCustomSetting('videoHeight', (int) $dimensions['height']);

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

    public function getWidth(): ?int
    {
        $dimensions = $this->getDimensions();
        if ($dimensions) {
            return (int) $dimensions['width'];
        }

        return null;
    }

    public function getHeight(): ?int
    {
        $dimensions = $this->getDimensions();
        if ($dimensions) {
            return (int) $dimensions['height'];
        }

        return null;
    }

    /**
     * @internal
     *
     */
    public function getSphericalMetaData(): array
    {
        return $this->getCustomSetting('SphericalMetaData') ?? [];
    }

    /**
     * @internal
     *
     */
    public function getSphericalMetaDataFromBackend(): array
    {
        $data = [];

        if (in_array(pathinfo($this->getFilename(), PATHINFO_EXTENSION), ['mp4', 'webm'])) {
            $chunkSize = 1024;
            $file_pointer = $this->getStream();

            $tag = '<rdf:SphericalVideo';
            $tagLength = strlen($tag);
            $buffer = false;

            // find open tag
            $overlapString = '';
            while ($buffer === false && ($chunk = fread($file_pointer, $chunkSize)) !== false) {
                if (strlen($chunk) <= $tagLength) {
                    break;
                }

                $chunk = $overlapString . $chunk;

                if (($position = strpos($chunk, $tag)) === false) {
                    // if open tag not found, back up just in case the open tag is on the split.
                    $overlapString = substr($chunk, $tagLength * -1);
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
                    $offset = strlen($buffer) - $tagLength; //subtract the tag size for case it's split between chunks
                    $buffer .= $chunk;
                }

                if ($position === false) {
                    // this would mean the open tag was found, but the close tag was not.  Maybe file corruption?
                    throw new RuntimeException('No close tag found.  Possibly corrupted file.');
                }

                $buffer = substr($buffer, 0, $position + $tagLength);
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
