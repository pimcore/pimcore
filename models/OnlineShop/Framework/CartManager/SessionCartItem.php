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

class SessionCartItem extends AbstractCartItem implements ICartItem {

    public function getCart() {
        if (empty($this->cart)) {
            $this->cart = SessionCart::getById($this->cartId);
        }
        return $this->cart;
    }


    public function save() {
        throw new \Exception("Not implemented, should not be needed for this cart type.");
    }

    public static function getByCartIdItemKey($cartId, $itemKey, $parentKey = "") {
        throw new \Exception("Not implemented, should not be needed for this cart type.");
    }

    public static function removeAllFromCart($cartId) {
        $cartItem = new self();
        $cart = $cartItem->getCart();
        $cart->setItems(null);
        $cart->save();
    }

    /**
     * @return \OnlineShop\Framework\CartManager\ICartItem[]
     */
    public function getSubItems() {
        return (array)$this->subItems;
    }


    /**
     * @return array
     */
    public function __sleep() {
        $vars = parent::__sleep();

        $blockedVars = array("cart","product");

        $finalVars = array();
        foreach ($vars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

}
