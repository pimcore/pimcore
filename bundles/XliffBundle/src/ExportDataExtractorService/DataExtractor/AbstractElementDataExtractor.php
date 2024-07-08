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

namespace Pimcore\Bundle\XliffBundle\ExportDataExtractorService\DataExtractor;

use Exception;
use Pimcore\Bundle\XliffBundle\AttributeSet\Attribute;
use Pimcore\Bundle\XliffBundle\AttributeSet\AttributeSet;
use Pimcore\Bundle\XliffBundle\TranslationItemCollection\TranslationItem;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Property;

abstract class AbstractElementDataExtractor implements DataExtractorInterface
{
    protected function createResultInstance(TranslationItem $translationItem): AttributeSet
    {
        return new AttributeSet($translationItem);
    }

    /**
     * @param string[] $targetLanguages
     *
     * @throws Exception
     */
    public function extract(TranslationItem $translationItem, string $sourceLanguage, array $targetLanguages): AttributeSet
    {
        $element = $translationItem->getElement();

        if (!$element instanceof ElementInterface) {
            throw new Exception('only pimcore elements allowed');
        }

        $result = $this
                    ->createResultInstance($translationItem)
                    ->setSourceLanguage($sourceLanguage)
                    ->setTargetLanguages($targetLanguages);

        $this->addProperties($element, $result);

        return $result;
    }

    protected function doExportProperty(Property $property): bool
    {
        return $property->getType() === 'text' && !$property->isInherited() && !empty($property->getData());
    }

    protected function addProperties(ElementInterface $element, AttributeSet $result): void
    {
        foreach ($element->getProperties() ?: [] as $property) {
            if ($this->doExportProperty($property)) {
                $result->addAttribute(Attribute::TYPE_PROPERTY, $property->getName(), $property->getData());
            }
        }
    }
}
