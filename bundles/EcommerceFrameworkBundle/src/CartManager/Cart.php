<?php
declare(strict_types=1);

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

use Pimcore\Cache\RuntimeCache;
use Pimcore\Logger;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @method Cart\Dao getDao()
 */
class Cart extends AbstractCart implements CartInterface
{
    protected function getCartItemClassName(): string
    {
        return CartItem::class;
    }

    protected function getCartCheckoutDataClassName(): string
    {
        return CartCheckoutData::class;
    }

    public function save(): void
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

    public function delete(): void
    {
        $cacheKey = Cart\Dao::TABLE_NAME . '_' . $this->getId();
        RuntimeCache::set($cacheKey, null);

        CartItem::removeAllFromCart($this->getId());
        CartCheckoutData::removeAllFromCart($this->getId());

        $this->clear();
        $this->getDao()->delete();
    }

    public function sortItems(callable $value_compare_func): static
    {
        //call get items to lazy load items
        $this->getItems();

        uasort($this->items, $value_compare_func);

        $arrayKeys = array_keys($this->items);
        foreach ($arrayKeys as $index => $key) {
            /** @var CartItem $ite */
            $ite = $this->items[$key];
            $ite->setSortIndex($index);
        }

        return $this;
    }

    public static function getById(int $id): ?Cart
    {
        $cacheKey = Cart\Dao::TABLE_NAME . '_' . $id;

        try {
            $cart = RuntimeCache::get($cacheKey);
        } catch (\Exception $e) {
            try {
                $cartClass = get_called_class();
                /** @var Cart $cart */
                $cart = new $cartClass;
                $cart->getDao()->getById($id);

                //call getter to make sure modification date is set too (not only timestamp)
                $cart->getModificationDate();

                $dataList = new CartCheckoutData\Listing();
                $dataList->setCondition('cartId = ' . $dataList->quote($cart->getId()));

                foreach ($dataList->getCartCheckoutDataItems() as $data) {
                    $cart->setCheckoutData($data->getKey(), $data->getData());
                }

                RuntimeCache::set($cacheKey, $cart);
            } catch (NotFoundException $ex) {
                Logger::debug($ex->getMessage());

                return null;
            }
        }

        return $cart;
    }

    public function getItems(): array
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

            $dateBackup = $this->getModificationDate();
            $this->modified();
            $this->setModificationDate($dateBackup);
        }

        return $this->items;
    }

    /**
     * @param string $countSubItems - use one of COUNT_MAIN_ITEMS_ONLY, COUNT_MAIN_OR_SUB_ITEMS, COUNT_MAIN_AND_SUB_ITEMS
     *
     * @return int
     */
    public function getItemCount(string $countSubItems = self::COUNT_MAIN_ITEMS_ONLY): int
    {
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

    public function getItemAmount(string $countSubItems = self::COUNT_MAIN_ITEMS_ONLY): int
    {
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
    public static function getAllCartsForUser(int $userId): array
    {
        $list = new Cart\Listing();
        $db = \Pimcore\Db::get();
        $list->setCondition('userid = ' . $db->quote($userId));
        $list->setCartClass(get_called_class());

        return $list->getCarts();
    }
}
