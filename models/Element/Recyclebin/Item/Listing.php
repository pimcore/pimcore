<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
    public function getItems(): array
    {
        return $this->getData();
    }

    public function setItems(array $items): static
    {
        return $this->setData($items);
    }
}
