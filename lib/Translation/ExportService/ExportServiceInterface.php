<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 09/05/2018
 * Time: 12:49
 */

namespace Pimcore\Translation\ExportService;

use Pimcore\Translation\ExportService\Exporter\ExporterInterface;
use Pimcore\Translation\TranslationItemCollection\TranslationItemCollection;

interface ExportServiceInterface
{
    /**
     * @param TranslationItemCollection $translationItems
     * @param string $sourceLanguage
     * @param array $targetLanguages
     * @param string|null $exportId
     * @return string
     *
     * @throws \Exception
     */
    public function exportTranslationItems(TranslationItemCollection $translationItems, string $sourceLanguage, array $targetLanguages, string $exportId = null): string;

    /**
     * @return ExporterInterface
     */
    public function getTranslationExporter(): ExporterInterface;

}
