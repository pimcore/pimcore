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

namespace Pimcore\Bundle\XliffBundle\ImporterService\Importer;

use Pimcore\Bundle\XliffBundle\AttributeSet\Attribute;
use Pimcore\Model\Document;
use Pimcore\Model\Element;

class DocumentImporter extends AbstractElementImporter
{
    protected function importAttribute(Element\ElementInterface $element, string $targetLanguage, Attribute $attribute): void
    {
        if ($targetLanguage != $element->getProperty('language')) {
            return;
        }

        parent::importAttribute($element, $targetLanguage, $attribute);

        if ($attribute->getType() === Attribute::TYPE_TAG && $element instanceof Document\PageSnippet) {
            $editable = $element->getEditable($attribute->getName());
            if ($editable) {
                if ($editable instanceof Document\Editable\Image || $editable instanceof Document\Editable\Link) {
                    $editable->setText($attribute->getContent());
                } else {
                    $editable->setDataFromEditmode($attribute->getContent());
                }

                $editable->setInherited(false);
                $element->setEditable($editable);
            }
        }

        if ($element instanceof Document\Page && ($attribute->getType() === Attribute::TYPE_SETTINGS || $attribute->getType() === Attribute::TYPE_ELEMENT_KEY)) {
            $setter = 'set' . ucfirst($attribute->getName());
            if (method_exists($element, $setter)) {
                $content = $attribute->getContent();
                $content = $attribute->getType() === Attribute::TYPE_ELEMENT_KEY ? Element\Service::getValidKey($content, 'document') : $content;

                $element->$setter($content);
            }
        }
    }
}
