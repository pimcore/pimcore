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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
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
        "addOverlay" => array("path", "x", "y", "alpha", "composite"),
        "applyMask" => array("path"),
        "cropPercent" => array("width","height","x","y"),
        "grayscale" => array(),
        "sepia" => array()
    );

    /**
     * @static
     * @param Asset_Image|Asset_Video|string
     * @param Asset_Image_Thumbnail_Config $config
     * @param string $path
     * @return string
     */
    public static function process ($asset, Asset_Image_Thumbnail_Config $config, $fileSystemPath = null) {

        $format = strtolower($config->getFormat());
        if(!$fileSystemPath) {
            $fileSystemPath = $asset->getFileSystemPath();
        }

        // simple detection for source type if SOURCE is selected
        if($format == "source" || empty($format)) {
            $typeMapping = array(
                "gif" => "gif",
                "jpeg" => "jpeg",
                "jpg" => "jpeg",
                "png" => "png",
                "tiff" => "tiff"
            );

            $fileExt = Pimcore_File::getFileExtension($asset->getFilename());
            if($typeMapping[$fileExt]) {
                $format = $typeMapping[$fileExt];
            } else {
                // use PNG if source doesn't have a valid mapping
                $format = "png";
            }
        }


        $filename = "thumb_" . $asset->getId() . "__" . $config->getName() . "." . $format;

        $fsPath = PIMCORE_TEMPORARY_DIRECTORY . "/" . $filename;
        $path = str_replace(PIMCORE_DOCUMENT_ROOT, "", $fsPath);

        // check for existing and still valid thumbnail
        if (is_file($fsPath) and filemtime($fsPath) > $asset->getModificationDate()) {
            return $path;
        }

        // transform image
        $image = Asset_Image::getImageTransformInstance();
        if(!$image->load($fileSystemPath)) {
            return "/pimcore/static/img/filetype-not-supported.png";
        }

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

        return $path;
    }
}
