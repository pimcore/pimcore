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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\Order\Listing\Filter;


use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\IOrderList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\IOrderListFilter;

class ProductType implements IOrderListFilter
{
    /**
     * @var array
     */
    protected $types = [];

    /**
     * @param IOrderList $orderList
     *
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        $orderList->joinOrderItemObjects();
        $orderList->getQuery()->where('orderItemObjects.o_className IN (?)', $this->getTypes());
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param array $types
     *
     * @return $this
     */
    public function setTypes(array $types)
    {
        $this->types = $types;
        return $this;
    }
}