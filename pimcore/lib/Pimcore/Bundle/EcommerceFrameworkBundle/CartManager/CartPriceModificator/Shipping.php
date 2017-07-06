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
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IModificatedPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\ModificatedPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Value\PriceAmount;
use Pimcore\Config\Config;
use Pimcore\Model\Object\OnlineShopTaxClass;

class Shipping implements IShipping
{
    /**
     * @var PriceAmount
     */
    protected $charge;

    /**
     * @var OnlineShopTaxClass
     */
    protected $taxClass;

    /**
     * @param Config $config
     */
    public function __construct(Config $config = null)
    {
        if ($config && $config->charge) {
            $this->charge = PriceAmount::create($config->charge);
        } else {
            $this->charge = PriceAmount::zero();
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'shipping';
    }

    /**
     * @param IPrice $currentSubTotal
     * @param ICart $cart
     *
     * @return IModificatedPrice
     */
    public function modify(IPrice $currentSubTotal, ICart $cart)
    {
        $modificatedPrice = new ModificatedPrice($this->getCharge(), $currentSubTotal->getCurrency());

        $taxClass = $this->getTaxClass();
        if ($taxClass) {
            $modificatedPrice->setTaxEntryCombinationMode($taxClass->getTaxEntryCombinationType());
            $modificatedPrice->setTaxEntries(TaxEntry::convertTaxEntries($taxClass));

            $modificatedPrice->setGrossAmount($this->getCharge(), true);
        }

        return $modificatedPrice;
    }

    /**
     * @param PriceAmount $charge
     *
     * @return ICartPriceModificator
     */
    public function setCharge(PriceAmount $charge)
    {
        $this->charge = $charge;

        return $this;
    }

    /**
     * @return PriceAmount
     */
    public function getCharge(): PriceAmount
    {
        return $this->charge;
    }

    /**
     * @return OnlineShopTaxClass
     */
    public function getTaxClass()
    {
        if (empty($this->taxClass)) {
            $this->taxClass = Factory::getInstance()->getPriceSystem('default')->getTaxClassForPriceModification($this);
        }

        return $this->taxClass;
    }

    /**
     * @param OnlineShopTaxClass $taxClass
     */
    public function setTaxClass($taxClass)
    {
        $this->taxClass = $taxClass;
    }
}
