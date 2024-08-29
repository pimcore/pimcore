<?php
declare(strict_types=1);

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

use Exception;
use Pimcore\Document\Adapter;
use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Tool\Console;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
class Ghostscript extends Adapter
{
    use TemporaryFileHelperTrait;

    private ?string $version = null;

    public function isAvailable(): bool
    {
        try {
            $ghostscript = self::getGhostscriptCli();
            $phpCli = Console::getPhpCli();
            if ($ghostscript && $phpCli) {
                return true;
            }
        } catch (Exception $e) {
            Logger::notice($e->getMessage());
        }

        return false;
    }

    public function isFileTypeSupported(string $fileType): bool
    {
        // it's also possible to pass a path or filename
        if (preg_match("/\.?pdf$/i", $fileType)) {
            return true;
        }

        return false;
    }

    /**
     *
     * @throws Exception
     */
    public static function getGhostscriptCli(): string
    {
        return Console::getExecutable('gs', true);
    }

    /**
     *
     * @throws Exception
     */
    public static function getPdftotextCli(): string
    {
        return Console::getExecutable('pdftotext', true);
    }

    public function load(Asset\Document $asset): static
    {
        // avoid timeouts
        $maxExecTime = (int) ini_get('max_execution_time');
        if ($maxExecTime > 1 && $maxExecTime < 250) {
            set_time_limit(250);
        }

        if (!$this->isFileTypeSupported($asset->getFilename())) {
            $message = "Couldn't load document " . $asset->getRealFullPath() . ' only PDF documents are currently supported';
            Logger::error($message);

            throw new Exception($message);
        }

        $this->asset = $asset;

        return $this;
    }

    public function getPdf(?Asset\Document $asset = null)
    {
        if (!$asset && $this->asset) {
            $asset = $this->asset;
        }

        if (preg_match("/\.?pdf$/i", $asset->getFilename())) { // only PDF's are supported
            $file = $asset->getStream();
            if (!is_resource($file)) {
                throw new Exception(sprintf('Could not get pdf from asset with id %s', $asset->getId()));
            }

            return $file;
        }

        $message = "Couldn't load document " . $asset->getRealFullPath() . ' only PDF documents are currently supported';
        Logger::error($message);

        throw new Exception($message);
    }

    public function getPageCount(): int
    {
        $process = Process::fromShellCommandline($this->buildPageCountCommand());
        $process->setTimeout(120);
        $process->mustRun();
        $pages = trim($process->getOutput());

        if (! is_numeric($pages)) {
            throw new Exception('Unable to get page-count of ' . $this->asset->getRealFullPath());
        }

        return (int) $pages;
    }

    /**
     *
     * @throws Exception
     */
    protected function buildPageCountCommand(): string
    {
        $command = self::getGhostscriptCli() . ' -dNODISPLAY -q';
        $localFile = self::getLocalFileFromStream($this->getPdf());

        // Adding permit-file-read flag to prevent issue with Ghostscript's SAFER mode which is enabled by default as of version 9.50.
        if (version_compare($this->getVersion(), '9.50', '>=')) {
            $command .= ' --permit-file-read=' . escapeshellarg($localFile);
        }

        $command .= ' -c ' . escapeshellarg('(' . $localFile . ') (r) file runpdfbegin pdfpagecount = quit');

        Console::addLowProcessPriority($command);

        return $command;
    }

    /**
     * Get the version of the installed Ghostscript CLI.
     *
     *
     * @throws Exception
     */
    protected function getVersion(): string
    {
        if (is_null($this->version)) {
            $process = new Process([self::getGhostscriptCli(), '--version']);
            $process->mustRun();
            $this->version = trim($process->getOutput());
        }

        return $this->version;
    }

    public function saveImage(string $imageTargetPath, int $page = 1, int $resolution = 200): mixed
    {
        try {
            $localFile = self::getLocalFileFromStream($this->getPdf());
            $cmd = [self::getGhostscriptCli(), '-sDEVICE=pngalpha', '-dFirstPage=' . $page, '-dLastPage=' . $page, '-dTextAlphaBits=4', '-dGraphicsAlphaBits=4', '-r'. $resolution, '-o', $imageTargetPath, $localFile];
            Console::addLowProcessPriority($cmd);
            $process = new Process($cmd);
            $process->setTimeout(240);
            $process->run();

            return $this;
        } catch (Exception $e) {
            Logger::error((string) $e);

            return false;
        }
    }

    public function getText(?int $page = null, ?Asset\Document $asset = null, ?string $path = null): mixed
    {
        try {
            if (!$asset && $this->asset) {
                $asset = $this->asset;
            }

            if (!$path || !file_exists($path)) {
                if (self::isFileTypeSupported($asset->getFilename())) {
                    $path = $asset->getLocalFile();
                }

                if (empty($path)) {
                    throw new Exception('Could not get local file for asset with id ' . $asset->getId());
                }
            }

            return $this->convertPdfToText($page, $path);
        } catch (Exception $e) {
            Logger::error((string) $e);

            return false;
        }
    }

    /**
     * @throws Exception
     */
    protected function convertPdfToText(?int $page, string $assetPath): string
    {
        try {
            $pdftotextBin = self::getPdftotextCli();
        } catch (Exception $e) {
            $pdftotextBin = false;
        }

        if ($pdftotextBin) {
            try {
                // first try to use poppler's pdftotext, because this produces more accurate results than the txtwrite device from ghostscript
                $cmd = [$pdftotextBin];
                if ($page) {
                    array_push($cmd, '-f', $page, '-l', $page);
                }
                array_push($cmd, $assetPath, '-');
                Console::addLowProcessPriority($cmd);
                $process = new Process($cmd);
                $process->setTimeout(120);
                $process->mustRun();

                return $process->getOutput();
            } catch (ProcessFailedException $e) {
                Logger::debug($e->getMessage());
            }
        }

        // pure ghostscript way
        $cmd = [self::getGhostscriptCli(), '-dBATCH', '-dNOPAUSE', '-sDEVICE=txtwrite'];
        if ($page) {
            array_push($cmd, '-dFirstPage=' . $page, '-dLastPage=' . $page);
        }
        $textFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/pdf-text-extract-' . uniqid() . '.txt';
        array_push($cmd, '-dTextFormat=2', '-sOutputFile=' . $textFile, $assetPath);

        Console::addLowProcessPriority($cmd);
        $process = new Process($cmd);
        $process->setTimeout(120);
        $process->mustRun();

        if (!is_file($textFile)) {
            throw new Exception('File not found: ' . $textFile);
        }

        $text = file_get_contents($textFile);

        // this is a little bit strange the default option -dTextFormat=3 from ghostscript should return utf-8 but it doesn't
        // so we use option 2 which returns UCS-2LE and convert it here back to UTF-8 which works fine
        $text = mb_convert_encoding($text, 'UTF-8', 'UCS-2LE');
        unlink($textFile);

        return $text;
    }
}
