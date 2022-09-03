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

namespace Pimcore\Model\Element\Recyclebin\Item;

use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Element\Recyclebin\Item\Listing\Dao getDao()
 * @method Model\Element\Recyclebin\Item[] load()
 * @method Model\Element\Recyclebin\Item|false current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return Model\Element\Recyclebin\Item[]
     */
    public function getItems()
    {
        return $this->getData();
    }

    /**
     * @param array $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        return $this->setData($items);
    }
}
