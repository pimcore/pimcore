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


use \Pimcore\Tool;

/**
 * Class OnlineShop_Framework_Impl_MultiCartManager
 */
class OnlineShop_Framework_Impl_MultiCartManager implements OnlineShop_Framework_ICartManager {

    /**
     * @var OnlineShop_Framework_ICart[]
     */
    protected $carts = array();

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @param $config
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function __construct($config) {
        $config = new OnlineShop_Framework_Config_HelperContainer($config, "cartmanager");
        $this->checkConfig($config);
        $this->config = $config;
    }

    /**
     * checks configuration and if specified classes exist
     *
     * @param $config
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    protected function checkConfig($config) {
        $tempCart = null;

        if(empty($config->cart->class)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("No Cart class defined.");
        } else {
            if(Tool::classExists($config->cart->class)) {
                $tempCart = new $config->cart->class($config->cart);
                if(!($tempCart instanceof OnlineShop_Framework_ICart)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart class " . $config->cart->class . " does not implement OnlineShop_Framework_ICart.");
                }
            } else {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart class " . $config->cart->class . " not found.");
            }
        }

        if(empty($config->pricecalculator->class)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("No pricecalculator class defined.");
        } else {
            if(Tool::classExists($config->pricecalculator->class)) {

                $tempCalc = new $config->pricecalculator->class($config->pricecalculator->config, $tempCart);
                if(!($tempCalc instanceof OnlineShop_Framework_ICartPriceCalculator)) {
                    throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart class " . $config->pricecalculator->class . " does not implement OnlineShop_Framework_ICartPriceCalculator.");
                }

            } else {
                throw new OnlineShop_Framework_Exception_InvalidConfigException("pricecalculator class " . $config->pricecalculator->class . " not found.");
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

    /**
     * checks if cart manager is initialized and if not, do so
     */
    protected function checkForInit() {
        if(!$this->initialized) {
            $this->initSavedCarts();
            $this->initialized = true;
        }
    }

    /**
     *
     */
    protected function initSavedCarts() {
        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();

        $classname = $this->getCartClassName();
        $carts = $classname::getAllCartsForUser($env->getCurrentUserId());
        if(empty($carts)) {
            $this->carts = array();
        } else {
            $orderManager = OnlineShop_Framework_Factory::getInstance()->getOrderManager();
            foreach($carts as $c) {
                /* @var OnlineShop_Framework_ICart $c */

                //check for order state of cart - remove it, when corresponding order is already committed
                $order = $orderManager->getOrderFromCart($c);
                if(empty($order) || $order->getOrderState() != $order::ORDER_STATE_COMMITTED) {
                    $this->carts[$c->getId()] = $c;
                } else {
                    //cart is already committed - cleanup cart and environment
                    \Logger::warn("Deleting cart with id " . $c->getId() . " because linked order " . $order->getId() . " is already committed.");
                    $c->delete();

                    $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
                    $env->removeCustomItem(OnlineShop_Framework_Impl_CheckoutManager::CURRENT_STEP . "_" . $c->getId());
                    $env->save();
                }
            }
        }
    }

    /**
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     * @param float $count
     * @param null $key
     * @param null $itemKey
     * @param bool $replace
     * @param array $params
     * @param array $subProducts
     * @param null $comment
     * @return null|string
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function addToCart(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product, $count,  $key = null, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null) {
        $this->checkForInit();
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart " . $key . " not found.");
        }

        $itemKey = $this->carts[$key]->addItem($product, $count, $itemKey, $replace, $params, $subProducts, $comment);
        $this->save();
        return $itemKey;
    }


    /**
     * @return void
     */
    function save() {
        $this->checkForInit();
        foreach($this->carts as $cart) {
            $cart->save();
        }
    }


    /**
     * @param null $key
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function deleteCart($key = null) {
        $this->checkForInit();
        $this->getCart($key)->delete();
        unset($this->carts[$key]);
    }

    /**
     * @param array $param
     * @return int|string
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

        /**
         * @var $cart OnlineShop_Framework_ICart
         */
        $cart->setName($param['name']);
        if($param['id']) {
            $cart->setId($param['id']);
        }

        $cart->save();
        $this->carts[$cart->getId()] = $cart;

        return $cart->getId();
    }

    /**
     * @param null $key
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
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
     * @param null $key
     * @return OnlineShop_Framework_ICart
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function getCart($key = null) {
        $this->checkForInit();
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart " . $key . " not found.");
        }
        return $this->carts[$key];
    }

    /**
     * @param string $name
     * @return null|OnlineShop_Framework_ICart
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
        return null;
    }


    /**
     * @return OnlineShop_Framework_ICart[]
     */
    public function getCarts() {
        $this->checkForInit();
        return $this->carts;
    }

    /**
     * @param string $itemKey
     * @param null $key
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function removeFromCart($itemKey, $key = null) {
        $this->checkForInit();
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Cart " . $key . " not found.");
        }
        $this->carts[$key]->removeItem($itemKey);
    }

    /**
     * @deprecated
     *
     * use getCartPriceCalculator instead
     * @param OnlineShop_Framework_ICart $cart
     * @return OnlineShop_Framework_ICartPriceCalculator
     */
    public function getCartPriceCalcuator(OnlineShop_Framework_ICart $cart) {
        return $this->getCartPriceCalculator($cart);
    }

    /**
     * @param OnlineShop_Framework_ICart $cart
     * @return OnlineShop_Framework_ICartPriceCalculator
     */
    public function getCartPriceCalculator(OnlineShop_Framework_ICart $cart) {
        return new $this->config->pricecalculator->class($this->config->pricecalculator->config, $cart);
    }
}
