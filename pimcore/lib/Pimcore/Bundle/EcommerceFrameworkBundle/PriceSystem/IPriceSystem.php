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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\ICartPriceModificator;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Model\Object\OnlineShopTaxClass;

/**
 * Interface IPriceSystem
 */
interface IPriceSystem
{
    /**
     * creates price info object for given product and quantity scale
     *
     * @param ICheckoutable $abstractProduct
     * @param null|int|string $quantityScale - numeric or string (allowed values: \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo::MIN_PRICE)
     * @param ICheckoutable[] $products
     *
     * @return IPriceInfo
     */
    public function getPriceInfo(ICheckoutable $abstractProduct, $quantityScale = null, $products = null);

    /**
     * filters and orders given product ids based on price information
     *
     * @param $productIds
     * @param $fromPrice
     * @param $toPrice
     * @param $order
     * @param $offset
     * @param $limit
     *
     * @return mixed
     */
    public function filterProductIds($productIds, $fromPrice, $toPrice, $order, $offset, $limit);

    /**
     * Returns OnlineShopTaxClass for given ICheckoutable.
     *
     * Should be overwritten in custom price systems with suitable implementation.
     *
     * @param ICheckoutable $product
     *
     * @return OnlineShopTaxClass
     */
    public function getTaxClassForProduct(ICheckoutable $product);

    /**
     * Returns OnlineShopTaxClass for given ICartPriceModificator
     *
     * Should be overwritten in custom price systems with suitable implementation.
     *
     * @param ICartPriceModificator $modificator
     * @param $environment
     *
     * @return OnlineShopTaxClass
     */
    public function getTaxClassForPriceModification(ICartPriceModificator $modificator);
}
