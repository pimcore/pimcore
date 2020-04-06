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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\ModificatedPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\RuleInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

class Discount implements DiscountInterface
{
    /**
     * @var Decimal
     */
    protected $amount;

    /**
     * @var null|RuleInterface
     */
    protected $rule = null;

    /**
     * @param RuleInterface $rule
     */
    public function __construct(RuleInterface $rule)
    {
        $this->rule = $rule;
        $this->amount = Decimal::create(0);
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
     * @param PriceInterface $currentSubTotal
     * @param CartInterface  $cart
     *
     * @return PriceInterface
     */
    public function modify(PriceInterface $currentSubTotal, CartInterface $cart)
    {
        $amount = $this->getAmount();
        if ($currentSubTotal->getAmount()->lessThan($amount->mul(-1))) {
            $amount = $currentSubTotal->getAmount()->mul(-1);
        }

        $modificatedPrice = new ModificatedPrice($amount, $currentSubTotal->getCurrency(), false, $this->rule->getLabel());
        $modificatedPrice->setRule($this->rule);

        $taxClass = Factory::getInstance()->getPriceSystem('default')->getTaxClassForPriceModification($this);
        if ($taxClass) {
            $modificatedPrice->setTaxEntryCombinationMode($taxClass->getTaxEntryCombinationType());
            $modificatedPrice->setTaxEntries(TaxEntry::convertTaxEntries($taxClass));

            $modificatedPrice->setGrossAmount($amount, true);
        }

        return $modificatedPrice;
    }

    /**
     * @inheritdoc
     */
    public function setAmount(Decimal $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAmount(): Decimal
    {
        return $this->amount;
    }

    public function getRuleId()
    {
        return $this->rule ? $this->rule->getId() : null;
    }
}
