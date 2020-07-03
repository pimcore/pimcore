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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter;

use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListFilterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListInterface;

class Payment implements OrderListFilterInterface
{
    const PAYMENT_STATE_OK = 'ok';
    const PAYMENT_STATE_FAIL = 'fail';

    /**
     * @var string
     */
    protected $value;

    /**
     * Allowed origin values
     *
     * @var array
     */
    protected $allowedValues = [
        self::PAYMENT_STATE_OK,
        self::PAYMENT_STATE_FAIL,
    ];

    /**
     * @param string $paymentState
     */
    public function __construct($paymentState)
    {
        if (!in_array($paymentState, $this->allowedValues)) {
            throw new \InvalidArgumentException('Invalid filter value');
        }

        $this->value = $paymentState;
    }

    /**
     * @param OrderListInterface $orderList
     *
     * @return OrderListFilterInterface
     */
    public function apply(OrderListInterface $orderList)
    {
        switch ($this->value) {
            case self::PAYMENT_STATE_OK:
                $orderList->addCondition('order.paymentAuthorizedData_aliasCC IS NOT NULL');
                break;

            case self::PAYMENT_STATE_FAIL:
                $orderList->addCondition('order.paymentAuthorizedData_aliasCC IS NULL');
                break;
        }

        return $this;
    }
}
