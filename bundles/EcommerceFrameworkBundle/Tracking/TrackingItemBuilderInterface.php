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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ProductInterface;

interface TrackingItemBuilderInterface
{
    /**
     * Build a product view object
     *
     * @param ProductInterface $product
     *
     * @return ProductAction
     */
    public function buildProductViewItem(ProductInterface $product);

    /**
     * Build a product action item object
     *
     * @param ProductInterface $product
     * @param int $quantity
     *
     * @return ProductAction
     */
    public function buildProductActionItem(ProductInterface $product, $quantity = 1);

    /**
     * Build a product impression object
     *
     * @param ProductInterface $product
     * @param string $list
     *
     * @return ProductImpression
     */
    public function buildProductImpressionItem(ProductInterface $product, string $list = 'default');

    /**
     * Build a checkout transaction object
     *
     * @param AbstractOrder $order
     *
     * @return Transaction
     */
    public function buildCheckoutTransaction(AbstractOrder $order);

    /**
     * Build checkout items
     *
     * @param AbstractOrder $order
     *
     * @return ProductAction[]
     */
    public function buildCheckoutItems(AbstractOrder $order);

    /**
     * Build checkout items by cart
     *
     * @param CartInterface $cart
     *
     * @return ProductAction[]
     */
    public function buildCheckoutItemsByCart(CartInterface $cart);

    /**
     * Build a checkout item object
     *
     * @param AbstractOrder $order
     * @param AbstractOrderItem $orderItem
     *
     * @return ProductAction
     */
    public function buildCheckoutItem(AbstractOrder $order, AbstractOrderItem $orderItem);
}

class_alias(TrackingItemBuilderInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ITrackingItemBuilder');
