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

abstract class AbstractCartCheckoutData extends \Pimcore\Model\AbstractModel
{
    protected $key;
    protected $data;
    /**
     * @var ICart
     */
    protected $cart;

    public function setCart(ICart $cart)
    {
        $this->cart = $cart;
    }

    public function getCart()
    {
        return $this->cart;
    }

    public function getCartId()
    {
        return $this->getCart()->getId();
    }

    abstract public function save();

    public static function getByKeyCartId($key, $cartId)
    {
        throw new \Exception("Not implemented.");
    }

    public static function removeAllFromCart($cartId)
    {
        throw new \Exception("Not implemented.");
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
