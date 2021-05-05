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

namespace Pimcore\Model\GridConfigFavourite;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\GridConfigFavourite\Listing\Dao getDao()
 * @method Model\GridConfigFavourite[] load()
 * @method Model\GridConfigFavourite current()
 *
 * @internal
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return Model\GridConfigFavourite[]
     */
    public function getGridconfigFavourites()
    {
        return $this->getData();
    }

    /**
     * @param array $gridconfigFavourites
     *
     * @return static
     */
    public function setGridconfigFavourites($gridconfigFavourites)
    {
        return $this->setData($gridconfigFavourites);
    }
}
