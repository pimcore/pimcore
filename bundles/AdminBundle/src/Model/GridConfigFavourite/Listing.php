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

namespace Pimcore\Bundle\AdminBundle\Model\GridConfigFavourite;

use Pimcore\Bundle\AdminBundle\Model\GridConfigFavourite;
use Pimcore\Model;

/**
 * @method GridConfigFavourite\Listing\Dao getDao()
 * @method GridConfigFavourite[] load()
 * @method GridConfigFavourite|false current()
 *
 * @internal
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return GridConfigFavourite[]
     */
    public function getGridconfigFavourites(): array
    {
        return $this->getData();
    }

    /**
     * @param GridConfigFavourite[]|null $gridconfigFavourites
     *
     * @return $this
     */
    public function setGridconfigFavourites(?array $gridconfigFavourites): static
    {
        return $this->setData($gridconfigFavourites);
    }
}
