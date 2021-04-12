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

namespace Pimcore\Translation\ImporterService\Importer;

use Pimcore\Model\DataObject;
use Pimcore\Model\Element;
use Pimcore\Translation\AttributeSet\Attribute;
use Pimcore\Translation\ExportDataExtractorService\DataExtractor\DataObjectDataExtractor;

class DataObjectImporter extends AbstractElementImporter
{
    /**
     * @param Element\ElementInterface $element
     * @param string $targetLanguage
     * @param Attribute $attribute
     *
     * @throws \Exception
     */
    protected function importAttribute(Element\ElementInterface $element, string $targetLanguage, Attribute $attribute)
    {
        parent::importAttribute($element, $targetLanguage, $attribute);

        if ($attribute->getType() === Attribute::TYPE_LOCALIZED_FIELD) {
            $setter = 'set' . ucfirst($attribute->getName());
            if (method_exists($element, $setter)) {
                $element->$setter($attribute->getContent(), $targetLanguage);
            }
        }

        if ($attribute->getType() === Attribute::TYPE_BRICK_LOCALIZED_FIELD) {
            list($brickField, $brick, $field) = explode(DataObjectDataExtractor::BRICK_DELIMITER, $attribute->getName());

            $brickGetter = null;
            $brickContainerGetter = 'get' . ucfirst($brickField);
            if ($brickContainer = $element->$brickContainerGetter()) {
                $brickGetter = 'get' . ucfirst($brick);
            }

            if (method_exists($brickContainer, $brickGetter)) {
                $brick = $brickContainer->$brickGetter();
                if ($brick instanceof DataObject\Objectbrick\Data\AbstractData) {
                    $localizedFields = $brick->get('localizedfields');
                    if ($localizedFields instanceof DataObject\Localizedfield) {
                        $localizedFields->setLocalizedValue($field, $attribute->getContent(), $targetLanguage);
                    }
                }
            }
        }

        if ($attribute->getType() === Attribute::TYPE_BLOCK_IN_LOCALIZED_FIELD) {
            list($blockName, $blockIndex, $fieldname, $sourceLanguage) = explode(DataObjectDataExtractor::BLOCK_DELIMITER, $attribute->getName());
            /** @var array $originalBlockData */
            $originalBlockData = $element->{'get' . $blockName}($sourceLanguage);
            $originalBlockItem = $originalBlockData[$blockIndex];
            $originalBlockItemData = $originalBlockItem[$fieldname];

            /** @var array $blockData */
            $blockData = $element->{'get' . $blockName}($targetLanguage);
            $blockItem = !empty($blockData) && $blockData[$blockIndex] ? $blockData[$blockIndex] : $originalBlockItem;

            $blockItemData = !empty($blockData) ? $blockItem[$fieldname] : clone $originalBlockItemData;

            /* @var $blockItemData DataObject\Data\BlockElement */
            $blockItemData->setLanguage($targetLanguage);

            $blockItemData->setData($attribute->getContent());
            $blockItem[$fieldname] = $blockItemData;
            $blockData[$blockIndex] = $blockItem;

            $element->{'set' . $blockName}($blockData, $targetLanguage);
        }

        if ($attribute->getType() === Attribute::TYPE_BLOCK) {
            list($blockName, $blockIndex, $dummy, $fieldname) = explode(DataObjectDataExtractor::BLOCK_DELIMITER, $attribute->getName());
            /** @var array $blockData */
            $blockData = $element->{'get' . $blockName}();
            $blockItem = $blockData[$blockIndex];
            $blockItemData = $blockItem['localizedfields'];
            if (!$blockItemData) {
                $blockItemData = new DataObject\Data\BlockElement('localizedfields', 'localizedfields', new DataObject\Localizedfield());
            }
            /** @var DataObject\Localizedfield $localizedFieldData */
            $localizedFieldData = $blockItemData->getData();
            $localizedFieldData->setLocalizedValue($fieldname, $attribute->getContent(), $targetLanguage);
        }

        if ($attribute->getType() === Attribute::TYPE_FIELD_COLLECTION_LOCALIZED_FIELD) {
            list($fieldCollectionField, $index, $field) = explode(DataObjectDataExtractor::FIELD_COLLECTIONS_DELIMITER, $attribute->getName());

            /** @var DataObject\Fieldcollection $fieldCollection */
            $fieldCollection = $element->{'get' . $fieldCollectionField}();
            if ($fieldCollection) {
                $item = $fieldCollection->get($index);
                /** @var DataObject\Localizedfield $localizedFields */
                if ($item && method_exists($item, 'getLocalizedfields') && ($localizedFields = $item->getLocalizedfields())) {
                    $localizedFields->setLocalizedValue($field, $attribute->getContent(), $targetLanguage);
                }
            }
        }
    }

    /**
     * @param DataObject\Concrete $element
     *
     * @throws \Exception
     */
    protected function saveElement(Element\ElementInterface $element)
    {
        $element->setOmitMandatoryCheck(true);
        parent::saveElement($element);
    }
}
