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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IRule;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Logger;
use Pimcore\Model\Object\OnlineShopOrder;
use Pimcore\Model\Object\OnlineShopOrderItem;

abstract class AbstractOrder implements ICondition
{
    /**
     * Persistent cache for all conditions inheriting from AbstractOrder
     *
     * @var int[]
     */
    private static $cache = [];

    /**
     * @param IRule $rule
     * @param string $field
     *
     * @return mixed
     */
    private function getData(IRule $rule, $field)
    {
        if (!array_key_exists($rule->getId(), self::$cache)) {
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

            try {
                $query = sprintf($query, OnlineShopOrderItem::classId(), OnlineShopOrder::classId(), $rule->getId());
                $conn = \Pimcore\Db::getConnection();

                self::$cache[$rule->getId()] = $conn->fetchRow($query);
            } catch (\Exception $e) {
                Logger::error($e);
            }
        }

        return self::$cache[$rule->getId()][$field];
    }

    /**
     * @param IRule $rule
     *
     * @return int
     */
    protected function getSoldCount(IRule $rule)
    {
        return (int)$this->getData($rule, 'soldCount');
    }

    /**
     * @param IRule $rule
     *
     * @return Decimal
     */
    protected function getSalesAmount(IRule $rule): Decimal
    {
        return Decimal::create($this->getData($rule, 'salesAmount'));
    }
}
