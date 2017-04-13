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
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Config\Config;
use Pimcore\Model\Object\OnlineShopTaxClass;

/**
 * Class Shipping
 */
class Shipping implements IShipping
{
    /**
     * @var float
     */
    protected $charge = 0;

    /**
     * @var OnlineShopTaxClass
     */
    protected $taxClass = 0;

    /**
     * @param Config $config
     */
    public function __construct(Config $config = null)
    {
        if ($config && $config->charge) {
            $this->charge = floatval($config->charge);
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
        $modificatedPrice = new \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\ModificatedPrice($this->getCharge(), $currentSubTotal->getCurrency());

        $taxClass = $this->getTaxClass();
        if ($taxClass) {
            $modificatedPrice->setTaxEntryCombinationMode($taxClass->getTaxEntryCombinationType());
            $modificatedPrice->setTaxEntries(TaxEntry::convertTaxEntries($taxClass));

            $modificatedPrice->setGrossAmount($this->getCharge(), true);
        }

        return $modificatedPrice;
    }

    /**
     * @param float $charge
     *
     * @return ICartPriceModificator
     */
    public function setCharge($charge)
    {
        $this->charge = $charge;

        return $this;
    }

    /**
     * @return float
     */
    public function getCharge()
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
