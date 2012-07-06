<?php

class OnlineShop_Framework_Impl_MultiCartManager implements OnlineShop_Framework_ICartManager {

    protected $carts = array();
    protected $cartClass;

    public function __construct($config) {
        $this->checkConfig($config);
        $this->config = $config;
        $this->cartClass = $config->cart->class;

        $this->initSavedCarts();
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

    protected function initSavedCarts() {
        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();

        $classname = $this->cartClass;
        $carts = $classname::getAllCartsForUser($env->getCurrentUserId());
        if(empty($carts)) {
            $this->carts = array();
        } else {
            foreach($carts as $c) {
                $this->carts[$c->getId()] = $c;
            }
        }
    }

    /**
     * @param OnlineShop_Framework_AbstractProduct $product
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
    public function addToCart(OnlineShop_Framework_AbstractProduct $product, $count,  $key , $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null) {
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart " . $key . " not found.");
        }

        $itemKey = $this->carts[$key]->addItem($product, $count, $itemKey, $replace, $params, $subProducts, $comment);
        $this->save();
        return $itemKey;
    }


    function save() {
        foreach($this->carts as $cart) {
            $cart->save();
        }
//        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
//        $env->setCustomItem("carts", $this->carts);
    }

    /**
     * @param  $key
     * @return void
     */
    public function deleteCart($key) {
        $this->getCart($key)->delete();
    }

    /**
     * @param  $param array of cart informations
     * @param null $key optional identification of cart in case of multicart
     * @return void
     */
    public function updateCartInformation($param, $key = null) {
        // TODO: Implement updateCartInformation() method.
    }

    /**
     * @param  $param array of cart informations
     * @return $key
     */
    public function createCart($param) {
        //TODO 
//        if(empty($param['name'])) {
//            throw new OnlineShop_Framework_Exception_InvalidConfigException("No name specified.");
//        }
//
        if(array_key_exists($param['name'], $this->carts)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart with name " . $param['name'] . " exists already.");
        }

        $cart = new $this->cartClass();
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
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart " . $key . " not found.");
        }

        $cart = new $this->cartClass();
        $this->carts[$key] = $cart;
    }

    /**
     * @param null $key optional identification of cart in case of multicart
     * @return OnlineShop_Framework_ICart
     */
    public function getCart($key = null) {
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart " . $key . " not found.");
        }
        return $this->carts[$key];
    }

    public function getCarts() {
        return $this->carts;
    }

    /**
     * @param string $itemKey
     * @param null $key optional identification of cart in case of multicart
     * @return void
     */
    public function removeFromCart($itemKey, $key = null) {
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
