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
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ProductInterface;

interface CartProductActionAddInterface
{
    /**
     * Track product add to cart
     *
     * @param CartInterface $cart
     * @param ProductInterface $product
     * @param int|float $quantity
     */
    public function trackCartProductActionAdd(CartInterface $cart, ProductInterface $product, $quantity = 1);
}

class_alias(CartProductActionAddInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\Tracking\ICartProductActionAdd');
