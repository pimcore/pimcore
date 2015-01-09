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

namespace Pimcore\Document\Adapter;

use Pimcore\Document\Adapter\Ghostscript;
use Pimcore\Tool\Console;
use Pimcore\Config;
use Pimcore\File;
use Pimcore\Model;

class LibreOffice extends Ghostscript {

    /**
     * @var string
     */
    protected $path;

    /**
     * @return bool
     */
    public function isAvailable() {
        try {
            $lo = self::getLibreOfficeCli();
            if($lo && parent::isAvailable()) { // LibreOffice and GhostScript is necessary
                return true;
            }
        } catch (\Exception $e) {
            \Logger::warning($e);
        }

        return false;
    }

    /**
     * @param string $fileType
     * @return bool
     */
    public function isFileTypeSupported($fileType) {

        // it's also possible to pass a path or filename
        if(preg_match("/\.?(pdf|doc|docx|odt|xls|xlsx|ods|ppt|pptx|odp)$/", $fileType)) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public static function getLibreOfficeCli () {

        $loPath = Config::getSystemConfig()->assets->libreoffice;
        if($loPath) {
            if(@is_executable($loPath)) {
                return $loPath;
            } else {
                \Logger::critical("LibreOffice binary: " . $loPath . " is not executable");
            }
        }

        $paths = array(
            "/usr/local/bin/soffice",
            "/usr/bin/soffice",
            "/bin/soffice"
        );

        foreach ($paths as $path) {
            if(@is_executable($path)) {
                return $path;
            }
        }

        throw new \Exception("No LibreOffice executable found, please configure the correct path in the system settings");
    }

    /**
     * @param $path
     * @return $this
     * @throws \Exception
     */
    public function load($path) {

        // avoid timeouts
        $maxExecTime = (int) ini_get("max_execution_time");
        if($maxExecTime > 1 && $maxExecTime < 250) {
            set_time_limit(250);
        }

        if(!$this->isFileTypeSupported($path)) {
            $message = "Couldn't load document " . $path . " only Microsoft/Libre/Open-Office/PDF documents are currently supported";
            \Logger::error($message);
            throw new \Exception($message);
        }

        // first we have to create a pdf out of the document (if it isn't already one), so that we can pass it to ghostscript
        // unfortunately there isn't an other way at the moment
        if(!preg_match("/\.?pdf$/", $path)) {
            if(!parent::isFileTypeSupported($path)) {
                $this->path = $this->getPdf($path);
            }
        } else {
            $this->path = $path;
        }

        parent::load($this->path);

        return $this;
    }

    /**
     * @param null $path
     * @return null|string|void
     * @throws \Exception
     */
    public function getPdf($path = null) {

        $pdfPath = null;
        if(!$path && $this->path) {
            $path = $this->path;
        }

        try {
            // if the document is already an PDF, delegate the call directly to parent::getPdf() (Ghostscript)
            $pdfPath = parent::getPdf($path);
            return $pdfPath;
        } catch (\Exception $e) {
            // nothing to do, delegate to libreoffice
        }

        $pdfFile = PIMCORE_TEMPORARY_DIRECTORY . "/document-pdf-cache/document_" . md5($path . filemtime($path)) . "__libreoffice.pdf";
        if(!is_dir(dirname($pdfFile))) {
            File::mkdir(dirname($pdfFile));
        }

        $lockKey = "soffice";

        if(!file_exists($pdfFile)) {

            Model\Tool\Lock::acquire($lockKey); // avoid parallel conversions of the same document

            // a list of all available filters is here:
            // http://cgit.freedesktop.org/libreoffice/core/tree/filter/source/config/fragments/filters
            $cmd = self::getLibreOfficeCli() . " --headless --nologo --nofirststartwizard --norestore --convert-to pdf:writer_web_pdf_Export --outdir " . PIMCORE_TEMPORARY_DIRECTORY . " " . $path;
            $out = Console::exec($cmd, PIMCORE_LOG_DIRECTORY . "/libreoffice-pdf-convert.log", 240);

            \Logger::debug("LibreOffice Output was: " . $out);

            $tmpName = PIMCORE_TEMPORARY_DIRECTORY . "/" . preg_replace("/\." . File::getFileExtension($path) . "$/", ".pdf",basename($path));
            if(file_exists($tmpName)) {
                rename($tmpName, $pdfFile);
                $pdfPath = $pdfFile;
            } else {
                $message = "Couldn't convert document to PDF: " . $path . " with the command: '" . $cmd . "'";
                \Logger::error($message);
                throw new \Exception($message);
            }

            Model\Tool\Lock::release($lockKey);
        } else {
            $pdfPath = $pdfFile;
        }

        return $pdfPath;
    }

    /**
     * @param null $page
     * @param null $path
     * @return bool|string
     * @throws \Exception
     */
    public function getText($page = null, $path = null) {

        $path = $path ? $path : $this->path;

        if($page || parent::isFileTypeSupported($path)) {
            // for per page extraction we have to convert the document to PDF and extract the text via ghostscript
            return parent::getText($page, $this->getPdf($path));
        } else if(File::getFileExtension($path)) {
            // if we want to get the text of the whole document, we can use libreoffices text export feature
            $cmd = self::getLibreOfficeCli() . " --headless --nologo --nofirststartwizard --norestore --convert-to txt:Text --outdir " . PIMCORE_TEMPORARY_DIRECTORY . " " . $path;
            $out = Console::exec($cmd, null, 240);

            \Logger::debug("LibreOffice Output was: " . $out);

            $tmpName = PIMCORE_TEMPORARY_DIRECTORY . "/" . preg_replace("/\." . File::getFileExtension($path) . "$/", ".txt",basename($path));
            if(file_exists($tmpName)) {
                $text = file_get_contents($tmpName);
                $text = \Pimcore\Tool\Text::convertToUTF8($text);
                unlink($tmpName);
                return $text;
            } else {
                $message = "Couldn't convert document to PDF: " . $path . " with the command: '" . $cmd . "' - now trying to get the text out of the PDF ...";
                \Logger::error($message);

                return parent::getText(null, $this->getPdf($path));
            }
        }

        return ""; // default empty string
    }
}
