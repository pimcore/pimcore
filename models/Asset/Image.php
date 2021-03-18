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
class Image extends Model\Asset
{
    use Model\Asset\MetaData\EmbeddedMetaDataTrait;

    /**
     * @var string
     */
    protected $type = 'image';

    /**
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws \Exception
     */
    protected function update($params = [])
    {
        if ($this->getDataChanged() || !$this->getCustomSetting('imageDimensionsCalculated') || !$this->getCustomSetting('embeddedMetaDataExtracted')) {
            // save the current data into a tmp file to calculate the dimensions, otherwise updates wouldn't be updated
            // because the file is written in parent::update();
            $tmpFile = $this->getTemporaryFile();

            if ($this->getDataChanged() || !$this->getCustomSetting('imageDimensionsCalculated')) {
                // getDimensions() might fail, so assume `false` first
                $imageDimensionsCalculated = false;

                try {
                    $dimensions = $this->getDimensions($tmpFile, true);
                    if ($dimensions && $dimensions['width']) {
                        $this->setCustomSetting('imageWidth', $dimensions['width']);
                        $this->setCustomSetting('imageHeight', $dimensions['height']);
                        $imageDimensionsCalculated = true;
                    }
                } catch (\Exception $e) {
                    Logger::error('Problem getting the dimensions of the image with ID ' . $this->getId());
                }

                // this is to be downward compatible so that the controller can check if the dimensions are already calculated
                // and also to just do the calculation once, because the calculation can fail, an then the controller tries to
                // calculate the dimensions on every request an also will create a version, ...
                $this->setCustomSetting('imageDimensionsCalculated', $imageDimensionsCalculated);
            }

            $this->handleEmbeddedMetaData(true, $tmpFile);
        }

        $this->clearThumbnails();

        parent::update($params);

        // now directly create "system" thumbnails (eg. for the tree, ...)
        if ($this->getDataChanged()) {
            try {
                $path = $this->getThumbnail(Image\Thumbnail\Config::getPreviewConfig())->getFileSystemPath();

                // set the modification time of the thumbnail to the same time from the asset
                // so that the thumbnail check doesn't fail in Asset\Image\Thumbnail\Processor::process();
                // we need the @ in front of touch because of some stream wrapper (eg. s3) which don't support touch()
                @touch($path, $this->getModificationDate());

                $this->generateLowQualityPreview();
            } catch (\Exception $e) {
                Logger::error('Problem while creating system-thumbnails for image ' . $this->getRealFullPath());
                Logger::error($e);
            }
        }
    }

    protected function postPersistData()
    {
        if (!isset($this->customSettings['disableImageFeatureAutoDetection'])) {
            $this->detectFaces();
        }

        if (!isset($this->customSettings['disableFocalPointDetection'])) {
            $this->detectFocalPoint();
        }
    }

    public function detectFocalPoint()
    {
        if ($this->getCustomSetting('focalPointX') && $this->getCustomSetting('focalPointY')) {
            return;
        }

        if ($faceCordintates = $this->getCustomSetting('faceCoordinates')) {
            $xPoints = [];
            $yPoints = [];

            foreach ($faceCordintates as $fc) {
                // focal point calculation
                $xPoints[] = ($fc['x'] + $fc['x'] + $fc['width']) / 2;
                $yPoints[] = ($fc['y'] + $fc['y'] + $fc['height']) / 2;
            }

            $focalPointX = array_sum($xPoints) / count($xPoints);
            $focalPointY = array_sum($yPoints) / count($yPoints);

            $this->setCustomSetting('focalPointX', $focalPointX);
            $this->setCustomSetting('focalPointY', $focalPointY);
        }
    }

    public function detectFaces()
    {
        if ($this->getCustomSetting('faceCoordinates')) {
            return;
        }

        $config = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['image']['focal_point_detection'];

        if (!$config['enabled']) {
            return;
        }

        $facedetectBin = \Pimcore\Tool\Console::getExecutable('facedetect');
        if ($facedetectBin) {
            $faceCoordinates = [];
            $thumbnail = $this->getThumbnail(Image\Thumbnail\Config::getPreviewConfig());
            $image = $this->getLocalFile($thumbnail->getFileSystemPath());
            $imageWidth = $thumbnail->getWidth();
            $imageHeight = $thumbnail->getHeight();

            $result = \Pimcore\Tool\Console::exec($facedetectBin . ' ' . escapeshellarg($image));
            if (strpos($result, "\n")) {
                $faces = explode("\n", trim($result));

                foreach ($faces as $coordinates) {
                    list($x, $y, $width, $height) = explode(' ', $coordinates);

                    // percentages
                    $Px = $x / $imageWidth * 100;
                    $Py = $y / $imageHeight * 100;
                    $Pw = $width / $imageWidth * 100;
                    $Ph = $height / $imageHeight * 100;

                    $faceCoordinates[] = [
                        'x' => $Px,
                        'y' => $Py,
                        'width' => $Pw,
                        'height' => $Ph,
                    ];
                }

                $this->setCustomSetting('faceCoordinates', $faceCoordinates);
            }
        }
    }

