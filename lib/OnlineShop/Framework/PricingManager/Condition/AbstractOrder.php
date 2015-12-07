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

namespace OnlineShop\Framework\PricingManager\Condition;

abstract class AbstractOrder implements \OnlineShop\Framework\PricingManager\ICondition
{
    /**
     * persistenter cache für alle condition die von dieser ableiten
     * @var int[]
     */
    private static $cache = [];


    /**
     * @param \OnlineShop\Framework\PricingManager\IRule $rule
     * @param string                             $field
     *
     * @return mixed
     */
    private function getData(\OnlineShop\Framework\PricingManager\IRule $rule, $field)
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
                    , \Pimcore\Model\Object\OnlineShopOrderItem::classId()
                    , \Pimcore\Model\Object\OnlineShopOrder::classId()
                    , $rule->getId()
                );

                $conn = \Pimcore\Db::getConnection();
                self::$cache[ $rule->getId() ] = $conn->fetchRow( $query );
            }
            catch(\Exception $e)
            {
                \Logger::error( $e );
            }
        }


        return self::$cache[ $rule->getId() ][ $field ];
    }


    /**
     * @param \OnlineShop\Framework\PricingManager\IRule $rule
     *
     * @return int
     */
    protected function getSoldCount(\OnlineShop\Framework\PricingManager\IRule $rule)
    {
        return (int)$this->getData($rule, 'soldCount');
    }


    /**
     * @param \OnlineShop\Framework\PricingManager\IRule $rule
     *
     * @return float
     */
    protected function getSalesAmount(\OnlineShop\Framework\PricingManager\IRule $rule)
    {
        return (float)$this->getData($rule, 'salesAmount');
    }
}