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

use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderList;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderListFilter;

class OrderDateTime implements IOrderListFilter
{
    /**
     * @var \DateTime
     */
    protected $from;

    /**
     * @var \DateTime
     */
    protected $till;

    /**
     * @var string
     */
    protected $column = 'order.orderDate';

    /**
     * @param IOrderList $orderList
     *
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        // init
        $query = $orderList->getQuery();

        if ($this->getFrom()) {
            $query->where($this->getColumn() . ' >= ?', $this->getFrom()->getTimestamp());
        }

        if ($this->getTill()) {
            $query->where($this->getColumn() . ' <= ?', $this->getTill()->getTimestamp());
        }
    }

    /**
     * @return \DateTime
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
     * @return \DateTime
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
