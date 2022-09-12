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

namespace Pimcore\Model\Tool\Email\Blacklist;

use Pimcore\Model;

/**
 * @internal
 *
 * @method \Pimcore\Model\Tool\Email\Blacklist\Listing\Dao getDao()
 * @method void delete()*
 * @method Model\Tool\Email\Blacklist[] load()
 * @method Model\Tool\Email\Blacklist|false current()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @param Model\Tool\Email\Blacklist[]|null $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        return $this->setData($items);
    }

    /**
     * @return Model\Tool\Email\Blacklist[]
     */
    public function getItems()
    {
        return $this->getData();
    }
}
