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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Image_Optimizer {

    protected static $optimizerBinaries = array();

    public static function optimize($path) {

        $format = getimagesize($path);

        if(array_key_exists("mime", $format)) {
            $format = strtolower(str_replace("image/", "",$format["mime"]));

            if($format == "png") {
                $optimizer = self::getPngOptimizerCli();
                if($optimizer) {
                    if($optimizer["type"] == "pngquant") {
                        Pimcore_Tool_Console::exec($optimizer["path"] . " --ext xxxoptimized.png " . $path);
                        $newFile = preg_replace("/\.png$/", "", $path);
                        $newFile .= "xxxoptimized.png";

                        if(file_exists($newFile)) {
                            unlink($path);
                            rename($newFile, $path);
                        }
                    } else if ($optimizer["type"] == "pngcrush") {
                        $newFile = $path . ".xxxoptimized";
                        Pimcore_Tool_Console::exec($optimizer["path"] . " " . $path . " " . $newFile);
                        if(file_exists($newFile)) {
                            unlink($path);
                            rename($newFile, $path);
                        }
                    }
                }
            } else if ($format == "jpeg") {
                $optimizer = self::getJpegOptimizerCli();
                if($optimizer) {
                    if($optimizer["type"] == "imgmin") {
                        $newFile = $path . ".xxxoptimized";
                        Pimcore_Tool_Console::exec($optimizer["path"] . " " . $path . " " . $newFile);
                        if(file_exists($newFile)) {
                            unlink($path);
                            rename($newFile, $path);
                        }
                    } else if($optimizer["type"] == "jpegoptim") {
                        $additionalParams = "";
                        if(filesize($path) > 10000) {
                            $additionalParams = " --all-progressive";
                        }
                        Pimcore_Tool_Console::exec($optimizer["path"] . $additionalParams . " -o --strip-all --max=85 " . $path);
                    }
                }
            }
        }
    }

    /**
     * @return bool|string
     */
    public static function getPngOptimizerCli () {

        if(array_key_exists("pngOptimizer", self::$optimizerBinaries)) {
            return self::$optimizerBinaries["pngOptimizer"];
        }

        $paths = array(
            "/usr/local/bin/pngquant",
            "/usr/bin/pngquant",
            "/bin/pngquant",
            "/usr/local/bin/pngcrush",
            "/usr/bin/pngcrush",
            "/bin/pngcrush",
        );

        foreach ($paths as $path) {
            if(@is_executable($path)) {
                self::$optimizerBinaries["pngOptimizer"] = array(
                    "path" => $path,
                    "type" => basename($path)
                );
                return self::$optimizerBinaries["pngOptimizer"];
            }
        }

        self::$optimizerBinaries["pngOptimizer"] = false;

        return false;
    }

    public static function getJpegOptimizerCli() {
        if(array_key_exists("jpegOptimizer", self::$optimizerBinaries)) {
            return self::$optimizerBinaries["jpegOptimizer"];
        }

        $paths = array(
            "/usr/local/bin/imgmin",
            "/usr/bin/imgmin",
            "/bin/imgmin",
            "/usr/local/bin/jpegoptim",
            "/usr/bin/jpegoptim",
            "/bin/jpegoptim",
        );

        foreach ($paths as $path) {
            if(@is_executable($path)) {
                self::$optimizerBinaries["jpegOptimizer"] = array(
                    "path" => $path,
                    "type" => basename($path)
                );
                return self::$optimizerBinaries["jpegOptimizer"];
            }
        }

        self::$optimizerBinaries["jpegOptimizer"] = false;

        return false;
    }
}