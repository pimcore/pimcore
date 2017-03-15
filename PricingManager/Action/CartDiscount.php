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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Action;

use OnlineShop\Framework\CartManager\CartPriceModificator\Discount;

class CartDiscount implements IDiscount
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
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return \OnlineShop\Framework\PricingManager\IAction
     */
    public function executeOnProduct(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        return $this;
    }

    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return \OnlineShop\Framework\PricingManager\IAction
     */
    public function executeOnCart(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        $priceCalculator = $environment->getCart()->getPriceCalculator();

        $amount = round($this->getAmount() !== 0 ? $this->getAmount() : ($priceCalculator->getSubTotal()->getAmount() * ($this->getPercent() / 100)), 2);
        $amount *= -1;

        //make sure that one rule is applied only once
        foreach($priceCalculator->getModificators() as &$modificator) {
            if($modificator instanceof Discount && $modificator->getRuleId() == $environment->getRule()->getId()) {
                $modificator->setAmount($amount);
                $priceCalculator->reset();
                return $this;
            }
        }

        $modDiscount = new Discount($environment->getRule());
        $modDiscount->setAmount($amount);
        $priceCalculator->addModificator($modDiscount);

        return $this;
    }


    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode(array(
                                'type' => 'CartDiscount',
                                'amount' => $this->getAmount(),
                                'percent' => $this->getPercent()
                           ));
    }

    /**
     * @param string $string
     *
     * @return \OnlineShop\Framework\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);
        if($json->amount)
            $this->setAmount( $json->amount );
        if($json->percent)
            $this->setPercent( $json->percent );
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
