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

namespace Pimcore\Model\Asset\Image\Thumbnail;

use Pimcore\File;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Tool\StopWatch;
use Pimcore\Model\Asset;

class Processor
{
    protected static $argumentMapping = [
        "resize" => ["width", "height"],
        "scaleByWidth" => ["width"],
        "scaleByHeight" => ["height"],
        "contain" => ["width", "height"],
        "cover" => ["width", "height", "positioning", "doNotScaleUp"],
        "frame" => ["width", "height"],
        "trim" => ["tolerance"],
        "rotate" => ["angle"],
        "crop" => ["x", "y", "width", "height"],
        "setBackgroundColor" => ["color"],
        "roundCorners" => ["width", "height"],
        "setBackgroundImage" => ["path"],
        "addOverlay" => ["path", "x", "y", "alpha", "composite", "origin"],
        "addOverlayFit" => ["path", "composite"],
        "applyMask" => ["path"],
        "cropPercent" => ["width", "height", "x", "y"],
        "grayscale" => [],
        "sepia" => [],
        "sharpen" => ['radius', 'sigma', 'amount', 'threshold'],
        "gaussianBlur" => ['radius', 'sigma'],
        "brightnessSaturation" => ['brightness', 'saturation', "hue"],
        "mirror" => ["mode"]
    ];

    /**
     * @param $format
     * @param array $allowed
     * @param string $fallback
     * @return string
     */
    public static function getAllowedFormat($format, $allowed = [], $fallback = "png")
    {
        $typeMappings = [
            "jpg" => "jpeg",
            "tif" => "tiff"
        ];

        if (array_key_exists($format, $typeMappings)) {
            $format = $typeMappings[$format];
        }

        if (in_array($format, $allowed)) {
            $target = $format;
        } else {
            $target = $fallback;
        }

        return $target;
    }

