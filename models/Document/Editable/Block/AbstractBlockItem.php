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

namespace Pimcore\Model\Document\Editable\Block;

use Pimcore\Model\Document;

abstract class AbstractBlockItem
{
    /**
     * @var Document\PageSnippet
     */
    protected $document;

    /**
     * @var array
     */
    protected $parentBlockNames;

    /**
     * @var int
     */
    protected $index;

    public function __construct(Document\PageSnippet $document, array $parentBlockNames, int $index)
    {
        $this->document = $document;
        $this->parentBlockNames = $parentBlockNames;
        $this->index = $index;
    }

    abstract protected function getItemType(): string;

    /**
     * @param string $name
     *
     * @return Document\Editable|null
     *
     * @deprecated and will be removed in Pimcore 7. Use getEditable() instead.
     */
    public function getElement(string $name)
    {
        return $this->getEditable($name);
    }

    /**
     * @param string $name
     *
     * @return Document\Editable|null
     */
    public function getEditable(string $name)
    {
        $namingStrategy = \Pimcore::getContainer()->get('pimcore.document.tag.naming.strategy');

        $id = $namingStrategy->buildChildElementTagName($name, $this->getItemType(), $this->parentBlockNames, $this->index);
        $editable = $this->document->getEditable($id);

        if ($editable) {
            $editable->setParentBlockNames($this->parentBlockNames);
        }

        return $editable;
    }

    /**
     * @param string $func
     * @param array $args
     *
     * @return Document\Editable|null
     */
    public function __call($func, $args)
    {
        $element = $this->getEditable($args[0]);
        $class = 'Pimcore\\Model\\Document\\Editable\\' . str_replace('get', '', $func);

        if ($element !== null && !strcasecmp(get_class($element), $class)) {
            return $element;
        }

        return null;
    }
}

class_alias(AbstractBlockItem::class, 'Pimcore\Model\Document\Tag\Block\AbstractBlockItem');
