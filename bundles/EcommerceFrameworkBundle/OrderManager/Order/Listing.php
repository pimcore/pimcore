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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order;

use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\AbstractOrderList;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListFilterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListInterface;
use Pimcore\Db;
use Pimcore\Model\DataObject\OnlineShopOrder;
use Pimcore\Model\DataObject\OnlineShopOrderItem;

class Listing extends AbstractOrderList implements OrderListInterface
{
    /**
     * @var Db\ZendCompatibility\QueryBuilder
     */
    protected $query;

    /**
     * @var OrderListFilterInterface[]
     */
    protected $filter = [];

    /**
     * @var bool
     */
    protected $useSubItems = false;

    /**
     * @var null|string[]
     */
    protected $availableFilterValues = null;

    /**
     * @param string $type
     *
     * @return OrderListInterface
     */
    public function setListType($type)
    {
        $this->listType = $type;

        // reset query
        $this->query = null;

        return $this;
    }

    /**
     * @deprecated
     * get select query
     *
     * @return Db\ZendCompatibility\QueryBuilder
     */
    public function getQuery()
    {
        if (!$this->query) {
            // init
            $select = Db::getConnection()->select();

            // base order
            $select->from(
                [ 'order' => 'object_query_' . OnlineShopOrder::classId() ],
                [
                    new Db\ZendCompatibility\Expression('SQL_CALC_FOUND_ROWS 1'), 'OrderId' => 'order.oo_id',
                ]
            );

            // join ordered products
            $this->joinItemsAndSubItems($select);

            // group by list type
            if ($this->getListType() == self::LIST_TYPE_ORDER_ITEM) {
                $select->columns(['Id' => 'orderItem.oo_id']);
                $select->group('OrderItemId');
            } else {
                $select->columns(['Id' => 'order.oo_id']);
                $select->group('OrderId');
            }

            // filter order state
            if (!is_null($this->getOrderState())) {
                $orderStates = [];
                foreach ((array)$this->getOrderState() as $orderState) {
                    $orderStates[] = $select->getAdapter()->quote($orderState);
                }

                $select->where('`order`.orderState IN('. implode(',', $orderStates) .')');
            }

            $this->query = $select;
        }

        return $this->query;
    }

    /**
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit($limit, $offset = 0)
    {
        parent::setLimit($limit, $offset);

        $this->getQuery()->limit($this->getLimit(), $this->getOffset());

        return $this;
    }

    /**
     * @param array|string $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->getQuery()
            ->reset(Db\ZendCompatibility\QueryBuilder::ORDER)
            ->order($order)
        ;

        return $this;
    }

    /**
     * @return $this
     */
    public function joinPricingRule()
    {
        $joins = $this->getQuery()->getPart(Db\ZendCompatibility\QueryBuilder::FROM);

        if (!array_key_exists('pricingRule', $joins)) {
            $this->getQuery()->joinLeft(
                ['pricingRule' => 'object_collection_PricingRule_' . OnlineShopOrderItem::classId()],
                'pricingRule.o_id = orderItem.o_id AND pricingRule.fieldname = "pricingRules"',
                ''
            );
        }

        return $this;
    }

