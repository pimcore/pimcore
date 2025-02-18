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

namespace Pimcore\Model\Asset\Thumbnail;

use Exception;
use Pimcore\Config as PimcoreConfig;
use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Tool;
use Pimcore\Tool\Storage;
use Symfony\Component\Mime\MimeTypes;

trait ImageThumbnailTrait
{
    use TemporaryFileHelperTrait;

    /**
     * @internal
     *
     */
    protected ?Asset $asset = null;

    /**
     * @internal
     *
     */
    protected ?Config $config = null;

    /**
     * @internal
     *
     */
    protected array $pathReference = [];

    /**
     * @internal
     *
     */
    protected ?int $width = null;

    /**
     * @internal
     *
     */
    protected ?int $height = null;

    /**
     * @internal
     *
     */
    protected ?int $realWidth = null;

    /**
     * @internal
     *
     */
    protected ?int $realHeight = null;

    /**
     * @internal
     *
     */
    protected ?string $mimetype = null;

    /**
     * @internal
     *
     */
    protected bool $deferred = true;

    private static array $supportedFormats = [];

    /**
     * @return null|resource
     */
    public function getStream()
    {
        $pathReference = $this->getPathReference(false);
        if ($pathReference['type'] === 'asset') {
            return $this->asset->getStream();
        } elseif (isset($pathReference['storagePath'])) {
            return Tool\Storage::get('thumbnail')->readStream($pathReference['storagePath']);
        }

        return null;
    }

    public function getPathReference(bool $deferredAllowed = false): array
    {
        if (!$deferredAllowed && (($this->pathReference['type'] ?? '') === 'deferred')) {
            $this->pathReference = [];
        }

        if (empty($this->pathReference)) {
            $this->generate($deferredAllowed);
        }

        return $this->pathReference;
    }

    /**
     * @internal
     */
    public function reset(): void
    {
        $this->pathReference = [];
        $this->width = null;
        $this->height = null;
        $this->realHeight = null;
        $this->realWidth = null;
    }

    public function getWidth(): ?int
    {
        if (!$this->width) {
            $this->getDimensions();
        }

        return $this->width;
    }

    public function getHeight(): ?int
    {
        if (!$this->height) {
            $this->getDimensions();
        }

        return $this->height;
    }

    public function getRealWidth(): ?int
    {
        if (!$this->realWidth) {
            $this->getDimensions();
        }

        return $this->realWidth;
    }

    public function getRealHeight(): ?int
    {
        if (!$this->realHeight) {
            $this->getDimensions();
        }

        return $this->realHeight;
    }

    /**
     * @internal
     *
     * @return array{width?: int, height?: int}
     */
    public function readDimensionsFromFile(): array
    {
        $dimensions = [];
        $pathReference = $this->getPathReference();
        if (in_array($pathReference['type'], ['thumbnail', 'asset'])) {
            try {
                $localFile = $this->getLocalFile();
                if (null !== $localFile && isset($pathReference['storagePath']) && $config = $this->getConfig()) {
                    $asset = $this->getAsset();
                    $filename = basename($pathReference['storagePath']);
                    $asset->addThumbnailFileToCache(
                        $localFile,
                        $filename,
                        $config
                    );
                    $thumbnail = $asset->getDao()->getCachedThumbnail($config->getName(), $filename);
                    if (isset($thumbnail['width'], $thumbnail['height'])) {
                        $dimensions['width'] = $thumbnail['width'];
                        $dimensions['height'] = $thumbnail['height'];
                    }
                }
            } catch (Exception $e) {
                // noting to do
            }
        }

        return $dimensions;
    }

