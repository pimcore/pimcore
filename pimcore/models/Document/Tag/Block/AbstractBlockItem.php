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

namespace Pimcore\Model\Document\Tag\Block;

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
     * @return Document\Tag|null
     */
    public function getElement(string $name)
    {
        $namingStrategy = \Pimcore::getContainer()->get('pimcore.document.tag.naming.strategy');

        $id = $namingStrategy->buildChildElementTagName($name, $this->getItemType(), $this->parentBlockNames, $this->index);
        $element = $this->document->getElement($id);

        if ($element) {
            $element->setParentBlockNames($this->parentBlockNames);
        }

        return $element;
    }

    /**
     * @param $func
     * @param $args
     *
     * @return Document\Tag|null
     */
    public function __call($func, $args)
    {
        $element = $this->getElement($args[0]);
        $class = 'Pimcore\\Model\\Document\\Tag\\' . str_replace('get', '', $func);

        if (!strcasecmp(get_class($element), $class)) {
            return $element;
        }
    }
}
