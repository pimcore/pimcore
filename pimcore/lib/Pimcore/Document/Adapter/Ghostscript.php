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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
class Pimcore_Document_Adapter_Ghostscript extends Pimcore_Document_Adapter {

    /**
     * @var string
     */
    protected $path;

    /**
     * @return bool
     */
    public function isAvailable() {
        try {
            $ghostscript = self::getGhostscriptCli();
            $phpCli = Pimcore_Tool_Console::getPhpCli();
            if($ghostscript && $phpCli) {
                return true;
            }
        } catch (Exception $e) {
            Logger::warning($e);
        }

        return false;
    }

    /**
     * @param string $fileType
     * @return bool
     */
    public function isFileTypeSupported($fileType) {

        // it's also possible to pass a path or filename
        if(preg_match("/\.?pdf$/", $fileType)) {
            return true;
        }

        return false;
    }

    /**
     * @static
     * @return string
     */
    public static function getGhostscriptCli () {

        $gsPath = Pimcore_Config::getSystemConfig()->assets->ghostscript;
        if($gsPath) {
            if(@is_executable($gsPath)) {
                return $gsPath;
            } else {
                Logger::critical("Ghostscript binary: " . $gsPath . " is not executable");
            }
        }

        $paths = array(
            "/usr/local/bin/gs",
            "/usr/bin/gs",
            "/bin/gs"
        );

        foreach ($paths as $path) {
            if(@is_executable($path)) {
                return $path;
            }
        }

        throw new Exception("No Ghostscript executable found, please configure the correct path in the system settings");
    }

    /**
     * @param $path
     * @return $this
     * @throws Exception
     */
    public function load($path) {

        // avoid timeouts
        set_time_limit(250);

        if(!$this->isFileTypeSupported($path)) {
            $message = "Couldn't load document " . $path . " only PDF documents are currently supported";
            Logger::error($message);
            throw new \Exception($message);
        }

        $this->path = $path;

        return $this;
    }

    /**
     * @param bool $blob
     * @return int
     * @throws Exception
     */
    public function getPageCount($blob = false) {

        $pages = Pimcore_Tool_Console::exec(self::getGhostscriptCli() . " -dNODISPLAY -q -c '(" . $this->path . ") (r) file runpdfbegin pdfpagecount = quit'");
        $pages = trim($pages);

        if(!is_numeric($pages)) {
            throw new \Exception("Unable to get page-count of " . $this->path);
        }

        return $pages;
    }

    /**
     * @param $path
     * @param int $page
     * @return $this|bool
     */
    public function saveImage($path, $page = 1, $resolution = 200) {

        try {
            Pimcore_Tool_Console::exec(self::getGhostscriptCli() . " -sDEVICE=png16m -dFirstPage=" . $page . " -dLastPage=" . $page . " -r" . $resolution . " -o " . $path . " " . $this->path);
            return $this;
        } catch (Exception $e) {
            Logger::error($e);
            return false;
        }
    }
}
