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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\RuleInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Model\DataObject\OnlineShopOrder;
use Pimcore\Model\DataObject\OnlineShopOrderItem;

class Sales extends AbstractOrder implements ConditionInterface
{
    protected int $amount;

    /**
     * @var int[]
     */
    protected array $currentSalesAmount = [];

    public function check(EnvironmentInterface $environment): bool
    {
        $rule = $environment->getRule();
        if ($rule) {
            // TODO change this->amount to a Decimal?
            $amount = Decimal::create($this->getAmount());

            return $this->getSalesAmount($rule)->lessThan($amount);
        } else {
            return false;
        }
    }

    public function toJSON(): string
    {
        // basic
        $json = [
            'type' => 'Sales', 'amount' => $this->getAmount(),
        ];

        return json_encode($json);
    }

    public function fromJSON(string $string): ConditionInterface
    {
        $json = json_decode($string);

        $this->setAmount($json->amount);

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = (int)$amount;
    }

    protected function getCurrentAmount(RuleInterface $rule): int
    {
        if (!array_key_exists($rule->getId(), $this->currentSalesAmount)) {
            $query = <<<'SQL'
SELECT 1

	, count(priceRule.id) as "count"
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
    	    AND orderItem.id = orderItems.dest_id
		)

	-- add active price rules
	JOIN object_collection_PriceRule_%1$d as priceRule
		ON( 1
			AND priceRule.id = orderItem.oo_id
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

            $this->currentSalesAmount[$rule->getId()] = (int)$conn->fetchAssociative($query)['amount'];
        }

        return $this->currentSalesAmount[$rule->getId()];
    }
}
