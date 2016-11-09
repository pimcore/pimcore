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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Image;

use Pimcore\File;
use Pimcore\Tool\Console;

class Optimizer
{
    /**
     * @param $path
     */
    public static function optimize($path)
    {
        $workingPath = $path;
        if (!stream_is_local($path)) {
            $workingPath = self::getTempFile();
            copy($path, $workingPath);
        }
        $format = getimagesize($workingPath);

        if (is_array($format) && array_key_exists("mime", $format)) {
            $format = strtolower(str_replace("image/", "", $format["mime"]));

            $optimizedFiles = [];
            $supportedOptimizers = [
                "png" => ["pngcrush", "zopflipng", "pngout", "advpng"],
                "jpeg" => ["jpegoptim", "cjpeg"]
            ];

            if (isset($supportedOptimizers[$format])) {
                foreach ($supportedOptimizers[$format] as $optimizer) {
                    $optimizerMethod = "optimize" . $optimizer;
                    $optimizedFile = self::$optimizerMethod($workingPath);
                    if ($optimizedFile && file_exists($optimizedFile)) {
                        $optimizedFiles[] = [
                            "filesize" => filesize($optimizedFile),
                            "path" => $optimizedFile,
                            "optimizer" => $optimizer,
                        ];
                    }
                }

                // order by filesize
                usort($optimizedFiles, function ($a, $b) {
                    if ($a["filesize"] == $b["filesize"]) {
                        return 0;
                    }

                    return ($a["filesize"] < $b["filesize"]) ? -1 : 1;
                });

                // first entry is the smallest -> use this one
                if (count($optimizedFiles)) {
                    copy($optimizedFiles[0]["path"], $path);
                }

                // cleanup
                foreach ($optimizedFiles as $tmpFile) {
                    unlink($tmpFile["path"]);
                }

                if (!stream_is_local($path)) {
                    unlink($workingPath);
                }
            }
        }
    }

    public static function optimizePngcrush($path)
    {
        $bin = \Pimcore\Tool\Console::getExecutable("pngcrush");
        if ($bin) {
            $newFile = self::getTempFile("png");
            Console::exec($bin . " " . escapeshellarg($path) . " " . $newFile, null, 60);
            if (file_exists($newFile)) {
                return $newFile;
            }
        }

        return null;
    }

    public static function optimizeZopflipng($path)
    {
        $bin = \Pimcore\Tool\Console::getExecutable("zopflipng");
        if ($bin) {
            $newFile = self::getTempFile("png");
            Console::exec($bin . " " . escapeshellarg($path) . " " . $newFile, null, 60);
            if (file_exists($newFile)) {
                return $newFile;
            }
        }

        return null;
    }

    public static function optimizePngout($path)
    {
        $bin = \Pimcore\Tool\Console::getExecutable("pngout", false);
        if ($bin) {
            $newFile = self::getTempFile("png");
            Console::exec($bin . " " . escapeshellarg($path) . " " . $newFile, null, 60);
            if (file_exists($newFile)) {
                return $newFile;
            }
        }

        return null;
    }

    public static function optimizeAdvpng($path)
    {
        $bin = \Pimcore\Tool\Console::getExecutable("advpng");
        if ($bin) {
            $newFile = self::getTempFile("png");
            copy($path, $newFile);
            Console::exec($bin . " -z4 " . $newFile, null, 60);

            return $newFile;
        }

        return null;
    }

    public static function optimizeCjpeg($path)
    {
        $bin = \Pimcore\Tool\Console::getExecutable("cjpeg");
        if ($bin) {
            $newFile = self::getTempFile("jpg");
            Console::exec($bin . " -outfile " . $newFile . " " . escapeshellarg($path), null, 60);
            if (file_exists($newFile)) {
                return $newFile;
            }
        }

        return null;
    }

    public static function optimizeJpegoptim($path)
    {
        $bin = \Pimcore\Tool\Console::getExecutable("jpegoptim");
        if ($bin) {
            $newFile = self::getTempFile("jpg");
            $additionalParams = "";
            if (filesize($path) > 10000) {
                $additionalParams = " --all-progressive";
            }
            $content = Console::exec($bin . $additionalParams . " -o --strip-all --max=85 --stdout " . escapeshellarg($path), null, 60);
            if ($content) {
                File::put($newFile, $content);
            }

            return $newFile;
        }

        return null;
    }

    /**
     * @param string $type
     * @return string
     */
    protected static function getTempFile($type = "")
    {
        $file = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/image-optimize-" . uniqid();
        if ($type) {
            $file .= "." . $type;
        }

        return $file;
    }
}
