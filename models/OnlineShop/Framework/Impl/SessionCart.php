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


class OnlineShop_Framework_Impl_SessionCart extends OnlineShop_Framework_AbstractCart implements OnlineShop_Framework_ICart {

    protected static $sessionNamespace = "onlineshop_sessioncarts";

    /**
     * @return string
     */
    protected function getCartItemClassName() {
        return "OnlineShop_Framework_Impl_SessionCartItem";
    }

    /**
     * @return string
     */
    protected function getCartCheckoutDataClassName() {
        return "OnlineShop_Framework_Impl_SessionCartCheckoutData";
    }

    protected static function getSession() {
        $session = new Zend_Session_Namespace(self::$sessionNamespace);
        if(empty($session->carts)) {
            $session->carts = array();
        }

        return $session;
    }


    public function save() {
        $session = self::getSession();

        if(!$this->getId()) {
            $this->setId(uniqid("sesscart_"));
        }

        $session->carts[$this->getId()] = serialize($this);
    }

    /**
     * @return void
     */
    public function delete() {
        $this->setIgnoreReadonly();

        $session = self::getSession();

        if(!$this->getId()) {
            throw new Exception("Cart saved not yes.");
        }

        $this->clear();
        unset($session->carts[$this->getId()]);

    }

    /**
     * @param callable $value_compare_func
     *
     * @return $this
     */
    public function sortItems(callable $value_compare_func)
    {
        uasort($this->items, $value_compare_func);

        return $this;
    }



    protected static $unserializedCarts = null;

    /**
     * @param int $id
     * @return OnlineShop_Framework_Impl_Cart
     */
    public static function getById($id) {
        $carts = self::getAllCartsForUser(-1);
        return $carts[$id];
    }

    /**
     * @static
     * @param int $userId
     * @return array
     */
    public static function getAllCartsForUser($userId) {
        if(self::$unserializedCarts == null) {
            foreach(self::getSession()->carts as $serializedCart) {
                $cart = unserialize($serializedCart);
                self::$unserializedCarts[$cart->getId()] = $cart;
            }
        }
        return self::$unserializedCarts;
    }

    /**
     * @return string
     */
    public static function getSessionNamespace()
    {
        return self::$sessionNamespace;
    }

    /**
     * @param string $sessionNamespace
     */
    public static function setSessionNamespace($sessionNamespace)
    {
        self::$sessionNamespace = $sessionNamespace;
    }


    /**
     * @return array
     */
    public function __sleep() {
        $vars = parent::__sleep();

        $blockedVars = array("creationDate","modificationDate","priceCalcuator");

        $finalVars = array();
        foreach ($vars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }


    /**
     * modified flag needs to be set
     */
    public function __wakeup() {
        $this->setIgnoreReadonly();

        // set current cart
        foreach($this->getItems() as $item)
        {
            $item->setCart( $this );
        }

        $this->modified();
        $this->unsetIgnoreReadonly();
    }
}
