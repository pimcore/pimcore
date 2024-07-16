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
