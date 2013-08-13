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
 
class Pimcore_Document_Adapter_LibreOffice extends Pimcore_Document_Adapter_Ghostscript {

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
        if(preg_match("/\.?(pdf|doc|docx|odt|xls|xlsx|ods|ppt|pptx|odp)$/", $fileType)) {
            return true;
        }

        return false;
    }
    /**
     * @static
     * @return string
     */
    public static function getLibreOfficeCli () {

        $loPath = Pimcore_Config::getSystemConfig()->assets->libreoffice;
        if($loPath) {
            if(@is_executable($loPath)) {
                return $loPath;
            } else {
                Logger::critical("LibreOffice binary: " . $loPath . " is not executable");
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

        throw new Exception("No LibreOffice executable found, please configure the correct path in the system settings");
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
            $message = "Couldn't load document " . $path . " only Microsoft/Libre/Open-Office/PDF documents are currently supported";
            Logger::error($message);
            throw new \Exception($message);
        }

        // first we have to create a pdf out of the document (if it isn't already one), so that we can pass it to ghostscript
        // unfortunately there isn't an other way at the moment
        if(!preg_match("/\.?pdf$/", $path)) {
            if(!parent::isFileTypeSupported($this->path)) {
                $pdfFile = PIMCORE_TEMPORARY_DIRECTORY . "/document_" . md5($path . filemtime($path)) . "__libreoffice.pdf";
                $lockKey = "soffice";

                if(!file_exists($pdfFile)) {

                    Tool_Lock::acquire($lockKey); // avoid parallel conversions of the same document

                    // a list of all available filters is here:
                    // http://cgit.freedesktop.org/libreoffice/core/tree/filter/source/config/fragments/filters
                    $out = Pimcore_Tool_Console::exec(self::getLibreOfficeCli() . " --headless --convert-to pdf:writer_web_pdf_Export --outdir " . PIMCORE_TEMPORARY_DIRECTORY . " " . $path);

                    Logger::debug("LibreOffice Output was: " . $out);

                    $tmpName = PIMCORE_TEMPORARY_DIRECTORY . "/" . preg_replace("/\." . Pimcore_File::getFileExtension($path) . "$/", ".pdf",basename($path));
                    if(file_exists($tmpName)) {
                        rename($tmpName, $pdfFile);
                        $this->path = $pdfFile;
                    }

                    Tool_Lock::release($lockKey);
                } else {
                    $this->path = $pdfFile;
                }
            }
        } else {
            $this->path = $path;
        }

        if(!file_exists($this->path)) {
            $message = "Couldn't load convert document to PDF: " . $path;
            Logger::error($message);
            throw new \Exception($message);
        }

        parent::load($this->path);

        return $this;
    }

}
