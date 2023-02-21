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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ActionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

// TODO use Decimal for amounts?
class ProductDiscount implements ProductDiscountInterface
{
    protected float $amount = 0;

    protected float $percent = 0;

    public function executeOnProduct(EnvironmentInterface $environment): ActionInterface
    {
        $priceinfo = $environment->getPriceInfo();

        $amount = Decimal::create($this->amount);

        // TODO use discount()?
        if ($amount->equals(Decimal::create(0))) {
            $amount = $priceinfo->getAmount()->mul($this->getPercent() / 100);
        }

        $amount = $priceinfo->getAmount()->sub($amount);
        $priceinfo->setAmount($amount->isPositive() ? $amount : Decimal::zero());

        return $this;
    }

    public function toJSON(): string
    {
        return json_encode([
            'type' => 'ProductDiscount',
            'amount' => $this->getAmount(),
            'percent' => $this->getPercent(),
        ]);
    }

    public function fromJSON(string $string): ActionInterface
    {
        $json = json_decode($string);
        if ($json->amount) {
            if ($json->amount < 0) {
                throw new \Exception('Only positive numbers and 0 are valid values for absolute discounts');
            }

            $this->setAmount($json->amount);
        }
        if ($json->percent) {
            if ($json->percent < 0) {
                throw new \Exception('Only positive numbers and 0 are valid values for % discounts');
            }

            $this->setPercent($json->percent);
        }

        return $this;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setPercent(float $percent): void
    {
        $this->percent = $percent;
    }

    public function getPercent(): float
    {
        return $this->percent;
    }
}
