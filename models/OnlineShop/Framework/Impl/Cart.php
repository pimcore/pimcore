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


class OnlineShop_Framework_Impl_Cart extends OnlineShop_Framework_AbstractCart implements OnlineShop_Framework_ICart {

    /**
     * @return string
     */
    protected function getCartItemClassName() {
        return "OnlineShop_Framework_Impl_CartItem";
    }

    /**
     * @return string
     */
    protected function getCartCheckoutDataClassName() {
        return "OnlineShop_Framework_Impl_CartCheckoutData";
    }

    public function save() {
        $this->getResource()->save();
        OnlineShop_Framework_Impl_CartItem::removeAllFromCart($this->getId());
        foreach ($this->items as $item) {
            $item->save();
        }

        OnlineShop_Framework_Impl_CartCheckoutData::removeAllFromCart($this->getId());
        foreach ($this->checkoutData as $data) {
            $data->save();
        }
    }

    /**
     * @return void
     */
    public function delete() {
        $this->setIgnoreReadonly();

        $cacheKey = OnlineShop_Framework_Impl_Cart_Resource::TABLE_NAME . "_" . $this->getId();
        Zend_Registry::set($cacheKey, null);

        OnlineShop_Framework_Impl_CartItem::removeAllFromCart($this->getId());
        OnlineShop_Framework_Impl_CartCheckoutData::removeAllFromCart($this->getId());

        $this->clear();
        $this->getResource()->delete();
    }


    /**
     * @param callable $value_compare_func
     *
     * @return $this
     */
    public function sortItems(callable $value_compare_func)
    {
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
     * @return OnlineShop_Framework_Impl_Cart
     */
    public static function getById($id) {
        $cacheKey = OnlineShop_Framework_Impl_Cart_Resource::TABLE_NAME . "_" . $id;
        try {
            $cart = Zend_Registry::get($cacheKey);
        }
        catch (Exception $e) {

            try {
                $cartClass = get_called_class();
                /* @var OnlineShop_Framework_ICart $cart */
                $cart = new $cartClass;
                $cart->setIgnoreReadonly();
                $cart->getResource()->getById($id);

                $itemList = new OnlineShop_Framework_Impl_CartItem_List();
                $itemList->setCartItemClassName( $cart->getCartItemClassName() );
                $itemList->setCondition("cartId = " . $itemList->quote($cart->getId()) . " AND parentItemKey = ''");
                $itemList->setOrderKey('sortIndex');
                $items = array();
                foreach ($itemList->getCartItems() as $item) {
                    if(static::isValidCartItem($item)){
                        $items[$item->getItemKey()] = $item;
                    }
                }
                $mod = $cart->getModificationDate();
                $cart->setItems($items);
                $cart->setModificationDate( $mod );

                $dataList = new OnlineShop_Framework_Impl_CartCheckoutData_List();
                $dataList->setCondition("cartId = " . $dataList->quote($cart->getId()));


                foreach ($dataList->getCartCheckoutDataItems() as $data) {
                    $cart->setCheckoutData($data->getKey(), $data->getData());
                }

                $cart->unsetIgnoreReadonly();

                Zend_Registry::set($cacheKey, $cart);
            } catch (Exception $ex) {
                Logger::debug($ex->getMessage());
                return null;
            }

        }

        return $cart;
    }

    /**
     * @static
     * @param int $userId
     * @return array
     */
    public static function getAllCartsForUser($userId) {
        $list = new OnlineShop_Framework_Impl_Cart_List();
        $db = \Pimcore\Resource::get();
        $list->setCondition("userid = " . $db->quote($userId));
        $list->setCartClass( get_called_class() );
        return $list->getCarts();
    }
}
