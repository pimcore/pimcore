<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\CartManager;
use OnlineShop\Framework\CartManager\CartPriceModificator\ICartPriceModificator;

/**
 * Class CartPriceCalculator
 */
class CartPriceCalculator implements ICartPriceCalculator {

    /**
     * @var bool
     */
    protected $isCalculated = false;

    /**
     * @var \OnlineShop_Framework_IPrice
     */
    protected $subTotal;

    /**
     * @var \OnlineShop_Framework_IPrice
     */
    protected $gradTotal;

    /**
     * @var ICartPriceModificator[]
     */
    protected $modificators;

    /**
     * @var \OnlineShop_Framework_IModificatedPrice[]
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
     * @throws \OnlineShop_Framework_Exception_UnsupportedException
     */
    public function calculate() {

        //sum up all item prices
        $subTotal = 0;
        $currency = null;
        foreach($this->cart->getItems() as $item) {
            if(is_object($item->getPrice())) {
                if(!$currency) {
                    $currency = $item->getPrice()->getCurrency();
                }

                if($currency->compare( $item->getPrice()->getCurrency() ) != 0) {
                    throw new \OnlineShop_Framework_Exception_UnsupportedException("Different currencies within one cart are not supported. See cart " . $this->cart->getId() . " and product " . $item->getProduct()->getId() . ")");
                }

                $subTotal += $item->getTotalPrice()->getAmount();
            }
        }
        //by default currency is retrieved from item prices. if there are no items, its loaded from the default locale defined in the environment
        if(!$currency) {
            $currency = $this->getDefaultCurrency();
        }
        $this->subTotal = $this->getDefaultPriceObject($subTotal, $currency);

        //consider all price modificators
        $currentSubTotal = $this->getDefaultPriceObject($subTotal, $currency);

        $this->modifications = array();
        foreach($this->getModificators() as $modificator) {
            /* @var \OnlineShop\Framework\CartManager\CartPriceModificator\ICartPriceModificator $modificator */
            $modification = $modificator->modify($currentSubTotal, $this->cart);
            if($modification !== null) {
                $this->modifications[$modificator->getName()] = $modification;
                $currentSubTotal->setAmount($currentSubTotal->getAmount() + $modification->getAmount());
            }
        }

        $this->gradTotal = $currentSubTotal;
        $this->isCalculated = true;
    }


    /**
     * gets default currency object based on the default currency locale defined in the environment
     *
     * @return \Zend_Currency
     */
    protected function getDefaultCurrency() {
        return new \Zend_Currency(\OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrencyLocale());
    }

    /**
     * possibility to overwrite the price object with should be used
     *
     * @param $amount
     * @param \Zend_Currency $currency
     * @return \OnlineShop_Framework_IPrice
     */
    protected function getDefaultPriceObject($amount, \Zend_Currency $currency) {
        return new \OnlineShop_Framework_Impl_Price($amount, $currency);
    }

    /**
     * @return \OnlineShop_Framework_IPrice $price
     */
    public function getGrandTotal() {
        if(!$this->isCalculated) {
            $this->calculate();
        }
        return $this->gradTotal;
    }

    /**
     * @return \OnlineShop_Framework_IModificatedPrice[] $priceModification
     */
    public function getPriceModifications() {
        if(!$this->isCalculated) {
            $this->calculate();
        }

        return $this->modifications;
    }

    /**
     * @return \OnlineShop_Framework_IPrice $price
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