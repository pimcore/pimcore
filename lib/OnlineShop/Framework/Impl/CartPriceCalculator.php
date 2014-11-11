<?php

class OnlineShop_Framework_Impl_CartPriceCalculator implements OnlineShop_Framework_ICartPriceCalculator {

    /**
     * @var bool
     */
    protected $isCalculated = false;

    /**
     * @var
     */
    protected $subTotal;

    /**
     * @var
     */
    protected $gradTotal;

    /**
     * @var array|OnlineShop_Framework_ICartPriceModificator
     */
    protected $modificators;

    /**
     * @var array
     */
    protected $modifications;

    /**
     * @var OnlineShop_Framework_ICart
     */
    protected $cart;


    public function __construct($config, OnlineShop_Framework_ICart $cart) {
        $this->modificators = array();
        if(!empty($config->modificators) && is_object($config->modificators)) {
            foreach($config->modificators as $modificator) {
                $step = new $modificator->class($modificator->config);
                #$this->modificators[] = $step;
                $this->addModificator( $step );
            }
        }

        $this->cart = $cart;
        $this->isCalculated = false;
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function calculate() {

        $subTotal = 0;
        $currency = null;
        foreach($this->cart->getItems() as $item) {
            if(is_object($item->getPrice())) {
                if(!$currency) {
                    $currency = $item->getPrice()->getCurrency();
                }

                if($currency->compare( $item->getPrice()->getCurrency() ) != 0) {
                    throw new OnlineShop_Framework_Exception_UnsupportedException("Different currencies within one cart are not supported");
                }

                $subTotal += $item->getTotalPrice()->getAmount();
            }
        }
        if(!$currency) {
            $currency = $this->getDefaultCurrency();
        }
        $this->subTotal = $this->getDefaultPriceObject($subTotal, $currency);

//        $modificationAmount = 0;
        $currentSubTotal = $this->getDefaultPriceObject($subTotal, $currency);

        $this->modifications = array();
        foreach($this->modificators as $modificator) {
            /* @var OnlineShop_Framework_ICartPriceModificator $modificator */
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
     * @return Zend_Currency
     */
    protected function getDefaultCurrency() {
        return new Zend_Currency(OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrencyLocale());
    }

    /**
     * @param $amount
     * @param Zend_Currency $currency
     * @return OnlineShop_Framework_IPrice
     */
    protected function getDefaultPriceObject($amount, Zend_Currency $currency) {
        return new OnlineShop_Framework_Impl_Price($amount, $currency);
    }

    /**
     * @return OnlineShop_Framework_IPrice $price
     */
    public function getGrandTotal() {
        if(!$this->isCalculated) {
            $this->calculate();
        }
        return $this->gradTotal;
    }

    /**
     * @return OnlineShop_Framework_IPrice[] $priceModification
     */
    public function getPriceModifications() {
        if(!$this->isCalculated) {
            $this->calculate();
        }

        return $this->modifications;
    }

    /**
     * @return OnlineShop_Framework_IPrice $price
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
     * @param OnlineShop_Framework_ICartPriceModificator $modificator
     *
     * @return OnlineShop_Framework_ICartPriceCalculator
     */
    public function addModificator(OnlineShop_Framework_ICartPriceModificator $modificator)
    {
        $this->reset();
        $this->modificators[] = $modificator;

        return $this;
    }

    /**
     * @return array|OnlineShop_Framework_ICartPriceCalculator
     */
    public function getModificators()
    {
        return $this->modificators;
    }

    /**
     * @param OnlineShop_Framework_ICartPriceModificator $modificator
     *
     * @return OnlineShop_Framework_ICartPriceCalculator
     */
    public function removeModificator(OnlineShop_Framework_ICartPriceModificator $modificator)
    {
        foreach($this->modificators as $key => $mod) {
            if($mod === $modificator) {
                unset($this->modificators[$key]);
            }
        }

        return $this;
    }


}
