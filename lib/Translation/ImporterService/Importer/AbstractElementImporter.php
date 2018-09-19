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
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Translation\AttributeSet\Attribute;
use Pimcore\Translation\AttributeSet\AttributeSet;

class AbstractElementImporter implements ImporterInterface
{
    /**
     * @inheritdoc
     */
    public function import(AttributeSet $attributeSet, bool $saveElement = true)
    {
        $translationItem = $attributeSet->getTranslationItem();
        $element = $translationItem->getElement();

        if (!$element instanceof Element\ElementInterface || $attributeSet->isEmpty()) {
            return;
        }

        foreach ($attributeSet->getAttributes() as $attribute) {
            $targetLanguage = $attributeSet->getTargetLanguages()[0];
            $this->importAttribute($element, $targetLanguage, $attribute);
        }

        if ($saveElement) {
            $this->saveElement($element);
        }
    }

    /**
     * @param Document|DataObject\Concrete $element
     * @param string $targetLanguage
     * @param Attribute $attribute
     *
     * @throws \Exception
     */
    protected function importAttribute(Element\ElementInterface $element, string $targetLanguage, Attribute $attribute)
    {
        if ($attribute->getType() === Attribute::TYPE_PROPERTY) {
            $property = $element->getProperty($attribute->getName(), true);
            if ($property) {
                $property->setData($attribute->getContent());
            } else {
                $element->setProperty($attribute->getName(), 'text', $attribute->getContent());
            }
        }
    }

    /**
     * @param Document|DataObject\Concrete $element
     *
     * @throws \Exception
     */
    protected function saveElement(Element\ElementInterface $element)
    {
        try {
            $element->save();
        } catch (\Exception $e) {
            throw new \Exception('Unable to save ' . Element\Service::getElementType($element) . ' with id ' . $element->getId() . ' because of the following reason: ' . $e->getMessage());
        }
    }
}
