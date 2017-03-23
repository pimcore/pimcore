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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Condition;

class Sales extends AbstractOrder implements \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\ICondition
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
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IEnvironment $environment
     *
     * @return boolean
     */
    public function check(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IEnvironment $environment)
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
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\ICondition
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




    protected function getCurrentAmount(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IRule $rule)
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

            $conn = \Pimcore\Db::getConnection();

            $this->currentSalesAmount[ $rule->getId() ] = (int)$conn->fetchRow( $query )['amount'];
        }


        return $this->currentSalesAmount[ $rule->getId() ];
    }
}