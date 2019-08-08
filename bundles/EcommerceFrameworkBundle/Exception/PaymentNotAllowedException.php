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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Exception;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;

class PaymentNotAllowedException extends AbstractEcommerceException
{
    /**
     * @var AbstractOrder
     */
    protected $order;

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var bool
     */
    protected $orderNeedsUpdate;

    /**
     * PaymentNotAllowedException constructor.
     *
     * @param string $message
     * @param AbstractOrder $order
     * @param CartInterface $cart
     * @param bool $orderNeedsUpdate
     */
    public function __construct(string $message, AbstractOrder $order, CartInterface $cart = null, bool $orderNeedsUpdate = null)
    {
        parent::__construct($message);

        $this->order = $order;
        $this->cart = $cart;
        $this->orderNeedsUpdate = $orderNeedsUpdate;
    }
}
