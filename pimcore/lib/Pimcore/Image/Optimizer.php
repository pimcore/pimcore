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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Image;

use Pimcore\Tool\Console; 
use Pimcore\Config; 

class Optimizer {

    /**
     * @var array
     */
    protected static $optimizerBinaries = array();

    /**
     * @param $path
     */
    public static function optimize($path) {

        $format = getimagesize($path);

        if(array_key_exists("mime", $format)) {
            $format = strtolower(str_replace("image/", "",$format["mime"]));

            if($format == "png") {
                $optimizer = self::getPngOptimizerCli();
                if($optimizer) {
                    /*if($optimizer["type"] == "pngquant") {
                        Console::exec($optimizer["path"] . " --ext xxxoptimized.png " . $path, null, 60);
                        $newFile = preg_replace("/\.png$/", "", $path);
                        $newFile .= "xxxoptimized.png";

                        if(file_exists($newFile)) {
                            unlink($path);
                            rename($newFile, $path);
                        }
                    } else */
                    if ($optimizer["type"] == "pngcrush") {
                        $newFile = $path . ".xxxoptimized";
                        Console::exec($optimizer["path"] . " " . $path . " " . $newFile, null, 60);
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
                        Console::exec($optimizer["path"] . " " . $path . " " . $newFile, null, 60);
                        if(file_exists($newFile)) {
                            unlink($path);
                            rename($newFile, $path);
                        }
                    } else if($optimizer["type"] == "jpegoptim") {
                        $additionalParams = "";
                        if(filesize($path) > 10000) {
                            $additionalParams = " --all-progressive";
                        }
                        Console::exec($optimizer["path"] . $additionalParams . " -o --strip-all --max=85 " . $path, null, 60);
                    }
                }
            }
        }
    }

    /**
     * @return bool|string
     */
    public static function getPngOptimizerCli () {

        // check if we have a cached path for this process
        if(array_key_exists("pngOptimizer", self::$optimizerBinaries)) {
            return self::$optimizerBinaries["pngOptimizer"];
        }

        // check the system-config for a path
        $configPath = Config::getSystemConfig()->assets->pngcrush;
        if($configPath) {
            if(@is_executable($configPath)) {
                self::$optimizerBinaries["pngOptimizer"] = array(
                    "path" => $configPath,
                    "type" => "pngcrush"
                );

                return $configPath;
            } else {
                \Logger::critical("Binary: " . $configPath . " is not executable");
            }
        }

        $paths = array(
            /*"/usr/local/bin/pngquant",
            "/usr/bin/pngquant",
            "/bin/pngquant",*/
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

    /**
     * @return bool
     */
    public static function getJpegOptimizerCli() {

        // check if we have a cached path for this process
        if(array_key_exists("jpegOptimizer", self::$optimizerBinaries)) {
            return self::$optimizerBinaries["jpegOptimizer"];
        }

        // check the system-config for a path
        foreach (["imgmin","jpegoptim"] as $type) {
            $configPath = Config::getSystemConfig()->assets->$type;
            if($configPath) {
                if(@is_executable($configPath)) {
                    self::$optimizerBinaries["pngOptimizer"] = array(
                        "path" => $configPath,
                        "type" => $type
                    );

                    return $configPath;
                } else {
                    \Logger::critical("Binary: " . $configPath . " is not executable");
                }
            }
        }

        $paths = array(
            "/usr/local/bin/jpegoptim",
            "/usr/bin/jpegoptim",
            "/bin/jpegoptim",
            "/usr/local/bin/imgmin",
            "/usr/bin/imgmin",
            "/bin/imgmin",
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