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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartPriceModificator;
use OnlineShop\Framework\CartManager\ICart;
use OnlineShop\Framework\Factory;
use OnlineShop\Framework\PriceSystem\IModificatedPrice;
use OnlineShop\Framework\PriceSystem\TaxManagement\TaxEntry;
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
     * @param \Zend_Config $config
     */
    public function __construct(\Zend_Config $config = null)
    {
        if($config && $config->charge)
        {
            $this->charge = floatval($config->charge);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "shipping";
    }

    /**
     * @param \OnlineShop\Framework\PriceSystem\IPrice $currentSubTotal
     * @param ICart $cart
     * @return IModificatedPrice
     */
    public function modify(\OnlineShop\Framework\PriceSystem\IPrice $currentSubTotal, ICart $cart)
    {
        $modificatedPrice = new \OnlineShop\Framework\PriceSystem\ModificatedPrice($this->getCharge(), new \Zend_Currency(\OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrencyLocale()));

        $taxClass = $this->getTaxClass();
        if($taxClass) {
            $modificatedPrice->setTaxEntryCombinationMode($taxClass->getTaxEntryCombinationType());
            $modificatedPrice->setTaxEntries(TaxEntry::convertTaxEntries($taxClass));

            $modificatedPrice->setGrossAmount($this->getCharge(), true);
        }

        return $modificatedPrice;

    }

    /**
     * @param float $charge
     *
     * @return \OnlineShop\Framework\CartManager\CartPriceModificator\ICartPriceModificator
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
        if(empty($this->taxClass)) {
            $this->taxClass = Factory::getInstance()->getPriceSystem("default")->getTaxClassForPriceModification($this);
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
