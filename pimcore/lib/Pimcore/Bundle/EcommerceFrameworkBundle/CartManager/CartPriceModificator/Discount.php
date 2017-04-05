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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\ModificatedPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IRule;

class Discount implements IDiscount
{
    /**
     * @var float
     */
    protected $amount = 0;

    /**
     * @var null|IRule
     */
    protected $rule = null;


    /**
     * @param IRule $rule
     */
    public function __construct(IRule $rule)
    {
        $this->rule = $rule;
    }


    /**
     * modificator name
     *
     * @return string
     */
    public function getName()
    {
        if ($this->rule) {
            return $this->rule->getName();
        }

        return "discount";
    }

    /**
     * modify price
     *
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice $currentSubTotal
     * @param ICart  $cart
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice
     */
    public function modify(\Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice $currentSubTotal, ICart $cart)
    {
        if ($this->getAmount() != 0) {
            $amount = $this->getAmount();
            if ($currentSubTotal->getAmount() < ($amount * -1)) {
                $amount = $currentSubTotal->getAmount() * -1;
            }

            $modificatedPrice = new ModificatedPrice($amount, $currentSubTotal->getCurrency(), false, $this->rule->getLabel());

            $taxClass = Factory::getInstance()->getPriceSystem("default")->getTaxClassForPriceModification($this);
            if ($taxClass) {
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
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\IDiscount
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

    public function getRuleId()
    {
        return $this->rule ? $this->rule->getId() : null;
    }
}
