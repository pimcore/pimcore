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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager;

use Pimcore\Cache\Runtime;
use Pimcore\Logger;

class Cart extends AbstractCart implements ICart {

    /**
     * @return string
     */
    protected function getCartItemClassName() {
        return '\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartItem';
    }

    /**
     * @return string
     */
    protected function getCartCheckoutDataClassName() {
        return '\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartCheckoutData';
    }

    public function save() {
        //make sure the items have been loaded otherwise we might loose the products (e.g. when a voucher is added)
        $items = $this->getItems();

        $this->getDao()->save();
        CartItem::removeAllFromCart($this->getId());
        foreach ((array)$items as $item) {
            $item->save();
        }

        CartCheckoutData::removeAllFromCart($this->getId());
        foreach ($this->checkoutData as $data) {
            $data->save();
        }
    }

    /**
     * @return void
     */
    public function delete() {
        $this->setIgnoreReadonly();

        $cacheKey = Cart\Dao::TABLE_NAME . "_" . $this->getId();
        Runtime::set($cacheKey, null);

        CartItem::removeAllFromCart($this->getId());
        CartCheckoutData::removeAllFromCart($this->getId());

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
        $cacheKey = Cart\Dao::TABLE_NAME . "_" . $id;
        try {
            $cart = Runtime::get($cacheKey);
        }
        catch (\Exception $e) {

            try {
                $cartClass = get_called_class();
                /* @var ICart $cart */
                $cart = new $cartClass;
                $cart->setIgnoreReadonly();
                $cart->getDao()->getById($id);

                $mod = $cart->getModificationDate();
                $cart->setModificationDate( $mod );

                $dataList = new CartCheckoutData\Listing();
                $dataList->setCondition("cartId = " . $dataList->quote($cart->getId()));


                foreach ($dataList->getCartCheckoutDataItems() as $data) {
                    $cart->setCheckoutData($data->getKey(), $data->getData());
                }

                $cart->unsetIgnoreReadonly();

                Runtime::set($cacheKey, $cart);
            } catch (\Exception $ex) {
                Logger::debug($ex->getMessage());
                return null;
            }

        }

        return $cart;
    }

    public function getItems() {
        if($this->items === null) {
            $itemList = new CartItem\Listing();
            $itemList->setCartItemClassName( $this->getCartItemClassName() );
            $itemList->setCondition("cartId = " . $itemList->quote($this->getId()) . " AND parentItemKey = ''");
            $itemList->setOrderKey(['sortIndex', 'addedDateTimestamp']);
            $items = array();
            foreach ($itemList->getCartItems() as $item) {
                if(static::isValidCartItem($item)){
                    $item->setCart($this);
                    $items[$item->getItemKey()] = $item;
                }
            }
            $this->items = $items;
            $this->setIgnoreReadonly();
            $this->modified();
            $this->unsetIgnoreReadonly();
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
                $itemList = new CartItem\Listing();
                $itemList->setCartItemClassName( $this->getCartItemClassName() );
                $itemList->setCondition("cartId = " . $itemList->quote($this->getId()) . " AND parentItemKey = ''");
                $this->itemCount = $itemList->getTotalCount();
            }
            return $this->itemCount;
        }
    }

    public function getItemAmount($countSubItems = false) {
        if($countSubItems) {
            return parent::getItemAmount($countSubItems);
        } else {
            if($this->itemAmount == null) {
                $itemList = new CartItem\Listing();
                $itemList->setCartItemClassName( $this->getCartItemClassName() );
                $itemList->setCondition("cartId = " . $itemList->quote($this->getId()) . " AND parentItemKey = ''");
                $this->itemAmount = $itemList->getTotalAmount();
            }
            return $this->itemAmount;
        }
    }

    /**
     * @static
     * @param int $userId
     * @return array
     */
    public static function getAllCartsForUser($userId) {
        $list = new Cart\Listing();
        $db = \Pimcore\Db::get();
        $list->setCondition("userid = " . $db->quote($userId));
        $list->setCartClass( get_called_class() );
        return $list->getCarts();
    }
}
