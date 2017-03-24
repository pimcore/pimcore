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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking;

interface ICheckoutStep
{
    /**
     * Track checkout step
     *
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\ICheckoutStep $step
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart         $cart
     * @param null                                $stepNumber
     *
     * @return
     */
    public function trackCheckoutStep(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\ICheckoutStep $step,
                                      \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart,
                                      $stepNumber = null, $checkoutOption = null);
}