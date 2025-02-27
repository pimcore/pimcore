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

namespace Pimcore\Model\Asset\Image\Thumbnail;

use Exception;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Tool\Serialize;

/**
 * @method bool isWriteable()
 * @method string getWriteTarget()
 * @method void delete(bool $forceClearTempFiles = false)
 * @method void save(bool $forceClearTempFiles = false)
 */
final class Config extends Model\AbstractModel
{
    use Model\Asset\Thumbnail\ClearTempFilesTrait;

    /**
     * @internal
     */
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
     * @internal
     *
     */
    protected array $items = [];

    /**
     * @internal
     *
     */
    protected array $medias = [];

    /**
     * @internal
     *
     */
    protected string $name = '';

    /**
     * @internal
     *
     */
    protected string $description = '';

    /**
     * @internal
     *
     */
    protected string $group = '';

    /**
     * @internal
     *
     */
    protected string $format = 'SOURCE';

    /**
     * @internal
     *
     */
    protected int $quality = 85;

    /**
     * @internal
     *
     */
    protected ?float $highResolution = null;

    /**
     * @internal
     *
     */
    protected bool $preserveColor = false;

    /**
     * @internal
     *
     */
    protected bool $preserveMetaData = false;

    /**
     * @internal
     *
     */
    protected bool $rasterizeSVG = false;

    /**
     * @internal
     *
     */
    protected bool $downloadable = false;

    /**
     * @internal
     *
     */
    protected ?int $modificationDate = null;

    /**
     * @internal
     *
     */
    protected ?int $creationDate = null;

    /**
     * @internal
     *
     */
    protected ?string $filenameSuffix = null;

    /**
     * @internal
     *
     */
    protected bool $preserveAnimation = false;

