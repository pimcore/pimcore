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
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

class CartAmount implements CartAmountInterface
{
    protected float $limit;

    public function check(EnvironmentInterface $environment): bool
    {
        if (!$environment->getCart() || $environment->getProduct() !== null) {
            return false;
        }

        $calculator = $environment->getCart()->getPriceCalculator();

        // TODO store limit as Decimal?
        return $calculator->getSubTotal()->getAmount()->greaterThanOrEqual(Decimal::create($this->getLimit()));
    }

    public function setLimit(float $limit): CartAmountInterface
    {
        $this->limit = $limit;

        return $this;
    }

    public function getLimit(): float
    {
        return $this->limit;
    }

    public function toJSON(): string
    {
        return json_encode([
            'type' => 'CartAmount',
            'limit' => $this->getLimit(),
        ]);
    }

    public function fromJSON(string $string): ConditionInterface
    {
        $json = json_decode($string);
        $this->setLimit($json->limit);

        return $this;
    }
}
