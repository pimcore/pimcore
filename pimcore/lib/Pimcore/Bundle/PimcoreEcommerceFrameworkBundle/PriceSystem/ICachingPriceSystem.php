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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem;

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
