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

namespace Pimcore\Model\Asset\Image\Thumbnail;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Tool\Serialize;

/**
 * @method \Pimcore\Model\Asset\Image\Thumbnail\Config\Dao getDao()
 * @method void save()
 * @method void delete()
 */
class Config extends Model\AbstractModel
{
    use Model\Asset\Thumbnail\ClearTempFilesTrait;

    protected const PREVIEW_THUMBNAIL_NAME = 'pimcore-system-treepreview';

    /**
     * format of array:
     * array(
     array(
     "method" => "myName",
     "arguments" =>
     array(
     "width" => 345,
     "height" => 200
     )
     )
     * )
     *
     * @var array
     */
    public $items = [];

    /**
     * @var array
     */
    public $medias = [];

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var string
     */
    public $group = '';

    /**
     * @var string
     */
    public $format = 'SOURCE';

    /**
     * @var int
     */
    public $quality = 85;

    /**
     * @var float
     */
    public $highResolution;

    /**
     * @var bool
     */
    public $preserveColor = false;

    /**
     * @var bool
     */
    public $preserveMetaData = false;

    /**
     * @var bool
     */
    public $rasterizeSVG = false;

    /**
     * @var bool
     */
    public $downloadable = false;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var string
     */
    public $filenameSuffix;

    /**
     * @var bool
     */
    public $forcePictureTag = false;

    /**
     * @param string|array|self $config
     *
     * @return self|null
     */
    public static function getByAutoDetect($config)
    {
        $thumbnail = null;

        if (is_string($config)) {
            try {
                $thumbnail = self::getByName($config);
            } catch (\Exception $e) {
                Logger::error('requested thumbnail ' . $config . ' is not defined');

                return null;
            }
        } elseif (is_array($config)) {
            // check if it is a legacy config or a new one
            if (array_key_exists('items', $config)) {
                $thumbnail = self::getByArrayConfig($config);
            } else {
                $thumbnail = self::getByLegacyConfig($config);
            }
        } elseif ($config instanceof self) {
            $thumbnail = $config;
        }

        return $thumbnail;
    }

