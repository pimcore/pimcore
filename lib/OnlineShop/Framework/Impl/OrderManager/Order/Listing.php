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


namespace OnlineShop\Framework\Impl\OrderManager\Order;

use OnlineShop\Framework\Impl\OrderManager;
use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListFilter;
use Pimcore\Model\Object\OnlineShopOrderItem;
use Pimcore\Model\Object\OnlineShopOrder;
use Zend_Db_Expr;
use Zend_Db_Select;

use \Pimcore\Resource;

class Listing extends OrderManager\AbstractOrderList implements IOrderList
{
    /**
     * @var Zend_Db_Select
     */
    protected $query;

    /**
     * @var IOrderListFilter[]
     */
    protected $filter = [];

    /**
     * @var bool
     */
    protected $useSubItems = false;


    /**
     * @param string $type
     *
     * @return IOrderList
     */
    public function setListType($type)
    {
        $this->listType = $type;

        // reset query
        $this->query = null;

        return $this;
    }


    /**
     * get select query
     * @return Zend_Db_Select
     */
    public function getQuery()
    {
        if(!$this->query)
        {
            // init
            $select = Resource::getConnection()->select();


            // base order
            $select->from(
                [ 'order' => 'object_query_' . OnlineShopOrder::classId() ]
                , [
                    new Zend_Db_Expr('SQL_CALC_FOUND_ROWS 1')
                    , 'OrderId' => 'order.oo_id'
                ]
            );


            // join ordered products
            $this->joinItemsAndSubItems($select);


            // group by list type
            if($this->getListType() == self::LIST_TYPE_ORDER_ITEM)
            {
                $select->columns(['Id' => 'orderItem.oo_id']);
                $select->group('OrderItemId');
            }
            else
            {
                $select->columns(['Id' => 'order.oo_id']);
                $select->group('OrderId');
            }


            // filter order state
            if(!is_null($this->getOrderState())) {
                $select->where('`order`.orderState IN(?)', implode(',',(array)$this->getOrderState()));
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

        $this->getQuery()->limit( $this->getLimit(), $this->getOffset() );

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
            ->reset( Zend_Db_Select::ORDER )
            ->order( $order )
        ;

        return $this;
    }


    /**
     * @return $this
     */
    public function joinPricingRule()
    {
        $this->getQuery()->join(
            ['pricingRule' => 'object_collection_PricingRule_' . OnlineShopOrderItem::classId()]
            , 'pricingRule.o_id = orderItem.o_id AND pricingRule.fieldname = "pricingRules"'
            , ''
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function joinPaymentInfo()
    {
        // create sub select
        $paymentQuery = Resource::getConnection()->select();
        $paymentQuery
            ->from(
                ['_paymentInfo' => 'object_collection_PaymentInfo_' . OnlineShopOrder::classId()]
                , [
                    'paymentReference' => 'GROUP_CONCAT(",", _paymentInfo.paymentReference, "," SEPARATOR ",")'
                    , 'o_id' => '_order.o_id'
                ]
            )
            ->join(
                ['_order' => 'object_' . OnlineShopOrder::classId()]
                , '_order.oo_id = _paymentInfo.o_id'
                , ''
            )
        ;

        // join
        $this->getQuery()->joinLeft(
            ['paymentInfo' => new Zend_Db_Expr( '(' . $paymentQuery . ')' )]
            , 'paymentInfo.o_id = `order`.oo_id'
            , ''
        );

        return $this;
    }


    /**
     * @return $this
     */
    public function joinOrderItemObjects()
    {
        $this->getQuery()->join(
            ['orderItemObjects' => 'objects']
            , 'orderItemObjects.o_id = orderItem.product__id'
            , ''
        );

        return $this;
    }

    /**
     * @param int $classId
     *
     * @return $this
     */
    public function joinProduct($classId)
    {
        $this->getQuery()->join(
            ['product' => 'object_query_' . (int)$classId]
            , 'product.oo_id = orderItem.product__id'
            , ''
        );

        return $this;
    }


    /**
     * @param int $classId
     *
     * @return $this
     */
    public function joinCustomer($classId)
    {
        $this->getQuery()->join(
            ['customer' => 'object_' . (int)$classId]
            , 'customer.o_id = order.customer__id'
            , ''
        );

        return $this;
    }


    /**
     * join for item / sub items
     * @param Zend_Db_Select $select
     *
     * @return $this
     */
    protected function joinItemsAndSubItems(Zend_Db_Select $select)
    {
        if(!$this->useSubItems())
        {
            // just order items
            $select->join(
                [ '_orderItems' => 'object_relations_' . OnlineShopOrder::classId() ]
                , '_orderItems.fieldname = "items" AND _orderItems.src_id = `order`.oo_id'
                , ''
            );
        }
        else
        {
            // join items and sub items
            $select->join(
                ['_orderItems' => new \Zend_Db_Expr( <<<SUBQUERY
(
    -- add items
    SELECT

        _orderItems.src_id as "orderId"
        , _orderItems.dest_id "dest_id"
        , "item" as "kind"

    FROM `object_relations_109` AS `_orderItems`

    WHERE 1

        AND _orderItems.fieldname = "items"

    UNION

    -- add sub items (1. level)
    SELECT

        _orderItems.src_id as "orderId"
        , _orderSubItems.dest_id as "dest_id"
        , "subItem" as "kind"

    FROM `object_relations_109` AS `_orderItems`

        JOIN `object_relations_108` AS `_orderSubItems`
            ON _orderSubItems.fieldname = "subItems" AND _orderSubItems.src_id = _orderItems.dest_id

    WHERE 1

        AND _orderItems.fieldname = "items"
)
SUBQUERY
                )]
                , '_orderItems.orderId = `order`.oo_id'
                , ''
            );
        }


        // join related order item
        $select->join(
            [ 'orderItem' => 'object_' . OnlineShopOrderItem::classId() ]
            , 'orderItem.o_id = _orderItems.dest_id'
            , ['OrderItemId' => 'orderItem.oo_id']
        );

        return $this;
    }


    /**
     * @param $field
     *
     * @return $this
     */
    public function addSelectField($field)
    {
        $this->getQuery()->columns($field);
    }

    /**
     * @param IOrderListFilter $filter
     *
     * @return $this
     */
    public function addFilter(IOrderListFilter $filter)
    {
        $this->filter[] = $filter;
        $filter->apply( $this );
    }


    /**
     * @param string $condition
     * @param string $value
     *
     * @return $this
     */
    public function addCondition($condition, $value = null)
    {
        $this->getQuery()->where( $condition, $value );

        return $this;
    }



    /**
     * get all available values that can bee used for filter
     * @param string $field
     *
     * @return array
     * @deprecated refactoring
     */
    protected function getAvailableFilterValues($field)
    {
        if(!$this->availableFilterValues)
        {
            $listing = new self();

            $query = $listing->getQuery();
            $query = str_replace('-- [GET_AVAILABLE_OPTIONS]', '
                , ifnull(GROUP_CONCAT(DISTINCT product.o_id, "|", product.o_parentId SEPARATOR "|"),0) as "available_productId"
                , ifnull(GROUP_CONCAT(DISTINCT pricingRule.ruleId SEPARATOR "|"),0) as "available_pricingRules"
            ', $query);
            $query = str_replace('GROUP BY orderItem', '', $query);


            $conn = \Pimcore\Resource::getConnection();
            $conn->query( 'SET SESSION group_concat_max_len = 1000000' );
            $this->availableFilterValues = $conn->fetchRow( $query );
        }

        return explode('|', $this->availableFilterValues[ 'available_' . $field ]);
    }


    /**
     * When an object is cloned, PHP 5 will perform a shallow copy of all of the object's properties.
     * Any properties that are references to other variables, will remain references.
     * Once the cloning is complete, if a __clone() method is defined,
     * then the newly created object's __clone() method will be called, to allow any necessary properties that need to be changed.
     * NOT CALLABLE DIRECTLY.
     *
     * @return mixed
     * @link http://php.net/manual/en/language.oop5.cloning.php
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }

    /**
     * @return boolean
     */
    public function useSubItems()
    {
        return $this->useSubItems;
    }

    /**
     * @param boolean $useSubItems
     *
     * @return $this
     */
    public function setUseSubItems($useSubItems)
    {
        $this->useSubItems = (bool)$useSubItems;
        return $this;
    }
}