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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartPriceModificator\ICartPriceModificator;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IModificatedPrice;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPrice;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;

/**
 * Class CartPriceCalculator
 */
class CartPriceCalculator implements ICartPriceCalculator {

    /**
     * @var bool
     */
    protected $isCalculated = false;

    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPrice
     */
    protected $subTotal;

    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPrice
     */
    protected $gradTotal;

    /**
     * @var ICartPriceModificator[]
     */
    protected $modificators;

    /**
     * @var IModificatedPrice[]
     */
    protected $modifications;

    /**
     * @var ICart
     */
    protected $cart;


    /**
     * @param $config
     * @param ICart $cart
     */
    public function __construct($config, ICart $cart) {
        $this->modificators = array();
        if(!empty($config->modificators) && is_object($config->modificators)) {
            foreach($config->modificators as $modificator) {
                $modificatorClass = new $modificator->class($modificator->config);
                $this->addModificator( $modificatorClass );
            }
        }

        $this->cart = $cart;
        $this->isCalculated = false;
    }


    /**
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException
     */
    public function calculate() {

        //sum up all item prices
        $subTotalNet = 0;
        $subTotalGross = 0;
        $currency = null;

        /**
         * @var $subTotalTaxes TaxEntry[]
         * @var $grandTotalTaxes TaxEntry[]
         */
        $subTotalTaxes = [];
        $grandTotalTaxes = [];

        foreach($this->cart->getItems() as $item) {
            if(is_object($item->getPrice())) {
                if(!$currency) {
                    $currency = $item->getPrice()->getCurrency();
                }

                if($currency->getShortName() != $item->getPrice()->getCurrency()->getShortName()) {
                    throw new UnsupportedException("Different currencies within one cart are not supported. See cart " . $this->cart->getId() . " and product " . $item->getProduct()->getId() . ")");
                }

                $subTotalNet += $item->getTotalPrice()->getNetAmount();
                $subTotalGross += $item->getTotalPrice()->getGrossAmount();

                $taxEntries = $item->getTotalPrice()->getTaxEntries();
                foreach($taxEntries as $taxEntry) {

                    $taxId = $taxEntry->getTaxId();
                    if(empty($subTotalTaxes[$taxId])) {
                        $subTotalTaxes[$taxId] = clone $taxEntry;
                        $grandTotalTaxes[$taxId] = clone $taxEntry;
                    } else {
                        $subTotalTaxes[$taxId]->setAmount($subTotalTaxes[$taxId]->getAmount() + $taxEntry->getAmount());
                        $grandTotalTaxes[$taxId]->setAmount($grandTotalTaxes[$taxId]->getAmount() + $taxEntry->getAmount());
                    }

                }
            }
        }

        //by default currency is retrieved from item prices. if there are no items, its loaded from the default locale defined in the environment
        if(!$currency) {
            $currency = $this->getDefaultCurrency();
        }

        //populate subTotal price, set net and gross amount, set tax entries and set tax entry combination mode to fixed
        $this->subTotal = $this->getDefaultPriceObject($subTotalGross, $currency);
        $this->subTotal->setNetAmount($subTotalNet);
        $this->subTotal->setTaxEntries($subTotalTaxes);
        $this->subTotal->setTaxEntryCombinationMode(TaxEntry::CALCULATION_MODE_FIXED);


        //consider all price modificators
        $currentSubTotal = $this->getDefaultPriceObject($subTotalGross, $currency);
        $currentSubTotal->setNetAmount($subTotalNet);
        $currentSubTotal->setTaxEntryCombinationMode(TaxEntry::CALCULATION_MODE_FIXED);


        $this->modifications = array();
        foreach($this->getModificators() as $modificator) {
            /* @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartPriceModificator\ICartPriceModificator $modificator */
            $modification = $modificator->modify($currentSubTotal, $this->cart);
            if($modification !== null) {
                $this->modifications[$modificator->getName()] = $modification;
                $currentSubTotal->setNetAmount($currentSubTotal->getNetAmount() + $modification->getNetAmount());
                $currentSubTotal->setGrossAmount($currentSubTotal->getGrossAmount() + $modification->getGrossAmount());

                $taxEntries = $modification->getTaxEntries();
                foreach($taxEntries as $taxEntry) {
                    $taxId = $taxEntry->getTaxId();
                    if(empty($grandTotalTaxes[$taxId])) {
                        $grandTotalTaxes[$taxId] = clone $taxEntry;
                    } else {
                        $grandTotalTaxes[$taxId]->setAmount($grandTotalTaxes[$taxId]->getAmount() + $taxEntry->getAmount());
                    }
                }
            }
        }

        $currentSubTotal->setTaxEntries($grandTotalTaxes);

        $this->gradTotal = $currentSubTotal;
        $this->isCalculated = true;
    }


    /**
     * gets default currency object based on the default currency locale defined in the environment
     *
     * @return Currency
     */
    protected function getDefaultCurrency() {
        return Factory::getInstance()->getEnvironment()->getDefaultCurrency();
    }

    /**
     * possibility to overwrite the price object that should be used
     *
     * @param $amount
     * @param Currency $currency
     * @return IPrice
     */
    protected function getDefaultPriceObject($amount, Currency $currency) {
        return new Price($amount, $currency);
    }

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPrice $price
     */
    public function getGrandTotal() {
        if(!$this->isCalculated) {
            $this->calculate();
        }
        return $this->gradTotal;
    }

    /**
     * @return IModificatedPrice[] $priceModification
     */
    public function getPriceModifications() {
        if(!$this->isCalculated) {
            $this->calculate();
        }

        return $this->modifications;
    }

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPrice $price
     */
    public function getSubTotal() {
        if(!$this->isCalculated) {
            $this->calculate();
        }
        
        return $this->subTotal;
    }

    /**
     * @return void
     */
    public function reset() {
        $this->isCalculated = false;
    }

    /**
     * @param ICartPriceModificator $modificator
     *
     * @return ICartPriceCalculator
     */
    public function addModificator(ICartPriceModificator $modificator)
    {
        $this->reset();
        $this->modificators[] = $modificator;

        return $this;
    }

    /**
     * @return ICartPriceModificator[]
     */
    public function getModificators()
    {
        return $this->modificators;
    }

    /**
     * @param ICartPriceModificator $modificator
     *
     * @return ICartPriceCalculator
     */
    public function removeModificator(ICartPriceModificator $modificator)
    {
        foreach($this->modificators as $key => $mod) {
            if($mod === $modificator) {
                unset($this->modificators[$key]);
            }
        }

        return $this;
    }


}