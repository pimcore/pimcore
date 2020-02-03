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

namespace Pimcore\Translation\ExportDataExtractorService;

use Pimcore\Translation\AttributeSet\AttributeSet;
use Pimcore\Translation\ExportDataExtractorService\DataExtractor\DataExtractorInterface;
use Pimcore\Translation\TranslationItemCollection\TranslationItem;

class ExportDataExtractorService implements ExportDataExtractorServiceInterface
{
    /**
     * @var DataExtractorInterface[]
     */
    private $dataExtractors;

    public function extract(TranslationItem $translationItem, string $sourceLanguage, array $targetLanguages): AttributeSet
    {
        return $this->getDataExtractor($translationItem->getType())->extract($translationItem, $sourceLanguage, $targetLanguages);
    }

    /**
     * @param DataExtractorInterface $dataExtractor
     *
     * @return $this
     */
    public function registerDataExtractor(string $type, DataExtractorInterface $dataExtractor): ExportDataExtractorServiceInterface
    {
        $this->dataExtractors[$type] = $dataExtractor;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return DataExtractorInterface
     *
     * @throws \Exception
     */
    public function getDataExtractor(string $type): DataExtractorInterface
    {
        if (isset($this->dataExtractors[$type])) {
            return $this->dataExtractors[$type];
        }

        throw new \Exception(sprintf('no data extractor for type "%s" registered', $type));
    }
}
