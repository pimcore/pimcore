<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Schedule
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\GridConfigFavourite;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\GridConfigFavourite\Listing\Dao getDao()
 * @method Model\GridConfigFavourite[] load()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var array|null
     */
    protected $gridconfigFavourites = null;

    /**
     * @return Model\GridConfigFavourite[]
     */
    public function getGridconfigFavourites()
    {
        if ($this->gridconfigFavourites === null) {
            $this->getDao()->load();
        }

        return $this->gridconfigFavourites;
    }

    /**
     * @param array $gridconfigFavourites
     */
    public function setGridconfigFavourites($gridconfigFavourites)
    {
        $this->gridconfigFavourites = $gridconfigFavourites;
    }
}
