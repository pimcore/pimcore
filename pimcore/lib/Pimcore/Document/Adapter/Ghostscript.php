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
        $maxExecTime = (int) ini_get("max_execution_time");
        if($maxExecTime > 1 && $maxExecTime < 250) {
            set_time_limit(250);
        }

        if(!$this->isFileTypeSupported($path)) {
            $message = "Couldn't load document " . $path . " only PDF documents are currently supported";
            Logger::error($message);
            throw new \Exception($message);
        }

        $this->path = $path;

        return $this;
    }

    public function getPdf($path = null) {

        if(!$path && $this->path) {
            $path = $this->path;
        }

        if(preg_match("/\.?pdf$/", $path)) { // only PDF's are supported
            return $path;
        }

        $message = "Couldn't load document " . $path . " only PDF documents are currently supported";
        Logger::error($message);
        throw new \Exception($message);
    }

    /**
     * @param bool $blob
     * @return int
     * @throws Exception
     */
    public function getPageCount() {

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

    public function getText($page = null, $path = null) {
        try {

            $path = $path ? $path : $this->path;

            if($page) {
                $pageRange = "-dFirstPage=" . $page . " -dLastPage=" . $page . " ";
            }

            $textFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/pdf-text-extract-" . uniqid() . ".txt";
            Pimcore_Tool_Console::exec(self::getGhostscriptCli() . " -dBATCH -dNOPAUSE -sDEVICE=txtwrite " . $pageRange . "-dTextFormat=2 -sOutputFile=" . $textFile . " " . $path);

            if(is_file($textFile)) {
                $text =  file_get_contents($textFile);

                // this is a little bit strange the default option -dTextFormat=3 from ghostscript should return utf-8 but it doesn't
                // so we use option 2 which returns UCS-2LE and convert it here back to UTF-8 which works fine
                $text = mb_convert_encoding($text, 'UTF-8', 'UCS-2LE');
                unlink($textFile);
                return $text;
            }

        } catch (Exception $e) {
            Logger::error($e);
            return false;
        }
    }
}
