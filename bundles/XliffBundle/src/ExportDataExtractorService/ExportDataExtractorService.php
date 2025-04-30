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

namespace Pimcore\Bundle\XliffBundle\ExportDataExtractorService;

use Exception;
use Pimcore\Bundle\XliffBundle\AttributeSet\AttributeSet;
use Pimcore\Bundle\XliffBundle\ExportDataExtractorService\DataExtractor\DataExtractorInterface;
use Pimcore\Bundle\XliffBundle\TranslationItemCollection\TranslationItem;

class ExportDataExtractorService implements ExportDataExtractorServiceInterface
{
    /**
     * @var DataExtractorInterface[]
     */
    private array $dataExtractors;

    /**
     *
     *
     * @throws Exception
     */
    public function extract(TranslationItem $translationItem, string $sourceLanguage, array $targetLanguages): AttributeSet
    {
        return $this->getDataExtractor($translationItem->getType())->extract($translationItem, $sourceLanguage, $targetLanguages);
    }

    /**
     *
     * @return $this
     */
    public function registerDataExtractor(string $type, DataExtractorInterface $dataExtractor): ExportDataExtractorServiceInterface
    {
        $this->dataExtractors[$type] = $dataExtractor;

        return $this;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getDataExtractor(string $type): DataExtractorInterface
    {
        if (isset($this->dataExtractors[$type])) {
            return $this->dataExtractors[$type];
        }

        throw new Exception(sprintf('no data extractor for type "%s" registered', $type));
    }
}
