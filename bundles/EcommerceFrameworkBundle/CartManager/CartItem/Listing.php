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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItemInterface;

/**
 * @method CartItemInterface[] load()
 * @method CartItemInterface current()
 * @method int getTotalCount()
 * @method int getTotalAmount()
 * @method \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem\Listing\Dao getDao()
 */
class Listing extends \Pimcore\Model\Listing\AbstractListing
{
    /**
     * @var array
     *
     * @deprecated use getter/setter methods or $this->data
     */
    public $cartItems;

    /**
     * @var array
     */
    protected $order = ['ASC'];

    /**
     * @var array
     */
    protected $orderKey = ['`sortIndex`', '`addedDateTimestamp`'];

    public function __construct()
    {
        $this->cartItems = & $this->data;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        if (in_array($key, ['productId', 'cartId', 'count', 'itemKey', 'addedDateTimestamp', 'sortIndex'])) {
            return true;
        }

        return false;
    }

    /**
     * @return CartItemInterface[]
     */
    public function getCartItems()
    {
        return $this->getData();
    }

    /**
     * @param CartItemInterface[] $cartItems
     *
     * @return static
     */
    public function setCartItems($cartItems)
    {
        return $this->setData($cartItems);
    }

    /**
     * @param string $className
     */
    public function setCartItemClassName($className)
    {
        $this->getDao()->setClassName($className);
    }
}
