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

namespace Pimcore\Translation\ExportDataExtractorService\DataExtractor;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Tool;
use Pimcore\Translation\AttributeSet\Attribute;
use Pimcore\Translation\AttributeSet\AttributeSet;
use Pimcore\Translation\TranslationItemCollection\TranslationItem;

class DataObjectDataExtractor extends AbstractElementDataExtractor
{
    const EXPORTABLE_TAGS = ['input', 'textarea', 'wysiwyg'];

    const BRICK_DELIMITER = '|';

    /**
     * @var array
     */
    protected $exportAttributes;

    public function __construct(array $exportAttributes = [])
    {
        $this->exportAttributes = $exportAttributes;
    }

    /**
     * @param TranslationItem $translationItem
     * @param string $sourceLanguage
     * @param string[] $targetLanguages
     *
     * @return AttributeSet
     *
     * @throws \Exception
     */
    public function extract(TranslationItem $translationItem, string $sourceLanguage, array $targetLanguages, array $exportAttributes = null): AttributeSet
    {
        $notInheritedSet = $this->extractRawAttributeSet($translationItem, $sourceLanguage, $targetLanguages, $exportAttributes, false);
        $inheritedSet = $this->extractRawAttributeSet($translationItem, $sourceLanguage, $targetLanguages, $exportAttributes, true);

        foreach ($inheritedSet->getAttributes() as $attribute) {
            if (!$this->isAttributeIncluded($notInheritedSet, $attribute)) {
                $notInheritedSet->addAttribute($attribute->getType(), $attribute->getName(), $attribute->getContent(), true);
            }
        }

        return $notInheritedSet;
    }

    /**
     * used to extract data either with enabled or disabled inheritance
     *
     * @param TranslationItem $translationItem
     * @param string $sourceLanguage
     * @param string[] $targetLanguages
     *
     * @return AttributeSet
     *
     * @throws \Exception
     */
    private function extractRawAttributeSet(TranslationItem $translationItem, string $sourceLanguage, array $targetLanguages, array $exportAttributes = null, bool $inherited): AttributeSet
    {
        $inheritedBackup = DataObject\AbstractObject::getGetInheritedValues();
        DataObject\AbstractObject::setGetInheritedValues($inherited);

        $result = parent::extract($translationItem, $sourceLanguage, $targetLanguages);

        $object = $translationItem->getElement();

        if ($object instanceof DataObject\Folder) {
            DataObject\AbstractObject::setGetInheritedValues($inheritedBackup);

            return $result;
        }

        if (!$object instanceof DataObject\Concrete) {
            throw new \Exception('only data objects allowed');
        }

        $this->addLocalizedFields($object, $result, $exportAttributes)
            ->addLocalizedFieldsInBricks($object, $result, $exportAttributes)
            ->addBlocks($object, $result, $exportAttributes);

        DataObject\AbstractObject::setGetInheritedValues($inheritedBackup);

        return $result;
    }

