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
use Gotenberg\Gotenberg as GotenbergAPI;
use Gotenberg\Stream;
use Pimcore\Config;
use Pimcore\Helper\GotenbergHelper;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Tool\Storage;

/**
 * @internal
 */
class Gotenberg extends Ghostscript
{
    public function isAvailable(): bool
    {
        try {
            $lo = self::checkGotenberg();
            if ($lo && parent::isAvailable()) { // GhostScript is necessary for pdf count, pdf to text conversion
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
        if (preg_match("/\.?(pdf|doc|docx|odt|xls|xlsx|ods|ppt|pptx|odp)$/i", $fileType)) {
            return true;
        }

        return false;
    }

    /**
     *
     * @throws Exception
     */
    public static function checkGotenberg(): bool
    {
        return GotenbergHelper::isAvailable();
    }

    public function load(Asset\Document $asset): static
    {
        // avoid timeouts
        $maxExecTime = (int) ini_get('max_execution_time');
        if ($maxExecTime > 1 && $maxExecTime < 250) {
            set_time_limit(250);
        }

        if (!$this->isFileTypeSupported($asset->getFilename())) {
            $message = "Couldn't load document " . $asset->getRealFullPath() . ' only Microsoft/Libre/Open-Office/PDF documents are currently supported';
            Logger::error($message);

            throw new Exception($message);
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

    public function getPdf(?Asset\Document $asset = null)
    {
        if (!$asset && $this->asset) {
            $asset = $this->asset;
        }

        try {
            // if the document is already an PDF, delegate the call directly to parent::getPdf() (Ghostscript)
            if (parent::isFileTypeSupported($asset->getFilename())) {
                return parent::getPdf($asset);
            }
        } catch (Exception $e) {
            // nothing to do, delegate to gotenberg
        }

        $storage = Storage::get('asset_cache');

        $storagePath = sprintf(
            '%s/%s/pdf-thumb__%s__libreoffice-document.png',
            rtrim($asset->getRealPath(), '/'),
            $asset->getId(),
            $asset->getId(),
        );

        if (!$storage->fileExists($storagePath)) {
            $localAssetTmpPath = $asset->getLocalFile();

            try {
                $request = GotenbergAPI::libreOffice(Config::getSystemConfiguration('gotenberg')['base_url'])
                    ->convert(
                        Stream::path($localAssetTmpPath)
                    );

                $response = GotenbergAPI::send($request);
                $fileContent = $response->getBody()->getContents();
                $storage->write($storagePath, $fileContent);

                $stream = fopen('php://memory', 'r+');
                fwrite($stream, $fileContent);
                rewind($stream);

                return $stream;
            } catch (Exception $e) {
                $message = "Couldn't convert document to PDF: " . $asset->getRealFullPath() . ' with Gotenberg: ';
                Logger::error($message. $e->getMessage());

                throw $e;
            }
        }

        return $storage->readStream($storagePath);
    }

    public function getText(?int $page = null, ?Asset\Document $asset = null, ?string $path = null): mixed
    {
        if (!$asset && $this->asset) {
            $asset = $this->asset;
        }

        if ($page) {
            // for per page extraction we have to convert the document to PDF and extract the text via ghostscript
            return parent::getText($page, $asset, $path);
        }

        // if asset is pdf extract via ghostscript
        if (parent::isFileTypeSupported($asset->getFilename())) {
            return parent::getText(null, $asset, $path);
        }

        if ($this->isFileTypeSupported($asset->getFilename())) {
            $storagePath = sprintf(
                '%s/%s/pdf-thumb__%s__libreoffice-document.png',
                rtrim($asset->getRealPath(), '/'),
                $asset->getId(),
                $asset->getId(),
            );

            $storage = Storage::get('asset_cache');

            $temp = tmpfile();

            if (!$storage->fileExists($storagePath)) {
                stream_copy_to_stream($this->getPdf($asset), $temp);
            } else {
                $data = $storage->readStream($storagePath);
                stream_copy_to_stream($storage->readStream($storagePath), $temp);
            }

            return parent::convertPdfToText($page, stream_get_meta_data($temp)['uri']);
        }

        return '';
    }
}