    /**
     * @param null|string $generator
     *
     * @return bool|string
     *
     * @throws \Exception
     */
    public function generateLowQualityPreview($generator = null)
    {
        $config = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['image']['low_quality_image_preview'];
        $sqipBin = null;

        if (!$config['enabled']) {
            return false;
        }

        if (!$generator) {
            $generator = $config['generator'];
        }

        if (!$generator) {
            $sqipBin = \Pimcore\Tool\Console::getExecutable('sqip');
            if ($sqipBin) {
                $generator = 'sqip';
            }
        }

        if ($generator == 'sqip') {
            // SQIP is preferred, produced smaller files & mostly better quality
            // primitive isn't able to process PJPEG so we have to generate a PNG
            $sqipConfig = Image\Thumbnail\Config::getPreviewConfig();
            $sqipConfig->setFormat('png');
            $pngPath = $this->getThumbnail($sqipConfig)->getFileSystemPath();
            $svgPath = $this->getLowQualityPreviewFileSystemPath();
            \Pimcore\Tool\Console::exec($sqipBin . ' -o ' . escapeshellarg($svgPath) . ' '. escapeshellarg($pngPath));
            unlink($pngPath);

            if (file_exists($svgPath)) {
                $svgData = file_get_contents($svgPath);
                $svgData = str_replace('<svg', '<svg preserveAspectRatio="xMidYMid slice"', $svgData);
                File::put($svgPath, $svgData);

                return $svgPath;
            }
        }

        // fallback
        if (class_exists('Imagick')) {
            // Imagick fallback
            $path = $this->getThumbnail(Image\Thumbnail\Config::getPreviewConfig())->getFileSystemPath();

            if (!stream_is_local($path)) {
                // imagick is only able to deal with local files
                // if your're using custom stream wrappers this wouldn't work, so we create a temp. local copy
                $path = $this->getTemporaryFile();
            }

            $imagick = new \Imagick($path);
            $imagick->setImageFormat('jpg');
            $imagick->setOption('jpeg:extent', '1kb');
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();

            // we can't use getImageBlob() here, because of a bug in combination with jpeg:extent
            // http://www.imagemagick.org/discourse-server/viewtopic.php?f=3&t=24366
            $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/image-optimize-' . uniqid() . '.jpg';
            $imagick->writeImage($tmpFile);
            $imageBase64 = base64_encode(file_get_contents($tmpFile));
            $imagick->destroy();
            unlink($tmpFile);

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

            File::put($this->getLowQualityPreviewFileSystemPath(), $svg);

            return $this->getLowQualityPreviewFileSystemPath();
        }

        return false;
    }

    /**
     * @return string
     */
    public function getLowQualityPreviewPath()
    {
        $fsPath = $this->getLowQualityPreviewFileSystemPath();
        $path = str_replace(PIMCORE_TEMPORARY_DIRECTORY . '/image-thumbnails', '', $fsPath);
        $path = urlencode_ignore_slash($path);

        $event = new GenericEvent($this, [
            'filesystemPath' => $fsPath,
            'frontendPath' => $path,
        ]);
        \Pimcore::getEventDispatcher()->dispatch(FrontendEvents::ASSET_IMAGE_THUMBNAIL, $event);
        $path = $event->getArgument('frontendPath');

        return $path;
    }

    /**
     * @return string
     */
    public function getLowQualityPreviewFileSystemPath()
    {
        $path = $this->getThumbnail(Image\Thumbnail\Config::getPreviewConfig())->getFileSystemPath(true);
        $svgPath = preg_replace("/\.p?jpe?g$/", '-low-quality-preview.svg', $path);

        return $svgPath;
    }

