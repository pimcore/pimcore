<?php

class OnlineShop_Framework_Impl_MultiCartManager implements OnlineShop_Framework_ICartManager {

    protected $carts = array();
    protected $initialized = false;

    public function __construct($config) {
        $this->checkConfig($config);
        $this->config = $config;
    }

    protected function checkConfig($config) {
        $tempCart = null;
        if(empty($config->cart->class)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("No Cart class defined.");
        } else {
            if(class_exists($config->cart->class)) {
                $tempCart = new $config->cart->class($config->cart);
                if(!($tempCart instanceof OnlineShop_Framework_ICart)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart class " . $config->cart->class . " does not implement OnlineShop_Framework_ICart.");
                }
            } else {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart class " . $config->cart->class . " not found.");
            }
        }

        if(empty($config->pricecalcualtor->class)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("No Pricecalcualtor class defined.");
        } else {
            if(class_exists($config->pricecalcualtor->class)) {

                $tempCalc = new $config->pricecalcualtor->class($config->pricecalcualtor->config, $tempCart);
                if(!($tempCalc instanceof OnlineShop_Framework_ICartPriceCalculator)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart class " . $config->pricecalcualtor->class . " does not implement OnlineShop_Framework_ICartPriceCalculator.");
                }

            } else {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("Pricecalcualtor class " . $config->pricecalcualtor->class . " not found.");
            }
        }

    }

    /**
     * @return string
     */
    public function getCartClassName()
    {
        // check if we need a guest cart
        if( OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getUseGuestCart()
            && $this->config->cart->guest)
        {
            $cartClass = $this->config->cart->guest->class;
        }
        else
        {
            $cartClass = $this->config->cart->class;
        }

        return $cartClass;
    }

    protected function checkForInit() {
        if(!$this->initialized) {
            $this->initSavedCarts();
            $this->initialized = true;
        }
    }

    protected function initSavedCarts() {
        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();

        $classname = $this->getCartClassName();
        $carts = $classname::getAllCartsForUser($env->getCurrentUserId());
        if(empty($carts)) {
            $this->carts = array();
        } else {
            foreach($carts as $c) {
                /* @var OnlineShop_Framework_ICart $c */
                $this->carts[$c->getId()] = $c;
            }
        }
    }

    /**
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     * @param $count
     * @param $key
     * @param null $itemKey
     * @param bool $replace
     * @param array $params
     * @param array $subProducts
     * @param null $comment
     * @return string
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function addToCart(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product, $count,  $key , $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null) {
        $this->checkForInit();
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart " . $key . " not found.");
        }

        $itemKey = $this->carts[$key]->addItem($product, $count, $itemKey, $replace, $params, $subProducts, $comment);
        $this->save();
        return $itemKey;
    }


    function save() {
        $this->checkForInit();
        foreach($this->carts as $cart) {
            $cart->save();
        }
    }

    /**
     * @param  $key
     * @return void
     */
    public function deleteCart($key) {
        $this->checkForInit();
        $this->getCart($key)->delete();
        unset($this->carts[$key]);
    }

    /**
     * @param  $param array of cart informations
     * @param null $key optional identification of cart in case of multicart
     * @return void
     */
    public function updateCartInformation($param, $key = null) {
        $this->checkForInit();
        // TODO: Implement updateCartInformation() method.
    }


    /**
     * @param array $param  array of cart informations
     *
     * @return string key
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function createCart($param) {
        $this->checkForInit();

        if(array_key_exists($param['id'], $this->carts)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart with id " . $param['id'] . " exists already.");
        }

        // create cart
        $class = $this->getCartClassName();
        $cart = new $class();
        $cart->setName($param['name']);
        if($param['id']) {
            $cart->setId($param['id']);
        }

        $cart->save();
        $this->carts[$cart->getId()] = $cart;

        return $cart->getId();
    }

    /**
     * @param null $key optional identification of cart in case of multicart
     * @return void
     */
    public function clearCart($key = null) {
        $this->checkForInit();
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart " . $key . " not found.");
        }

        $class = $this->getCartClassName();
        $cart = new $class();
        $this->carts[$key] = $cart;
    }

    /**
     * @param null $key optional identification of cart in case of multicart
     * @return OnlineShop_Framework_ICart
     */
    public function getCart($key = null) {
        $this->checkForInit();
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart " . $key . " not found.");
        }
        return $this->carts[$key];
    }

    /**
     * return cart with the given name
     * @param $name
     *
     * @return OnlineShop_Framework_ICart
     */
    public function getCartByName($name)
    {
        $this->checkForInit();
        foreach($this->carts as $cart)
        {
            if($cart->getName() == $name)
            {
                return $cart;
            }
        }
    }


    public function getCarts() {
        $this->checkForInit();
        return $this->carts;
    }

    /**
     * @param string $itemKey
     * @param null $key optional identification of cart in case of multicart
     * @return void
     */
    public function removeFromCart($itemKey, $key = null) {
        $this->checkForInit();
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart " . $key . " not found.");
        }
        $this->carts[$key]->removeItem($itemKey);
    }

    /**
     * @return OnlineShop_Framework_ICartPriceCalculator
     */
    public function getCartPriceCalcuator(OnlineShop_Framework_ICart $cart) {
        return new $this->config->pricecalcualtor->class($this->config->pricecalcualtor->config, $cart);
    }
}
