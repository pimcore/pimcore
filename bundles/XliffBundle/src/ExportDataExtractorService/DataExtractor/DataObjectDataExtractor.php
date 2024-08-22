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
use Locale;
use Pimcore\Bundle\XliffBundle\AttributeSet\Attribute;
use Pimcore\Bundle\XliffBundle\AttributeSet\AttributeSet;
use Pimcore\Bundle\XliffBundle\TranslationItemCollection\TranslationItem;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Tool;

class DataObjectDataExtractor extends AbstractElementDataExtractor
{
    const EXPORTABLE_TAGS = ['input', 'textarea', 'wysiwyg'];

    const BRICK_DELIMITER = '|';

    const FIELD_COLLECTIONS_DELIMITER = '|';

    const BLOCK_DELIMITER = '|';

    protected array $exportAttributes;

    public function __construct(array $exportAttributes = [])
    {
        $this->exportAttributes = $exportAttributes;
    }

    /**
     * @param string[] $targetLanguages
     *
     * @throws Exception
     */
    public function extract(
        TranslationItem $translationItem,
        string $sourceLanguage,
        array $targetLanguages,
        array $exportAttributes = null
    ): AttributeSet {
        $notInheritedSet = $this->extractRawAttributeSet(
            $translationItem,
            $sourceLanguage,
            $targetLanguages,
            $exportAttributes,
            false
        );

        $inheritedSet = $this->extractRawAttributeSet(
            $translationItem,
            $sourceLanguage,
            $targetLanguages,
            $exportAttributes,
            true
        );

        foreach ($inheritedSet->getAttributes() as $attribute) {
            if (!$this->isAttributeIncluded($notInheritedSet, $attribute)) {
                $notInheritedSet->addAttribute(
                    $attribute->getType(),
                    $attribute->getName(),
                    $attribute->getContent(),
                    true
                );
            }
        }

        return $notInheritedSet;
    }

    /**
     * used to extract data either with enabled or disabled inheritance
     *
     * @param string[] $targetLanguages
     *
     * @throws Exception
     */
    private function extractRawAttributeSet(
        TranslationItem $translationItem,
        string $sourceLanguage,
        array $targetLanguages,
        ?array $exportAttributes,
        bool $inherited
    ): AttributeSet {
        return DataObject\Service::useInheritedValues(
            $inherited,
            function () use ($translationItem, $sourceLanguage, $targetLanguages, $exportAttributes) {
                $result = parent::extract($translationItem, $sourceLanguage, $targetLanguages);

                $object = $translationItem->getElement();

                if ($object instanceof DataObject\Folder) {
                    return $result;
                }

                if (!$object instanceof DataObject\Concrete) {
                    throw new Exception('only data objects allowed');
                }

                $this->addLocalizedFields($object, $result, $exportAttributes)
                    ->addLocalizedFieldsInBricks($object, $result, $exportAttributes)
                    ->addBlocks($object, $result, $exportAttributes)
                    ->addLocalizedFieldsInFieldCollections($object, $result, $exportAttributes);

                return $result;
            });
    }

