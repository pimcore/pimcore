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

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class ImageGallery implements \Iterator, OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @var Hotspotimage[]
     */
    protected array $items;

    /**
     * @param Hotspotimage[] $items
     */
    public function __construct(array $items = [])
    {
        $this->setItems($items);
        $this->markMeDirty();
    }

    /**
     * @return Hotspotimage|false
     */
    #[\ReturnTypeWillChange]
    public function current(): Hotspotimage|bool// : Hotspotimage|false
    {
        return current($this->items);
    }

    #[\ReturnTypeWillChange]
    public function next(): void// : void
    {
        next($this->items);
    }

    #[\ReturnTypeWillChange]
    public function key(): int|string|null// : mixed
    {
        return key($this->items);
    }

    #[\ReturnTypeWillChange]
    public function valid(): bool// : bool
    {
        return $this->current() !== false;
    }

    #[\ReturnTypeWillChange]
    public function rewind(): void// : void
    {
        reset($this->items);
    }

    /**
     * @return Hotspotimage[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param Hotspotimage[] $items
     */
    public function setItems(array $items)
    {
        if (!is_array($items)) {
            $items = [];
        }
        $this->items = $items;
        $this->rewind();
        $this->markMeDirty();
    }
}
