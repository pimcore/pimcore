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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset;

use Pimcore\Model;
use Pimcore\Logger;

/**
 * @method \Pimcore\Model\Asset\Dao getDao()
 */
class Image extends Model\Asset
{

    /**
     * @var string
     */
    public $type = "image";

    /**
     * @return void
     */
    protected function update()
    {

        // only do this if the file exists and contains data
        if ($this->getDataChanged() || !$this->getCustomSetting("imageDimensionsCalculated")) {
            try {
                // save the current data into a tmp file to calculate the dimensions, otherwise updates wouldn't be updated
                // because the file is written in parent::update();
                $tmpFile = $this->getTemporaryFile();
                $dimensions = $this->getDimensions($tmpFile, true);
                unlink($tmpFile);

                if ($dimensions && $dimensions["width"]) {
                    $this->setCustomSetting("imageWidth", $dimensions["width"]);
                    $this->setCustomSetting("imageHeight", $dimensions["height"]);
                }
            } catch (\Exception $e) {
                Logger::error("Problem getting the dimensions of the image with ID " . $this->getId());
            }

            // this is to be downward compatible so that the controller can check if the dimensions are already calculated
            // and also to just do the calculation once, because the calculation can fail, an then the controller tries to
            // calculate the dimensions on every request an also will create a version, ...
            $this->setCustomSetting("imageDimensionsCalculated", true);
        }

        parent::update();

        $this->clearThumbnails();

        // now directly create "system" thumbnails (eg. for the tree, ...)
        if ($this->getDataChanged()) {
            try {
                $path = $this->getThumbnail(Image\Thumbnail\Config::getPreviewConfig())->getFileSystemPath();

                // set the modification time of the thumbnail to the same time from the asset
                // so that the thumbnail check doesn't fail in Asset\Image\Thumbnail\Processor::process();
                // we need the @ in front of touch because of some stream wrapper (eg. s3) which don't support touch()
                @touch($path, $this->getModificationDate());
            } catch (\Exception $e) {
                Logger::error("Problem while creating system-thumbnails for image " . $this->getRealFullPath());
                Logger::error($e);
            }
        }
    }

    /**
     * @return void
     */
    public function clearThumbnails($force = false)
    {
        if ($this->getDataChanged() || $force) {
            recursiveDelete($this->getImageThumbnailSavePath());
        }
    }

    /**
     * @param $name
     */
    public function clearThumbnail($name)
    {
        $dir = $this->getImageThumbnailSavePath() . "/thumb__" . $name;
        if (is_dir($dir)) {
            recursiveDelete($dir);
        }
    }

     /**
     * Legacy method for backwards compatibility. Use getThumbnail($config)->getConfig() instead.
     * @param mixed $config
     * @return Image\Thumbnail|bool
     */
    public function getThumbnailConfig($config)
    {
        $thumbnail = $this->getThumbnail($config);

        return $thumbnail->getConfig();
    }

    /**
     * Returns a path to a given thumbnail or an thumbnail configuration.
     * @param null $config
     * @param bool $deferred
     * @return Image\Thumbnail
     */
    public function getThumbnail($config = null, $deferred = true)
    {
        return new Image\Thumbnail($this, $config, $deferred);
    }

    /**
     * @static
     * @throws \Exception
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
            return "landscape";
        } elseif ($this->getWidth() == $this->getHeight()) {
            return "square";
        } elseif ($this->getHeight() > $this->getWidth()) {
            return "portrait";
        }

        return "unknown";
    }

    /**
     * @return string
     */
    public function getRelativeFileSystemPath()
    {
        return str_replace(PIMCORE_DOCUMENT_ROOT, "", $this->getFileSystemPath());
    }

    /**
     * @param null $path
     * @param bool $force
     * @return array
     * @throws \Exception
     */
    public function getDimensions($path = null, $force = false)
    {
        if (!$force) {
            $width = $this->getCustomSetting("imageWidth");
            $height = $this->getCustomSetting("imageHeight");

            if ($width && $height) {
                return [
                    "width" => $width,
                    "height" => $height
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
            if ($imageSize[0] && $imageSize[1]) {
                $dimensions = [
                    "width" => $imageSize[0],
                    "height" => $imageSize[1]
                ];
            }
        }

        if (!$dimensions) {
            $image = self::getImageTransformInstance();

            $status = $image->load($path, ["preserveColor" => true]);
            if ($status === false) {
                return;
            }

            $dimensions = [
                "width" => $image->getWidth(),
                "height" => $image->getHeight()
            ];
        }

        // EXIF orientation
        if (function_exists("exif_read_data")) {
            $exif = @exif_read_data($path);
            if (is_array($exif)) {
                if (array_key_exists("Orientation", $exif)) {
                    $orientation = intval($exif["Orientation"]);
                    if (in_array($orientation, [5, 6, 7, 8])) {
                        // flip height & width
                        $dimensions = [
                            "width" => $dimensions["height"],
                            "height" => $dimensions["width"]
                        ];
                    }
                }
            }
        }

        return $dimensions;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        $dimensions = $this->getDimensions();

        return $dimensions["width"];
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        $dimensions = $this->getDimensions();

        return $dimensions["height"];
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


    /**
     * @return array
     */
    public function getEXIFData()
    {
        $data = [];

        if (function_exists("exif_read_data") && is_file($this->getFileSystemPath())) {
            $supportedTypes = [IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM];

            if (in_array(@exif_imagetype($this->getFileSystemPath()), $supportedTypes)) {
                $exif = @exif_read_data($this->getFileSystemPath());
                if (is_array($exif)) {
                    foreach ($exif as $name => $value) {
                        if ((is_string($value) && strlen($value) < 50) || is_numeric($value)) {
                            $data[$name] = \ForceUTF8\Encoding::toUTF8($value);
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getIPTCData()
    {
        $data = [];

        if (is_file($this->getFileSystemPath())) {
            $result = getimagesize($this->getFileSystemPath(), $info);
            if ($result) {
                $mapping = [
                    '2#105' => 'headline',
                    '2#120' => 'caption',
                    '2#092' => 'location',
                    '2#090' => 'city',
                    '2#095' => 'state',
                    '2#101' => 'country',
                    '2#100' => 'countryCode',
                    '2#080' => 'photographerName',
                    '2#110' => 'credit',
                    '2#085' => 'photographerTitle',
                    '2#115' => 'source',
                    '2#116' => 'copyright',
                    '2#005' => 'objectName',
                    '2#122' => 'captionWriters',
                    '2#040' => 'instructions',
                    '2#015' => 'category',
                    '2#020' => 'supplementalCategories',
                    '2#103' => 'transmissionReference',
                    '2#010' => 'urgency',
                    '2#025' => 'keywords',
                    '2#055' => 'date',
                    '2#060' => 'time',
                ];

                if ($info && isset($info['APP13'])) {
                    $iptcRaw = iptcparse($info['APP13']);
                    if (is_array($iptcRaw)) {
                        foreach ($iptcRaw as $key => $value) {
                            if (is_array($value) && count($value) === 1) {
                                $value = $value[0];
                            }

                            if (isset($mapping[$key])) {
                                $data[$mapping[$key]] = \ForceUTF8\Encoding::toUTF8($value);
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }
}
