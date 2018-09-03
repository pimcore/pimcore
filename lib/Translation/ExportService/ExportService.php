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

namespace Pimcore\Translation\ExportService;

use Pimcore\Translation\ExportDataExtractorService\ExportDataExtractorServiceInterface;
use Pimcore\Translation\ExportService\Exporter\ExporterInterface;
use Pimcore\Translation\TranslationItemCollection\TranslationItemCollection;

class ExportService implements ExportServiceInterface
{
    /**
     * @var ExportDataExtractorServiceInterface
     */
    private $exportDataExtractorService;

    /**
     * @var ExporterInterface
     */
    private $translationExporter;

    /**
     * ExportService constructor.
     *
     * @param ExportDataExtractorServiceInterface $exportDataExtractorService
     * @param ExporterInterface $translationExporter
     */
    public function __construct(
        ExportDataExtractorServiceInterface $exportDataExtractorService,
        ExporterInterface $translationExporter
    ) {
        $this->exportDataExtractorService = $exportDataExtractorService;
        $this->translationExporter = $translationExporter;
    }

    /**
     * @inheritdoc
     */
    public function exportTranslationItems(TranslationItemCollection $translationItems, string $sourceLanguage, array $targetLanguages, string $exportId = null): string
    {
        $exportId = empty($exportId) ? uniqid() : $exportId;

        foreach ($translationItems->getItems() as $item) {
            $attributeSet = $this->getExportDataExtractorService()->extract($item, $sourceLanguage, $targetLanguages);
            $this->getTranslationExporter()->export($attributeSet, $exportId);
        }

        return $exportId;
    }

    /**
     * @return ExportDataExtractorServiceInterface
     */
    public function getExportDataExtractorService(): ExportDataExtractorServiceInterface
    {
        return $this->exportDataExtractorService;
    }

    /**
     * @param ExportDataExtractorServiceInterface $exportDataExtractorService
     *
     * @return ExportService
     */
    public function setExportDataExtractorService(ExportDataExtractorServiceInterface $exportDataExtractorService): ExportService
    {
        $this->exportDataExtractorService = $exportDataExtractorService;

        return $this;
    }

    /**
     * @return ExporterInterface
     */
    public function getTranslationExporter(): ExporterInterface
    {
        return $this->translationExporter;
    }

    /**
     * @param ExporterInterface $translationExporter
     *
     * @return ExportService
     */
    public function setTranslationExporter(ExporterInterface $translationExporter): ExportService
    {
        $this->translationExporter = $translationExporter;

        return $this;
    }
}
