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

/**
 * Base filter for LIKE queries. For simple queries you'll just
 * need to override the getConditionColumn() method and return
 * the query part coming before LIKE.
 */
abstract class AbstractSearch implements IOrderListFilter
{
    /**
     * Search value
     * @var string
     */
    protected $value;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = trim($value);
    }

    /**
     * Return the string coming before LIKE, e.g. 'order.invoiceEmail'
     *
     * @return string
     */
    abstract protected function getConditionColumn();

    /**
     * Pad the value with wildcards
     *
     * @return string
     */
    protected function getConditionValue()
    {
        return '%' . $this->value . '%';
    }

    /**
     * @param IOrderList $orderList
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        if (empty($this->value)) {
            return $orderList;
        }

        $this->prepareApply($orderList);

        $query = sprintf('%s LIKE ?', $this->getConditionColumn());
        $value = $this->getConditionValue();

        $orderList->addCondition($query, $value);
        return $this;
    }

    /**
     * Override if necessary (e.g. join a table)
     *
     * @param IOrderList $orderList
     */
    protected function prepareApply(IOrderList $orderList)
    {
    }
}