    /**
     * @return array{width: ?int, height: ?int}
     */
    public function getDimensions(): array
    {
        if (!$this->width || !$this->height) {
            $config = $this->getConfig();
            $asset = $this->getAsset();
            $dimensions = [];

            if ($config) {
                $thumbnail = $asset->getDao()->getCachedThumbnail($config->getName(), $this->getFilename());
                if (isset($thumbnail['width'], $thumbnail['height'])) {
                    $dimensions['width'] = $thumbnail['width'];
                    $dimensions['height'] = $thumbnail['height'];
                }
            }

            if (empty($dimensions) && $this->exists()) {
                $dimensions = $this->readDimensionsFromFile();
            }

            // try to calculate the final dimensions based on the thumbnail configuration
            if (empty($dimensions) && $config && $asset instanceof Image) {
                $dimensions = $config->getEstimatedDimensions($asset);
            }

            if (empty($dimensions)) {
                // unable to calculate dimensions -> use fallback
                // generate the thumbnail and get dimensions from the thumbnail file
                $dimensions = $this->readDimensionsFromFile();
            }

            // realWidth / realHeight is only relevant if using high-res option (retina, ...)
            $width = $dimensions['width'] ?? null;
            $this->width = $this->realWidth = ($width !== null ? (int) $width : null);
            $height = $dimensions['height'] ?? null;
            $this->height = $this->realHeight = ($height !== null ? (int) $height : null);
            if ($config && $config->getHighResolution() > 1) {
                if ($this->width) {
                    $this->width = (int)floor($this->realWidth / $config->getHighResolution());
                }
                if ($this->height) {
                    $this->height = (int)floor($this->realHeight / $config->getHighResolution());
                }
            }
        }

        return [
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function getConfig(): ?Config
    {
        return $this->config;
    }

    public function getMimeType(): string
    {
        if (!$this->mimetype) {
            $pathReference = $this->getPathReference(true);
            if ($pathReference['type'] === 'data-uri') {
                $this->mimetype = substr($pathReference['src'], 5, strpos($pathReference['src'], ';') - 5);
            } else {
                $fileExt = $this->getFileExtension();
                $mimeTypes = MimeTypes::getDefault()->getMimeTypes($fileExt);

                if (!empty($mimeTypes)) {
                    $this->mimetype = $mimeTypes[0];
                } else {
                    // unknown
                    $this->mimetype = 'application/octet-stream';
                }
            }
        }

        return $this->mimetype;
    }

    public function getFileExtension(): string
    {
        return pathinfo($this->getPath(), PATHINFO_EXTENSION);
    }

    protected function convertToWebPath(array $pathReference, bool $frontend): ?string
    {
        $type = $pathReference['type'] ?? null;
        $path = $pathReference['src'] ?? null;

        if ($frontend) {
            if ($type === 'data-uri') {
                return $path;
            } elseif ($type === 'deferred') {
                $prefix = \Pimcore\Config::getSystemConfiguration('assets')['frontend_prefixes']['thumbnail_deferred'];
                $path = $prefix . urlencode_ignore_slash($path);
            } elseif ($type === 'thumbnail') {
                $prefix = \Pimcore\Config::getSystemConfiguration('assets')['frontend_prefixes']['thumbnail'];
                $path = $prefix . urlencode_ignore_slash($path);
            } elseif ($type === 'asset') {
                $prefix = \Pimcore\Config::getSystemConfiguration('assets')['frontend_prefixes']['source'];
                $path = $prefix . urlencode_ignore_slash($path);
            } else {
                $path = urlencode_ignore_slash($path);
            }
        }

        return $path;
    }

    public function getFrontendPath(): string
    {
        $path = $this->getPath(['deferredAllowed' => true, 'frontend' => true]);
        if (!preg_match('@^(https?|data):@', $path)) {
            $path = \Pimcore\Tool::getHostUrl() . $path;
        }

        return $path;
    }

    /**
     * @internal
     *
     * @throws Exception
     */
    public function getLocalFile(): ?string
    {
        $stream = $this->getStream();

        if (null === $stream) {
            return null;
        }

        $localFile = self::getLocalFileFromStream($stream);
        @fclose($stream);

        return $localFile;
    }

    public function exists(): bool
    {
        $pathReference = $this->getPathReference(true);
        $type = $pathReference['type'] ?? '';
        if (
            $type === 'asset' ||
            $type === 'data-uri' ||
            $type === 'thumbnail'
        ) {
            return true;
        } elseif ($type === 'deferred') {
            return false;
        } elseif (isset($pathReference['storagePath'])) {
            // this is probably redundant, but as it doesn't hurt we can keep it
            return $this->existsOnStorage($pathReference);
        }

        return false;
    }

    /**
     * @internal
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function existsOnStorage(?array $pathReference = []): bool
    {
        $pathReference ??= $this->getPathReference(true);
        if (isset($pathReference['storagePath'])) {
            // this is probably redundant, but as it doesn't hurt we can keep it
            return Storage::get('thumbnail')->fileExists($pathReference['storagePath']);
        }

        return false;
    }

    public static function supportsFormat(string $format): bool
    {
        if (!isset(self::$supportedFormats[$format])) {
            self::$supportedFormats[$format] = \Pimcore\Image::getInstance()->supportsFormat($format);
        }

        return self::$supportedFormats[$format];
    }

    public function getFileSize(): ?int
    {
        $thumbnail = $this->getAsset()->getDao()->getCachedThumbnail($this->getConfig()->getName(), $this->getFilename());
        if ($thumbnail && $thumbnail['filesize']) {
            return $thumbnail['filesize'];
        }

        $pathReference = $this->getPathReference(false);
        if ($pathReference['type'] === 'asset') {
            return $this->asset->getFileSize();
        } elseif (isset($pathReference['storagePath'])) {
            return Tool\Storage::get('thumbnail')->fileSize($pathReference['storagePath']);
        }

        return null;
    }

    /**
     * @internal
     */
    public function getFilename(): string
    {
        $pathReference = $this->getPathReference(true);

        return basename($pathReference['src']);
    }

    /**
     * Returns path for thumbnail image in a given file format
     *
     *
     */
    public function getAsFormat(string $format): static
    {
        $thumb = clone $this;

        $config = $thumb->getConfig() ? clone $thumb->getConfig() : new Config();
        $config->setFormat($format);

        $thumb->config = $config;
        $thumb->reset();

        return $thumb;
    }

    private function checkAllowedFormats(string $format, ?Asset $asset = null): bool
    {
        $format = strtolower($format);
        if ($asset) {
            if (
                $format === 'original' ||
                $format === 'source'
            ) {
                return true;
            }

            $original = strtolower(pathinfo($asset->getRealFullPath(), PATHINFO_EXTENSION));

            if ($format === $original) {
                return true;
            }
        }

        $assetConfig = PimcoreConfig::getSystemConfiguration('assets');

        return in_array(
            $format,
            $assetConfig['thumbnails']['allowed_formats'],
            true
        );
    }

    private function checkMaxScalingFactor(?float $scalingFactor = null): bool
    {
        if ($scalingFactor === null) {
            return true;
        }

        $assetConfig = PimcoreConfig::getSystemConfiguration('assets');

        return $scalingFactor <= $assetConfig['thumbnails']['max_scaling_factor'];
    }
}
