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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Document\Adapter;

use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Tool\Console;
use Pimcore\Tool\Storage;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
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
            Logger::notice($e->getMessage());
        }

        return false;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function load(Asset\Document $asset)
    {
        // avoid timeouts
        $maxExecTime = (int) ini_get('max_execution_time');
        if ($maxExecTime > 1 && $maxExecTime < 250) {
            set_time_limit(250);
        }

        if (!$this->isFileTypeSupported($asset->getFilename())) {
            $message = "Couldn't load document " . $asset->getRealFullPath() . ' only Microsoft/Libre/Open-Office/PDF documents are currently supported';
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
     * {@inheritdoc}
     */
    public function getPdf(?Asset\Document $asset = null)
    {
        if (!$asset && $this->asset) {
            $asset = $this->asset;
        }

        try {
            // if the document is already an PDF, delegate the call directly to parent::getPdf() (Ghostscript)
            $stream = parent::getPdf($asset);

            return $stream;
        } catch (\Exception $e) {
            // nothing to do, delegate to libreoffice
        }

        $storagePath = sprintf('%s/pdf-thumb__%s__libreoffice-document.png',
            rtrim($asset->getRealPath(), '/'),
            $asset->getId(),
        );
        $storage = Storage::get('asset_cache');

        $lock = \Pimcore::getContainer()->get(LockFactory::class)->createLock('soffice');
        if (!$storage->fileExists($storagePath)) {

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

            $logFile = PIMCORE_LOG_DIRECTORY . '/libreoffice-pdf-convert.log';
            $tmpHandle = fopen($logFile, 'a');
            $process->wait(function ($type, $buffer) use ($tmpHandle) {
                fwrite($tmpHandle, $buffer);
            });
            fclose($tmpHandle);

            $out = $process->getOutput();
            $lock->release();

            Logger::debug('LibreOffice Output was: ' . $out);

            $tmpName = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . preg_replace("/\." . File::getFileExtension($asset->getFilename()) . '$/', '.pdf', $asset->getFilename());
            if (file_exists($tmpName)) {
                $storage->write($storagePath, file_get_contents($tmpName));
                unlink($tmpName);
                unlink($logFile);
            } else {
                $message = "Couldn't convert document to PDF: " . $asset->getRealFullPath() . " with the command: '" . $process->getCommandLine() . "'";
                Logger::error($message);
                throw new \Exception($message);
            }
        }

        return $storage->readStream($storagePath);
    }

    /**
     * {@inheritdoc}
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
                $message = "Couldn't convert document to Text: " . $asset->getRealFullPath() . " with the command: '" . $process->getCommandLine() . "' - now trying to get the text out of the PDF with ghostscript...";
                Logger::error($message);

                return parent::getText(null, $asset);
            }
        }

        return ''; // default empty string
    }
}
