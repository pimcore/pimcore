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

namespace Pimcore\Tool;

class Misc {

    /**
     * @param array $config
     * @return string
     */
    public static function roboHash($config = []) {

        $defaultConfig = [
            "seed" => rand(0,20000),
            "width" => null,
            "height" => null
        ];

        $config = array_merge($defaultConfig, $config);

        $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/robo-hash-" . md5(serialize($config)) . ".png";

        if(!is_file($tmpFile)) {
            $seed = crc32($config["seed"]);
            $colors = array("blue","brown","green","grey","orange","pink","purple","red","white","yellow");
            $partDirs = array("003#01Body", "004#02Face", "000#Mouth","001#Eyes","002#Accessory");

            $im = null;

            srand($seed);
            $color = $colors[array_rand($colors)];
            $dir = PIMCORE_PATH . "/static/img/robohash/" . $color;

            foreach ($partDirs as $key => $partDir) {
                $partDir = $dir . "/" . $partDir;
                $files = scandir($partDir);

                srand($seed + $key);
                $id = rand(0,9);

                foreach ($files as $file) {
                    if(preg_match("/^00" . $id . "#/", $file)) {
                        $partIm = imagecreatefrompng($partDir . "/" . $file);
                        break;
                    }
                }

                if($im) {
                    imagecopy($im, $partIm, 0,0,0,0,300,300);
                } else {
                    $im = $partIm;
                    imagesavealpha($im, true);
                }
            }

            if($config["width"] && $config["height"]) {
                $w = $config["width"];
                $h = $config["height"];
                $imResized = imagecreatetruecolor($w, $h);
                imagesavealpha($imResized, true);
                imagealphablending($imResized, false);
                $trans_colour = imagecolorallocatealpha($imResized, 255, 0, 0, 127);
                imagefill($imResized, 0, 0, $trans_colour);
                imagecopyresampled($imResized, $im, 0, 0, 0, 0, $w, $h, 300, 300);
                $im = $imResized;
            }

            imagepng($im, $tmpFile);
            imagedestroy($im);
        }

        return $tmpFile;
    }
}