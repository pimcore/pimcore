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

namespace Pimcore\Bundle\XliffBundle\ExportService;

use Pimcore\Bundle\XliffBundle\ExportDataExtractorService\ExportDataExtractorServiceInterface;
use Pimcore\Bundle\XliffBundle\ExportService\Exporter\ExporterInterface;
use Pimcore\Bundle\XliffBundle\TranslationItemCollection\TranslationItemCollection;

class ExportService implements ExportServiceInterface
{
    private ExportDataExtractorServiceInterface $exportDataExtractorService;

    private ExporterInterface $translationExporter;

    /**
     * ExportService constructor.
     *
     */
    public function __construct(
        ExportDataExtractorServiceInterface $exportDataExtractorService,
        ExporterInterface $translationExporter
    ) {
        $this->exportDataExtractorService = $exportDataExtractorService;
        $this->translationExporter = $translationExporter;
    }

    public function exportTranslationItems(TranslationItemCollection $translationItems, string $sourceLanguage, array $targetLanguages, string $exportId = null): string
    {
        $exportId = empty($exportId) ? uniqid() : $exportId;

        foreach ($translationItems->getItems() as $item) {
            $attributeSet = $this->getExportDataExtractorService()->extract($item, $sourceLanguage, $targetLanguages);
            $this->getTranslationExporter()->export($attributeSet, $exportId);
        }

        return $exportId;
    }

    public function getExportDataExtractorService(): ExportDataExtractorServiceInterface
    {
        return $this->exportDataExtractorService;
    }

    public function setExportDataExtractorService(ExportDataExtractorServiceInterface $exportDataExtractorService): ExportService
    {
        $this->exportDataExtractorService = $exportDataExtractorService;

        return $this;
    }

    public function getTranslationExporter(): ExporterInterface
    {
        return $this->translationExporter;
    }

    public function setTranslationExporter(ExporterInterface $translationExporter): ExportService
    {
        $this->translationExporter = $translationExporter;

        return $this;
    }
}
