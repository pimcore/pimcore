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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
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
    public function __construct($items)
    {
        $this->setItems($items);
        $this->markMeDirty();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $var = current($this->items);

        return $var;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        $var = key($this->items);

        return $var;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        $var = $this->current() !== false;

        return $var;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
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
}
