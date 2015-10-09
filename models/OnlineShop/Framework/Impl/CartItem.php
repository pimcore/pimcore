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


class OnlineShop_Framework_Impl_CartItem extends OnlineShop_Framework_AbstractCartItem implements OnlineShop_Framework_ICartItem {

    /**
     * @var int
     */
    protected $sortIndex = 0;


    /**
     * @param int $sortIndex
     */
    public function setSortIndex($sortIndex)
    {
        $this->sortIndex = (int)$sortIndex;
    }

    /**
     * @return int
     */
    public function getSortIndex()
    {
        return $this->sortIndex;
    }


    public function getCart() {
        if (empty($this->cart)) {
            $this->cart = OnlineShop_Framework_Impl_Cart::getById($this->cartId);
        }
        return $this->cart;
    }




    public function save() {
        $items = $this->getSubItems();
        if (!empty($this->subItems)) {
            foreach ($this->subItems as $item) {
                $item->save();
            }
        }
        $this->getResource()->save();
    }

    public static function getByCartIdItemKey($cartId, $itemKey, $parentKey = "") {
        $cacheKey = OnlineShop_Framework_Impl_CartItem_Resource::TABLE_NAME . "_" . $cartId . "_" . $parentKey . $itemKey;

        try {
            $cartItem = Zend_Registry::get($cacheKey);
        }
        catch (Exception $e) {
            try {
                $cartItem = new static();
                $cartItem->getResource()->getByCartIdItemKey($cartId, $itemKey, $parentKey);
                $cartItem->getSubItems();
                Zend_Registry::set($cacheKey, $cartItem);
            } catch (Exception $ex) {
                Logger::debug($ex->getMessage());
                return null;
            }

        }

        return $cartItem;
    }

    public static function removeAllFromCart($cartId) {
        $cartItem = new static();
        $cartItem->getResource()->removeAllFromCart($cartId);
    }

    /**
     * @return OnlineShop_Framework_ICartItem[]
     */
    public function getSubItems() {

        if ($this->subItems == null) {
            $this->subItems = array();

            $itemClass = get_class($this) . "_List";
            $itemList = new $itemClass();

            $db = \Pimcore\Resource::get();
            $itemList->setCondition("cartId = " . $db->quote($this->getCartId()) . " AND parentItemKey = " . $db->quote($this->getItemKey()));

            foreach ($itemList->getCartItems() as $item) {
                if ($item->getProduct() != null) {
                    $this->subItems[] = $item;
                } else {
                    Logger::warn("product " . $item->getProductId() . " not found");
                }
            }
        }
        return $this->subItems;
    }



}
