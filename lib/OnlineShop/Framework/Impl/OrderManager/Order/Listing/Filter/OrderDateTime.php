<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter;

use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListFilter;
use Zend_Date;

class OrderDateTime implements IOrderListFilter
{
    /**
     * @var Zend_Date
     */
    protected $from;

    /**
     * @var Zend_Date
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

        if($this->getFrom())
        {
            $query->where($this->getColumn() . ' >= ?', $this->getFrom()->getTimestamp());
        }

        if($this->getTill())
        {
            $query->where($this->getColumn() . ' <= ?', $this->getTill()->getTimestamp());
        }
    }

    /**
     * @return Zend_Date
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param Zend_Date $from
     *
     * @return $this
     */
    public function setFrom(Zend_Date $from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return Zend_Date
     */
    public function getTill()
    {
        return $this->till;
    }

    /**
     * @param Zend_Date $till
     *
     * @return $this
     */
    public function setTill(Zend_Date $till)
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