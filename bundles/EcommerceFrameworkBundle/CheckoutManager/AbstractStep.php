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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;

abstract class AbstractStep implements CheckoutStepInterface
{
    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * AbstractStep constructor.
     *
     * @param CartInterface $cart
     * @param array $options
     */
    public function __construct(CartInterface $cart, array $options = [])
    {
        $this->cart = $cart;
        $this->options = $options;
    }
}
