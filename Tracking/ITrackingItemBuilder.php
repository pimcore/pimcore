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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking;

use Pimcore\Model\Element\ElementInterface;

interface ITrackingItemBuilder
{
    /**
     * Build a product view object
     *
     * @param \OnlineShop\Framework\Model\IProduct|ElementInterface $product
     * @return ProductAction
     */
    public function buildProductViewItem(\OnlineShop\Framework\Model\IProduct $product);

    /**
     * Build a product action item object
     *
     * @param \OnlineShop\Framework\Model\IProduct|ElementInterface $product
     * @return ProductAction
     */
    public function buildProductActionItem(\OnlineShop\Framework\Model\IProduct $product);

    /**
     * Build a product impression object
     *
     * @param \OnlineShop\Framework\Model\IProduct|ElementInterface $product
     * @return ProductImpression
     */
    public function buildProductImpressionItem(\OnlineShop\Framework\Model\IProduct $product);

    /**
     * Build a checkout transaction object
     *
     * @param \OnlineShop\Framework\Model\AbstractOrder $order
     * @return Transaction
     */
    public function buildCheckoutTransaction(\OnlineShop\Framework\Model\AbstractOrder $order);

    /**
     * Build checkout items
     *
     * @param \OnlineShop\Framework\Model\AbstractOrder $order
     * @return ProductAction[]
     */
    public function buildCheckoutItems(\OnlineShop\Framework\Model\AbstractOrder $order);

    /**
     * Build checkout items by cart
     * 
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return mixed
     */
    public function buildCheckoutItemsByCart(\OnlineShop\Framework\CartManager\ICart $cart);

    /**
     * Build a checkout item object
     *
     * @param \OnlineShop\Framework\Model\AbstractOrder $order
     * @param \OnlineShop\Framework\Model\AbstractOrderItem $orderItem
     * @return ProductAction
     */
    public function buildCheckoutItem(\OnlineShop\Framework\Model\AbstractOrder $order, \OnlineShop\Framework\Model\AbstractOrderItem $orderItem);
}