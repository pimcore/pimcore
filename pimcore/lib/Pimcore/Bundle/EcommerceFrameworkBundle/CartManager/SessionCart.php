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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\Tools\SessionConfigurator;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;

class SessionCart extends AbstractCart implements ICart
{

    /**
     * @return string
     */
    protected function getCartItemClassName()
    {
        return '\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCartItem';
    }

    /**
     * @return string
     */
    protected function getCartCheckoutDataClassName()
    {
        return '\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCartCheckoutData';
    }

    /**
     * @return AttributeBagInterface
     */
    protected function getSession()
    {
        /**
         * @var AttributeBagInterface $session
         */
        $session = \Pimcore::getContainer()->get("session")->getBag(SessionConfigurator::ATTRIBUTE_BAG_CART);

        if (empty($session->get("carts"))) {
            $session->set("carts", []);
        }

        return $session;
    }


    public function save()
    {
        $session = $this->getSession();

        if (!$this->getId()) {
            $this->setId(uniqid("sesscart_"));
        }

        $carts = $session->get("carts");
        $carts[$this->getId()] = serialize($this);
        $session->set("carts", $carts);
    }

    /**
     * @return void
     * @throws \Exception if the cart is not yet saved.
     */
    public function delete()
    {
        $this->setIgnoreReadonly();

        $session = $this->getSession();

        if (!$this->getId()) {
            throw new \Exception("Cart saved not yet.");
        }

        $this->clear();

        $carts = $session->get("carts");
        unset($carts[$this->getId()]);
        $session->set("carts", $carts);
    }

    /**
     * @param callable $value_compare_func
     *
     * @return $this
     */
    public function sortItems(callable $value_compare_func)
    {
        if (is_array($this->items)) {
            uasort($this->items, $value_compare_func);
        }

        return $this;
    }



    protected static $unserializedCarts = null;

    /**
     * @param int $id
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\SessionCart
     */
    public static function getById($id)
    {
        $carts = static::getAllCartsForUser(-1);

        return $carts[$id];
    }

    /**
     * @static
     * @param int $userId
     * @return array
     */
    public static function getAllCartsForUser($userId)
    {
        if (static::$unserializedCarts == null) {
            $tmpCart = new static();

            foreach ($tmpCart->getSession()->get("carts") as $serializedCart) {
                $cart = unserialize($serializedCart);
                static::$unserializedCarts[$cart->getId()] = $cart;
            }
        }

        return static::$unserializedCarts;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $vars = parent::__sleep();

        $blockedVars = ["creationDate", "modificationDate", "priceCalcuator"];

        $finalVars = [];
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
    public function __wakeup()
    {
        $this->setIgnoreReadonly();

        // set current cart
        foreach ($this->getItems() as $item) {
            $item->setCart($this);

            if ($item->getSubItems()) {
                foreach ($item->getSubItems() as $subItem) {
                    $subItem->setCart($this);
                }
            }
        }

        $this->modified();
        $this->unsetIgnoreReadonly();
    }
}
