<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\XliffBundle\ExportService;

use Exception;
use Pimcore\Bundle\XliffBundle\ExportService\Exporter\ExporterInterface;
use Pimcore\Bundle\XliffBundle\TranslationItemCollection\TranslationItemCollection;

interface ExportServiceInterface
{
    /**
     *
     *
     * @throws Exception
     */
    public function exportTranslationItems(TranslationItemCollection $translationItems, string $sourceLanguage, array $targetLanguages, ?string $exportId = null): string;

    public function getTranslationExporter(): ExporterInterface;
}
