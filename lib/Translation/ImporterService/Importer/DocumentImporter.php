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

use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Translation\AttributeSet\Attribute;

class DocumentImporter extends AbstractElementImporter
{
    /**
     * @inheritdoc
     */
    protected function importAttribute(Element\ElementInterface $element, string $targetLanguage, Attribute $attribute)
    {
        parent::importAttribute($element, $targetLanguage, $attribute);

        if ($attribute->getType() === Attribute::TYPE_TAG && $element instanceof Document\PageSnippet) {
            $tag = $element->getEditable($attribute->getName());
            if ($tag) {
                if (in_array($tag->getType(), ['image', 'link'])) {
                    $tag->setText($attribute->getContent());
                } else {
                    $tag->setDataFromEditmode($attribute->getContent());
                }

                $tag->setInherited(false);
                $element->setEditable($tag->getName(), $tag);
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
