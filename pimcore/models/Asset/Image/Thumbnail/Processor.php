<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Asset\Image\Thumbnail;

use Pimcore\File;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Tool\StopWatch;
use Pimcore\Model\Asset;

class Processor {


    protected static $argumentMapping = array(
        "resize" => array("width","height"),
        "scaleByWidth" => array("width"),
        "scaleByHeight" => array("height"),
        "contain" => array("width","height"),
        "cover" => array("width","height","positioning","doNotScaleUp"),
        "frame" => array("width","height"),
        "trim" => array("tolerance"),
        "rotate" => array("angle"),
        "crop" => array("x","y","width","height"),
        "setBackgroundColor" => array("color"),
        "roundCorners" => array("width","height"),
        "setBackgroundImage" => array("path"),
        "addOverlay" => array("path", "x", "y", "alpha", "composite", "origin"),
        "applyMask" => array("path"),
        "cropPercent" => array("width","height","x","y"),
        "grayscale" => array(),
        "sepia" => array(),
        "sharpen" => array('radius', 'sigma', 'amount', 'threshold'),
        "gaussianBlur" => array('radius', 'sigma'),
        "mirror" => array("mode")
    );

    /**
     * @param $format
     * @param array $allowed
     * @param string $fallback
     * @return string
     */
    protected static function getAllowedFormat($format, $allowed = array(), $fallback = "png") {
        $typeMappings = array(
            "jpg" => "jpeg",
            "tif" => "tiff"
        );

        if(array_key_exists($format, $typeMappings)) {
            $format = $typeMappings[$format];
        }

        if(in_array($format, $allowed)) {
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
     * @return mixed|string
     */
    public static function process ($asset, Config $config, $fileSystemPath = null, $deferred = false) {

        $format = strtolower($config->getFormat());
        $contentOptimizedFormat = false;
        $modificationDate = 0;

        if(!$fileSystemPath) {
            $fileSystemPath = $asset->getFileSystemPath();
        }

        if($asset instanceof Asset) {
            $id = $asset->getId();
            // do not use the asset modification date because not every modification of an asset has an impact on the
            // binary data on the hdd (e.g. meta-data, properties, ...), so it's better to use the filemtime instead
            $modificationDate = filemtime($asset->getFileSystemPath());
        } else {
            $id = "dyn~" . crc32($fileSystemPath);
            if(file_exists($fileSystemPath)) {
                $modificationDate = filemtime($fileSystemPath);
            }
        }

        $fileExt = File::getFileExtension(basename($fileSystemPath));

        // simple detection for source type if SOURCE is selected
        if($format == "source" || empty($format)) {
            $format = self::getAllowedFormat($fileExt, array("jpeg","gif","png"), "png");
            $contentOptimizedFormat = true; // format can change depending of the content (alpha-channel, ...)
        }

        if($format == "print") {
            $format = self::getAllowedFormat($fileExt, array("svg","jpeg","png","tiff"), "png");

            if(($format == "tiff" || $format == "svg") && \Pimcore\Tool::isFrontentRequestByAdmin()) {
                // return a webformat in admin -> tiff cannot be displayed in browser
                $format = "png";
            } else if($format == "tiff") {
                $transformations = $config->getItems();
                if(is_array($transformations) && count($transformations) > 0) {
                    foreach ($transformations as $transformation) {
                        if(!empty($transformation)) {
                            if($transformation["method"] == "tifforiginal") {
                                return str_replace(PIMCORE_DOCUMENT_ROOT, "", $fileSystemPath);
                            }
                        }
                    }
                }
            } else if($format == "svg") {
                return str_replace(PIMCORE_DOCUMENT_ROOT, "", $fileSystemPath);
            }
        }



        $thumbDir = $asset->getImageThumbnailSavePath() . "/thumb__" . $config->getName();
        $filename = preg_replace("/\." . preg_quote(File::getFileExtension($asset->getFilename())) . "/", "", $asset->getFilename());
        // add custom suffix if available
        if($config->getFilenameSuffix()) {
            $filename .= "~-~" . $config->getFilenameSuffix();
        }
        // add high-resolution modifier suffix to the filename
        if($config->getHighResolution() > 1) {
            $filename .= "@" . $config->getHighResolution() . "x";
        }
        $filename .= "." . $format;

        $fsPath = $thumbDir . "/" . $filename;

        if(!is_dir(dirname($fsPath))) {
            File::mkdir(dirname($fsPath));
        }
        $path = str_replace(PIMCORE_DOCUMENT_ROOT, "", $fsPath);

        // check for existing and still valid thumbnail
        if (is_file($fsPath) and filemtime($fsPath) >= $modificationDate) {
            return $path;
        }

        // deferred means that the image will be generated on-the-fly (when requested by the browser)
        // the configuration is saved for later use in Pimcore\Controller\Plugin\Thumbnail::routeStartup()
        // so that it can be used also with dynamic configurations
        if($deferred) {
            $configId = "thumb_" . $id . "__" . md5($path);
            \Pimcore\Model\Tool\TmpStore::add($configId, $config, "thumbnail_deferred");

            return $path;
        }

        // transform image
        $image = Asset\Image::getImageTransformInstance();
        if(!$image->load($fileSystemPath)) {
            return "/pimcore/static/img/filetype-not-supported.png";
        }

        $image->setUseContentOptimizedFormat($contentOptimizedFormat);


        $startTime = StopWatch::microtime_float();

        $transformations = $config->getItems();

        // check if the original image has an orientation exif flag
        // if so add a transformation at the beginning that rotates and/or mirrors the image
        if (function_exists("exif_read_data")) {
            $exif = @exif_read_data($fileSystemPath);
            if (is_array($exif)) {
                if(array_key_exists("Orientation", $exif)) {
                    $orientation = intval($exif["Orientation"]);

                    if($orientation > 1) {
                        $angleMappings = [
                            2 => 180,
                            3 => 180,
                            4 => 180,
                            5 => 90,
                            6 => 90,
                            7 => 90,
                            8 => 270,
                        ];

                        if(array_key_exists($orientation, $angleMappings)) {
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

                        if(array_key_exists($orientation, $mirrorMappings)) {
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

        if(is_array($transformations) && count($transformations) > 0) {
            foreach ($transformations as $transformation) {
                if(!empty($transformation)) {
                    $arguments = array();
                    $mapping = self::$argumentMapping[$transformation["method"]];

                    if(is_array($transformation["arguments"])) {
                        foreach ($transformation["arguments"] as $key => $value) {
                            $position = array_search($key, $mapping);
                            if($position !== false) {

                                // high res calculations if enabled
                                if(!in_array($transformation["method"], ["cropPercent"]) && in_array($key, array("width","height", "x", "y"))) {
                                    if($config->getHighResolution() && $config->getHighResolution() > 1) {
                                        $value *= $config->getHighResolution();
                                    }
                                }

                                $arguments[$position] = $value;
                            }
                        }
                    }

                    ksort($arguments);
                    call_user_func_array(array($image,$transformation["method"]),$arguments);
                }
            }
        }

        $image->save($fsPath, $format, $config->getQuality());

        if($contentOptimizedFormat) {
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
        if(is_file($fsPath) && filesize($fsPath) < 50) {
            unlink($fsPath);
        }

        return $path;
    }

    /**
     *
     */
    public static function processOptimizeQueue() {

        $ids = TmpStore::getIdsByTag("image-optimize-queue");

        // id = path of image relative to PIMCORE_TEMPORARY_DIRECTORY
        foreach($ids as $id) {
            $file = PIMCORE_TEMPORARY_DIRECTORY . "/" . $id;
            if(file_exists($file)) {
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
