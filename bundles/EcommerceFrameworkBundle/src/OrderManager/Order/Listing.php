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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\AbstractOrderList;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListFilterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListInterface;
use Pimcore\Db;
use Pimcore\Model\DataObject\OnlineShopOrder;
use Pimcore\Model\DataObject\OnlineShopOrderItem;

class Listing extends AbstractOrderList implements OrderListInterface
{
    protected ?DoctrineQueryBuilder $queryBuilder = null;

    /**
     * @var OrderListFilterInterface[]
     */
    protected array $filter = [];

    protected bool $useSubItems = false;

    /**
     * @var null|string[]
     */
    protected ?array $availableFilterValues = null;

    public function setListType(string $type): static
    {
        $this->listType = $type;

        // reset query
        $this->queryBuilder = null;

        return $this;
    }

    /**
     * get query builder
     *
     * @return DoctrineQueryBuilder
     */
    public function getQueryBuilder(): DoctrineQueryBuilder
    {
        if (!$this->queryBuilder) {
            // init
            $queryBuilder = Db::getConnection()->createQueryBuilder();
            $queryBuilder->select(['SQL_CALC_FOUND_ROWS 1', 'order.oo_id AS OrderId']);
            $queryBuilder->from('object_query_' . OnlineShopOrder::classId(), '`order`');

            // join ordered products
            $this->joinItemsAndSubItems($queryBuilder);

            // group by list type
            if ($this->getListType() == self::LIST_TYPE_ORDER_ITEM) {
                $queryBuilder->addSelect(['orderItem.oo_id AS Id']);
                $queryBuilder->groupBy('OrderItemId');
            } else {
                $queryBuilder->addSelect(['order.oo_id as Id']);
                $queryBuilder->groupBy('OrderId');
            }

            // filter order state
            if (!is_null($this->getOrderState())) {
                $orderStates = [];
                foreach ((array)$this->getOrderState() as $orderState) {
                    $orderStates[] = $queryBuilder->expr()->literal($orderState);
                }

                $queryBuilder->andWhere('order.orderState IN('. implode(',', $orderStates) .')');
            }

            $this->queryBuilder = $queryBuilder;
        }

        return $this->queryBuilder;
    }

    /**
     *
     * @return $this
     */
    public function setLimit(int $limit, int $offset = 0): static
    {
        parent::setLimit($limit, $offset);

        $this->getQueryBuilder()
            ->setFirstResult($this->getOffset())
            ->setMaxResults($this->getLimit());

        return $this;
    }

    public function setOrder(string $order): static
    {
        $this->getQueryBuilder()->add('orderBy', $order, false);

        return $this;
    }

    public function joinPricingRule(): static
    {
        $queryBuilder = $this->getQueryBuilder();
        $joins = $queryBuilder->getQueryPart('from');

        if (!array_key_exists('pricingRule', $joins)) {
            $queryBuilder->leftJoin(
                'orderItem',
                'object_collection_PricingRule_' . OnlineShopOrderItem::classId(),
                'pricingRule',
                'pricingRule.id = orderItem.id AND pricingRule.fieldname = "pricingRules"'
            );
        }

        return $this;
    }

    public function joinPriceModifications(): static
    {
        $queryBuilder = $this->getQueryBuilder();

        $joins = $queryBuilder->getQueryPart('from');

        if (!array_key_exists('OrderPriceModifications', $joins)) {
            $queryBuilder->leftJoin(
                '`order`',
                'object_collection_OrderPriceModifications_' . OnlineShopOrder::classId(),
                'OrderPriceModifications',
                'OrderPriceModifications.id = order.oo_id AND OrderPriceModifications.fieldname = "priceModifications"'
            );
        }

        return $this;
    }

    public function joinPaymentInfo(): static
    {
        $queryBuilder = $this->getQueryBuilder();

        $joins = $queryBuilder->getQueryPart('from');

        if (!array_key_exists('paymentInfo', $joins)) {
            // create sub select
            $paymentQueryBuilder = Db::getConnection()->createQueryBuilder();

            $paymentQueryBuilder->select('GROUP_CONCAT(",", _paymentInfo.paymentReference, "," SEPARATOR ",") AS paymentReference', '_order.id AS id')
                ->from('object_collection_PaymentInfo_' . OnlineShopOrder::classId(), '_paymentInfo')
                ->join('_paymentInfo', 'object_' . OnlineShopOrder::classId(), '_order', '_order.oo_id = _paymentInfo.id');

            // join
            $queryBuilder->leftJoin('`order`', (string) $paymentQueryBuilder, 'paymentInfo', 'paymentInfo.id = `order`.oo_id');
        }

        return $this;
    }

