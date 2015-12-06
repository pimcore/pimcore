<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\PriceSystem;

/**
 * Interface ICachingPriceSystem
 */
interface ICachingPriceSystem extends IPriceSystem {

    /**
     * load price infos once for gives product entries and caches them
     *
     * @param $productEntries
     * @param $options
     * @return mixed
     */
    public function loadPriceInfos($productEntries, $options);

    /**
     * clears cached price infos
     *
     * @param $productEntries
     * @param $options
     * @return mixed
     */
    public function clearPriceInfos($productEntries,$options);

}
