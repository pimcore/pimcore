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
use Pimcore\Model\Asset;
use Pimcore\Tool\Console;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Process\Process;

class LibreOffice extends Ghostscript
{
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
     * { @inheritdoc }
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
     * @return mixed
     *
     * @throws \Exception
     */
    public static function getLibreOfficeCli()
    {
        return \Pimcore\Tool\Console::getExecutable('soffice', true);
    }

    /**
     * { @inheritdoc }
     */
    public function load(Asset\Document $asset)
    {
        // avoid timeouts
        $maxExecTime = (int) ini_get('max_execution_time');
        if ($maxExecTime > 1 && $maxExecTime < 250) {
            set_time_limit(250);
        }

        if (!$this->isFileTypeSupported($asset->getFilename())) {
            $message = "Couldn't load document " . $asset->getFullPath() . ' only Microsoft/Libre/Open-Office/PDF documents are currently supported';
            Logger::error($message);
            throw new \Exception($message);
        }

        $this->asset = $asset;

        // first we have to create a pdf out of the document (if it isn't already one), so that we can pass it to ghostscript
        // unfortunately there isn't any other way at the moment
        if (!preg_match("/\.?pdf$/i", $asset->getFilename())) {
            if (!parent::isFileTypeSupported($asset->getFilename())) {
                $this->getPdf();
            }
        }

        return $this;
    }

    /**
     * { @inheritdoc }
     */
    public function getPdf(?Asset\Document $asset = null)
    {
        $pdfPath = null;
        if (!$asset && $this->asset) {
            $asset = $this->asset;
        }

        try {
            // if the document is already an PDF, delegate the call directly to parent::getPdf() (Ghostscript)
            $pdfPath = parent::getPdf($asset);

            return $pdfPath;
        } catch (\Exception $e) {
            // nothing to do, delegate to libreoffice
        }

        $pdfFile = PIMCORE_TEMPORARY_DIRECTORY . sprintf('/document-pdf-cache/document_%s_%s__libreoffice.pdf', $asset->getId(), $asset->getModificationDate());
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
                '--outdir', PIMCORE_SYSTEM_TEMP_DIRECTORY, $asset->getLocalFile(),
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

            $tmpName = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . preg_replace("/\." . File::getFileExtension($asset->getFilename()) . '$/', '.pdf', $asset->getFilename());
            if (file_exists($tmpName)) {
                File::rename($tmpName, $pdfFile);
                $pdfPath = $pdfFile;
            } else {
                $message = "Couldn't convert document to PDF: " . $asset->getFullPath() . " with the command: '" . $process->getCommandLine() . "'";
                Logger::error($message);
                throw new \Exception($message);
            }
        } else {
            $pdfPath = $pdfFile;
        }

        return $pdfPath;
    }

    /**
     * { @inheritdoc }
     */
    public function getText(?int $page = null, ?Asset\Document $asset = null)
    {
        if (!$asset && $this->asset) {
            $asset = $this->asset;
        }

        if ($page || parent::isFileTypeSupported($asset->getFilename())) {
            // for per page extraction we have to convert the document to PDF and extract the text via ghostscript
            return parent::getText($page, $asset);
        } elseif (self::isFileTypeSupported($asset->getFilename())) {
            // if we want to get the text of the whole document, we can use libreoffices text export feature
            $cmd = [self::getLibreOfficeCli(), '--headless', '--nologo', '--nofirststartwizard', '--norestore', '--convert-to', 'txt:Text', '--outdir',  PIMCORE_SYSTEM_TEMP_DIRECTORY, $asset->getLocalFile()];
            Console::addLowProcessPriority($cmd);
            $process = new Process($cmd);
            $process->setTimeout(240);
            $process->run();
            $out = $process->getOutput();

            Logger::debug('LibreOffice Output was: ' . $out);

            $tmpName = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . preg_replace("/\." . File::getFileExtension($asset->getFilename()) . '$/', '.txt', $asset->getFilename());
            if (file_exists($tmpName)) {
                $text = file_get_contents($tmpName);
                $text = \Pimcore\Tool\Text::convertToUTF8($text);
                unlink($tmpName);

                return $text;
            } else {
                $message = "Couldn't convert document to Text: " . $asset->getFullPath() . " with the command: '" . $process->getCommandLine() . "' - now trying to get the text out of the PDF with ghostscript...";
                Logger::error($message);

                return parent::getText(null, $asset);
            }
        }

        return ''; // default empty string
    }
}
