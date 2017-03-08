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


namespace OnlineShop\Framework\CartManager\CartPriceModificator;

use OnlineShop\Framework\CartManager\ICart;
use OnlineShop\Framework\Factory;
use OnlineShop\Framework\PriceSystem\TaxManagement\TaxEntry;

class Discount implements IDiscount
{
    /**
     * @var float
     */
    protected $amount = 0;

    /**
     * @var null|\OnlineShop\Framework\PricingManager\IRule
     */
    protected $rule = null;


    /**
     * @param \OnlineShop\Framework\PricingManager\IRule $rule
     */
    public function __construct(\OnlineShop\Framework\PricingManager\IRule $rule) {
        $this->rule = $rule;
    }


    /**
     * modificator name
     *
     * @return string
     */
    public function getName()
    {
        if($this->rule) {
            return $this->rule->getName();
        }
        return "discount";
    }

    /**
     * modify price
     *
     * @param \OnlineShop\Framework\PriceSystem\IPrice $currentSubTotal
     * @param ICart  $cart
     *
     * @return \OnlineShop\Framework\PriceSystem\IPrice
     */
    public function modify(\OnlineShop\Framework\PriceSystem\IPrice $currentSubTotal, ICart $cart)
    {
        if($this->getAmount() != 0) {
            $amount = $this->getAmount();
            if($currentSubTotal->getAmount() < ($amount * -1)) {
                $amount = $currentSubTotal->getAmount() * -1;
            }

            $modificatedPrice = new \OnlineShop\Framework\PriceSystem\ModificatedPrice($amount, $currentSubTotal->getCurrency(), false, $this->rule->getLabel());

            $taxClass = Factory::getInstance()->getPriceSystem("default")->getTaxClassForPriceModification($this);
            if($taxClass) {
                $modificatedPrice->setTaxEntryCombinationMode($taxClass->getTaxEntryCombinationType());
                $modificatedPrice->setTaxEntries(TaxEntry::convertTaxEntries($taxClass));

                $modificatedPrice->setGrossAmount($amount, true);
            }

            return $modificatedPrice;

        }
    }

    /**
     * @param float $amount
     *
     * @return \OnlineShop\Framework\CartManager\CartPriceModificator\IDiscount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    public function getRuleId() {
        return $this->rule ? $this->rule->getId() : null;
    }

}