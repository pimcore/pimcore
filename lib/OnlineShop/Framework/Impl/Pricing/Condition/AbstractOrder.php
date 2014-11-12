<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 11.04.13
 * Time: 10:27
 * To change this template use File | Settings | File Templates.
 */

abstract class OnlineShop_Framework_Impl_Pricing_Condition_AbstractOrder implements OnlineShop_Framework_Pricing_ICondition
{
    /**
     * persistenter cache für alle condition die von dieser ableiten
     * @var int[]
     */
    private static $cache = [];


    /**
     * @param OnlineShop_Framework_Pricing_IRule $rule
     * @param string                             $field
     *
     * @return mixed
     */
    private function getData(OnlineShop_Framework_Pricing_IRule $rule, $field)
    {
        if(!array_key_exists($rule->getId(), self::$cache))
        {
            $query = <<<'SQL'
SELECT 1

    , priceRule.ruleId
	, count(priceRule.o_id) as "soldCount"
	, sum(orderItem.totalPrice) as "salesAmount"

	-- DEBUG INFOS
	, orderItem.oo_id as "orderItem"
	, `order`.orderdate

FROM object_query_%2$d as `order`

    -- ordered products
    JOIN object_relations_%2$d as orderItems
        ON( 1
            AND orderItems.fieldname = "items"
            AND orderItems.src_id = `order`.oo_id
        )

	-- order item
	JOIN object_%1$d as orderItem
		ON ( 1
    	    AND orderItem.o_id = orderItems.dest_id
		)

	-- add active price rules
	JOIN object_collection_PricingRule_%1$d as priceRule
		ON( 1
			AND priceRule.o_id = orderItem.oo_id
			AND priceRule.fieldname = "PricingRules"
			AND priceRule.ruleId = %3$d
		)

WHERE 1
    AND `order`.orderState = "committed"

LIMIT 1
SQL;

            try
            {
                $query = sprintf($query
                    , \Object_OnlineShopOrderItem::classId()
                    , \Object_OnlineShopOrder::classId()
                    , $rule->getId()
                );

                $conn = \Pimcore_Resource::getConnection();
                self::$cache[ $rule->getId() ] = $conn->fetchRow( $query );
            }
            catch(Exception $e)
            {
                Logger::error( $e );
            }
        }


        return self::$cache[ $rule->getId() ][ $field ];
    }


    /**
     * @param OnlineShop_Framework_Pricing_IRule $rule
     *
     * @return int
     */
    protected function getSoldCount(OnlineShop_Framework_Pricing_IRule $rule)
    {
        return (int)$this->getData($rule, 'soldCount');
    }


    /**
     * @param OnlineShop_Framework_Pricing_IRule $rule
     *
     * @return float
     */
    protected function getSalesAmount(OnlineShop_Framework_Pricing_IRule $rule)
    {
        return (float)$this->getData($rule, 'salesAmount');
    }
}