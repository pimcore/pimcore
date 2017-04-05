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

use Pimcore\Model\Element\ElementInterface;

interface ITrackingItemBuilder
{
    /**
     * Build a product view object
     *
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct|ElementInterface $product
     * @return ProductAction
     */
    public function buildProductViewItem(\Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct $product);

    /**
     * Build a product action item object
     *
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct|ElementInterface $product
     * @return ProductAction
     */
    public function buildProductActionItem(\Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct $product);

    /**
     * Build a product impression object
     *
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct|ElementInterface $product
     * @return ProductImpression
     */
    public function buildProductImpressionItem(\Pimcore\Bundle\EcommerceFrameworkBundle\Model\IProduct $product);

    /**
     * Build a checkout transaction object
     *
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order
     * @return Transaction
     */
    public function buildCheckoutTransaction(\Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order);

    /**
     * Build checkout items
     *
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order
     * @return ProductAction[]
     */
    public function buildCheckoutItems(\Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order);

    /**
     * Build checkout items by cart
     *
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart
     * @return mixed
     */
    public function buildCheckoutItemsByCart(\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart);

    /**
     * Build a checkout item object
     *
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem $orderItem
     * @return ProductAction
     */
    public function buildCheckoutItem(\Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder $order, \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem $orderItem);
}