    /**
     * @return string|null
     */
    public function getLowQualityPreviewDataUri(): ?string
    {
        $file = $this->getLowQualityPreviewFileSystemPath();
        $dataUri = null;
        if (file_exists($file)) {
            $dataUri = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($file));
        }

        return $dataUri;
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
        if (($this->getDataChanged() || $force) && is_dir($this->getImageThumbnailSavePath())) {
            $directoryIterator = new \DirectoryIterator($this->getImageThumbnailSavePath());
            $filterIterator = new \CallbackFilterIterator($directoryIterator, function (\SplFileInfo $fileInfo) {
                return strpos($fileInfo->getFilename(), 'image-thumb__' . $this->getId()) === 0;
            });
            /** @var \SplFileInfo $fileInfo */
            foreach ($filterIterator as $fileInfo) {
                recursiveDelete($fileInfo->getPathname());
            }
        }
    }

    /**
     * @param string $name
     */
    public function clearThumbnail($name)
    {
        $dir = $this->getImageThumbnailSavePath() . '/image-thumb__' . $this->getId() . '__' . $name;
        if (is_dir($dir)) {
            recursiveDelete($dir);
        }
    }

    /**
     * Legacy method for backwards compatibility. Use getThumbnail($config)->getConfig() instead.
     *
     * @param string|array|Image\Thumbnail\Config $config
     *
     * @return Image\Thumbnail\Config
     */
    public function getThumbnailConfig($config)
    {
        $thumbnail = $this->getThumbnail($config);

        return $thumbnail->getConfig();
    }

    /**
     * Returns a path to a given thumbnail or an thumbnail configuration.
     *
     * @param string|array|Image\Thumbnail\Config $config
     * @param bool $deferred
     *
     * @return Image\Thumbnail
     */
    public function getThumbnail($config = null, $deferred = true)
    {
        return new Image\Thumbnail($this, $config, $deferred);
    }

    /**
     * @static
     *
     * @throws \Exception
     *
     * @return null|\Pimcore\Image\Adapter
     */
    public static function getImageTransformInstance()
    {
        try {
            $image = \Pimcore\Image::getInstance();
        } catch (\Exception $e) {
            $image = null;
        }

        if (!$image instanceof \Pimcore\Image\Adapter) {
            throw new \Exception("Couldn't get instance of image tranform processor.");
        }

        return $image;
    }

    /**
     * @return string
     */
    public function getFormat()
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
     * @return string
     */
    public function getRelativeFileSystemPath()
    {
        return str_replace(PIMCORE_WEB_ROOT, '', $this->getFileSystemPath());
    }

    /**
     * @param string|null $path
     * @param bool $force
     *
     * @return array|null
     *
     * @throws \Exception
     */
    public function getDimensions($path = null, $force = false)
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
            $path = $this->getFileSystemPath();
        }

        $dimensions = null;

        //try to get the dimensions with getimagesize because it is much faster than e.g. the Imagick-Adapter
        if (is_readable($path)) {
            $imageSize = getimagesize($path);
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
                    $orientation = intval($exif['Orientation']);
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

    /**
     * @return int
     */
    public function getWidth()
    {
        $dimensions = $this->getDimensions();

        return $dimensions['width'];
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        $dimensions = $this->getDimensions();

        return $dimensions['height'];
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return Model\Asset
     */
    public function setCustomSetting($key, $value)
    {
        if (in_array($key, ['focalPointX', 'focalPointY'])) {
            // if the focal point changes we need to clean all thumbnails on save
            if ($this->getCustomSetting($key) != $value) {
                $this->setDataChanged();
            }
        }

        return parent::setCustomSetting($key, $value);
    }

    /**
     * @return bool
     */
    public function isVectorGraphic()
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
     * @return bool
     */
    public function isAnimated()
    {
        $isAnimated = false;

        switch ($this->getMimetype()) {
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
     *
     * @return bool
     */
    private function isAnimatedGif()
    {
        $isAnimated = false;

        if ($this->getMimetype() == 'image/gif') {
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
     *
     * @return bool
     */
    private function isAnimatedPng()
    {
        $isAnimated = false;

        if ($this->getMimetype() == 'image/png') {
            $fileContent = $this->getData();

            /**
             * Valid APNGs have an "acTL" chunk somewhere before their first "IDAT" chunk.
             *
             * @see http://foone.org/apng/
             */
            $isAnimated = strpos(substr($fileContent, 0, strpos($fileContent, 'IDAT')), 'acTL') !== false;
        }

        return $isAnimated;
    }
}
