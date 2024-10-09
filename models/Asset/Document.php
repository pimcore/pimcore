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

namespace Pimcore\Model\Asset;

use Exception;
use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Logger;
use Pimcore\Model;

/**
 * @method Dao getDao()
 */
class Document extends Model\Asset
{
    public const CUSTOM_SETTING_PDF_SCAN_STATUS = 'document_pdf_scan_status';

    protected string $type = 'document';

    protected function update(array $params = []): void
    {
        if ($this->getDataChanged()) {
            $this->removeCustomSetting('document_page_count');
        }

        parent::update($params);

        if ($params['isUpdate']) {
            $this->clearThumbnails();
        }
    }

    /**
     *
     * @internal
     */
    public function processPageCount(string $path = null): bool
    {
        if (!$this->isPageCountProcessingEnabled()) {
            return false;
        }

        if (!\Pimcore\Document::isAvailable()) {
            Logger::error(
                sprintf(
                    "Couldn't create image-thumbnail of document %s as no document adapter is available",
                    $this->getRealFullPath()
                )
            );

            return false;
        }

        try {
            $converter = \Pimcore\Document::getInstance();
            $converter->load($this);

            // read from blob here, because in $this->update() $this->getFileSystemPath() contains the old data
            $pageCount = $converter->getPageCount();
            $this->setCustomSetting('document_page_count', $pageCount);
        } catch (Exception $e) {
            Logger::error((string) $e);
            $this->setCustomSetting('document_page_count', 'failed');
        }

        return true;
    }

    /**
     * returns null when page count wasn't processed yet (done asynchronously)
     *
     */
    public function getPageCount(): ?int
    {
        $pageCount = $this->getCustomSetting('document_page_count');
        if ($pageCount === null || $pageCount === '') {
            return null;
        }

        return (int) $this->getCustomSetting('document_page_count');
    }

    /**
     * @param bool $deferred $deferred deferred means that the image will be generated on-the-fly (details see below)
     */
    public function getImageThumbnail(
        array|string|Image\Thumbnail\Config $thumbnailName,
        int $page = 1,
        bool $deferred = false
    ): Document\ImageThumbnailInterface {
        if (!$this->isThumbnailsEnabled()) {
            return new Document\ImageThumbnail(null);
        }

        if ($this->isPageCountProcessingEnabled()) {
            if (!\Pimcore\Document::isAvailable()) {
                Logger::error(
                    sprintf(
                        "Couldn't create image-thumbnail of document %s as no document adapter is available",
                        $this->getRealFullPath()
                    )
                );

                return new Document\ImageThumbnail(null);
            }

            if (!$this->getCustomSetting('document_page_count')) {
                Logger::info('Image thumbnail not yet available, processing is done asynchronously.');
                $this->addToUpdateTaskQueue();
            }
        }

        return new Document\ImageThumbnail($this, $thumbnailName, $page, $deferred);
    }

    /**
     * @throws Exception
     */
    public function getText(int $page = null): ?string
    {
        if (!$this->isTextProcessingEnabled()) {
            return null;
        }

        if ($this->isPageCountProcessingEnabled()) {
            if (!\Pimcore\Document::isAvailable() || !\Pimcore\Document::isFileTypeSupported($this->getFilename())) {
                Logger::warning(
                    sprintf(
                        "Couldn't get text out of document %s as no supported document adapter is available",
                        $this->getRealFullPath()
                    )
                );
            } elseif (!$this->getCustomSetting('document_page_count')) {
                Logger::info(
                    sprintf(
                        'Unable to fetch text of %s as it was not processed yet by the maintenance script',
                        $this->getRealFullPath()
                    )
                );
            } else {
                $cacheKey = 'asset_document_text_' . $this->getId() . '_' . ($page ? $page : 'all');
                if (!$text = Cache::load($cacheKey)) {
                    $document = \Pimcore\Document::getInstance();
                    $text = $document->getText($page, $this);
                    Cache::save($text, $cacheKey, $this->getCacheTags(), null, 99, true);
                }

                return (string)$text;
            }
        }

        return null;
    }

    public function checkIfPdfContainsJS(): bool
    {
        if (!$this->isPdfScanningEnabled()) {
            return false;
        }

        $this->setCustomSetting(
            self::CUSTOM_SETTING_PDF_SCAN_STATUS,
            Model\Asset\Enum\PdfScanStatus::IN_PROGRESS->value
        );

        $chunkSize = 1024;
        $filePointer = $this->getStream();

        $tagLength = strlen('/JS');

        while ($chunk = fread($filePointer, $chunkSize)) {
            if (strlen($chunk) <= $tagLength) {
                break;
            }

            if (str_contains($chunk, '/JS') || str_contains($chunk, '/JavaScript')) {
                $this->setCustomSetting(
                    self::CUSTOM_SETTING_PDF_SCAN_STATUS,
                    Model\Asset\Enum\PdfScanStatus::UNSAFE->value
                );

                return true;
            }
        }

        $this->setCustomSetting(
            self::CUSTOM_SETTING_PDF_SCAN_STATUS,
            Model\Asset\Enum\PdfScanStatus::SAFE->value
        );

        return true;
    }

    public function getScanStatus(): ?Model\Asset\Enum\PdfScanStatus
    {
        if ($scanStatus = $this->getCustomSetting(self::CUSTOM_SETTING_PDF_SCAN_STATUS)) {
            return Model\Asset\Enum\PdfScanStatus::tryFrom($scanStatus);
        }

        return null;
    }

    private function isThumbnailsEnabled(): bool
    {
        return Config::getSystemConfiguration('assets')['document']['thumbnails']['enabled'];
    }

    private function isPageCountProcessingEnabled(): bool
    {
        return Config::getSystemConfiguration('assets')['document']['process_page_count'];
    }

    private function isTextProcessingEnabled(): bool
    {
        return Config::getSystemConfiguration('assets')['document']['process_text'];
    }

    private function isPdfScanningEnabled(): bool
    {
        return Config::getSystemConfiguration('assets')['document']['scan_pdf'];
    }
}
