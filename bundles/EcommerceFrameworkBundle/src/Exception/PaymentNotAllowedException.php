<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Exception;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;

class PaymentNotAllowedException extends AbstractEcommerceException
{
    protected AbstractOrder $order;

    protected ?CartInterface $cart = null;

    protected ?bool $orderNeedsUpdate = null;

    /**
     * PaymentNotAllowedException constructor.
     *
     * @param string $message
     * @param AbstractOrder $order
     * @param CartInterface|null $cart
     * @param bool|null $orderNeedsUpdate
     */
    public function __construct(string $message, AbstractOrder $order, CartInterface $cart = null, bool $orderNeedsUpdate = null)
    {
        parent::__construct($message);

        $this->order = $order;
        $this->cart = $cart;
        $this->orderNeedsUpdate = $orderNeedsUpdate;
    }
}
