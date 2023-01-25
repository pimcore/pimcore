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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItemInterface;

/**
 * @method CartItemInterface[] load()
 * @method CartItemInterface|false current()
 * @method int getTotalCount()
 * @method int getTotalAmount()
 * @method \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem\Listing\Dao getDao()
 */
class Listing extends \Pimcore\Model\Listing\AbstractListing
{
    protected array $order = ['ASC'];

    protected array $orderKey = ['`sortIndex`', '`addedDateTimestamp`'];

    public function isValidOrderKey(string $key): bool
    {
        if (in_array($key, ['productId', 'cartId', 'count', 'itemKey', 'addedDateTimestamp', 'sortIndex'])) {
            return true;
        }

        return false;
    }

    /**
     * @return CartItemInterface[]
     */
    public function getCartItems(): array
    {
        return $this->getData();
    }

    /**
     * @param CartItemInterface[] $cartItems
     *
     * @return $this
     */
    public function setCartItems(array $cartItems): static
    {
        return $this->setData($cartItems);
    }

    public function setCartItemClassName(string $className): void
    {
        $this->getDao()->setClassName($className);
    }
}