    /**
     * @param $asset
     * @param Config $config
     * @param null $fileSystemPath
     * @param bool $deferred deferred means that the image will be generated on-the-fly (details see below)
     * @param bool $returnAbsolutePath
     * @return mixed|string
     */
    public static function process($asset, Config $config, $fileSystemPath = null, $deferred = false, $returnAbsolutePath = false)
    {
        $errorImage = PIMCORE_PATH . "/static6/img/filetype-not-supported.png";
        $format = strtolower($config->getFormat());
        $contentOptimizedFormat = false;

        if (!$fileSystemPath && $asset instanceof Asset) {
            $fileSystemPath = $asset->getFileSystemPath();
        }

        if ($asset instanceof Asset) {
            $id = $asset->getId();
        } else {
            $id = "dyn~" . crc32($fileSystemPath);
        }

        if (!file_exists($fileSystemPath)) {
            return self::returnPath($errorImage, $returnAbsolutePath);
        }

        $modificationDate = filemtime($fileSystemPath);

        $fileExt = File::getFileExtension(basename($fileSystemPath));

        // simple detection for source type if SOURCE is selected
        if ($format == "source" || empty($format)) {
            $format = self::getAllowedFormat($fileExt, ["jpeg", "gif", "png"], "png");
            $contentOptimizedFormat = true; // format can change depending of the content (alpha-channel, ...)
        }

        if ($format == "print") {
            $format = self::getAllowedFormat($fileExt, ["svg", "jpeg", "png", "tiff"], "png");

            if (($format == "tiff" || $format == "svg") && \Pimcore\Tool::isFrontentRequestByAdmin()) {
                // return a webformat in admin -> tiff cannot be displayed in browser
                $format = "png";
            } elseif ($format == "tiff") {
                $transformations = $config->getItems();
                if (is_array($transformations) && count($transformations) > 0) {
                    foreach ($transformations as $transformation) {
                        if (!empty($transformation)) {
                            if ($transformation["method"] == "tifforiginal") {
                                return self::returnPath($fileSystemPath, $returnAbsolutePath);
                            }
                        }
                    }
                }
            } elseif ($format == "svg") {
                return self::returnPath($fileSystemPath, $returnAbsolutePath);
            }
        }



        $thumbDir = $asset->getImageThumbnailSavePath() . "/thumb__" . $config->getName();
        $filename = preg_replace("/\." . preg_quote(File::getFileExtension($asset->getFilename())) . "/", "", $asset->getFilename());
        // add custom suffix if available
        if ($config->getFilenameSuffix()) {
            $filename .= "~-~" . $config->getFilenameSuffix();
        }
        // add high-resolution modifier suffix to the filename
        if ($config->getHighResolution() > 1) {
            $filename .= "@" . $config->getHighResolution() . "x";
        }
        $filename .= "." . $format;

        $fsPath = $thumbDir . "/" . $filename;

        if (!is_dir(dirname($fsPath))) {
            File::mkdir(dirname($fsPath));
        }

        $path = self::returnPath($fsPath, false);

        // check for existing and still valid thumbnail
        if (is_file($fsPath) and filemtime($fsPath) >= $modificationDate) {
            return self::returnPath($fsPath, $returnAbsolutePath);
        }

        // deferred means that the image will be generated on-the-fly (when requested by the browser)
        // the configuration is saved for later use in Pimcore\Controller\Plugin\Thumbnail::routeStartup()
        // so that it can be used also with dynamic configurations
        if ($deferred) {
            $configId = "thumb_" . $id . "__" . md5(str_replace(PIMCORE_TEMPORARY_DIRECTORY, "", $fsPath));
            TmpStore::add($configId, $config, "thumbnail_deferred");

            return self::returnPath($fsPath, $returnAbsolutePath);
        }

        // transform image
        $image = Asset\Image::getImageTransformInstance();
        if (!$image->load($fileSystemPath)) {
            return self::returnPath($errorImage, $returnAbsolutePath);
        }

        $image->setUseContentOptimizedFormat($contentOptimizedFormat);


        $startTime = StopWatch::microtime_float();

        $transformations = $config->getItems();

        // check if the original image has an orientation exif flag
        // if so add a transformation at the beginning that rotates and/or mirrors the image
        if (function_exists("exif_read_data")) {
            $exif = @exif_read_data($fileSystemPath);
            if (is_array($exif)) {
                if (array_key_exists("Orientation", $exif)) {
                    $orientation = intval($exif["Orientation"]);

                    if ($orientation > 1) {
                        $angleMappings = [
                            2 => 180,
                            3 => 180,
                            4 => 180,
                            5 => 90,
                            6 => 90,
                            7 => 90,
                            8 => 270,
                        ];

                        if (array_key_exists($orientation, $angleMappings)) {
                            array_unshift($transformations, [
                                "method" => "rotate",
                                "arguments" => [
                                    "angle" => $angleMappings[$orientation]
                                ]
                            ]);
                        }

                        // values that have to be mirrored, this is not very common, but should be covered anyway
                        $mirrorMappings = [
                            2 => "vertical",
                            4 => "horizontal",
                            5 => "vertical",
                            7 => "horizontal"
                        ];

                        if (array_key_exists($orientation, $mirrorMappings)) {
                            array_unshift($transformations, [
                                "method" => "mirror",
                                "arguments" => [
                                    "mode" => $mirrorMappings[$orientation]
                                ]
                            ]);
                        }
                    }
                }
            }
        }

        if (is_array($transformations) && count($transformations) > 0) {
            foreach ($transformations as $transformation) {
                if (!empty($transformation)) {
                    $arguments = [];
                    $mapping = self::$argumentMapping[$transformation["method"]];

                    if (is_array($transformation["arguments"])) {
                        foreach ($transformation["arguments"] as $key => $value) {
                            $position = array_search($key, $mapping);
                            if ($position !== false) {

                                // high res calculations if enabled
                                if (!in_array($transformation["method"], ["cropPercent"]) && in_array($key, ["width", "height", "x", "y"])) {
                                    if ($config->getHighResolution() && $config->getHighResolution() > 1) {
                                        $value *= $config->getHighResolution();
                                    }
                                }

                                $arguments[$position] = $value;
                            }
                        }
                    }

                    ksort($arguments);
                    call_user_func_array([$image, $transformation["method"]], $arguments);
                }
            }
        }

        $image->save($fsPath, $format, $config->getQuality());

        if ($contentOptimizedFormat) {
            $tmpStoreKey = str_replace(PIMCORE_TEMPORARY_DIRECTORY . "/", "", $fsPath);
            TmpStore::add($tmpStoreKey, "-", "image-optimize-queue");
        }

        clearstatcache();

        \Logger::debug("Thumbnail " . $path . " generated in " . (StopWatch::microtime_float() - $startTime) . " seconds");

        // set proper permissions
        @chmod($fsPath, File::getDefaultMode());

        // quick bugfix / workaround, it seems that imagemagick / image optimizers creates sometimes empty PNG chunks (total size 33 bytes)
        // no clue why it does so as this is not continuous reproducible, and this is the only fix we can do for now
        // if the file is corrupted the file will be created on the fly when requested by the browser (because it's deleted here)
        if (is_file($fsPath) && filesize($fsPath) < 50) {
            unlink($fsPath);
        }

        return self::returnPath($fsPath, $returnAbsolutePath);
    }

    /**
     * @param $path
     * @param $absolute
     * @return mixed
     */
    protected static function returnPath($path, $absolute)
    {
        if (!$absolute) {
            $path = str_replace(PIMCORE_DOCUMENT_ROOT, "", $path);
        }

        return $path;
    }

    /**
     *
     */
    public static function processOptimizeQueue()
    {
        $ids = TmpStore::getIdsByTag("image-optimize-queue");

        // id = path of image relative to PIMCORE_TEMPORARY_DIRECTORY
        foreach ($ids as $id) {
            $file = PIMCORE_TEMPORARY_DIRECTORY . "/" . $id;
            if (file_exists($file)) {
                $originalFilesize = filesize($file);
                \Pimcore\Image\Optimizer::optimize($file);
                \Logger::debug("Optimized image: " . $file . " saved " . formatBytes($originalFilesize-filesize($file)));
            } else {
                \Logger::debug("Skip optimizing of " . $file . " because it doesn't exist anymore");
            }

            TmpStore::delete($id);
        }
    }
}
