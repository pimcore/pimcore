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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Document\Adapter;

use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Tool\Console;

class LibreOffice extends Ghostscript
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @return bool
     */
    public function isAvailable()
    {
        try {
            $lo = self::getLibreOfficeCli();
            if ($lo && parent::isAvailable()) { // LibreOffice and GhostScript is necessary
                return true;
            }
        } catch (\Exception $e) {
            Logger::warning($e);
        }

        return false;
    }

    /**
     * @param string $fileType
     *
     * @return bool
     */
    public function isFileTypeSupported($fileType)
    {

        // it's also possible to pass a path or filename
        if (preg_match("/\.?(pdf|doc|docx|odt|xls|xlsx|ods|ppt|pptx|odp)$/", $fileType)) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    public static function getLibreOfficeCli()
    {
        return \Pimcore\Tool\Console::getExecutable('soffice', true);
    }

    /**
     * @param $path
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function load($path)
    {
        $path = $this->preparePath($path);

        // avoid timeouts
        $maxExecTime = (int) ini_get('max_execution_time');
        if ($maxExecTime > 1 && $maxExecTime < 250) {
            set_time_limit(250);
        }

        if (!$this->isFileTypeSupported($path)) {
            $message = "Couldn't load document " . $path . ' only Microsoft/Libre/Open-Office/PDF documents are currently supported';
            Logger::error($message);
            throw new \Exception($message);
        }

        // first we have to create a pdf out of the document (if it isn't already one), so that we can pass it to ghostscript
        // unfortunately there isn't an other way at the moment
        if (!preg_match("/\.?pdf$/", $path)) {
            if (!parent::isFileTypeSupported($path)) {
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
     *
     * @return null|string|void
     *
     * @throws \Exception
     */
    public function getPdf($path = null)
    {
        if ($path) {
            $path = $this->preparePath($path);
        }

        $pdfPath = null;
        if (!$path && $this->path) {
            $path = $this->path;
        }

        try {
            // if the document is already an PDF, delegate the call directly to parent::getPdf() (Ghostscript)
            $pdfPath = parent::getPdf($path);

            return $pdfPath;
        } catch (\Exception $e) {
            // nothing to do, delegate to libreoffice
        }

        $pdfFile = PIMCORE_TEMPORARY_DIRECTORY . '/document-pdf-cache/document_' . md5($path . filemtime($path)) . '__libreoffice.pdf';
        if (!is_dir(dirname($pdfFile))) {
            File::mkdir(dirname($pdfFile));
        }

        $lockKey = 'soffice';

        if (!file_exists($pdfFile)) {

            // a list of all available filters is here:
            // http://cgit.freedesktop.org/libreoffice/core/tree/filter/source/config/fragments/filters
            $cmd = self::getLibreOfficeCli() . ' --headless --nologo --nofirststartwizard --norestore --convert-to pdf:writer_web_pdf_Export --outdir ' . escapeshellarg(PIMCORE_SYSTEM_TEMP_DIRECTORY) . ' ' . escapeshellarg($path);

            Model\Tool\Lock::acquire($lockKey); // avoid parallel conversions
            $out = Console::exec($cmd, PIMCORE_LOG_DIRECTORY . '/libreoffice-pdf-convert.log', 240);
            Model\Tool\Lock::release($lockKey);

            Logger::debug('LibreOffice Output was: ' . $out);

            $tmpName = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . preg_replace("/\." . File::getFileExtension($path) . '$/', '.pdf', basename($path));
            if (file_exists($tmpName)) {
                File::rename($tmpName, $pdfFile);
                $pdfPath = $pdfFile;
            } else {
                $message = "Couldn't convert document to PDF: " . $path . " with the command: '" . $cmd . "'";
                Logger::error($message);
                throw new \Exception($message);
            }
        } else {
            $pdfPath = $pdfFile;
        }

        return $pdfPath;
    }

    /**
     * @param null $page
     * @param null $path
     *
     * @return bool|string
     *
     * @throws \Exception
     */
    public function getText($page = null, $path = null)
    {
        $path = $path ? $this->preparePath($path) : $this->path;

        if ($page || parent::isFileTypeSupported($path)) {
            // for per page extraction we have to convert the document to PDF and extract the text via ghostscript
            return parent::getText($page, $this->getPdf($path));
        } elseif (File::getFileExtension($path)) {
            // if we want to get the text of the whole document, we can use libreoffices text export feature
            $cmd = self::getLibreOfficeCli() . ' --headless --nologo --nofirststartwizard --norestore --convert-to txt:Text --outdir ' . escapeshellarg(PIMCORE_TEMPORARY_DIRECTORY) . ' ' . escapeshellarg($path);
            $out = Console::exec($cmd, null, 240);

            Logger::debug('LibreOffice Output was: ' . $out);

            $tmpName = PIMCORE_TEMPORARY_DIRECTORY . '/' . preg_replace("/\." . File::getFileExtension($path) . '$/', '.txt', basename($path));
            if (file_exists($tmpName)) {
                $text = file_get_contents($tmpName);
                $text = \Pimcore\Tool\Text::convertToUTF8($text);
                unlink($tmpName);

                return $text;
            } else {
                $message = "Couldn't convert document to PDF: " . $path . " with the command: '" . $cmd . "' - now trying to get the text out of the PDF ...";
                Logger::error($message);

                return parent::getText(null, $this->getPdf($path));
            }
        }

        return ''; // default empty string
    }
}