    public function joinOrderItemObjects(): static
    {
        $queryBuilder = $this->getQueryBuilder();

        $joins = $queryBuilder->getQueryPart('from');

        if (!array_key_exists('orderItemObjects', $joins)) {
            $queryBuilder->join('orderItem', 'objects', 'orderItemObjects',
                'orderItemObjects.id = orderItem.product__id');
        }

        return $this;
    }

    public function joinProduct(string $classId): static
    {
        $queryBuilder = $this->getQueryBuilder();

        $joins = $queryBuilder->getQueryPart('from');

        if (!array_key_exists('product', $joins)) {
            $queryBuilder->join(
                'orderItem',
                'object_query_' . $classId,
                'product',
                'product.oo_id = orderItem.product__id'
            );
        }

        return $this;
    }

    /**
     * @param string $classId
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function joinCustomer(string $classId): static
    {
        $queryBuilder = $this->getQueryBuilder();

        $joins = $queryBuilder->getQueryPart('from');

        if (!array_key_exists('customer', $joins)) {
            $queryBuilder->join('`order`', 'object_' . $classId, 'customer',
                'customer.id = order.customer__id'
            );
        }

        return $this;
    }

    /**
     * join for item / sub items
     *
     * @param DoctrineQueryBuilder $select
     *
     * @return $this
     */
    protected function joinItemsAndSubItems(DoctrineQueryBuilder $select): static
    {
        if (!$this->useSubItems()) {
            // just order items
            $select->join(
                '`order`',
                'object_relations_' . OnlineShopOrder::classId(),
                '_orderItems',
                '_orderItems.fieldname = "items" AND _orderItems.src_id = order.oo_id'
            );
        } else {
            $select->join('`order`', (string) $this->getOrderItemsSubQuery(), '_orderItems',
                '_orderItems.orderId = order.oo_id'
            );
        }

        // join related order item
        $select->addSelect('orderItem.oo_id AS OrderItemId');
        $select->join('_orderItems', 'object_' . OnlineShopOrderItem::classId(), 'orderItem',
            'orderItem.id = _orderItems.dest_id');

        return $this;
    }

    public function addSelectField(string $field): static
    {
        $this->getQueryBuilder()->addSelect($field);

        return $this;
    }

    public function addFilter(OrderListFilterInterface $filter): static
    {
        $this->filter[] = $filter;
        $filter->apply($this);

        return $this;
    }

    /**
     * @param string $condition
     * @param string|null $value
     *
     * @return $this
     */
    public function addCondition(string $condition, string $value = null): static
    {
        if (null === $value) {
            $value = [];
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $this->getQueryBuilder()->where($condition)->setParameters($value);

        return $this;
    }

    public function useSubItems(): bool
    {
        return $this->useSubItems;
    }

    public function setUseSubItems(bool $useSubItems): static
    {
        $this->useSubItems = $useSubItems;

        return $this;
    }

    private function getOrderItemsSubQuery(): string
    {
        // join items and sub items
        $orderClassId = OnlineShopOrder::classId();
        $orderItemClassId = OnlineShopOrderItem::classId();

        return <<<SUBQUERY
(
    -- add items
    SELECT

        _orderItems.src_id as "orderId"
        , _orderItems.dest_id "dest_id"
        , "item" as "kind"

    FROM `object_relations_{$orderClassId}` AS `_orderItems`

    WHERE 1

        AND _orderItems.fieldname = "items"

    UNION

    -- add sub items (1. level)
    SELECT

        _orderItems.src_id as "orderId"
        , _orderSubItems.dest_id as "dest_id"
        , "subItem" as "kind"

    FROM `object_relations_{$orderClassId}` AS `_orderItems`

        JOIN `object_relations_{$orderItemClassId}` AS `_orderSubItems`
            ON _orderSubItems.fieldname = "subItems" AND _orderSubItems.src_id = _orderItems.dest_id

    WHERE 1

        AND _orderItems.fieldname = "items"
)
SUBQUERY;
    }
}