    private function isAttributeIncluded(AttributeSet $attributeSet, Attribute $attribute): bool
    {
        foreach ($attributeSet->getAttributes() as $_attribute) {
            if ($_attribute->getType() === $attribute->getType() && $_attribute->getContent() === $attribute->getContent()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param (DataObject\Concrete $document
     * @param AttributeSet $result
     *
     * @return DataObjectDataExtractor
     *
     * @throws \Exception
     */
    protected function addLocalizedFields(DataObject\Concrete $object, AttributeSet $result, array $exportAttributes = null): DataObjectDataExtractor
    {
        /**
         * @var Localizedfields $fd
         */
        if ($fd = $object->getClass()->getFieldDefinition('localizedfields')) {
            $definitions = $fd->getFielddefinitions();

            $locale = str_replace('-', '_', $result->getSourceLanguage());
            if (!Tool::isValidLanguage($locale)) {
                $locale = \Locale::getPrimaryLanguage($locale);
            }

            /**
             * @var Data $definition
             */
            foreach ($definitions as $definition) {
                if (!$this->isFieldExportable($object->getClassName(), $definition, $exportAttributes)) {
                    continue;
                }

                $content = $object->{'get' . ucfirst($definition->getName())}($locale);

                if (!empty($content)) {
                    $result->addAttribute(Attribute::TYPE_LOCALIZED_FIELD, $definition->getName(), $content);
                }
            }
        }

        return $this;
    }

    /**
     * @param DataObject\Concrete $object
     * @param AttributeSet $result
     * @param array|null $exportAttributes
     *
     * @return DataObjectDataExtractor
     *
     * @throws \Exception
     */
    protected function addBlocks(
        DataObject\Concrete $object,
        AttributeSet $result,
        array $exportAttributes = null
    ): DataObjectDataExtractor {
        $locale = str_replace('-', '_', $result->getSourceLanguage());
        if (!Tool::isValidLanguage($locale)) {
            $locale = \Locale::getPrimaryLanguage($locale);
        }

        $fieldDefinitions = $object->getClass()->getFieldDefinitions();
        foreach ($fieldDefinitions as $fd) {
            if ($fd instanceof DataObject\ClassDefinition\Data\Block) {

                /** @var $blockLocalizedFieldDefinition DataObject\ClassDefinition\Data\Localizedfields */
                $blockLocalizedFieldDefinition = $fd->getFielddefinition('localizedfields');
                if ($blockLocalizedFieldDefinition) {
                    $blockLocalizedFieldsDefinitions = $blockLocalizedFieldDefinition->getFieldDefinitions();

                    /** @var $blockItems array */
                    $blocks = $object->{'get'.ucfirst($fd->getName())}();

                    if ($blocks) {
                        /** @var $blockItem DataObject\Data\BlockElement */
                        $blockIdx = -1;
                        foreach ($blocks as $blockItems) {
                            $blockIdx++;
                            if ($blockItems) {
                                /** @var $blockItem DataObject\Data\BlockElement */
                                foreach ($blockItems as $blockItem) {
                                    if ($blockItem->getType() == 'localizedfields') {

                                        /** @var DataObject\Localizedfield $blockItemData */
                                        $blockItemData = $blockItem->getData();

                                        /** @var $blockLocalizedFieldDefinition DataObject\ClassDefinition\Data */
                                        foreach ($blockLocalizedFieldsDefinitions as $blockLocalizedFieldDefinition) {
                                            // check allowed datatypes
                                            if (!in_array(
                                                $blockLocalizedFieldDefinition->getFieldtype(),
                                                self::EXPORTABLE_TAGS
                                            )) {
                                                continue;
                                            }

                                            $content = $blockItemData->getLocalizedValue(
                                                $blockLocalizedFieldDefinition->getName(),
                                                $locale
                                            );

                                            if (!empty($content)) {
                                                $name = $fd->getName(
                                                    ).'-'.$blockIdx.'-localizedfield-'.$blockLocalizedFieldDefinition->getName(
                                                    );
                                                $result->addAttribute(Attribute::TYPE_BLOCK, $name, $content);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param DataObject\Concrete $object
     * @param AttributeSet $result
     * @param array|null $exportAttributes
     *
     * @return DataObjectDataExtractor
     *
     * @throws \Exception
     */
    protected function addLocalizedFieldsInBricks(
        DataObject\Concrete $object,
        AttributeSet $result,
        array $exportAttributes = null
    ): DataObjectDataExtractor {
        $locale = str_replace('-', '_', $result->getSourceLanguage());
        if (!Tool::isValidLanguage($locale)) {
            $locale = \Locale::getPrimaryLanguage($locale);
        }

        if ($fieldDefinitions = $object->getClass()->getFieldDefinitions()) {
            foreach ($fieldDefinitions as $fieldDefinition) {
                if (!$fieldDefinition instanceof Data\Objectbricks) {
                    continue;
                }

                $brickContainerGetter = 'get'.ucfirst($fieldDefinition->getName());
                if (!$brickContainer = $object->$brickContainerGetter()) {
                    continue;
                }

                foreach ($fieldDefinition->getAllowedTypes() ?: [] as $brickType) {
                    $brickGetter = 'get'.ucfirst($brickType);

                    /**
                     * @var DataObject\Objectbrick\Data\AbstractData $brick
                     */
                    if (!$brick = $brickContainer->$brickGetter()) {
                        continue;
                    }

                    $brickDefinition = DataObject\Objectbrick\Definition::getByKey($brickType);

                    if (!$localizedFieldsDefinition = $brickDefinition->getFieldDefinition('localizedfields')) {
                        continue;
                    }

                    if (!$localizedFields = $brick->getLocalizedfields()) {
                        continue;
                    }

                    foreach ($localizedFieldsDefinition->getFieldDefinitions() ?: [] as $fd) {
                        $content = $localizedFields->getLocalizedValue($fd->getName(), $locale);

                        if (!empty($content)) {
                            $name = $fieldDefinition->getName() . self::BRICK_DELIMITER . $brickType . self::BRICK_DELIMITER . $fd->getName();
                            $result->addAttribute(Attribute::TYPE_BRICK_LOCALIZED_FIELD, $name, $content);
                        }
                    }
                }
            }
        }

        return $this;
    }

    protected function isFieldExportable(string $className, Data $definition, array $exportAttributes = null): bool
    {
        // check allowed datatypes
        if (!in_array($definition->getFieldtype(), self::EXPORTABLE_TAGS)) {
            return false;
        }

        if (is_null($exportAttributes) && isset($this->exportAttributes[$className])) {
            $exportAttributes = $this->exportAttributes[$className];
        }

        if (!empty($exportAttributes) && !in_array($definition->getName(), $exportAttributes)) {
            return false;
        }

        return true;
    }
}
