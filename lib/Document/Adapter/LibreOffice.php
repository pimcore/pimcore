<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Document\Adapter;

use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Tool\Console;
use Symfony\Component\Lock\Factory as LockFactory;
use Symfony\Component\Process\Process;

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
            Logger::notice($e->getMessage());
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
        if (preg_match("/\.?(pdf|doc|docx|odt|xls|xlsx|ods|ppt|pptx|odp)$/i", $fileType)) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public static function getLibreOfficeCli()
    {
        return Console::getExecutable('soffice', true);
    }

    /**
     * @param string $path
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
        // unfortunately there isn't any other way at the moment
        if (!preg_match("/\.?pdf$/i", $path)) {
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
     * @param string|null $path
     *
     * @return null|string
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
            if (parent::isFileTypeSupported($path)) {
                return parent::getPdf($path);
            }
        } catch (\Exception $e) {
            // nothing to do, delegate to libreoffice
        }

        $pdfFile = PIMCORE_TEMPORARY_DIRECTORY . '/document-pdf-cache/document_' . md5($path . filemtime($path)) . '__libreoffice.pdf';
        if (!is_dir(dirname($pdfFile))) {
            File::mkdir(dirname($pdfFile));
        }

        $lock = \Pimcore::getContainer()->get(LockFactory::class)->createLock('soffice');
        if (!file_exists($pdfFile)) {

            // a list of all available filters is here:
            // http://cgit.freedesktop.org/libreoffice/core/tree/filter/source/config/fragments/filters
            $cmd = [
                self::getLibreOfficeCli(),
                '--headless', '--nologo', '--nofirststartwizard',
                '--norestore', '--convert-to', 'pdf:writer_web_pdf_Export',
                '--outdir', PIMCORE_SYSTEM_TEMP_DIRECTORY, $path,
            ];

            $lock->acquire(true);
            Console::addLowProcessPriority($cmd);
            $process = new Process($cmd);
            $process->setTimeout(240);
            $process->start();

            $tmpFile = PIMCORE_LOG_DIRECTORY . '/libreoffice-pdf-convert.log';
            $tmpHandle = fopen($tmpFile, 'a');
            $process->wait(function ($type, $buffer) use ($tmpHandle) {
                fwrite($tmpHandle, $buffer);
            });
            fclose($tmpHandle);

            $out = $process->getOutput();
            $lock->release();

            Logger::debug('LibreOffice Output was: ' . $out);

            $tmpName = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . preg_replace("/\." . File::getFileExtension($path) . '$/', '.pdf', basename($path));
            if (file_exists($tmpName)) {
                File::rename($tmpName, $pdfFile);
                $pdfPath = $pdfFile;
            } else {
                $message = "Couldn't convert document to PDF: " . $path . " with the command: '" . $process->getCommandLine() . "'";
                Logger::error($message);

                throw new \Exception($message);
            }
        } else {
            $pdfPath = $pdfFile;
        }

        return $pdfPath;
    }

    /**
     * @param int|null $page
     * @param string|null $path
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getText($page = null, $path = null)
    {
        $path = $path ? $this->preparePath($path) : $this->path;

        if ($page) {
            // for per page extraction we have to convert the document to PDF and extract the text via ghostscript
            return parent::getText($page, $this->getPdf($path));
        }
        if (File::getFileExtension($path)) {
            // if we want to get the text of the whole document, we can use libreoffices text export feature
            $cmd = [self::getLibreOfficeCli(), '--headless', '--nologo', '--nofirststartwizard', '--norestore', '--convert-to', 'txt:Text', '--outdir',  PIMCORE_TEMPORARY_DIRECTORY, $path];
            Console::addLowProcessPriority($cmd);
            $process = new Process($cmd);
            $process->setTimeout(240);
            $process->run();
            $out = $process->getOutput();

            Logger::debug('LibreOffice Output was: ' . $out);

            $tmpName = PIMCORE_TEMPORARY_DIRECTORY . '/' . preg_replace("/\." . File::getFileExtension($path) . '$/', '.txt', basename($path));
            if (file_exists($tmpName)) {
                $text = file_get_contents($tmpName);
                $text = \Pimcore\Tool\Text::convertToUTF8($text);
                unlink($tmpName);

                return $text;
            }

            $message = "Couldn't convert document to Text: " . $path . " with the command: '" . $process->getCommandLine() . "' - now trying to get the text out of the PDF with ghostscript...";
            Logger::notice($message);

            return parent::getText(null, $this->getPdf($path));
        }

        return ''; // default empty string
    }
}
