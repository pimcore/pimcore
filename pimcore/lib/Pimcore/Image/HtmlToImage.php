<?php 
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Image;

use Pimcore\Config; 
use Pimcore\Tool\Console;

class HtmlToImage {

    /**
     * @return bool
     */
    public static function isSupported() {
        return (bool) self::getWkhtmltoimageBinary();
    }

    /**
     * @return bool
     */
    public static function getWkhtmltoimageBinary () {

        if(Config::getSystemConfig()->documents->wkhtmltoimage) {
            if(@is_executable(Config::getSystemConfig()->documents->wkhtmltoimage)) {
                return (string) Config::getSystemConfig()->documents->wkhtmltoimage;
            } else {
                \Logger::critical("wkhtmltoimage binary: " . Config::getSystemConfig()->documents->wkhtmltoimage . " is not executable");
            }
        }

        $paths = array(
            "/usr/bin/wkhtmltoimage-amd64",
            "/usr/local/bin/wkhtmltoimage-amd64",
            "/bin/wkhtmltoimage-amd64",
            "/usr/bin/wkhtmltoimage",
            "/usr/local/bin/wkhtmltoimage",
            "/bin/wkhtmltoimage",
            realpath(PIMCORE_DOCUMENT_ROOT . "/../wkhtmltox/wkhtmltoimage.exe") // for windows sample package (XAMPP)
        );

        foreach ($paths as $path) {
            if(@is_executable($path)) {
                return $path;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function getXvfbBinary () {
        $paths = array("/usr/bin/xvfb-run","/usr/local/bin/xvfb-run","/bin/xvfb-run");

        foreach ($paths as $path) {
            if(@is_executable($path)) {
                return $path;
            }
        }

        return false;
    }

    /**
     * @param $url
     * @param $outputFile
     * @param int $screenWidth
     * @param string $format
     * @return bool
     */
    public static function convert($url, $outputFile, $screenWidth = 1200, $format = "png") {

        // add parameter pimcore_preview to prevent inclusion of google analytics code, cache, etc.
        $url .= (strpos($url, "?") ? "&" : "?") . "pimcore_preview=true";


        $arguments = " --width " . $screenWidth . " --format " . $format . " \"" . $url . "\" " . $outputFile;

        // use xvfb if possible
        if($xvfb = self::getXvfbBinary()) {
            $command = $xvfb . " --auto-servernum --server-args=\"-screen 0, 1280x1024x24\" " .
                self::getWkhtmltoimageBinary() . " --use-xserver" . $arguments;
        } else {
            $command = self::getWkhtmltoimageBinary() . $arguments;
        }

        Console::exec($command, PIMCORE_LOG_DIRECTORY . "/wkhtmltoimage.log", 60);

        if(file_exists($outputFile) && filesize($outputFile) > 1000) {
            return true;
        }
        return false;
    }
}