    /**
     * @param string $name
     *
     * @return null|Config
     */
    public static function getByName($name)
    {
        $cacheKey = self::getCacheKey($name);

        if ($name === self::PREVIEW_THUMBNAIL_NAME) {
            return self::getPreviewConfig();
        }

        try {
            $thumbnail = \Pimcore\Cache\Runtime::get($cacheKey);
            $thumbnail->setName($name);
            if (!$thumbnail) {
                throw new \Exception('Thumbnail in registry is null');
            }
        } catch (\Exception $e) {
            try {
                $thumbnail = new self();
                $thumbnail->getDao()->getByName($name);
                \Pimcore\Cache\Runtime::set($cacheKey, $thumbnail);
            } catch (\Exception $e) {
                return null;
            }
        }

        // only return clones of configs, this is necessary since we cache the configs in the registry (see above)
        // sometimes, e.g. when using the cropping tools, the thumbnail configuration is modified on-the-fly, since
        // pass-by-reference this modifications would then go to the cache/registry (singleton), by cloning the config
        // we can bypass this problem in an elegant way without parsing the XML config again and again
        $clone = clone $thumbnail;

        return $clone;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected static function getCacheKey(string $name): string
    {
        return 'imagethumb_' . crc32($name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function exists(string $name): bool
    {
        $cacheKey = self::getCacheKey($name);
        if (\Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            return true;
        }

        if ($name === self::PREVIEW_THUMBNAIL_NAME) {
            return true;
        }

        $thumbnail = new self();

        return $thumbnail->getDao()->exists($name);
    }

    /**
     * @param bool $hdpi
     *
     * @return Config
     */
    public static function getPreviewConfig($hdpi = false)
    {
        $customPreviewImageThumbnail = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['preview_image_thumbnail'];
        $thumbnail = null;

        if ($customPreviewImageThumbnail) {
            $thumbnail = self::getByName($customPreviewImageThumbnail);
        }

        if (!$thumbnail) {
            $thumbnail = new self();
            $thumbnail->setName(self::PREVIEW_THUMBNAIL_NAME);
            $thumbnail->addItem('scaleByWidth', [
                'width' => 400,
            ]);
            $thumbnail->addItem('setBackgroundImage', [
                'path' => '/bundles/pimcoreadmin/img/tree-preview-transparent-background.png',
                'mode' => 'asTexture',
            ]);
            $thumbnail->setQuality(60);
            $thumbnail->setFormat('PJPEG');
        }

        if ($hdpi) {
            $thumbnail->setHighResolution(2);
        }

        return $thumbnail;
    }

    /**
     * Returns thumbnail config for webservice export.
     *
     * @deprecated
     */
    public function getForWebserviceExport()
    {
        $arrayConfig = object2array($this);
        $items = $arrayConfig['items'];
        $arrayConfig['items'] = $items;

        return $arrayConfig;
    }

    /**
     * @param string $name
     */
    protected function createMediaIfNotExists($name)
    {
        if (!array_key_exists($name, $this->medias)) {
            $this->medias[$name] = [];
        }
    }

    /**
     * @param string $name
     * @param array $parameters
     * @param string $media
     *
     * @return bool
     */
    public function addItem($name, $parameters, $media = null)
    {
        $item = [
            'method' => $name,
            'arguments' => $parameters,
        ];

        // default is added to $this->items for compatibility reasons
        if (!$media || $media == 'default') {
            $this->items[] = $item;
        } else {
            $this->createMediaIfNotExists($media);
            $this->medias[$media][] = $item;
        }

        return true;
    }

    /**
     * @param int $position
     * @param string $name
     * @param array $parameters
     * @param string $media
     *
     * @return bool
     */
    public function addItemAt($position, $name, $parameters, $media = null)
    {
        if (!$media || $media == 'default') {
            $itemContainer = &$this->items;
        } else {
            $this->createMediaIfNotExists($media);
            $itemContainer = &$this->medias[$media];
        }

        array_splice($itemContainer, $position, 0, [[
            'method' => $name,
            'arguments' => $parameters,
        ]]);

        return true;
    }

    public function resetItems()
    {
        $this->items = [];
        $this->medias = [];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function selectMedia($name)
    {
        if (preg_match('/^[0-9a-f]{8}$/', $name)) {
            $hash = $name;
        } else {
            $hash = hash('crc32b', $name);
        }

        foreach ($this->medias as $key => $value) {
            $currentHash = hash('crc32b', $key);
            if ($key === $name || $currentHash === $hash) {
                $this->setItems($value);
                $this->setFilenameSuffix('media--' . $currentHash . '--query');

                return true;
            }
        }

        return false;
    }

    /**
     * @param string $description
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param array $items
     *
     * @return self
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $format
     *
     * @return self
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param int $quality
     *
     * @return self
     */
    public function setQuality($quality)
    {
        if ($quality) {
            $this->quality = (int) $quality;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * @param float $highResolution
     */
    public function setHighResolution($highResolution)
    {
        $this->highResolution = (float) $highResolution;
    }

    /**
     * @return float
     */
    public function getHighResolution()
    {
        return $this->highResolution;
    }

    /**
     * @param array $medias
     */
    public function setMedias($medias)
    {
        $this->medias = $medias;
    }

    /**
     * @return array
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * @return bool
     */
    public function hasMedias()
    {
        return !empty($this->medias);
    }

    /**
     * @param string $filenameSuffix
     */
    public function setFilenameSuffix($filenameSuffix)
    {
        $this->filenameSuffix = $filenameSuffix;
    }

    /**
     * @return string
     */
    public function getFilenameSuffix()
    {
        return $this->filenameSuffix;
    }

    /**
     * @static
     *
     * @param array $config
     *
     * @return self
     */
    public static function getByArrayConfig($config)
    {
        $pipe = new self();

        if (isset($config['format']) && $config['format']) {
            $pipe->setFormat($config['format']);
        }
        if (isset($config['quality']) && $config['quality']) {
            $pipe->setQuality($config['quality']);
        }
        if (isset($config['items']) && $config['items']) {
            $pipe->setItems($config['items']);
        }

        if (isset($config['highResolution']) && $config['highResolution']) {
            $pipe->setHighResolution($config['highResolution']);
        }

        // set name
        $hash = md5(Serialize::serialize($pipe));
        $pipe->setName('auto_' . $hash);

        return $pipe;
    }

    /**
     * This is just for compatibility, this method will be removed with the next major release
     *
     * @deprecated
     * @static
     *
     * @param array $config
     *
     * @return self
     */
    public static function getByLegacyConfig($config)
    {
        $pipe = new self();

        if (isset($config['format'])) {
            $pipe->setFormat($config['format']);
        }

        if (isset($config['quality'])) {
            $pipe->setQuality($config['quality']);
        }

        if (isset($config['cover'])) {
            $pipe->addItem('cover', [
                'width' => $config['width'],
                'height' => $config['height'],
                'positioning' => ((isset($config['positioning']) && !empty($config['positioning'])) ? (string)$config['positioning'] : 'center'),
                'forceResize' => (isset($config['forceResize']) ? (bool)$config['forceResize'] : false),
            ]);
        } elseif (isset($config['contain'])) {
            $pipe->addItem('contain', [
                'width' => $config['width'],
                'height' => $config['height'],
                'forceResize' => (isset($config['forceResize']) ? (bool)$config['forceResize'] : false),
            ]);
        } elseif (isset($config['frame'])) {
            $pipe->addItem('frame', [
                'width' => $config['width'],
                'height' => $config['height'],
                'forceResize' => (isset($config['forceResize']) ? (bool)$config['forceResize'] : false),
            ]);
        } elseif (isset($config['aspectratio']) && $config['aspectratio']) {
            if (isset($config['height']) && isset($config['width']) && $config['height'] > 0 && $config['width'] > 0) {
                $pipe->addItem('contain', [
                    'width' => $config['width'],
                    'height' => $config['height'],
                    'forceResize' => (isset($config['forceResize']) ? (bool)$config['forceResize'] : false),
                ]);
            } elseif (isset($config['height']) && $config['height'] > 0) {
                $pipe->addItem('scaleByHeight', [
                    'height' => $config['height'],
                    'forceResize' => (isset($config['forceResize']) ? (bool)$config['forceResize'] : false),
                ]);
            } else {
                $pipe->addItem('scaleByWidth', [
                    'width' => $config['width'],
                    'forceResize' => (isset($config['forceResize']) ? (bool)$config['forceResize'] : false),
                ]);
            }
        } else {
            if (!isset($config['width']) && isset($config['height'])) {
                $pipe->addItem('scaleByHeight', [
                    'height' => $config['height'],
                    'forceResize' => (isset($config['forceResize']) ? (bool)$config['forceResize'] : false),
                ]);
            } elseif (isset($config['width']) && !isset($config['height'])) {
                $pipe->addItem('scaleByWidth', [
                    'width' => $config['width'],
                    'forceResize' => (isset($config['forceResize']) ? (bool)$config['forceResize'] : false),
                ]);
            } elseif (isset($config['width']) && isset($config['height'])) {
                $pipe->addItem('resize', [
                    'width' => $config['width'],
                    'height' => $config['height'],
                ]);
            }
        }

        if (isset($config['highResolution'])) {
            $pipe->setHighResolution($config['highResolution']);
        }

        $hash = md5(Serialize::serialize($pipe));
        $pipe->setName('auto_' . $hash);

        return $pipe;
    }

    /**
     * @param Model\Asset\Image $asset
     *
     * @return array
     */
    public function getEstimatedDimensions($asset)
    {
        $originalWidth = $asset->getWidth();
        $originalHeight = $asset->getHeight();

        $dimensions = [
            'width' => $originalWidth,
            'height' => $originalHeight,
        ];

        $transformations = $this->getItems();
        if (is_array($transformations) && count($transformations) > 0) {
            if ($originalWidth && $originalHeight) {
                foreach ($transformations as $transformation) {
                    if (!empty($transformation)) {
                        $arg = $transformation['arguments'];

                        $forceResize = false;
                        if (isset($arg['forceResize']) && $arg['forceResize'] === true) {
                            $forceResize = true;
                        }

                        if (in_array($transformation['method'], ['resize', 'cover', 'frame', 'crop'])) {
                            $dimensions['width'] = $arg['width'];
                            $dimensions['height'] = $arg['height'];
                        } elseif ($transformation['method'] == '1x1_pixel') {
                            return [
                                'width' => 1,
                                'height' => 1,
                            ];
                        } elseif ($transformation['method'] == 'scaleByWidth') {
                            if ($arg['width'] <= $dimensions['width'] || $asset->isVectorGraphic() || $forceResize) {
                                $dimensions['height'] = round(($arg['width'] / $dimensions['width']) * $dimensions['height'], 0);
                                $dimensions['width'] = $arg['width'];
                            }
                        } elseif ($transformation['method'] == 'scaleByHeight') {
                            if ($arg['height'] < $dimensions['height'] || $asset->isVectorGraphic() || $forceResize) {
                                $dimensions['width'] = round(($arg['height'] / $dimensions['height']) * $dimensions['width'], 0);
                                $dimensions['height'] = $arg['height'];
                            }
                        } elseif ($transformation['method'] == 'contain') {
                            $x = $dimensions['width'] / $arg['width'];
                            $y = $dimensions['height'] / $arg['height'];

                            if (!$forceResize && $x <= 1 && $y <= 1 && !$asset->isVectorGraphic()) {
                                continue;
                            }

                            if ($x > $y) {
                                $dimensions['height'] = round(($arg['width'] / $dimensions['width']) * $dimensions['height'], 0);
                                $dimensions['width'] = $arg['width'];
                            } else {
                                $dimensions['width'] = round(($arg['height'] / $dimensions['height']) * $dimensions['width'], 0);
                                $dimensions['height'] = $arg['height'];
                            }
                        } elseif ($transformation['method'] == 'cropPercent') {
                            $dimensions['width'] = ceil($dimensions['width'] * ($arg['width'] / 100));
                            $dimensions['height'] = ceil($dimensions['height'] * ($arg['height'] / 100));
                        } elseif (in_array($transformation['method'], ['rotate', 'trim'])) {
                            // unable to calculate dimensions -> return empty
                            return [];
                        }
                    }
                }
            } else {
                // this method is only if we don't have the source dimensions
                // this doesn't necessarily return both with & height
                // and is only a very rough estimate, you should avoid falling back to this functionality
                foreach ($transformations as $transformation) {
                    if (!empty($transformation)) {
                        if (is_array($transformation['arguments']) && in_array($transformation['method'], ['resize', 'scaleByWidth', 'scaleByHeight', 'cover', 'frame'])) {
                            foreach ($transformation['arguments'] as $key => $value) {
                                if ($key == 'width' || $key == 'height') {
                                    $dimensions[$key] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }

        // ensure we return int's, sometimes $arg[...] contain strings
        $dimensions['width'] = (int) $dimensions['width'];
        $dimensions['height'] = (int) $dimensions['height'];

        return $dimensions;
    }

    /**
     * @deprecated
     *
     * @param string $colorspace
     */
    public function setColorspace($colorspace)
    {
        // no functionality, just for compatibility reasons
    }

    /**
     * @deprecated
     */
    public function getColorspace()
    {
        // no functionality, just for compatibility reasons
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $modificationDate
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return bool
     */
    public function isPreserveColor()
    {
        return $this->preserveColor;
    }

    /**
     * @param bool $preserveColor
     */
    public function setPreserveColor($preserveColor)
    {
        $this->preserveColor = $preserveColor;
    }

    /**
     * @return bool
     */
    public function isPreserveMetaData()
    {
        return $this->preserveMetaData;
    }

    /**
     * @param bool $preserveMetaData
     */
    public function setPreserveMetaData($preserveMetaData)
    {
        $this->preserveMetaData = $preserveMetaData;
    }

    /**
     * @return bool
     */
    public function isRasterizeSVG(): bool
    {
        return $this->rasterizeSVG;
    }

    /**
     * @param bool $rasterizeSVG
     */
    public function setRasterizeSVG(bool $rasterizeSVG): void
    {
        $this->rasterizeSVG = $rasterizeSVG;
    }

    /**
     * @return bool
     */
    public function isSvgTargetFormatPossible()
    {
        $supportedTransformations = ['resize', 'scaleByWidth', 'scaleByHeight'];
        foreach ($this->getItems() as $item) {
            if (!in_array($item['method'], $supportedTransformations)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @param string $group
     */
    public function setGroup(string $group): void
    {
        $this->group = $group;
    }

    /**
     * @return bool
     */
    public function getForcePictureTag(): bool
    {
        return $this->forcePictureTag;
    }

    /**
     * @param bool $forcePictureTag
     */
    public function setForcePictureTag(bool $forcePictureTag): void
    {
        $this->forcePictureTag = $forcePictureTag;
    }

    /**
     * @return bool
     */
    public function isDownloadable(): bool
    {
        return $this->downloadable;
    }

    /**
     * @param bool $downloadable
     */
    public function setDownloadable(bool $downloadable): void
    {
        $this->downloadable = $downloadable;
    }

    public function clearTempFiles()
    {
        $this->doClearTempFiles(PIMCORE_TEMPORARY_DIRECTORY . '/image-thumbnails', $this->getName());
    }
}
