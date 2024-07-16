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

use Iterator;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class ImageGallery implements Iterator, OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @var array<int, Hotspotimage|null>
     */
    protected array $items;

    /**
     * @param array<int, Hotspotimage|null> $items
     */
    public function __construct(array $items = [])
    {
        $this->setItems($items);
        $this->markMeDirty();
    }

    public function current(): Hotspotimage|null|false
    {
        return current($this->items);
    }

    public function next(): void
    {
        next($this->items);
    }

    public function key(): int|string|null
    {
        return key($this->items);
    }

    public function valid(): bool
    {
        return $this->current() !== false;
    }

    public function rewind(): void
    {
        reset($this->items);
    }

    /**
     * @return array<int, Hotspotimage|null>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array<int, Hotspotimage|null> $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
        $this->rewind();
        $this->markMeDirty();
    }

    public function hasValidImages(): bool
    {
        foreach ($this->getItems() as $item) {
            if ($item instanceof Hotspotimage) {
                return true;
            }
        }

        return false;
    }
}
