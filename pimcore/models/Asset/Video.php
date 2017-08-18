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
use Pimcore\Logger;
use Pimcore\Model;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Video extends Model\Asset
{
    /**
     * @var string
     */
    public $type = 'video';

    protected function update()
    {

        // only do this if the file exists and contains data
        if ($this->getDataChanged() || !$this->getCustomSetting('duration')) {
            try {
                $this->setCustomSetting('duration', $this->getDurationFromBackend());
            } catch (\Exception $e) {
                Logger::err('Unable to get duration of video: ' . $this->getId());
            }
        }

        $this->clearThumbnails();
        parent::update();
    }

    /**
     *
     */
    public function delete()
    {
        parent::delete();
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

            // video thumbnails and image previews
            $files = glob(PIMCORE_TEMPORARY_DIRECTORY . '/video-image-cache/video_' . $this->getId() . '__*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }

            $imageFiles = glob($this->getImageThumbnailSavePath() . '/image-thumb__' . $this->getId() . '__*');
            $videoFiles = glob($this->getVideoThumbnailSavePath() . '/video-thumb__' . $this->getId() . '__*');

            $files = array_merge($imageFiles, $videoFiles);
            foreach($files as $file) {
                recursiveDelete($file);
            }
        }
    }

    /**
     * @param string $config
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
     * @param $thumbnailName
     * @param array $onlyFormats
     *
     * @return string
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
                            'frontendPath' => $path
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
     * @param $thumbnailName
     * @param null $timeOffset
     * @param null $imageAsset
     *
     * @return mixed|string
     *
     * @throws \Exception
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
     * @return null
     *
     * @throws \Exception
     */
    protected function getDurationFromBackend()
    {
        if (\Pimcore\Video::isAvailable()) {
            $converter = \Pimcore\Video::getInstance();
            $converter->load($this->getFileSystemPath());

            return $converter->getDuration();
        }

        return null;
    }

    /**
     * @return mixed
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
}
