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

namespace Pimcore\Bundle\XliffBundle\ImporterService\Importer;

use Pimcore\Bundle\XliffBundle\AttributeSet\Attribute;
use Pimcore\Bundle\XliffBundle\ExportDataExtractorService\DataExtractor\DataObjectDataExtractor;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element;

class DataObjectImporter extends AbstractElementImporter
{
    protected function importAttribute(Element\ElementInterface $element, string $targetLanguage, Attribute $attribute): void
    {
        parent::importAttribute($element, $targetLanguage, $attribute);

        if ($attribute->getType() === Attribute::TYPE_LOCALIZED_FIELD) {
            $setter = 'set' . ucfirst($attribute->getName());
            if (method_exists($element, $setter)) {
                $element->$setter($attribute->getContent(), $targetLanguage);
            }
        }

        if ($attribute->getType() === Attribute::TYPE_BRICK_LOCALIZED_FIELD) {
            [$brickField, $brick, $field] = explode(DataObjectDataExtractor::BRICK_DELIMITER, $attribute->getName());

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
            [$blockName, $blockIndex, $fieldname, $sourceLanguage] = explode(DataObjectDataExtractor::BLOCK_DELIMITER, $attribute->getName());
            /** @var array $originalBlockData */
            $originalBlockData = $element->{'get' . $blockName}($sourceLanguage);
            $originalBlockItem = $originalBlockData[$blockIndex] ?? null;
            $originalBlockItemData = $originalBlockItem[$fieldname] ?? null;

            /** @var array $blockData */
            $blockData = $element->{'get' . $blockName}($targetLanguage);
            $blockItem =  isset($blockData[$blockIndex]) ? $blockData[$blockIndex] : $originalBlockItem;
            /** @var DataObject\Data\BlockElement $blockItemData */
            $blockItemData = !empty($blockData) ? clone $blockItem[$fieldname] : clone $originalBlockItemData;

            $blockItemData->setLanguage($targetLanguage);

            $blockItemData->setData($attribute->getContent());

            $blockItem[$fieldname] = $blockItemData;
            $blockData[$blockIndex] = $blockItem;

            $element->{'set' . $blockName}($blockData, $targetLanguage);
        }

        if ($attribute->getType() === Attribute::TYPE_BLOCK_IN_LOCALIZED_FIELD_COLLECTION) {
            [
                $fieldCollectionName,
                $fieldCollectionItemIndex,
                $blockName,
                $blockIndex,
                $fieldname,
                $sourceLanguage
            ] = explode(DataObjectDataExtractor::BLOCK_DELIMITER, $attribute->getName());

            /** @var DataObject\Fieldcollection|null $fieldCollection */
            $fieldCollection = $element->{'get' . $fieldCollectionName}();

            if ($fieldCollection) {
                $item = $fieldCollection->get((int) $fieldCollectionItemIndex);
                /** @var DataObject\Localizedfield $localizedFields */
                if ($item) {
                    /** @var array $originalBlockData */
                    $originalBlockData = $item->{'get' . $blockName}($sourceLanguage);
                    $originalBlockItem = $originalBlockData[$blockIndex] ?? null;
                    $originalBlockItemData = $originalBlockItem[$fieldname] ?? null;

                    /** @var array $blockData */
                    $blockData = $item->{'get' . $blockName}($targetLanguage);
                    $blockItem = isset($blockData[$blockIndex]) ? $blockData[$blockIndex] : $originalBlockItem;

                    /** @var DataObject\Data\BlockElement $blockItemData */
                    $blockItemData = !empty($blockData) ? clone $blockItem[$fieldname] : clone $originalBlockItemData;

                    $blockItemData->setLanguage($targetLanguage);

                    $blockItemData->setData($attribute->getContent());

                    $blockItem[$fieldname] = $blockItemData;
                    $blockData[$blockIndex] = $blockItem;

                    $item->{'set' . $blockName}($blockData, $targetLanguage);
                }
            }
        }

        if ($attribute->getType() === Attribute::TYPE_BLOCK) {
            [
                $blockName,
                $blockIndex,
                $dummy,
                $fieldname
            ] = explode(DataObjectDataExtractor::BLOCK_DELIMITER, $attribute->getName());
            /** @var array $blockData */
            $blockData = $element->{'get' . $blockName}();
            $blockItem = $blockData[$blockIndex];
            $blockItemData = $blockItem['localizedfields'];
            if (!$blockItemData) {
                $blockItemData = new DataObject\Data\BlockElement(
                    'localizedfields',
                    'localizedfields',
                    new DataObject\Localizedfield()
                );
            }
            /** @var DataObject\Localizedfield $localizedFieldData */
            $localizedFieldData = $blockItemData->getData();
            $localizedFieldData->setLocalizedValue($fieldname, $attribute->getContent(), $targetLanguage);
        }

        if ($attribute->getType() === Attribute::TYPE_FIELD_COLLECTION_LOCALIZED_FIELD) {
            [
                $fieldCollectionField,
                $index,
                $field
            ] = explode(DataObjectDataExtractor::FIELD_COLLECTIONS_DELIMITER, $attribute->getName());

            /** @var DataObject\Fieldcollection|null $fieldCollection */
            $fieldCollection = $element->{'get' . $fieldCollectionField}();
            if ($fieldCollection) {
                $item = $fieldCollection->get((int) $index);
                /** @var DataObject\Localizedfield $localizedFields */
                if (
                    $item &&
                    method_exists($item, 'getLocalizedfields') &&
                    ($localizedFields = $item->getLocalizedfields())
                ) {
                    $localizedFields->setLocalizedValue($field, $attribute->getContent(), $targetLanguage);
                }
            }
        }
    }

    protected function saveElement(Element\ElementInterface $element): void
    {
        if ($element instanceof DataObject\Concrete) {
            $isDirtyDetectionDisabled = DataObject::isDirtyDetectionDisabled();

            try {
                DataObject::disableDirtyDetection();
                $element->setOmitMandatoryCheck(true);
                parent::saveElement($element);
            } finally {
                DataObject::setDisableDirtyDetection($isDirtyDetectionDisabled);
            }
        }
    }
}
