<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 08/05/2018
 * Time: 14:55
 */

namespace Pimcore\Translation\TranslationItemCollection;

use Pimcore\Model\Element;
use Pimcore\Model\Element\ElementInterface;


class TranslationItemCollection
{
    /**
     * @var TranslationItem[]
     */
    private $items = [];

    /**
     * @param string $type
     * @param string $id
     * @param object $element
     * @return TranslationItemCollection
     */
    public function add(string $type, string $id, $element): TranslationItemCollection
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
        $this->items[] = new TranslationItem(Element\Service::getElementType($element), $element->getId(), $element);

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
        foreach($this->getItems() as $element) {

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