    /**
     *
     *
     * @internal
     */
    public static function getByAutoDetect(array|string|Config $config): ?Config
    {
        $thumbnail = null;

        if (is_string($config)) {
            try {
                $thumbnail = self::getByName($config);
            } catch (Exception $e) {
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
     *
     *
     * @throws Exception
     */
    public static function getByName(string $name): ?Config
    {
        $cacheKey = self::getCacheKey($name);

        if ($name === self::PREVIEW_THUMBNAIL_NAME) {
            return self::getPreviewConfig();
        }

        try {
            $thumbnail = RuntimeCache::get($cacheKey);
            if (!$thumbnail) {
                throw new Exception('Thumbnail in registry is null');
            }

            $thumbnail->setName($name);
        } catch (Exception $e) {
            try {
                $thumbnail = new self();
                /** @var Model\Asset\Image\Thumbnail\Config\Dao $dao */
                $dao = $thumbnail->getDao();
                $dao->getByName($name);
                RuntimeCache::set($cacheKey, $thumbnail);
            } catch (Model\Exception\NotFoundException $e) {
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

    protected static function getCacheKey(string $name): string
    {
        return 'imagethumb_' . crc32($name);
    }

    public static function exists(string $name): bool
    {
        $cacheKey = self::getCacheKey($name);
        if (RuntimeCache::isRegistered($cacheKey)) {
            return true;
        }

        if ($name === self::PREVIEW_THUMBNAIL_NAME) {
            return true;
        }

        return (bool) self::getByName($name);
    }

    /**
     * @internal
     *
     */
    public static function getPreviewConfig(): Config
    {
        $customPreviewImageThumbnail = \Pimcore\Config::getSystemConfiguration('assets')['preview_image_thumbnail'];
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

        $thumbnail->setHighResolution(2);

        return $thumbnail;
    }

    protected function createMediaIfNotExists(string $name): void
    {
        if (!array_key_exists($name, $this->medias)) {
            $this->medias[$name] = [];
        }
    }

    /**
     * @internal
     *
     *
     */
    public function addItem(string $name, array $parameters, ?string $media = null): bool
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
     * @internal
     *
     *
     */
    public function addItemAt(int $position, string $name, array $parameters, ?string $media = null): bool
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

    /**
     * @internal
     */
    public function resetItems(): void
    {
        $this->items = [];
        $this->medias = [];
    }

    public function selectMedia(string $name): bool
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
     * @return $this
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return $this
     */
    public function setItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @return $this
     */
    public function setQuality(int $quality): static
    {
        if ($quality) {
            $this->quality = $quality;
        }

        return $this;
    }

    public function getQuality(): int
    {
        return $this->quality;
    }

    public function setHighResolution(?float $highResolution): void
    {
        $this->highResolution = $highResolution;
    }

    public function getHighResolution(): ?float
    {
        return $this->highResolution;
    }

    public function setMedias(array $medias): void
    {
        $this->medias = $medias;
    }

    public function getMedias(): array
    {
        return $this->medias;
    }

    public function hasMedias(): bool
    {
        return !empty($this->medias);
    }

    public function setFilenameSuffix(string $filenameSuffix): void
    {
        $this->filenameSuffix = $filenameSuffix;
    }

    public function getFilenameSuffix(): ?string
    {
        return $this->filenameSuffix;
    }

    /**
     *
     *
     * @internal
     */
    public static function getByArrayConfig(array $config): Config
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
        $pipe->generateAutoName();

        return $pipe;
    }

    /**
     * This is mainly here for backward compatibility
     *
     *
     *
     * @internal
     */
    public static function getByLegacyConfig(array $config): Config
    {
        $pipe = new self();

        if (isset($config['format'])) {
            $pipe->setFormat($config['format']);
        }

        if (isset($config['quality'])) {
            $pipe->setQuality((int)$config['quality']);
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

        $pipe->generateAutoName();

        return $pipe;
    }

    /**
     *
     *
     * @internal
     */
    public function getEstimatedDimensions(Model\Asset\Image $asset): array
    {
        $originalWidth = $asset->getWidth();
        $originalHeight = $asset->getHeight();

        $dimensions = [
            'width' => $originalWidth,
            'height' => $originalHeight,
        ];

        $transformations = $this->getItems();
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

        // ensure we return int's, sometimes $arg[...] contain strings
        $dimensions['width'] = (int) $dimensions['width'] * ($this->getHighResolution() ?: 1);
        $dimensions['height'] = (int) $dimensions['height'] * ($this->getHighResolution() ?: 1);

        return $dimensions;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setModificationDate(int $modificationDate): void
    {
        $this->modificationDate = $modificationDate;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function setCreationDate(int $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function isPreserveColor(): bool
    {
        return $this->preserveColor;
    }

    public function setPreserveColor(bool $preserveColor): void
    {
        $this->preserveColor = $preserveColor;
    }

    public function isPreserveMetaData(): bool
    {
        return $this->preserveMetaData;
    }

    public function setPreserveMetaData(bool $preserveMetaData): void
    {
        $this->preserveMetaData = $preserveMetaData;
    }

    public function isRasterizeSVG(): bool
    {
        return $this->rasterizeSVG;
    }

    public function setRasterizeSVG(bool $rasterizeSVG): void
    {
        $this->rasterizeSVG = $rasterizeSVG;
    }

    public function isSvgTargetFormatPossible(): bool
    {
        $supportedTransformations = ['resize', 'scaleByWidth', 'scaleByHeight'];
        foreach ($this->getItems() as $item) {
            if (!in_array($item['method'], $supportedTransformations)) {
                return false;
            }
        }

        return true;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function setGroup(string $group): void
    {
        $this->group = $group;
    }

    public function getPreserveAnimation(): bool
    {
        return $this->preserveAnimation;
    }

    public function setPreserveAnimation(bool $preserveAnimation): void
    {
        $this->preserveAnimation = $preserveAnimation;
    }

    public function isDownloadable(): bool
    {
        return $this->downloadable;
    }

    public function setDownloadable(bool $downloadable): void
    {
        $this->downloadable = $downloadable;
    }

    public function __clone(): void
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }

        //rebuild asset path for overlays
        foreach ($this->items as &$item) {
            if (in_array($item['method'], ['addOverlay', 'addOverlayFit'])) {
                if (isset($item['arguments']['id'])) {
                    $img = Model\Asset\Image::getById($item['arguments']['id']);
                    if ($img) {
                        $item['arguments']['path'] = $img->getFullPath();
                    }
                }
            }
        }
    }

    /**
     * @internal
     *
     */
    public static function getAutoFormats(): array
    {
        return \Pimcore\Config::getSystemConfiguration('assets')['image']['thumbnails']['auto_formats'];
    }

    /**
     * @internal
     *
     * @return Config[]
     */
    public function getAutoFormatThumbnailConfigs(): array
    {
        $autoFormatThumbnails = [];

        foreach ($this->getAutoFormats() as $autoFormat => $autoFormatConfig) {
            if ($autoFormatConfig['enabled'] && Model\Asset\Image\Thumbnail::supportsFormat($autoFormat)) {
                $autoFormatThumbnail = clone $this;
                $autoFormatThumbnail->setFormat($autoFormat);
                if (!empty($autoFormatConfig['quality'])) {
                    $autoFormatThumbnail->setQuality($autoFormatConfig['quality']);
                }

                $autoFormatThumbnails[$autoFormat] = $autoFormatThumbnail;
            }
        }

        return $autoFormatThumbnails;
    }

    /**
     * @internal
     */
    public function generateAutoName(): void
    {
        $serialized = Serialize::serialize($this->getItems());

        $this->setName($this->getName() . '_auto_' . md5($serialized));
    }

    /**
     * @internal
     */
    public function getHash(array $params = []): string
    {
        return hash('xxh32', serialize([
            $this->getPreserveAnimation(),
            $this->getQuality(),
            $this->isPreserveColor(),
            $this->isPreserveMetaData(),
            $this->getItems(),
            $params,
        ]));
    }
}
