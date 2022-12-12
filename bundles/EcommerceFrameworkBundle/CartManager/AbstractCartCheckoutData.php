<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

abstract class AbstractCartCheckoutData extends \Pimcore\Model\AbstractModel
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var array|string|null
     */
    protected $data;

    /**
     * @var CartInterface|null
     */
    protected $cart;

    public function setCart(CartInterface $cart)
    {
        $this->cart = $cart;
    }

    /**
     * @return CartInterface|null
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @return int|string|null
     */
    public function getCartId()
    {
        return $this->getCart()->getId();
    }

    abstract public function save();

    public static function getByKeyCartId($key, $cartId)
    {
        throw new \Exception('Not implemented.');
    }

    /**
     * @param string|int $cartId
     */
    public static function removeAllFromCart($cartId)
    {
        throw new \Exception('Not implemented.');
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param array|string|null $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array|string|null
     */
    public function getData()
    {
        return $this->data;
    }
}