    /**
     * @return $this
     *
     */
    public function joinPriceModifications()
    {
        $joins = $this->getQuery()->getPart(Db\ZendCompatibility\QueryBuilder::FROM);

        if (!array_key_exists('OrderPriceModifications', $joins)) {
            $this->getQuery()->joinLeft(
                ['OrderPriceModifications' => 'object_collection_OrderPriceModifications_' . OnlineShopOrder::classId()],
                'OrderPriceModifications.o_id = order.oo_id AND OrderPriceModifications.fieldname = "priceModifications"',
                ''
            );
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function joinPaymentInfo()
    {
        $joins = $this->getQuery()->getPart(Db\ZendCompatibility\QueryBuilder::FROM);

        if (!array_key_exists('paymentInfo', $joins)) {
            // create sub select
            $paymentQuery = Db::getConnection()->select();

            $paymentQuery
                ->from(
                    ['_paymentInfo' => 'object_collection_PaymentInfo_' . OnlineShopOrder::classId()],
                    [
                        'paymentReference' => 'GROUP_CONCAT(",", _paymentInfo.paymentReference, "," SEPARATOR ",")', 'o_id' => '_order.o_id',
                    ]
                )
                ->join(
                    ['_order' => 'object_' . OnlineShopOrder::classId()],
                    '_order.oo_id = _paymentInfo.o_id',
                    ''
                )
            ;

            // join
            $this->getQuery()->joinLeft(
                ['paymentInfo' => new Db\ZendCompatibility\Expression('(' . $paymentQuery . ')')],
                'paymentInfo.o_id = `order`.oo_id',
                ''
            );
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function joinOrderItemObjects()
    {
        $joins = $this->getQuery()->getPart(Db\ZendCompatibility\QueryBuilder::FROM);

        if (!array_key_exists('orderItemObjects', $joins)) {
            $this->getQuery()->join(
                ['orderItemObjects' => 'objects'],
                'orderItemObjects.o_id = orderItem.product__id',
                ''
            );
        }

        return $this;
    }

    /**
     * @param string $classId
     *
     * @return $this
     */
    public function joinProduct($classId)
    {
        $joins = $this->getQuery()->getPart(Db\ZendCompatibility\QueryBuilder::FROM);

        if (!array_key_exists('product', $joins)) {
            $this->getQuery()->join(
                ['product' => 'object_query_' . $classId],
                'product.oo_id = orderItem.product__id',
                ''
            );
        }

        return $this;
    }

    /**
     * @param mixed $classId
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function joinCustomer($classId)
    {
        $joins = $this->getQuery()->getPart(Db\ZendCompatibility\QueryBuilder::FROM);

        if (!array_key_exists('customer', $joins)) {
            $this->getQuery()->join(
                ['customer' => 'object_' . $classId],
                'customer.o_id = order.customer__id',
                ''
            );
        }

        return $this;
    }

    /**
     * join for item / sub items
     *
     * @param Db\ZendCompatibility\QueryBuilder $select
     *
     * @return $this
     */
    protected function joinItemsAndSubItems($select)
    {
        if (!$this->useSubItems()) {
            // just order items
            $select->join(
                [ '_orderItems' => 'object_relations_' . OnlineShopOrder::classId() ],
                '_orderItems.fieldname = "items" AND _orderItems.src_id = `order`.oo_id',
                ''
            );
        } else {
            // join items and sub items
            $orderClassId = OnlineShopOrder::classId();
            $orderItemClassId = OnlineShopOrderItem::classId();
            $select->join(
                ['_orderItems' => new Db\ZendCompatibility\Expression(
                    <<<SUBQUERY
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
SUBQUERY
                )],
                '_orderItems.orderId = `order`.oo_id',
                ''
            );
        }

        // join related order item
        $select->join(
            [ 'orderItem' => 'object_' . OnlineShopOrderItem::classId() ],
            'orderItem.o_id = _orderItems.dest_id',
            ['OrderItemId' => 'orderItem.oo_id']
        );

        return $this;
    }

    /**
     * @param string $field
     *
     * @return $this
     */
    public function addSelectField($field)
    {
        $this->getQuery()->columns($field);

        return $this;
    }

    /**
     * @param OrderListFilterInterface $filter
     *
     * @return $this
     */
    public function addFilter(OrderListFilterInterface $filter)
    {
        $this->filter[] = $filter;
        $filter->apply($this);

        return $this;
    }

    /**
     * @param string $condition
     * @param string $value
     *
     * @return $this
     */
    public function addCondition($condition, $value = null)
    {
        $this->getQuery()->where($condition, $value);

        return $this;
    }

    /**
     * get all available values that can bee used for filter
     *
     * @param string $field
     *
     * @return array
     *
     * @deprecated refactoring
     */
    protected function getAvailableFilterValues($field)
    {
        if (!$this->availableFilterValues) {
            $listing = new self();

            $query = $listing->getQuery();
            $query = str_replace('-- [GET_AVAILABLE_OPTIONS]', '
                , ifnull(GROUP_CONCAT(DISTINCT product.o_id, "|", product.o_parentId SEPARATOR "|"),0) as "available_productId"
                , ifnull(GROUP_CONCAT(DISTINCT pricingRule.ruleId SEPARATOR "|"),0) as "available_pricingRules"
            ', $query);
            $query = str_replace('GROUP BY orderItem', '', $query);

            $conn = \Pimcore\Db::getConnection();
            $conn->query('SET SESSION group_concat_max_len = 1000000');
            $this->availableFilterValues = $conn->fetchRow($query);
        }

        return explode('|', $this->availableFilterValues['available_' . $field]);
    }

    /**
     * When an object is cloned, PHP 5 will perform a shallow copy of all of the object's properties.
     * Any properties that are references to other variables, will remain references.
     * Once the cloning is complete, if a __clone() method is defined,
     * then the newly created object's __clone() method will be called, to allow any necessary properties that need to be changed.
     * NOT CALLABLE DIRECTLY.
     *
     * @return mixed
     *
     * @link http://php.net/manual/en/language.oop5.cloning.php
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }

    /**
     * @return bool
     */
    public function useSubItems()
    {
        return $this->useSubItems;
    }

    /**
     * @param bool $useSubItems
     *
     * @return $this
     */
    public function setUseSubItems($useSubItems)
    {
        $this->useSubItems = (bool)$useSubItems;

        return $this;
    }
}