    private function isAttributeIncluded(AttributeSet $attributeSet, Attribute $attribute): bool
    {
        foreach ($attributeSet->getAttributes() as $_attribute) {
            if ($_attribute->getType() === $attribute->getType() &&
                $_attribute->getContent() === $attribute->getContent()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    protected function addLocalizedFields(
        DataObject\Concrete $object,
        AttributeSet $result,
        array $exportAttributes = null
    ): DataObjectDataExtractor {
        /** @var Localizedfields|null $fd */
        $fd = $object->getClass()->getFieldDefinition('localizedfields');
        if ($fd) {
            $definitions = $fd->getFieldDefinitions();

            $locale = str_replace('-', '_', $result->getSourceLanguage());
            if (!Tool::isValidLanguage($locale)) {
                $locale = Locale::getPrimaryLanguage($locale);
            }

            foreach ($definitions as $definition) {
                if (!$this->isFieldExportable($object->getClassName(), $definition, $exportAttributes)) {
                    if ($definition->getFieldtype() === Attribute::TYPE_BLOCK) {
                        $this->addBlocksInLocalizedfields($fd, $definition, $object, $result, $exportAttributes);
                    }

                    continue;
                }

                $content = $object->get($definition->getName(), $locale);

                $targetContent = [];
                foreach ($result->getTargetLanguages() as $targetLanguage) {
                    if (Tool::isValidLanguage($targetLanguage)) {
                        $targetContent[$targetLanguage] = $object->get($definition->getName(), $targetLanguage);
                    }
                }

                if (!empty($content) && is_string($content)) {
                    $result->addAttribute(
                        Attribute::TYPE_LOCALIZED_FIELD,
                        $definition->getName(),
                        $content,
                        false,
                        $targetContent
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    protected function addBlocksInLocalizedfields(
        Localizedfields $fd,
        Data $definition,
        DataObject\Concrete $object,
        AttributeSet $result,
        array $exportAttributes = null
    ): void {
        $locale = str_replace('-', '_', $result->getSourceLanguage());
        if (!Tool::isValidLanguage($locale)) {
            $locale = Locale::getPrimaryLanguage($locale);
        }

        $blockElements = $object->get($definition->getName(), $locale);

        $targetBlockElements = [];
        foreach ($result->getTargetLanguages() as $targetLanguage) {
            if (Tool::isValidLanguage($targetLanguage)) {
                $targetBlockElements[$targetLanguage] = $object->get($definition->getName(), $targetLanguage);
            }
        }

        if ($blockElements) {
            foreach ($blockElements as $index => $blockElementFields) {
                /** @var DataObject\Data\BlockElement $blockElement */
                foreach ($blockElementFields as $bIndex => $blockElement) {
                    // check allowed datatypes
                    if (!in_array($blockElement->getType(), self::EXPORTABLE_TAGS)) {
                        continue;
                    }

                    $content = $blockElement->getData();

                    $targetContent = [];
                    foreach ($targetBlockElements as $targetBlockLanguage => $targetBlockElement) {
                        if (isset($targetBlockElement[$index][$bIndex])) {
                            $targetContent[$targetBlockLanguage] = $targetBlockElement[$index][$bIndex]->getData();
                        }
                    }

                    if (!empty($content) && is_string($content)) {
                        $name =
                            $definition->getName() .
                            self::BLOCK_DELIMITER . $index .
                            self::BLOCK_DELIMITER . $blockElement->getName() .
                            self::BLOCK_DELIMITER . $locale;

                        $result->addAttribute(
                            Attribute::TYPE_BLOCK_IN_LOCALIZED_FIELD,
                            $name,
                            $content,
                            false,
                            $targetContent
                        );
                    }
                }
            }
        }
    }

    protected function addBlocksInLocalizedFieldCollections(
        Data $definition,
        DataObject\Localizedfield $localizedField,
        Data $fieldCollectionDefinition,
        mixed $fieldCollectionItem,
        AttributeSet $result,
        string $locale
    ): void {
        $blockElements = $localizedField->getLocalizedValue($definition->getName(), $locale);

        $targetBlockElements = [];
        foreach ($result->getTargetLanguages() as $targetLanguage) {
            if (Tool::isValidLanguage($targetLanguage)) {
                $targetBlockElements[$targetLanguage] = $localizedField->getLocalizedValue(
                    $definition->getName(),
                    $targetLanguage
                );
            }
        }

        if ($blockElements) {
            foreach ($blockElements as $blockElementIndex => $blockElementFields) {
                /** @var DataObject\Data\BlockElement $blockElement */
                foreach ($blockElementFields as $blockIndex => $blockElement) {
                    // check allowed datatypes
                    if (!in_array($blockElement->getType(), self::EXPORTABLE_TAGS)) {
                        continue;
                    }

                    $content = $blockElement->getData();

                    $targetContent = [];
                    foreach ($targetBlockElements as $targetBlockLanguage => $targetBlockElement) {
                        if (isset($targetBlockElement[$blockElementIndex][$blockIndex])) {
                            $targetContent[$targetBlockLanguage] =
                                $targetBlockElement[$blockElementIndex][$blockIndex]->getData();
                        }
                    }

                    if (!empty($content) && is_string($content)) {
                        $name =
                            $fieldCollectionDefinition->getName() .
                            self::BLOCK_DELIMITER . $fieldCollectionItem->getIndex() .
                            self::BLOCK_DELIMITER . $definition->getName() .
                            self::BLOCK_DELIMITER . $blockElementIndex .
                            self::BLOCK_DELIMITER . $blockElement->getName() .
                            self::BLOCK_DELIMITER . $locale;

                        $result->addAttribute(
                            Attribute::TYPE_BLOCK_IN_LOCALIZED_FIELD_COLLECTION,
                            $name,
                            $content,
                            false,
                            $targetContent
                        );
                    }
                }
            }
        }
    }

    /**
     *
     *
     * @throws Exception
     */
    protected function addBlocks(
        DataObject\Concrete $object,
        AttributeSet $result,
        array $exportAttributes = null
    ): DataObjectDataExtractor {
        $locale = str_replace('-', '_', $result->getSourceLanguage());
        if (!Tool::isValidLanguage($locale)) {
            $locale = Locale::getPrimaryLanguage($locale);
        }

        $fieldDefinitions = $object->getClass()->getFieldDefinitions();
        foreach ($fieldDefinitions as $fd) {
            if ($fd instanceof DataObject\ClassDefinition\Data\Block) {
                /** @var DataObject\ClassDefinition\Data\Localizedfields|null $blockLocalizedFieldDefinition */
                $blockLocalizedFieldDefinition = $fd->getFieldDefinition('localizedfields');
                if ($blockLocalizedFieldDefinition) {
                    $blockLocalizedFieldsDefinitions = $blockLocalizedFieldDefinition->getFieldDefinitions();

                    /** @var array $blocks */
                    $blocks = $object->get($fd->getName());

                    if ($blocks) {
                        $blockIdx = -1;
                        /** @var DataObject\Data\BlockElement[] $blockItems */
                        foreach ($blocks as $blockItems) {
                            $blockIdx++;
                            if ($blockItems) {
                                foreach ($blockItems as $blockItem) {
                                    if ($blockItem->getType() == 'localizedfields') {
                                        /** @var DataObject\Localizedfield $blockItemData */
                                        $blockItemData = $blockItem->getData();

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

                                            $targetContent = [];
                                            foreach ($result->getTargetLanguages() as $targetLanguage) {
                                                if (Tool::isValidLanguage($targetLanguage)) {
                                                    $targetContent[$targetLanguage] = $blockItemData->getLocalizedValue(
                                                        $blockLocalizedFieldDefinition->getName(),
                                                        $targetLanguage
                                                    );
                                                }
                                            }

                                            if (!empty($content)) {
                                                $name =
                                                    $fd->getName() .
                                                    self::BLOCK_DELIMITER . $blockIdx .
                                                    self::BLOCK_DELIMITER . 'localizedfield' .
                                                    self::BLOCK_DELIMITER . $blockLocalizedFieldDefinition->getName();

                                                $result->addAttribute(
                                                    Attribute::TYPE_BLOCK,
                                                    $name,
                                                    $content,
                                                    false,
                                                    $targetContent
                                                );
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
     *
     *
     * @throws Exception
     */
    protected function addLocalizedFieldsInBricks(
        DataObject\Concrete $object,
        AttributeSet $result,
        array $exportAttributes = null
    ): DataObjectDataExtractor {
        $locale = str_replace('-', '_', $result->getSourceLanguage());
        if (!Tool::isValidLanguage($locale)) {
            $locale = Locale::getPrimaryLanguage($locale);
        }

        if ($fieldDefinitions = $object->getClass()->getFieldDefinitions()) {
            foreach ($fieldDefinitions as $fieldDefinition) {
                if (!$fieldDefinition instanceof Data\Objectbricks) {
                    continue;
                }

                if (!$brickContainer = $object->get($fieldDefinition->getName())) {
                    continue;
                }

                foreach ($fieldDefinition->getAllowedTypes() ?: [] as $brickType) {
                    $brickGetter = 'get' . ucfirst($brickType);

                    /** @var DataObject\Objectbrick\Data\AbstractData $brick */
                    if (!$brick = $brickContainer->$brickGetter()) {
                        continue;
                    }

                    $brickDefinition = DataObject\Objectbrick\Definition::getByKey($brickType);

                    $localizedFieldsDefinition = $brickDefinition->getFieldDefinition('localizedfields');
                    if (!$localizedFieldsDefinition instanceof Localizedfields) {
                        continue;
                    }

                    $localizedFields = $brick->get('localizedfields');

                    if (!$localizedFields instanceof DataObject\Localizedfield) {
                        continue;
                    }

                    foreach ($localizedFieldsDefinition->getFieldDefinitions() ?: [] as $fd) {
                        //relations are loaded from dependencies
                        if ($fd instanceof Data\Relations\AbstractRelations) {
                            continue;
                        }

                        // check allowed data-types
                        if (!in_array($fd->getFieldtype(), self::EXPORTABLE_TAGS)) {
                            continue;
                        }

                        $content = $localizedFields->getLocalizedValue($fd->getName(), $locale);

                        $targetContent = [];
                        foreach ($result->getTargetLanguages() as $targetLanguage) {
                            if (Tool::isValidLanguage($targetLanguage)) {
                                $targetContent[$targetLanguage] = $localizedFields->getLocalizedValue(
                                    $fd->getName(),
                                    $targetLanguage
                                );
                            }
                        }

                        if (!empty($content)) {
                            $name =
                                $fieldDefinition->getName() .
                                self::BRICK_DELIMITER . $brickType .
                                self::BRICK_DELIMITER . $fd->getName();

                            $result->addAttribute(
                                Attribute::TYPE_BRICK_LOCALIZED_FIELD,
                                $name,
                                $content,
                                false,
                                $targetContent
                            );
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    protected function addLocalizedFieldsInFieldCollections(
        DataObject\Concrete $object,
        AttributeSet $result,
        array $exportAttributes = null
    ): DataObjectDataExtractor {
        $locale = str_replace('-', '_', $result->getSourceLanguage());
        if (!Tool::isValidLanguage($locale)) {
            $locale = Locale::getPrimaryLanguage($locale);
        }

        if ($fieldDefinitions = $object->getClass()->getFieldDefinitions()) {
            foreach ($fieldDefinitions as $fieldDefinition) {
                if (!$fieldDefinition instanceof Data\Fieldcollections) {
                    continue;
                }

                $fieldCollection = $object->get($fieldDefinition->getName());

                if (!$fieldCollection instanceof DataObject\Fieldcollection) {
                    continue;
                }

                $itemFieldDefinitions = $fieldCollection->getItemDefinitions();
                if (empty($itemFieldDefinitions)) {
                    continue;
                }

                $items = $fieldCollection->getItems();
                $items = $items ?: [];

                foreach ($items as $item) {
                    $type = $item->getType();

                    $definition = $itemFieldDefinitions[$type] ?? null;
                    if (!$definition instanceof DataObject\Fieldcollection\Definition) {
                        continue;
                    }

                    $localizedFieldsDefinition = $definition->getFieldDefinition('localizedfields');
                    if (!$localizedFieldsDefinition instanceof Localizedfields) {
                        continue;
                    }

                    $localizedFields = $item->get('localizedfields');

                    if (!$localizedFields instanceof DataObject\Localizedfield) {
                        continue;
                    }

                    foreach ($localizedFieldsDefinition->getFieldDefinitions() ?: [] as $fd) {
                        //relations are loaded from dependencies
                        if ($fd instanceof Data\Relations\AbstractRelations) {
                            continue;
                        }

                        if ($fd->getFieldtype() === Attribute::TYPE_BLOCK) {
                            $this->addBlocksInLocalizedFieldCollections(
                                $fd,
                                $localizedFields,
                                $fieldDefinition,
                                $item,
                                $result,
                                $locale
                            );

                            continue;
                        }

                        // check allowed data-types
                        if (!in_array($fd->getFieldtype(), self::EXPORTABLE_TAGS)) {
                            continue;
                        }

                        $content = $localizedFields->getLocalizedValue($fd->getName(), $locale);

                        $targetContent = [];
                        foreach ($result->getTargetLanguages() as $targetLanguage) {
                            if (Tool::isValidLanguage($targetLanguage)) {
                                $targetContent[$targetLanguage] = $localizedFields->getLocalizedValue(
                                    $fd->getName(),
                                    $targetLanguage
                                );
                            }
                        }

                        if (!empty($content) && is_string($content)) {
                            $name =
                                $fieldDefinition->getName() .
                                self::FIELD_COLLECTIONS_DELIMITER . $item->getIndex() .
                                self::FIELD_COLLECTIONS_DELIMITER . $fd->getName();

                            $result->addAttribute(
                                Attribute::TYPE_FIELD_COLLECTION_LOCALIZED_FIELD,
                                $name,
                                $content,
                                false,
                                $targetContent
                            );
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
