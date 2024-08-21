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
use Imagick;
use Pimcore;
use Pimcore\Config;
use Pimcore\Event\FrontendEvents;
use Pimcore\File;
use Pimcore\Model;
use Pimcore\Tool;
use Pimcore\Tool\Storage;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Image extends Model\Asset
{
    use Model\Asset\MetaData\EmbeddedMetaDataTrait;

    protected string $type = 'image';

    private bool $clearThumbnailsOnSave = false;

    protected function update(array $params = []): void
    {
        if ($this->getDataChanged()) {
            foreach (['imageWidth', 'imageHeight', 'imageDimensionsCalculated'] as $key) {
                $this->removeCustomSetting($key);
            }
        }

        if ($params['isUpdate']) {
            $this->clearThumbnails($this->clearThumbnailsOnSave);
            $this->clearThumbnailsOnSave = false; // reset to default
        }

        parent::update($params);
    }

    private function isLowQualityPreviewEnabled(): bool
    {
        return Config::getSystemConfiguration('assets')['image']['low_quality_image_preview']['enabled'];
    }

    /**
     *
     *
     * @throws Exception
     *
     * @internal
     */
    public function generateLowQualityPreview(): false|string
    {
        if (!$this->isLowQualityPreviewEnabled()) {
            return false;
        }

        // fallback
        if (class_exists('Imagick')) {
            // Imagick fallback
            $path = $this->getThumbnail(Image\Thumbnail\Config::getPreviewConfig())->getLocalFile();

            if (null === $path) {
                return false;
            }

            $imagick = new Imagick($path);
            $imagick->setImageFormat('jpg');
            $imagick->setOption('jpeg:extent', '1kb');
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();

            // we can't use getImageBlob() here, because of a bug in combination with jpeg:extent
            // http://www.imagemagick.org/discourse-server/viewtopic.php?f=3&t=24366
            $tmpFile = File::getLocalTempFilePath('jpg');
            $imagick->writeImage($tmpFile);
            $imageBase64 = base64_encode(file_get_contents($tmpFile));
            $imagick->destroy();

            $svg = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<svg version="1.1"  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="$width" height="$height" viewBox="0 0 $width $height" preserveAspectRatio="xMidYMid slice">
	<filter id="blur" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
    <feGaussianBlur stdDeviation="20 20" edgeMode="duplicate" />
    <feComponentTransfer>
      <feFuncA type="discrete" tableValues="1 1" />
    </feComponentTransfer>
  </filter>
    <image filter="url(#blur)" x="0" y="0" height="100%" width="100%" xlink:href="data:image/jpg;base64,$imageBase64" />
</svg>
EOT;
            $storagePath = $this->getLowQualityPreviewStoragePath();
            Storage::get('thumbnail')->write($storagePath, $svg);

            return $storagePath;
        }

        return false;
    }

    public function getLowQualityPreviewPath(): string
    {
        $storagePath = $this->getLowQualityPreviewStoragePath();
        $path = $storagePath;

        if (Tool::isFrontend()) {
            $path = urlencode_ignore_slash($storagePath);
            $prefix = Config::getSystemConfiguration('assets')['frontend_prefixes']['thumbnail'];
            $path = $prefix . $path;
        }

        $event = new GenericEvent($this, [
            'storagePath' => $storagePath,
            'frontendPath' => $path,
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::ASSET_IMAGE_THUMBNAIL);
        $path = $event->getArgument('frontendPath');

        return $path;
    }

    private function getLowQualityPreviewStoragePath(): string
    {
        return sprintf(
            '%s/%s/image-thumb__%s__-low-quality-preview.svg',
            rtrim($this->getRealPath(), '/'),
            $this->getId(),
            $this->getId()
        );
    }

    public function getLowQualityPreviewDataUri(): ?string
    {
        if (!$this->isLowQualityPreviewEnabled()) {
            return null;
        }

        try {
            $dataUri = 'data:image/svg+xml;base64,' . base64_encode(Storage::get('thumbnail')->read($this->getLowQualityPreviewStoragePath()));
        } catch (Exception $e) {
            $dataUri = null;
        }

        return $dataUri;
    }

    /**
     * Legacy method for backwards compatibility. Use getThumbnail($config)->getConfig() instead.
     *
     * @internal
     *
     * @deprecated Will be removed in Pimcore 12
     */
    public function getThumbnailConfig(array|string|Image\Thumbnail\Config|null $config): ?Image\Thumbnail\Config
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '11.1',
            'Using "%s" is deprecated and will be removed in Pimcore 12, use "%s" instead.',
            __METHOD__,
            'getThumbnail($config)->getConfig()'
        );

        $thumbnail = $this->getThumbnail($config);

        return $thumbnail->getConfig();
    }

    /**
     * Returns a path to a given thumbnail or a thumbnail configuration.
     */
    public function getThumbnail(array|string|Image\Thumbnail\Config|null $config = null, bool $deferred = true): Image\ThumbnailInterface
    {
        return new Image\Thumbnail($this, $config, $deferred);
    }

    /**
     * @internal
     *
     * @throws Exception
     *
     */
    public static function getImageTransformInstance(): ?\Pimcore\Image\Adapter
    {
        try {
            $image = \Pimcore\Image::getInstance();
        } catch (Exception $e) {
            $image = null;
        }

        if (!$image instanceof \Pimcore\Image\Adapter) {
            throw new Exception("Couldn't get instance of image tranform processor.");
        }

        return $image;
    }

    public function getFormat(): string
    {
        if ($this->getWidth() > $this->getHeight()) {
            return 'landscape';
        } elseif ($this->getWidth() == $this->getHeight()) {
            return 'square';
        } elseif ($this->getHeight() > $this->getWidth()) {
            return 'portrait';
        }

        return 'unknown';
    }

    /**
     * @throws Exception
     */
    public function getDimensions(string $path = null, bool $force = false): ?array
    {
        if (!$force) {
            $width = $this->getCustomSetting('imageWidth');
            $height = $this->getCustomSetting('imageHeight');

            if ($width && $height) {
                return [
                    'width' => $width,
                    'height' => $height,
                ];
            }
        }

        if (!$path) {
            $path = $this->getLocalFile();
        }

        if (!$path) {
            return null;
        }

        $dimensions = null;

        //try to get the dimensions with getimagesize because it is much faster than e.g. the Imagick-Adapter
        if (is_readable($path)) {
            $imageSize = @getimagesize($path);
            if ($imageSize && $imageSize[0] && $imageSize[1]) {
                $dimensions = [
                    'width' => $imageSize[0],
                    'height' => $imageSize[1],
                ];
            }
        }

        if (!$dimensions) {
            $image = self::getImageTransformInstance();

            $status = $image->load($path, ['preserveColor' => true, 'asset' => $this]);
            if ($status === false) {
                return null;
            }

            $dimensions = [
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
            ];
        }

        // EXIF orientation
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            if (is_array($exif)) {
                if (array_key_exists('Orientation', $exif)) {
                    $orientation = (int)$exif['Orientation'];
                    if (in_array($orientation, [5, 6, 7, 8])) {
                        // flip height & width
                        $dimensions = [
                            'width' => $dimensions['height'],
                            'height' => $dimensions['width'],
                        ];
                    }
                }
            }
        }

        if (($width = $dimensions['width']) && ($height = $dimensions['height'])) {
            // persist dimensions to database
            $this->setCustomSetting('imageDimensionsCalculated', true);
            $this->setCustomSetting('imageWidth', $width);
            $this->setCustomSetting('imageHeight', $height);
            $this->getDao()->updateCustomSettings();
            $this->clearDependentCache();
        }

        return $dimensions;
    }

    public function getWidth(): int
    {
        $dimensions = $this->getDimensions();

        if ($dimensions) {
            return $dimensions['width'];
        }

        return 0;
    }

    public function getHeight(): int
    {
        $dimensions = $this->getDimensions();

        if ($dimensions) {
            return $dimensions['height'];
        }

        return 0;
    }

    public function setCustomSetting(string $key, mixed $value): static
    {
        if (in_array($key, ['focalPointX', 'focalPointY'])) {
            // if the focal point changes we need to clean all thumbnails on save
            if ($this->getCustomSetting($key) != $value) {
                $this->clearThumbnailsOnSave = true;
            }
        }

        return parent::setCustomSetting($key, $value);
    }

    public function isVectorGraphic(): bool
    {
        // we use a simple file-extension check, for performance reasons
        if (preg_match("@\.(svgz?|eps|pdf|ps|ai|indd)$@", $this->getFilename())) {
            return true;
        }

        return false;
    }

    /**
     * Checks if this file represents an animated image (png or gif)
     *
     */
    public function isAnimated(): bool
    {
        $isAnimated = false;

        switch ($this->getMimeType()) {
            case 'image/gif':
                $isAnimated = $this->isAnimatedGif();

                break;
            case 'image/png':
                $isAnimated = $this->isAnimatedPng();

                break;
            default:
                break;
        }

        return $isAnimated;
    }

    /**
     * Checks if this object represents an animated gif file
     */
    private function isAnimatedGif(): bool
    {
        $isAnimated = false;

        if ($this->getMimeType() == 'image/gif') {
            $fileContent = $this->getData();

            /**
             * An animated gif contains multiple "frames", with each frame having a header made up of:
             *  - a static 4-byte sequence (\x00\x21\xF9\x04)
             *  - 4 variable bytes
             *  - a static 2-byte sequence (\x00\x2C) (some variants may use \x00\x21 ?)
             *
             * @see http://it.php.net/manual/en/function.imagecreatefromgif.php#104473
             */
            $numberOfFrames = preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $fileContent, $matches);

            $isAnimated = $numberOfFrames > 1;
        }

        return $isAnimated;
    }

    /**
     * Checks if this object represents an animated png file
     */
    private function isAnimatedPng(): bool
    {
        $isAnimated = false;

        if ($this->getMimeType() == 'image/png') {
            $fileContent = $this->getData();

            /**
             * Valid APNGs have an "acTL" chunk somewhere before their first "IDAT" chunk.
             *
             * @see http://foone.org/apng/
             */
            $posIDAT = strpos($fileContent, 'IDAT');
            if ($posIDAT !== false) {
                $isAnimated = str_contains(substr($fileContent, 0, $posIDAT), 'acTL');
            }
        }

        return $isAnimated;
    }
}
