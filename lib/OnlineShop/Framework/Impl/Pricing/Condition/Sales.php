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


class OnlineShop_Framework_Impl_Pricing_Condition_Sales extends OnlineShop_Framework_Impl_Pricing_Condition_AbstractOrder implements OnlineShop_Framework_Pricing_ICondition
{
    /**
     * @var int
     */
    protected $amount;

    /**
     * @var int[]
     */
    protected $currentSalesAmount = [];


    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        $rule = $environment->getRule();
        if($rule)
        {
            return $this->getSalesAmount( $rule ) < $this->getAmount();
        }
        else
        {
            return false;
        }
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = [
            'type' => 'Sales'
            , 'amount' => $this->getAmount()
        ];

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return OnlineShop_Framework_Pricing_ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $this->setAmount( $json->amount );

        return $this;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount($amount)
    {
        $this->amount = (int)$amount;
    }




    protected function getCurrentAmount(OnlineShop_Framework_Pricing_IRule $rule)
    {
        if(!array_key_exists($rule->getId(), $this->currentSalesAmount))
        {
            $query = <<<'SQL'
SELECT 1

	, count(priceRule.o_id) as "count"
	, sum(orderItem.totalPrice) as "amount"

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
			AND orderItem.origin__id is null
    	    AND orderItem.o_id = orderItems.dest_id
		)

	-- add active price rules
	JOIN object_collection_PriceRule_%1$d as priceRule
		ON( 1
			AND priceRule.o_id = orderItem.oo_id
			AND priceRule.fieldname = "priceRules"
			AND priceRule.ruleId = %3$d
		)

WHERE 1
    AND `order`.orderState = "committed"
    AND `order`.origin__id is null

LIMIT 1
SQL;

            $query = sprintf($query
                , \Pimcore\Model\Object\OnlineShopOrderItem::classId()
                , \Pimcore\Model\Object\OnlineShopOrder::classId()
                , $rule->getId()
            );

            $conn = \Pimcore\Resource::getConnection();

            $this->currentSalesAmount[ $rule->getId() ] = (int)$conn->fetchRow( $query )['amount'];
        }


        return $this->currentSalesAmount[ $rule->getId() ];
    }
}