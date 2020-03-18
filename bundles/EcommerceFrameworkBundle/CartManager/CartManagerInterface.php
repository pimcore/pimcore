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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\ComponentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;

interface CartManagerInterface extends ComponentInterface
{
    /**
     * Returns cart class name configured in the ecommerce framework config
     *
     * Is also responsible for checking if guest cart class should be used or not,
     * by calling \Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment::getUseGuestCart();
     *
     * @return string
     */
    public function getCartClassName();

    /**
     * Adds item to given cart
     *
     * @param CheckoutableInterface $product - product to add
     * @param float $count
     * @param string $key            - optional key of cart where the item should be added to
     * @param null|string $itemKey   - optional item key
     * @param bool $replace          - replace item if same key already exists
     * @param array $params          - optional addtional item information
     * @param AbstractSetProductEntry[] $subProducts
     * @param null|string $comment
     *
     * @return string - item key
     */
    public function addToCart(
        CheckoutableInterface $product,
        $count,
        $key = null,
        $itemKey = null,
        $replace = false,
        array $params = [],
        array $subProducts = [],
        $comment = null
    );

    /**
     * Removes item from given cart
     *
     * @param string      $itemKey
     * @param null|string $key     - optional identification of cart in case of multi cart
     *
     * @return void
     */
    public function removeFromCart($itemKey, $key = null);

    /**
     * Returns cart
     *
     * @param null|string $key - optional identification of cart in case of multi cart
     *
     * @return CartInterface
     */
    public function getCart($key = null);

    /**
     * Returns cart by name
     *
     * @param string $name
     *
     * @return null|CartInterface
     */
    public function getCartByName($name);

    /**
     * Returns cart by name, if it does not exist, it will be created
     *
     * @param string $name
     *
     * @return CartInterface
     */
    public function getOrCreateCartByName($name);

    /**
     * Returns all carts
     *
     * @return CartInterface[]
     */
    public function getCarts();

    /**
     * Clears given cart
     *
     * @param null|string  $key - optional identification of cart in case of multi cart
     *
     * @return void
     */
    public function clearCart($key = null);

    /**
     * Creates new cart
     *
     * @param array $params - array of cart information
     *
     * @return string key of new created cart
     */
    public function createCart(array $params);

    /**
     * Deletes cart
     *
     * @param null|string $key - optional identification of cart in case of multi cart
     *
     * @return void
     */
    public function deleteCart($key = null);

    /**
     * Creates price calculator for given cart
     *
     * @return CartPriceCalculatorInterface
     */
    public function getCartPriceCalculator(CartInterface $cart);

    /**
     * Resets cart manager - carts need to be reloaded after reset() is called
     *
     * @return void
     */
    public function reset();
}

class_alias(CartManagerInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartManager');
