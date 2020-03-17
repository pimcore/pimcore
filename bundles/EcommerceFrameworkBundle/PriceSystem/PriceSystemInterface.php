<?php

declare(strict_types=1);

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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\CartPriceModificatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\OnlineShopTaxClass;

interface PriceSystemInterface
{
    /**
     * Creates price info object for given product and quantity scale
     *
     * @param CheckoutableInterface&Concrete $product
     * @param null|int|string $quantityScale - Numeric or string (allowed values: PriceInfoInterface::MIN_PRICE)
     * @param CheckoutableInterface[] $products
     *
     * @return PriceInfoInterface
     */
    public function getPriceInfo(CheckoutableInterface $product, $quantityScale = null, $products = null): PriceInfoInterface;

    /**
     * Filters and orders given product IDs based on price information
     *
     * @param array $productIds
     * @param float $fromPrice
     * @param float $toPrice
     * @param string $order
     * @param int $offset
     * @param int $limit
     *
     * @return mixed
     */
    public function filterProductIds($productIds, $fromPrice, $toPrice, $order, $offset, $limit);

    /**
     * Returns OnlineShopTaxClass for given CheckoutableInterface
     *
     * Should be overwritten in custom price systems with suitable implementation.
     *
     * @param CheckoutableInterface&Concrete $product
     *
     * @return OnlineShopTaxClass
     */
    public function getTaxClassForProduct(CheckoutableInterface $product);

    /**
     * Returns OnlineShopTaxClass for given CartPriceModificatorInterface
     *
     * Should be overwritten in custom price systems with suitable implementation.
     *
     * @param CartPriceModificatorInterface $modificator
     *
     * @return OnlineShopTaxClass
     */
    public function getTaxClassForPriceModification(CartPriceModificatorInterface $modificator);
}

class_alias(PriceSystemInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceSystem');
