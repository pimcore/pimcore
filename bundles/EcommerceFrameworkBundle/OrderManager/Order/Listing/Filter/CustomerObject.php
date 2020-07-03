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
use Pimcore\Model\Element\ElementInterface;

class CustomerObject implements OrderListFilterInterface
{
    /**
     * @var ElementInterface
     */
    protected $customer;

    /**
     * @param ElementInterface $customer
     */
    public function __construct(ElementInterface $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @param OrderListInterface $orderList
     *
     * @return OrderListFilterInterface
     */
    public function apply(OrderListInterface $orderList)
    {
        $orderList->addCondition('order.customer__id = ?', $this->customer->getId());

        return $this;
    }
}
