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
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\ModificatedPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Value\PriceAmount;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IRule;

class Discount implements IDiscount
{
    /**
     * @var PriceAmount
     */
    protected $amount;

    /**
     * @var null|IRule
     */
    protected $rule = null;

    /**
     * @param IRule $rule
     */
    public function __construct(IRule $rule)
    {
        $this->rule   = $rule;
        $this->amount = PriceAmount::create(0);
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

        return 'discount';
    }

    /**
     * modify price
     *
     * @param IPrice $currentSubTotal
     * @param ICart  $cart
     *
     * @return IPrice
     */
    public function modify(IPrice $currentSubTotal, ICart $cart)
    {
        if ($this->getAmount() != 0) {
            $amount = $this->getAmount();
            if ($currentSubTotal->getAmount()->lessThan($amount->mul(-1))) {
                $amount = $currentSubTotal->getAmount()->mul(-1);
            }

            $modificatedPrice = new ModificatedPrice($amount, $currentSubTotal->getCurrency(), false, $this->rule->getLabel());

            $taxClass = Factory::getInstance()->getPriceSystem('default')->getTaxClassForPriceModification($this);
            if ($taxClass) {
                $modificatedPrice->setTaxEntryCombinationMode($taxClass->getTaxEntryCombinationType());
                $modificatedPrice->setTaxEntries(TaxEntry::convertTaxEntries($taxClass));

                $modificatedPrice->setGrossAmount($amount, true);
            }

            return $modificatedPrice;
        }
    }

    /**
     * @inheritdoc
     */
    public function setAmount(PriceAmount $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAmount(): PriceAmount
    {
        return $this->amount;
    }

    public function getRuleId()
    {
        return $this->rule ? $this->rule->getId() : null;
    }
}
