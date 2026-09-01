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

namespace Pimcore\Model\Asset\Image\Thumbnail;

use Exception;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Image\Adapter\Dimension as DimensionAdapter;
use Pimcore\Image\Adapter\GD;
use Pimcore\Image\Adapter\Imagick;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Tool\Serialize;
use Throwable;

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
     * Operations which affect the legacy logical HTML dimensions even when Processor returns the original source before invoking an image adapter.
     *
     * @var list<string>
     */
    private const PASS_THROUGH_LOGICAL_TRANSFORMATIONS = [
        'resize',
        'scaleByWidth',
        'scaleByHeight',
        'contain',
        'cover',
        'frame',
        'crop',
        'cropPercent',
    ];

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
    protected bool $forceProcessICCProfiles = false;

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
    protected bool $useCropBox = false;

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
     *
     * @return array{width: int, height: int}|array{}
     */
    public function getEstimatedDimensions(Model\Asset\Image $asset): array
    {
        $sourceWidth = $asset->getCustomSetting('imageWidth');
        $sourceHeight = $asset->getCustomSetting('imageHeight');

        return $this->getEstimatedDimensionsForSource(
            $asset,
            is_numeric($sourceWidth) ? (int) $sourceWidth : 0,
            is_numeric($sourceHeight) ? (int) $sourceHeight : 0
        );
    }

    /**
     * Estimates logical thumbnail dimensions from already-known source dimensions without reading or mutating the source asset.
     *
     * @internal
     *
     * @return array{width: int, height: int}|array{}
     */
    public function getEstimatedDimensionsForSource(
        Model\Asset\Image $asset,
        int $sourceWidth,
        int $sourceHeight
    ): array {
        $transformations = $this->getItems();

        // Processor returns this data URI before loading the source or applying any other transformations, so its dimensions are always known.
        foreach ($transformations as $transformation) {
            if (($transformation['method'] ?? null) === '1x1_pixel') {
                return [
                    'width' => 1,
                    'height' => 1,
                ];
            }
        }

        // Processor copies the source bytes for ORIGINAL instead of saving the transformed adapter.
        // Inspect the generated file to retain the existing physical-file dimension semantics.
        if (strtolower($this->getFormat()) === 'original') {
            return [];
        }

        $usesSourceAssetOutput = $this->usesOriginalSvgOutput($asset)
            || Processor::usesOriginalAssetOutput($asset, $this);

        // Pass-through output returns the source before Processor creates an adapter.
        // Generated thumbnails may only be estimated for the exact core implementations whose semantics DimensionAdapter models.
        if (!$usesSourceAssetOutput && !$this->supportsConfiguredAdapterForDimensionEstimation()) {
            return [];
        }

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            return [];
        }

        $transformationsForEstimation = [];

        foreach ($transformations as $transformation) {
            if (empty($transformation) || isset($transformation['isApplied'])) {
                continue;
            }

            $method = $transformation['method'] ?? null;
            if ($method === 'tifforiginal') {
                if (!$usesSourceAssetOutput) {
                    return [];
                }

                // Processor routing marker, not an image operation.
                continue;
            }

            if (!is_string($method)) {
                if ($usesSourceAssetOutput) {
                    continue;
                }

                return [];
            }

            if ($usesSourceAssetOutput) {
                if (in_array($method, ['rotate', 'trim'], true)) {
                    return [];
                }

                if (!in_array($method, self::PASS_THROUGH_LOGICAL_TRANSFORMATIONS, true)) {
                    // Processor returns the source before invoking visual-only or project-specific operations.
                    // The legacy logical estimator ignored them, including their runtime-only argument validity.
                    continue;
                }
            }

            if (!Processor::hasTransformationArgumentMapping($method)
                || !DimensionAdapter::supportsReliableTransformation($method)) {
                // Unknown/custom operations cannot safely suppress file inspection.
                return [];
            }

            $arguments = $transformation['arguments'] ?? null;
            if (is_array($arguments)) {
                $arguments = $this->normalizeReliableTransformationArguments(
                    $method,
                    $arguments,
                    $usesSourceAssetOutput
                );
                if ($arguments === null) {
                    return [];
                }

                $transformation['arguments'] = $arguments;
            }

            $transformationsForEstimation[] = $transformation;
        }

        // Pass-through routing is based on the filename and returns before an adapter loads the bytes.
        // Use that same filename-based classification for logical HTML dimensions.
        // Generated output retains the conservative extension/MIME conflict handling used to protect physical estimates.
        $vectorGraphicState = $usesSourceAssetOutput
            ? $asset->isVectorGraphic()
            : $asset->getVectorGraphicStateForDimensionEstimation();
        if (!$usesSourceAssetOutput && $vectorGraphicState !== false) {
            // Generated output is reliable only when the source is positively known to be raster.
            // Vector rasterization depends on the installed ImageMagick/delegate pipeline, and unknown/conflicting evidence can still describe vector bytes.
            // Inspect the generated file instead.
            return [];
        }

        $dimensionAdapter = new DimensionAdapter(
            $sourceWidth,
            $sourceHeight,
            $vectorGraphicState === true,
            $usesSourceAssetOutput
        );

        try {
            // Reuse Processor's real argument mapping, focal-point handling and high-resolution normalization, and Adapter's actual dimension math.
            Processor::applyTransformations(
                $dimensionAdapter,
                $asset,
                $this,
                $transformationsForEstimation,
                !$usesSourceAssetOutput,
                $sourceWidth,
                $sourceHeight
            );
        } catch (Throwable $exception) {
            // Malformed or unsupported runtime arguments require file inspection.
            Logger::debug('Thumbnail dimension estimation declined: ' . $exception->getMessage());

            return [];
        }

        if (!$dimensionAdapter->isReliable()
            || $dimensionAdapter->getWidth() <= 0
            || $dimensionAdapter->getHeight() <= 0) {
            return [];
        }

        $estimatedWidth = $dimensionAdapter->getWidth();
        $estimatedHeight = $dimensionAdapter->getHeight();

        $highResolution = $this->getHighResolution();
        if ($usesSourceAssetOutput && $highResolution > 1) {
            // Pass-through output uses the source bytes, but dimensions retain the legacy logical contract.
            // The trait truncates real dimensions before deriving the displayed size, including fractional high-resolution products.
            $estimatedWidth = (int) ($estimatedWidth * $highResolution);
            $estimatedHeight = (int) ($estimatedHeight * $highResolution);
        }

        return [
            'width' => $estimatedWidth,
            'height' => $estimatedHeight,
        ];
    }

    private function supportsConfiguredAdapterForDimensionEstimation(): bool
    {
        try {
            $adapter = Model\Asset\Image::getImageTransformInstance();
        } catch (Throwable) {
            return false;
        }

        return in_array($adapter::class, [GD::class, Imagick::class], true);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function normalizeReliableTransformationArguments(
        string $method,
        array $arguments,
        bool $passThroughLogical = false
    ): ?array {
        $requiredStringArgument = match ($method) {
            'setBackgroundColor' => 'color',
            'setBackgroundImage', 'addOverlay', 'addOverlayFit', 'applyMask' => 'path',
            'mirror' => 'mode',
            default => null,
        };
        if ($requiredStringArgument !== null
            && (!array_key_exists($requiredStringArgument, $arguments)
                || !is_string($arguments[$requiredStringArgument]))) {
            return null;
        }

        if ($method === 'roundCorners'
            && (!array_key_exists('width', $arguments) || !array_key_exists('height', $arguments))) {
            return null;
        }

        foreach (['width', 'height', 'x', 'y'] as $pixelArgument) {
            if (!array_key_exists($pixelArgument, $arguments)) {
                continue;
            }

            $normalizedValue = $this->normalizeIntegerArgument($arguments[$pixelArgument]);
            if ($normalizedValue === null) {
                return null;
            }

            // Normalize only the copied estimation plan.
            // Config items, hashes and the arguments used by real thumbnail generation remain unchanged.
            $arguments[$pixelArgument] = $normalizedValue;
        }

        if (array_key_exists('forceResize', $arguments) && !is_bool($arguments['forceResize'])) {
            return null;
        }

        if (!$passThroughLogical
            && $method === 'cover'
            && !$this->hasValidCoverPositioning($arguments['positioning'] ?? 'center')) {
            return null;
        }

        return $arguments;
    }

    private function normalizeIntegerArgument(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            // The positive bound is exclusive because PHP_INT_MAX itself is not exactly representable as a float on 64-bit runtimes.
            $exclusiveUpperBound = -(float) PHP_INT_MIN;
            if (!is_finite($value)
                || floor($value) !== $value
                || $value < (float) PHP_INT_MIN
                || $value >= $exclusiveUpperBound) {
                return null;
            }

            return (int) $value;
        }

        if (!is_string($value) || preg_match('/^[+-]?\d+$/D', $value) !== 1) {
            return null;
        }

        $normalizedValue = filter_var($value, FILTER_VALIDATE_INT);

        return $normalizedValue === false ? null : $normalizedValue;
    }

    private function hasValidCoverPositioning(mixed $positioning): bool
    {
        if (!$positioning) {
            return true;
        }

        if (is_string($positioning)) {
            return in_array($positioning, [
                'center',
                'topleft',
                'topright',
                'bottomleft',
                'bottomright',
                'centerleft',
                'centerright',
                'topcenter',
                'bottomcenter',
            ], true);
        }

        return is_array($positioning)
            && isset($positioning['x'], $positioning['y'])
            && is_numeric($positioning['x'])
            && is_numeric($positioning['y']);
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

    public function isForceProcessICCProfiles(): bool
    {
        return $this->forceProcessICCProfiles;
    }

    public function setForceProcessICCProfiles(bool $forceProcessICCProfiles): void
    {
        $this->forceProcessICCProfiles = $forceProcessICCProfiles;
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

    /**
     * Whether the image thumbnail route returns the original SVG source instead of invoking Processor.
     *
     * @internal
     */
    public function usesOriginalSvgOutput(Model\Asset\Image $asset): bool
    {
        return preg_match('@\.svgz?$@', $asset->getFilename()) === 1
            && !$this->isRasterizeSVG()
            && $this->isSvgTargetFormatPossible();
    }

    public function setRasterizeSVG(bool $rasterizeSVG): void
    {
        $this->rasterizeSVG = $rasterizeSVG;
    }

    public function isUseCropBox(): bool
    {
        return $this->useCropBox;
    }

    public function setUseCropBox(bool $cropbox): void
    {
        $this->useCropBox = $cropbox;
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
                    $img = Model\Asset\Image::getById((int)$item['arguments']['id']);
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
        return $this->buildHash($params, $this->isUseCropBox());
    }

    /**
     * Hash as produced by Pimcore 12.3.0 - 12.3.11, where the crop box flag was
     * serialized unconditionally (#18317). getHash() no longer includes the flag
     * when the crop box is disabled, so thumbnails generated by those versions
     * live under this hash. It is used as a lookup fallback so such thumbnails
     * are reused instead of being regenerated.
     *
     * @internal
     */
    public function getCropBoxCompatHash(array $params = []): string
    {
        return $this->buildHash($params, true);
    }

    private function buildHash(array $params, bool $includeCropBox): string
    {
        $elements = [
            $this->getPreserveAnimation(),
            $this->getQuality(),
            $this->isPreserveColor(),
            $this->isPreserveMetaData(),
        ];

        // Only include the crop box flag in the hash when it is actually enabled.
        // Adding it unconditionally would shift the serialized array indices and
        // change the resulting hash for every thumbnail config - even those that
        // do not use the crop box (the default) - forcing a full, unnecessary
        // regeneration of all thumbnails on upgrade. Appending it only when true
        // keeps the hash backward compatible for the common case while still
        // distinguishing configs that opt into the crop box.
        if ($includeCropBox) {
            $elements[] = $this->isUseCropBox();
        }

        $elements[] = $this->getItems();
        $elements[] = $params;

        return hash('xxh32', serialize($elements));
    }

    /**
     * @internal
     *
     */
    public static function getMaxDpiFactor(): int
    {
        return \Pimcore\Config::getSystemConfiguration('assets')['image']['thumbnails']['max_srcset_dpi_factor'];
    }
}
