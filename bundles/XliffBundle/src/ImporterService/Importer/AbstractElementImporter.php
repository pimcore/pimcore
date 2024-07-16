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

use Exception;
use Pimcore;
use Pimcore\Bundle\XliffBundle\AttributeSet\Attribute;
use Pimcore\Bundle\XliffBundle\AttributeSet\AttributeSet;
use Pimcore\Bundle\XliffBundle\Event\Model\TranslationXliffEvent;
use Pimcore\Bundle\XliffBundle\Event\XliffEvents;
use Pimcore\Model\Element;

class AbstractElementImporter implements ImporterInterface
{
    public function import(AttributeSet $attributeSet, bool $saveElement = true): void
    {
        $translationItem = $attributeSet->getTranslationItem();
        $element = $translationItem->getElement();

        $event = new TranslationXliffEvent($attributeSet);
        Pimcore::getEventDispatcher()->dispatch($event, XliffEvents::XLIFF_ATTRIBUTE_SET_IMPORT);

        $attributeSet = $event->getAttributeSet();

        if (!$element instanceof Element\ElementInterface || $attributeSet->isEmpty()) {
            return;
        }

        $targetLanguage = $attributeSet->getTargetLanguages()[0];
        foreach ($attributeSet->getAttributes() as $attribute) {
            $this->importAttribute($element, $targetLanguage, $attribute);
        }

        if ($saveElement) {
            $this->saveElement($element);
        }
    }

    /**
     * @throws Exception
     */
    protected function importAttribute(Element\ElementInterface $element, string $targetLanguage, Attribute $attribute): void
    {
        if ($attribute->getType() === Attribute::TYPE_PROPERTY) {
            $property = $element->getProperty($attribute->getName(), true);
            if ($property) {
                $property->setData($attribute->getContent());
            } else {
                $element->setProperty($attribute->getName(), 'text', $attribute->getContent(), false, true);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function saveElement(Element\ElementInterface $element): void
    {
        try {
            $element->save();
        } catch (Exception $e) {
            throw new Exception('Unable to save ' . Element\Service::getElementType($element) . ' with id ' . $element->getId() . ' because of the following reason: ' . $e->getMessage());
        }
    }
}
