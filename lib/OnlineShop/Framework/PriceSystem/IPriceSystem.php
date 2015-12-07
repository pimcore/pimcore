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
 * Interface IPriceSystem
 */
interface IPriceSystem {

    /**
     * creates price info object for given product and quantity scale
     *
     * @param \OnlineShop\Framework\Model\ICheckoutable $abstractProduct
     * @param null|int|string $quantityScale - numeric or string (allowed values: \OnlineShop\Framework\PriceSystem\IPriceInfo::MIN_PRICE)
     * @param \OnlineShop\Framework\Model\ICheckoutable[] $products
     * @return IPriceInfo
     */
    public function getPriceInfo(\OnlineShop\Framework\Model\ICheckoutable $abstractProduct, $quantityScale = null, $products = null);

    /**
     * filters and orders given product ids based on price information
     *
     * @param $productIds
     * @param $fromPrice
     * @param $toPrice
     * @param $order
     * @param $offset
     * @param $limit
     * @return mixed
     */
    public function filterProductIds($productIds, $fromPrice, $toPrice, $order, $offset, $limit);



}
