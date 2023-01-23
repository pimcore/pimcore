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

namespace Pimcore\Bundle\XliffBundle\TranslationItemCollection;

use Pimcore\Model\Element;
use Pimcore\Model\Element\ElementInterface;

class TranslationItemCollection
{
    /**
     * @var TranslationItem[]
     */
    private array $items = [];

    public function add(string $type, string $id, ElementInterface $element): TranslationItemCollection
    {
        $this->items[] = new TranslationItem($type, $id, $element);

        return $this;
    }

    public function addItem(TranslationItem $item): TranslationItemCollection
    {
        $this->items[] = $item;

        return $this;
    }

    public function addPimcoreElement(ElementInterface $element): TranslationItemCollection
    {
        $this->items[] = new TranslationItem(Element\Service::getElementType($element), (string) $element->getId(), $element);

        return $this;
    }

    /**
     * @return TranslationItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        $elementsArray = [];
        foreach ($this->getItems() as $element) {
            $elementsArray[$element->getType()] = $elementsArray[$element->getType()] ?? [];
            $elementsArray[$element->getType()][] = $element->getId();
        }

        return $elementsArray;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }
}
