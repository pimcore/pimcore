<?php

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter;

use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListFilterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListInterface;

class OrderDateTime implements OrderListFilterInterface
{
    /**
     * @var \DateTime|null
     */
    protected $from;

    /**
     * @var \DateTime|null
     */
    protected $till;

    /**
     * @var string
     */
    protected $column = 'order.orderDate';

    /**
     * @param OrderListInterface $orderList
     *
     * @return OrderListFilterInterface
     */
    public function apply(OrderListInterface $orderList)
    {
        // init
        $queryBuilder = $orderList->getQueryBuilder();

        if ($this->getFrom()) {
            $queryBuilder->andWhere($this->getColumn() . ' >= :from_date')->setParameter(':from_date', $this->getFrom()->getTimestamp());
        }

        if ($this->getTill()) {
            $queryBuilder->andWhere($this->getColumn() . ' <= :till_date')->setParameter(':till_date', $this->getTill()->getTimestamp());
        }

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param \DateTime $from
     *
     * @return $this
     */
    public function setFrom(\DateTime $from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getTill()
    {
        return $this->till;
    }

    /**
     * @param \DateTime $till
     *
     * @return $this
     */
    public function setTill(\DateTime $till)
    {
        $this->till = $till;

        return $this;
    }

    /**
     * @return string
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @param string $column
     */
    public function setColumn($column)
    {
        $this->column = $column;
    }
}
