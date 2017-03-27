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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager;

/**
 * Class \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\AbstractStep
 */
abstract class AbstractStep implements ICheckoutStep {

    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart
     */
    protected $cart;

    public function __construct(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart) {
        $this->cart = $cart;
    }

}
