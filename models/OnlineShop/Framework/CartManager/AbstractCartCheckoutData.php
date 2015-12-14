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

abstract class AbstractCartCheckoutData extends \Pimcore\Model\AbstractModel {

    protected $key;
    protected $data;
    /**
     * @var ICart
     */
    protected $cart;

    public function setCart(ICart $cart) {
        $this->cart = $cart;
    }

    public function getCart() {
        return $this->cart;
    }

    public function getCartId() {
        return $this->getCart()->getId();
    }

    public abstract function save();

    public abstract static function getByKeyCartId($key, $cartId);

    public abstract static function removeAllFromCart($cartId);

    public function setKey($key) {
        $this->key = $key;
    }

    public function getKey() {
        return $this->key;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }


}
