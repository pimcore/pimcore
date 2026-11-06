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

    /**
     * How much of each end of an EPS file's PostScript is searched for its size comments.
     */
    private const EPS_COMMENTS_BYTES = 65536;

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
    public function getDimensions(?string $path = null, bool $force = false): ?array
    {
        if (!$force) {
            $width = $this->getCustomSetting('imageWidth');
            $height = $this->getCustomSetting('imageHeight');

            if ($width && $height && is_numeric($width) && is_numeric($height)) {
                return [
                    'width' => (int) $width,
                    'height' => (int) $height,
                ];
            }
        }

        if (!$path) {
            $path = $this->getLocalFile();
        }

        if (!$path) {
            return null;
        }

        $dimensions = $this->getDimensionsFromFile($path);
        if ($dimensions === null) {
            return null;
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

    /**
     * Determines image dimensions without updating the asset or its database record.
     *
     * @internal
     *
     * @return array{width: int, height: int}|null
     */
    public function getDimensionsFromFile(string $path): ?array
    {
        $dimensions = null;
        $dimensionPath = $path;

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

        // EPS needs a PostScript interpreter to render, which ImageMagick's default policy on many
        // distributions refuses. Its DSC header comments carry the size, so read that instead.
        if (!$dimensions) {
            $dimensions = self::getEpsDimensionsFromHeader($path);
        }

        if (!$dimensions) {
            // Flysystem commonly materializes remote streams as extensionless temporary files.
            // Some ImageMagick delegates (notably SVG) require the source suffix to select a decoder.
            // Reuse the already-downloaded bytes in a local suffixed file; this performs no additional storage read and does not mutate the asset.
            $sourceExtension = pathinfo($this->getFilename(), PATHINFO_EXTENSION);
            if (pathinfo($dimensionPath, PATHINFO_EXTENSION) === '' && $sourceExtension !== '') {
                $suffixedPath = File::getLocalTempFilePath($sourceExtension);
                if (@copy($dimensionPath, $suffixedPath)) {
                    $dimensionPath = $suffixedPath;
                }
            }

            $image = self::getImageTransformInstance();

            $status = $image->load($dimensionPath, ['preserveColor' => true, 'asset' => $this]);
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
            $exif = @exif_read_data($dimensionPath);
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

        return $dimensions;
    }

    /**
     * The size an EPS file declares in its DSC header comments: Photoshop's %ImageData, which the Imagick
     * adapter also prefers, else %%HiResBoundingBox or %%BoundingBox in points - what ImageMagick reports at
     * its default 72 dpi. Handles DOS EPS binary headers and boxes deferred to the trailer with "(atend)".
     * Reads at most EPS_COMMENTS_BYTES from each end of the PostScript section, so the size of the file
     * does not matter, and never reads outside that section.
     *
     * @return array{width: int, height: int}|null null if the file is not EPS or declares no usable size
     */
    private static function getEpsDimensionsFromHeader(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $handle = @fopen($path, 'rb');
        if (!$handle) {
            return null;
        }

        try {
            $start = 0;
            $length = (int) (fstat($handle)['size'] ?? 0);
            $magic = (string) fread($handle, 12);

            if (str_starts_with($magic, "\xC5\xD0\xD3\xC6") && strlen($magic) === 12) {
                // DOS EPS binary header: offset and length of the PostScript section
                ['start' => $start, 'length' => $length] = unpack('Vstart/Vlength', substr($magic, 4, 8));
            } elseif (!str_starts_with($magic, '%!PS-Adobe')) {
                return null;
            }

            if ($length <= 0 || fseek($handle, $start) !== 0) {
                return null;
            }
            $lines = self::readLines($handle, min($length, self::EPS_COMMENTS_BYTES), $length > self::EPS_COMMENTS_BYTES, false);

            // EPS declares itself on the first line ("%!PS-Adobe-3.0 EPSF-3.0"); other PostScript is left to the adapter
            if (!preg_match('/^%!PS-Adobe-\S+\s+EPSF-/', $lines[0] ?? '')) {
                return null;
            }

            // the Imagick adapter looks for this in the first 100 lines too
            foreach (array_slice($lines, 0, 100) as $line) {
                if (preg_match('/^%ImageData:\s*(\d+)\s+(\d+)/i', $line, $matches)) {
                    return self::positiveDimensions((float) $matches[1], (float) $matches[2]);
                }
            }

            $boxes = self::readBoundingBoxes(self::headerComments($lines));
            if (in_array('atend', $boxes, true)) {
                // deferred to the trailer, at the end of the PostScript section
                $tailLength = min($length, self::EPS_COMMENTS_BYTES);
                $trailer = fseek($handle, $start + $length - $tailLength) === 0
                    ? self::readBoundingBoxes(self::trailerComments(self::readLines($handle, $tailLength, false, $tailLength < $length)))
                    : [];
                foreach ($boxes as $key => $box) {
                    if ($box === 'atend') {
                        $boxes[$key] = $trailer[$key] ?? null;
                    }
                }
            }

            foreach (['HiResBoundingBox', 'BoundingBox'] as $key) {
                if (is_array($boxes[$key] ?? null)) {
                    [$llx, $lly, $urx, $ury] = $boxes[$key];

                    return self::positiveDimensions($urx - $llx, $ury - $lly);
                }
            }

            return null;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Reads $bytes and splits them into lines, dropping a line the read cut off: the last one if the
     * section continues after the read, the first one if it started before it.
     *
     * @param resource $handle
     *
     * @return string[]
     */
    private static function readLines($handle, int $bytes, bool $cutAtEnd, bool $cutAtStart): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) fread($handle, $bytes));
        if ($cutAtEnd) {
            array_pop($lines);
        }
        if ($cutAtStart) {
            array_shift($lines);
        }

        return $lines;
    }

    /**
     * The header comments end at %%EndComments or, without it, at the first line that is not a comment.
     *
     * @param string[] $lines
     *
     * @return string[]
     */
    private static function headerComments(array $lines): array
    {
        $header = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '%%EndComments') || !preg_match('/^%[!-~]/', $line)) {
                break;
            }
            $header[] = $line;
        }

        return $header;
    }

    /**
     * The trailer comments follow the last %%Trailer.
     *
     * @param string[] $lines
     *
     * @return string[]
     */
    private static function trailerComments(array $lines): array
    {
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (str_starts_with($lines[$i], '%%Trailer')) {
                return array_slice($lines, $i + 1);
            }
        }

        return [];
    }

    /**
     * The first value of each box comment; in a header, a box deferred to the trailer is 'atend'.
     *
     * @param string[] $comments
     *
     * @return array<string, float[]|string|null>
     */
    private static function readBoundingBoxes(array $comments): array
    {
        $boxes = [];
        foreach ($comments as $line) {
            if (!preg_match('/^%%(HiResBoundingBox|BoundingBox):\s*(.*)$/', $line, $matches) || isset($boxes[$matches[1]])) {
                continue;
            }
            $value = trim($matches[2]);
            if ($value === '(atend)') {
                $boxes[$matches[1]] = 'atend';
            } elseif (preg_match('/^(-?[\d.]+)\s+(-?[\d.]+)\s+(-?[\d.]+)\s+(-?[\d.]+)/', $value, $numbers)) {
                $boxes[$matches[1]] = array_map('floatval', array_slice($numbers, 1, 4));
            }
        }

        return $boxes;
    }

    /**
     * @return array{width: int, height: int}|null
     */
    private static function positiveDimensions(float $width, float $height): ?array
    {
        // a cast of a non-finite or out of range float is undefined; the size columns are 32 bit
        $max = 2147483647;
        if (!is_finite($width) || !is_finite($height) || abs($width) > $max || abs($height) > $max) {
            return null;
        }

        $width = (int) round($width);
        $height = (int) round($height);

        return $width > 0 && $height > 0 ? ['width' => $width, 'height' => $height] : null;
    }

    public function getWidth(): int
    {
        $dimensions = $this->getDimensions();

        if ($dimensions) {
            return (int) $dimensions['width'];
        }

        return 0;
    }

    public function getHeight(): int
    {
        $dimensions = $this->getDimensions();

        if ($dimensions) {
            return (int) $dimensions['height'];
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

    /**
     * The focal point of the image as percentage coordinates, null if it does not have one.
     * Both coordinates are required and 0 is a valid one (left/top edge), so they must not
     * be checked for truthiness.
     *
     * @return array{x: int|float|string, y: int|float|string}|null
     */
    public function getFocalPoint(): ?array
    {
        $x = $this->getCustomSetting('focalPointX');
        $y = $this->getCustomSetting('focalPointY');

        if (!is_numeric($x) || !is_numeric($y)) {
            return null;
        }

        return ['x' => $x, 'y' => $y];
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
     * Classifies vector-sensitive transformations without image I/O.
     * Conflicting filename and persisted MIME evidence is intentionally unknown so callers can fail closed and inspect the actual generated file.
     *
     * @internal
     */
    public function getVectorGraphicStateForDimensionEstimation(): ?bool
    {
        $extension = strtolower(pathinfo($this->getFilename(), PATHINFO_EXTENSION));
        $extensionState = match (true) {
            in_array($extension, ['svg', 'svgz', 'eps', 'pdf', 'ps', 'ai', 'indd'], true) => true,
            in_array($extension, [
                'avif',
                'bmp',
                'gif',
                'heic',
                'heif',
                'jpeg',
                'jpg',
                'png',
                'tif',
                'tiff',
                'webp',
            ], true) => false,
            default => null,
        };

        $mimeType = strtolower((string) $this->getMimeType());
        $mimeState = match (true) {
            in_array($mimeType, [
                'application/illustrator',
                'application/pdf',
                'application/postscript',
                'image/eps',
                'image/svg+xml',
                'image/x-eps',
            ], true) => true,
            in_array($mimeType, [
                'image/avif',
                'image/bmp',
                'image/gif',
                'image/heic',
                'image/heif',
                'image/jpeg',
                'image/png',
                'image/tiff',
                'image/webp',
            ], true) => false,
            default => null,
        };

        // A persisted MIME type can be replaced by project event listeners and is not proof of the loaded image format.
        // Unknown extensions therefore remain unknown for vector-sensitive estimation.
        if ($extensionState === null) {
            return null;
        }

        if ($mimeState !== null && $extensionState !== $mimeState) {
            return null;
        }

        return $extensionState;
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
