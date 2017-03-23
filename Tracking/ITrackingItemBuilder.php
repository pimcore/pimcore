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
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IProduct|ElementInterface $product
     * @return ProductAction
     */
    public function buildProductViewItem(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IProduct $product);

    /**
     * Build a product action item object
     *
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IProduct|ElementInterface $product
     * @return ProductAction
     */
    public function buildProductActionItem(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IProduct $product);

    /**
     * Build a product impression object
     *
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IProduct|ElementInterface $product
     * @return ProductImpression
     */
    public function buildProductImpressionItem(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IProduct $product);

    /**
     * Build a checkout transaction object
     *
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder $order
     * @return Transaction
     */
    public function buildCheckoutTransaction(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder $order);

    /**
     * Build checkout items
     *
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder $order
     * @return ProductAction[]
     */
    public function buildCheckoutItems(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder $order);

    /**
     * Build checkout items by cart
     * 
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart
     * @return mixed
     */
    public function buildCheckoutItemsByCart(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart);

    /**
     * Build a checkout item object
     *
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder $order
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrderItem $orderItem
     * @return ProductAction
     */
    public function buildCheckoutItem(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder $order, \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrderItem $orderItem);
}