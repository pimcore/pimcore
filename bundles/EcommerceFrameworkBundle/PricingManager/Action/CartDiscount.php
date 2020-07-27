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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\Discount;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ActionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

// TODO use Decimal for amounts?
class CartDiscount implements DiscountInterface
{
    /**
     * @var float
     */
    protected $amount = 0;

    /**
     * @var float
     */
    protected $percent = 0;

    /**
     * @param EnvironmentInterface $environment
     *
     * @return ActionInterface
     */
    public function executeOnProduct(EnvironmentInterface $environment)
    {
        return $this;
    }

    /**
     * @param EnvironmentInterface $environment
     *
     * @return ActionInterface
     */
    public function executeOnCart(EnvironmentInterface $environment)
    {
        $priceCalculator = $environment->getCart()->getPriceCalculator();

        $amount = Decimal::create($this->amount);
        if ($amount->isZero()) {
            $amount = $priceCalculator->getSubTotal()->getAmount()->toPercentage($this->getPercent());
        }

        $amount = $amount->mul(-1);

        //make sure that one rule is applied only once
        foreach ($priceCalculator->getModificators() as &$modificator) {
            if ($modificator instanceof Discount && $modificator->getRuleId() == $environment->getRule()->getId()) {
                $modificator->setAmount($amount);
                $priceCalculator->calculate(true);

                return $this;
            }
        }

        $modDiscount = new Discount($environment->getRule());
        $modDiscount->setAmount($amount);

        $priceCalculator->addModificator($modDiscount);
        $priceCalculator->calculate(true);

        return $this;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode([
            'type' => 'CartDiscount',
            'amount' => $this->getAmount(),
            'percent' => $this->getPercent(),
        ]);
    }

    /**
     * @param string $string
     *
     * @return ActionInterface
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);
        if ($json->amount) {
            $this->setAmount($json->amount);
        }
        if ($json->percent) {
            $this->setPercent($json->percent);
        }

        return $this;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $percent
     */
    public function setPercent($percent)
    {
        $this->percent = $percent;
    }

    /**
     * @return float
     */
    public function getPercent()
    {
        return $this->percent;
    }
}
