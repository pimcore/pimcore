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

use Pimcore\Cache\Runtime;
use Pimcore\Logger;

class Cart extends AbstractCart implements CartInterface
{
    /**
     * @return string
     */
    protected function getCartItemClassName()
    {
        return CartItem::class;
    }

    /**
     * @return string
     */
    protected function getCartCheckoutDataClassName()
    {
        return CartCheckoutData::class;
    }

    public function save()
    {
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
    public function delete()
    {
        $this->setIgnoreReadonly();

        $cacheKey = Cart\Dao::TABLE_NAME . '_' . $this->getId();
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
     *
     * @return Cart|null
     */
    public static function getById($id)
    {
        $cacheKey = Cart\Dao::TABLE_NAME . '_' . $id;

        try {
            $cart = Runtime::get($cacheKey);
        } catch (\Exception $e) {
            try {
                $cartClass = get_called_class();
                // @var Cart $cart
                $cart = new $cartClass;
                $cart->setIgnoreReadonly();
                $cart->getDao()->getById($id);

                //call getter to make sure modification date is set too (not only timestamp)
                $cart->getModificationDate();

                $dataList = new CartCheckoutData\Listing();
                $dataList->setCondition('cartId = ' . $dataList->quote($cart->getId()));

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

    public function getItems()
    {
        if ($this->items === null) {
            $itemList = new CartItem\Listing();
            $itemList->setCartItemClassName($this->getCartItemClassName());
            $itemList->setCondition('cartId = ' . $itemList->quote($this->getId()) . " AND parentItemKey = ''");
            $itemList->setOrderKey(['sortIndex', 'addedDateTimestamp']);
            $items = [];
            foreach ($itemList->getCartItems() as $item) {
                if (static::isValidCartItem($item)) {
                    $item->setCart($this);
                    $items[$item->getItemKey()] = $item;
                }
            }
            $this->items = $items;
            $this->setIgnoreReadonly();

            $dateBackup = $this->getModificationDate();
            $this->modified();
            $this->setModificationDate($dateBackup);

            $this->unsetIgnoreReadonly();
        }

        return $this->items;
    }

    /**
     * @param mixed $countSubItems - use one of COUNT_MAIN_ITEMS_ONLY, COUNT_MAIN_OR_SUB_ITEMS, COUNT_MAIN_AND_SUB_ITEMS
     *
     * @return int
     */
    public function getItemCount(/*?string*/ $countSubItems = false)
    {
        if (is_bool($countSubItems) || $countSubItems === null) {
            @trigger_error(
                'Use of true/false for $countSubItems is deprecated and will be removed in version 10.0.0. Use one of COUNT_MAIN_ITEMS_ONLY, COUNT_MAIN_OR_SUB_ITEMS, COUNT_MAIN_AND_SUB_ITEMS instead.',
                E_USER_DEPRECATED
            );
        }

        //TODO remove this in Pimcore 10.0.0
        if ($countSubItems === false) {
            $countSubItems = self::COUNT_MAIN_ITEMS_ONLY;
        } elseif ($countSubItems !== self::COUNT_MAIN_ITEMS_ONLY && $countSubItems !== self::COUNT_MAIN_OR_SUB_ITEMS && $countSubItems !== self::COUNT_MAIN_AND_SUB_ITEMS) {
            $countSubItems = self::COUNT_MAIN_AND_SUB_ITEMS;
        }

        if ($countSubItems === self::COUNT_MAIN_ITEMS_ONLY) {
            if ($this->itemCount == null) {
                $itemList = new CartItem\Listing();
                $itemList->setCartItemClassName($this->getCartItemClassName());
                $itemList->setCondition('cartId = ' . $itemList->quote($this->getId()) . " AND parentItemKey = ''");
                $this->itemCount = $itemList->getTotalCount();
            }

            return $this->itemCount;
        } else {
            return parent::getItemCount($countSubItems);
        }
    }

    public function getItemAmount(/*?string*/ $countSubItems = false)
    {
        if (is_bool($countSubItems)) {
            @trigger_error(
                'Use of true/false for $countSubItems is deprecated and will be removed in version 10.0.0. Use one of COUNT_MAIN_ITEMS_ONLY, COUNT_MAIN_OR_SUB_ITEMS, COUNT_MAIN_AND_SUB_ITEMS instead.',
                E_USER_DEPRECATED
            );
        }

        if ($countSubItems === false) {
            $countSubItems = self::COUNT_MAIN_ITEMS_ONLY;
        } elseif ($countSubItems !== self::COUNT_MAIN_ITEMS_ONLY && $countSubItems !== self::COUNT_MAIN_OR_SUB_ITEMS && $countSubItems !== self::COUNT_MAIN_AND_SUB_ITEMS) {
            $countSubItems = self::COUNT_MAIN_OR_SUB_ITEMS;
        }

        if ($countSubItems === self::COUNT_MAIN_ITEMS_ONLY) {
            if ($this->itemAmount == null) {
                $itemList = new CartItem\Listing();
                $itemList->setCartItemClassName($this->getCartItemClassName());
                $itemList->setCondition('cartId = ' . $itemList->quote($this->getId()) . " AND parentItemKey = ''");
                $this->itemAmount = $itemList->getTotalAmount();
            }

            return $this->itemAmount;
        } else {
            return parent::getItemAmount($countSubItems);
        }
    }

    /**
     * @static
     *
     * @param int $userId
     *
     * @return array
     */
    public static function getAllCartsForUser($userId)
    {
        $list = new Cart\Listing();
        $db = \Pimcore\Db::get();
        $list->setCondition('userid = ' . $db->quote($userId));
        $list->setCartClass(get_called_class());

        return $list->getCarts();
    }
}
