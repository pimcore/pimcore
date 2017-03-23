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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\CheckoutManager;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tools\Config\HelperContainer;
use Pimcore\Logger;
use \Pimcore\Tool;

/**
 * Class MultiCartManager
 */
class MultiCartManager implements ICartManager {

    /**
     * @var ICart[]
     */
    protected $carts = array();

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @param $config
     * @throws InvalidConfigException
     */
    public function __construct($config) {
        $config = new HelperContainer($config, "cartmanager");
        $this->checkConfig($config);
        $this->config = $config;
    }

    /**
     * checks configuration and if specified classes exist
     *
     * @param $config
     * @throws InvalidConfigException
     */
    protected function checkConfig($config) {
        $tempCart = null;

        if(empty($config->cart->class)) {
            throw new InvalidConfigException("No Cart class defined.");
        } else {
            if(Tool::classExists($config->cart->class)) {
                $tempCart = new $config->cart->class($config->cart);
                if(!($tempCart instanceof ICart)) {
                    throw new InvalidConfigException("Cart class " . $config->cart->class . ' does not implement \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart.');
                }
            } else {
                throw new InvalidConfigException("Cart class " . $config->cart->class . " not found.");
            }
        }

        if(empty($config->pricecalculator->class)) {
            throw new InvalidConfigException("No pricecalculator class defined.");
        } else {
            if(Tool::classExists($config->pricecalculator->class)) {

                $tempCalc = new $config->pricecalculator->class($config->pricecalculator->config, $tempCart);
                if(!($tempCalc instanceof ICartPriceCalculator)) {
                    throw new InvalidConfigException("Cart class " . $config->pricecalculator->class . ' does not implement \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICartPriceCalculator.');
                }

            } else {
                throw new InvalidConfigException("pricecalculator class " . $config->pricecalculator->class . " not found.");
            }
        }

    }

    /**
     * @return string
     */
    public function getCartClassName()
    {
        // check if we need a guest cart
        if( Factory::getInstance()->getEnvironment()->getUseGuestCart()
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
        $env = Factory::getInstance()->getEnvironment();

        $classname = $this->getCartClassName();
        $carts = $classname::getAllCartsForUser($env->getCurrentUserId());
        if(empty($carts)) {
            $this->carts = array();
        } else {
            $orderManager = Factory::getInstance()->getOrderManager();
            foreach($carts as $c) {
                /* @var ICart $c */

                //check for order state of cart - remove it, when corresponding order is already committed
                $order = $orderManager->getOrderFromCart($c);
                if(empty($order) || $order->getOrderState() != $order::ORDER_STATE_COMMITTED) {
                    $this->carts[$c->getId()] = $c;
                } else {
                    //cart is already committed - cleanup cart and environment
                    Logger::warn("Deleting cart with id " . $c->getId() . " because linked order " . $order->getId() . " is already committed.");
                    $c->delete();

                    $env = Factory::getInstance()->getEnvironment();
                    $env->removeCustomItem(CheckoutManager::CURRENT_STEP . "_" . $c->getId());
                    $env->save();
                }
            }
        }
    }

    /**
     * @param ICheckoutable $product
     * @param float $count
     * @param null $key
     * @param null $itemKey
     * @param bool $replace
     * @param array $params
     * @param array $subProducts
     * @param null $comment
     * @return null|string
     * @throws InvalidConfigException
     */
    public function addToCart(ICheckoutable $product, $count,  $key = null, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null) {
        $this->checkForInit();
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new InvalidConfigException("Cart " . $key . " not found.");
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
     * @throws InvalidConfigException
     */
    public function deleteCart($key = null) {
        $this->checkForInit();
        $this->getCart($key)->delete();
        unset($this->carts[$key]);
    }

    /**
     * @param array $param
     * @return int|string
     * @throws InvalidConfigException
     */
    public function createCart($param) {
        $this->checkForInit();

        if(array_key_exists($param['id'], $this->carts)) {
            throw new InvalidConfigException("Cart with id " . $param['id'] . " exists already.");
        }

        // create cart
        $class = $this->getCartClassName();
        $cart = new $class();

        /**
         * @var $cart ICart
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
     * @throws InvalidConfigException
     */
    public function clearCart($key = null) {
        $this->checkForInit();
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new InvalidConfigException("Cart " . $key . " not found.");
        }

        $class = $this->getCartClassName();
        $cart = new $class();
        $this->carts[$key] = $cart;
    }

    /**
     * @param null $key
     * @return ICart
     * @throws InvalidConfigException
     */
    public function getCart($key = null) {
        $this->checkForInit();
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new InvalidConfigException("Cart " . $key . " not found.");
        }
        return $this->carts[$key];
    }

    /**
     * @param string $name
     * @return null|ICart
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
     * @return ICart[]
     */
    public function getCarts() {
        $this->checkForInit();
        return $this->carts;
    }

    /**
     * @param string $itemKey
     * @param null $key
     * @throws InvalidConfigException
     */
    public function removeFromCart($itemKey, $key = null) {
        $this->checkForInit();
        if(empty($key) || !array_key_exists($key, $this->carts)) {
            throw new InvalidConfigException("Cart " . $key . " not found.");
        }
        $this->carts[$key]->removeItem($itemKey);
    }

    /**
     * @deprecated
     *
     * use getCartPriceCalculator instead
     * @param ICart $cart
     * @return ICartPriceCalculator
     */
    public function getCartPriceCalcuator(ICart $cart) {
        return $this->getCartPriceCalculator($cart);
    }

    /**
     * @param ICart $cart
     * @return ICartPriceCalculator
     */
    public function getCartPriceCalculator(ICart $cart) {
        return new $this->config->pricecalculator->class($this->config->pricecalculator->config, $cart);
    }


    /**
     *
     */
    public function reset() {
        $this->carts = [];
        $this->initialized = false;
    }
}
