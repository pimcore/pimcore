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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
class Asset_Image_Thumbnail_Processor {


    protected static $argumentMapping = array(
        "resize" => array("width","height"),
        "scaleByWidth" => array("width"),
        "scaleByHeight" => array("height"),
        "contain" => array("width","height"),
        "cover" => array("width","height","positioning"),
        "frame" => array("width","height"),
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
        "sharpen" => array('radius', 'sigma', 'amount', 'threshold')
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
     * @param Asset_Image_Thumbnail_Config $config
     * @param null $fileSystemPath
     * @param bool $deferred deferred means that the image will be generated on-the-fly (details see below)
     * @return mixed|string
     */
    public static function process ($asset, Asset_Image_Thumbnail_Config $config, $fileSystemPath = null, $deferred = false) {

        $format = strtolower($config->getFormat());
        $contentOptimizedFormat = false;
        $modificationDate = 0;

        if(!$fileSystemPath) {
            $fileSystemPath = $asset->getFileSystemPath();
        }

        if($asset instanceof Asset) {
            $id = $asset->getId();
            $modificationDate = $asset->getModificationDate();
        } else {
            $id = "dyn~" . crc32($fileSystemPath);
            if(file_exists($fileSystemPath)) {
                $modificationDate = filemtime($fileSystemPath);
            }
        }

        $fileExt = Pimcore_File::getFileExtension(basename($fileSystemPath));

        // simple detection for source type if SOURCE is selected
        if($format == "source" || empty($format)) {
            $format = self::getAllowedFormat($fileExt, array("jpeg","gif","png"), "png");
            $contentOptimizedFormat = true; // format can change depending of the content (alpha-channel, ...)
        }

        if($format == "print") {
            $format = self::getAllowedFormat($fileExt, array("svg","jpeg","png","tiff"), "png");

            if(($format == "tiff" || $format == "svg") && Pimcore_Tool::isFrontentRequestByAdmin()) {
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

        // add high-resolution modifier suffix to the filename
        $highResSuffix = "";
        if($config->getHighResolution()) {
            $highResSuffix = "@" . $config->getHighResolution() . "x";
        }

        $thumbDir = $asset->getImageThumbnailSavePath() . "/thumb__" . $config->getName();
        $filename = preg_replace("/\." . preg_quote(Pimcore_File::getFileExtension($asset->getFilename())) . "/", "", $asset->getFilename()) . $highResSuffix . "." . $format;
        $fsPath = $thumbDir . "/" . $filename;

        if(!is_dir(dirname($fsPath))) {
            Pimcore_File::mkdir(dirname($fsPath));
        }
        $path = str_replace(PIMCORE_DOCUMENT_ROOT, "", $fsPath);

        // deferred means that the image will be generated on-the-fly (when requested by the browser)
        // the configuration is saved for later use in Pimcore_Controller_Plugin_Thumbnail::routeStartup()
        // so that it can be used also with dynamic configurations
        if($deferred) {
            $configPath = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/thumb_" . $id . "__" . $config->getName() . "." . $format . ".deferred.config";
            Pimcore_File::put($configPath, Pimcore_Tool_Serialize::serialize($config));

            return $path;
        }

        // check for existing and still valid thumbnail
        if (is_file($fsPath) and filemtime($fsPath) >= $modificationDate) {
            return $path;
        }

        // transform image
        $image = Asset_Image::getImageTransformInstance();
        if(!$image->load($fileSystemPath)) {
            return "/pimcore/static/img/filetype-not-supported.png";
        }

        $image->setUseContentOptimizedFormat($contentOptimizedFormat);

        $transformations = $config->getItems();
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
                                if(in_array($key, array("width","height", "x", "y"))) {
                                    if($config->getHighResolution() && $config->getHighResolution() > 1) {
                                        $value *= $config->getHighResolution();
                                    }
                                }

                                $arguments[$position] = $value;
                            }
                        }
                    }

                    ksort($arguments);
                    if(count($mapping) == count($arguments)) {
                        call_user_func_array(array($image,$transformation["method"]),$arguments);
                    } else {
                        $message = "Image Transform failed: cannot call method `" . $transformation["method"] . "´ with arguments `" . implode(",",$arguments) . "´ because there are too few arguments";
                        Logger::error($message);
                    }
                }
            }
        }

        $image->save($fsPath, $format, $config->getQuality());

        if($contentOptimizedFormat) {
            Pimcore_Image_Optimizer::optimize($fsPath);
        }

        return $path;
    }
}
