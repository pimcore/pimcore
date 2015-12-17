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

class Cart extends AbstractCart implements ICart {

    /**
     * @return string
     */
    protected function getCartItemClassName() {
        return '\OnlineShop\Framework\CartManager\CartItem';
    }

    /**
     * @return string
     */
    protected function getCartCheckoutDataClassName() {
        return '\OnlineShop\Framework\CartManager\CartCheckoutData';
    }

    public function save() {
        $this->getDao()->save();
        \OnlineShop\Framework\CartManager\CartItem::removeAllFromCart($this->getId());
        foreach ($this->items as $item) {
            $item->save();
        }

        \OnlineShop\Framework\CartManager\CartCheckoutData::removeAllFromCart($this->getId());
        foreach ($this->checkoutData as $data) {
            $data->save();
        }
    }

    /**
     * @return void
     */
    public function delete() {
        $this->setIgnoreReadonly();

        $cacheKey = \OnlineShop\Framework\CartManager\Cart\Dao::TABLE_NAME . "_" . $this->getId();
        \Zend_Registry::set($cacheKey, null);

        \OnlineShop\Framework\CartManager\CartItem::removeAllFromCart($this->getId());
        \OnlineShop\Framework\CartManager\CartCheckoutData::removeAllFromCart($this->getId());

        $this->clear();
        $this->getDao()->delete();
    }


    /**
     * @param callable $value_compare_func
     *
     * @return $this
     */
    public function sortItems(callable $value_compare_func)
    {
        //call get items to lazy load items
        $this->getItems();

        uasort($this->items, $value_compare_func);

        $arrayKeys = array_keys($this->items);
        foreach ($arrayKeys as $index => $key) {
            $ite = $this->items[$key];
            $ite->setSortIndex($index);
        }

        return $this;
    }

    /**
     * @param int $id
     * @return Cart
     */
    public static function getById($id) {
        $cacheKey = \OnlineShop\Framework\CartManager\Cart\Dao::TABLE_NAME . "_" . $id;
        try {
            $cart = \Zend_Registry::get($cacheKey);
        }
        catch (\Exception $e) {

            try {
                $cartClass = get_called_class();
                /* @var \OnlineShop\Framework\CartManager\ICart $cart */
                $cart = new $cartClass;
                $cart->setIgnoreReadonly();
                $cart->getDao()->getById($id);

                $mod = $cart->getModificationDate();
                $cart->setModificationDate( $mod );

                $dataList = new \OnlineShop\Framework\CartManager\CartCheckoutData\Listing();
                $dataList->setCondition("cartId = " . $dataList->quote($cart->getId()));


                foreach ($dataList->getCartCheckoutDataItems() as $data) {
                    $cart->setCheckoutData($data->getKey(), $data->getData());
                }

                $cart->unsetIgnoreReadonly();

                \Zend_Registry::set($cacheKey, $cart);
            } catch (\Exception $ex) {
                \Logger::debug($ex->getMessage());
                return null;
            }

        }

        return $cart;
    }

    public function getItems() {
        if($this->items === null) {
            $itemList = new \OnlineShop\Framework\CartManager\CartItem\Listing();
            $itemList->setCartItemClassName( $this->getCartItemClassName() );
            $itemList->setCondition("cartId = " . $itemList->quote($this->getId()) . " AND parentItemKey = ''");
            $itemList->setOrderKey('sortIndex');
            $items = array();
            foreach ($itemList->getCartItems() as $item) {
                if(static::isValidCartItem($item)){
                    $item->setCart($this);
                    $items[$item->getItemKey()] = $item;
                }
            }
            $this->items = $items;
        }
        return $this->items;
    }


    /**
     * @param bool|false $countSubItems
     * @return int
     */
    public function getItemCount($countSubItems = false) {
        if($countSubItems) {
            return parent::getItemCount($countSubItems);
        } else {
            if($this->itemCount == null) {
                $itemList = new \OnlineShop\Framework\CartManager\CartItem\Listing();
                $itemList->setCartItemClassName( $this->getCartItemClassName() );
                $itemList->setCondition("cartId = " . $itemList->quote($this->getId()) . " AND parentItemKey = ''");
                $this->itemCount = $itemList->getTotalCount();
            }
            return $this->itemCount;
        }
    }


    /**
     * @static
     * @param int $userId
     * @return array
     */
    public static function getAllCartsForUser($userId) {
        $list = new \OnlineShop\Framework\CartManager\Cart\Listing();
        $db = \Pimcore\Db::get();
        $list->setCondition("userid = " . $db->quote($userId));
        $list->setCartClass( get_called_class() );
        return $list->getCarts();
    }
}
