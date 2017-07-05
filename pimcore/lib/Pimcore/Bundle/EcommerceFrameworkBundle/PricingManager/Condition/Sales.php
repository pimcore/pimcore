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

use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Value\PriceAmount;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IRule;
use Pimcore\Model\Object\OnlineShopOrder;
use Pimcore\Model\Object\OnlineShopOrderItem;

class Sales extends AbstractOrder implements ICondition
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
     * @param IEnvironment $environment
     *
     * @return bool
     */
    public function check(IEnvironment $environment)
    {
        $rule = $environment->getRule();
        if ($rule) {
            // TODO change this->amount to a PriceAmount?
            $amount = PriceAmount::create($this->getAmount());

            return $this->getSalesAmount($rule)->lessThan($amount);
        } else {
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
            'type' => 'Sales', 'amount' => $this->getAmount()
        ];

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $this->setAmount($json->amount);

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

    protected function getCurrentAmount(IRule $rule)
    {
        if (!array_key_exists($rule->getId(), $this->currentSalesAmount)) {
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

            $query = sprintf($query, OnlineShopOrderItem::classId(), OnlineShopOrder::classId(), $rule->getId());
            $conn = \Pimcore\Db::getConnection();

            $this->currentSalesAmount[$rule->getId()] = (int)$conn->fetchRow($query)['amount'];
        }

        return $this->currentSalesAmount[$rule->getId()];
    }
}
