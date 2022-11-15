<?php

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

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class ImageGallery implements \Iterator, OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @var Hotspotimage[]
     */
    protected $items;

    /**
     * @param Hotspotimage[] $items
     */
    public function __construct($items = [])
    {
        $this->setItems($items);
        $this->markMeDirty();
    }

    /**
     * @return Hotspotimage|false
     */
    #[\ReturnTypeWillChange]
    public function current()// : Hotspotimage|false
    {
        return current($this->items);
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function next()// : void
    {
        next($this->items);
    }

    /**
     * @return int|string|null
     */
    #[\ReturnTypeWillChange]
    public function key()// : mixed
    {
        return key($this->items);
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()// : bool
    {
        return $this->current() !== false;
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function rewind()// : void
    {
        reset($this->items);
    }

    /**
     * @return Hotspotimage[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param Hotspotimage[] $items
     */
    public function setItems($items)
    {
        if (!is_array($items)) {
            $items = [];
        }
        $this->items = $items;
        $this->rewind();
        $this->markMeDirty();
    }

    /**
     * @return bool
     */
    public function hasValidImages(): bool
    {
        foreach ($this->getItems() as $item) {
            if ($item instanceof \Pimcore\Model\DataObject\Data\Hotspotimage) {
                return true;
            }
        }

        return false;
    }
}
