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

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListFilterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListInterface;
use Pimcore\Db\ZendCompatibility\QueryBuilder as ZendCompatibilityQueryBuilder;

class ProductType implements OrderListFilterInterface
{
    /**
     * @var array
     */
    protected $types = [];

    /**
     * @param OrderListInterface $orderList
     *
     * @return OrderListFilterInterface
     */
    public function apply(OrderListInterface $orderList)
    {
        $types = [];
        $orderList->joinOrderItemObjects();

        $db = \Pimcore\Db::get();
        foreach ($this->getTypes() as $type) {
            $types[] = $db->quote($type);
        }
        $queryBuilder = $orderList->getQueryBuilderCompatibility();
        $condition = 'orderItemObjects.o_className IN (' . implode(',', $types) . ')';
        if ($queryBuilder instanceof ZendCompatibilityQueryBuilder) {
            $queryBuilder->where($condition);
        } elseif ($queryBuilder instanceof DoctrineQueryBuilder) {
            $queryBuilder->andWhere($condition);
        }

        return $this;
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
