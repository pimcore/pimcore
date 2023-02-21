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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem\Dao;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Logger;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @method Dao getDao()
 */
class CartItem extends AbstractCartItem implements CartItemInterface
{
    protected int $sortIndex = 0;

    public function setSortIndex(int $sortIndex): void
    {
        $this->sortIndex = (int)$sortIndex;
    }

    public function getSortIndex(): int
    {
        return $this->sortIndex;
    }

    public function getCart(): ?CartInterface
    {
        if (empty($this->cart)) {
            $cartClass = '\\'.Factory::getInstance()->getCartManager()->getCartClassName();
            $this->cart = $cartClass::getById($this->cartId);
        }

        return $this->cart;
    }

    public function save(): void
    {
        $items = $this->getSubItems();
        if (!empty($this->subItems)) {
            foreach ($this->subItems as $item) {
                $item->save();
            }
        }
        $this->getDao()->save();
    }

    public static function getByCartIdItemKey(int|string $cartId, string $itemKey, string $parentKey = ''): ?CartItemInterface
    {
        $cacheKey = CartItem\Dao::TABLE_NAME . '_' . $cartId . '_' . $parentKey . $itemKey;

        try {
            $cartItem = RuntimeCache::get($cacheKey);
        } catch (\Exception $e) {
            try {
                $cartItem = new static();
                $cartItem->getDao()->getByCartIdItemKey($cartId, $itemKey, $parentKey);
                $cartItem->getSubItems();
                RuntimeCache::set($cacheKey, $cartItem);
            } catch (NotFoundException $ex) {
                Logger::debug($ex->getMessage());

                return null;
            }
        }

        return $cartItem;
    }

    public static function removeAllFromCart(int|string $cartId): void
    {
        $cartItem = new static();
        $cartItem->getDao()->removeAllFromCart($cartId);
    }

    /**
     * @return CartItemInterface[]
     */
    public function getSubItems(): array
    {
        if ($this->subItems == null) {
            $this->subItems = [];

            $itemClass = get_class($this) . '\\Listing';
            if (!\Pimcore\Tool::classExists($itemClass)) {
                $itemClass = get_class($this) . '_List';
                if (!\Pimcore\Tool::classExists($itemClass)) {
                    throw new \Exception("Class $itemClass does not exist.");
                }
            }
            $itemList = new $itemClass();
            $itemList->setCartItemClassName(get_class($this));

            $db = \Pimcore\Db::get();
            $itemList->setCondition('cartId = ' . $db->quote($this->getCartId()) . ' AND parentItemKey = ' . $db->quote($this->getItemKey()));

            foreach ($itemList->getCartItems() as $item) {
                if ($item->getProduct() != null) {
                    $this->subItems[] = $item;
                } else {
                    Logger::warn('product ' . $item->getProductId() . ' not found');
                }
            }
        }

        return $this->subItems;
    }
}
