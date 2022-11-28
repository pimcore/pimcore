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

use Pimcore\Bundle\EcommerceFrameworkBundle\ComponentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;

interface CartManagerInterface extends ComponentInterface
{
    /**
     * Returns cart class name configured in the ecommerce framework config
     *
     * Is also responsible for checking if guest cart class should be used or not,
     * by calling \Pimcore\Bundle\EcommerceFrameworkBundle\Environment::getUseGuestCart();
     *
     * @return string
     */
    public function getCartClassName(): string;

    /**
     * Adds item to given cart
     *
     * @param CheckoutableInterface $product - product to add
     * @param int $count
     * @param string|null $key            - optional key of cart where the item should be added to
     * @param string|null $itemKey   - optional item key
     * @param bool $replace          - replace item if same key already exists
     * @param array $params          - optional addtional item information
     * @param AbstractSetProductEntry[] $subProducts
     * @param string|null $comment
     *
     * @return string - item key
     */
    public function addToCart(
        CheckoutableInterface $product,
        int $count,
        string $key = null,
        string $itemKey = null,
        bool $replace = false,
        array $params = [],
        array $subProducts = [],
        string $comment = null
    ): string;

    /**
     * Removes item from given cart
     *
     * @param string $itemKey
     * @param string|null $key     - optional identification of cart in case of multi cart
     *
     * @return void
     */
    public function removeFromCart(string $itemKey, string $key = null): void;

    /**
     * Returns cart
     *
     * @param string|null $key - optional identification of cart in case of multi cart
     *
     * @return CartInterface
     */
    public function getCart(string $key = null): CartInterface;

    /**
     * Returns cart by name
     *
     * @param string $name
     *
     * @return null|CartInterface
     */
    public function getCartByName(string $name): ?CartInterface;

    /**
     * Returns cart by name, if it does not exist, it will be created
     *
     * @param string $name
     *
     * @return CartInterface
     */
    public function getOrCreateCartByName(string $name): CartInterface;

    /**
     * Returns all carts
     *
     * @return CartInterface[]
     */
    public function getCarts(): array;

    /**
     * Clears given cart
     *
     * @param string|null $key - optional identification of cart in case of multi cart
     *
     * @return void
     */
    public function clearCart(string $key = null): void;

    /**
     * Creates new cart
     *
     * @param array $params - array of cart information
     *
     * @return string|int key of new created cart
     */
    public function createCart(array $params): int|string;

    /**
     * Deletes cart
     *
     * @param string|null $key - optional identification of cart in case of multi cart
     *
     * @return void
     */
    public function deleteCart(string $key = null): void;

    /**
     * Creates price calculator for given cart
     *
     */
    public function getCartPriceCalculator(CartInterface $cart): CartPriceCalculatorInterface;

    /**
     * Resets cart manager - carts need to be reloaded after reset() is called
     *
     * @return void
     */
    public function reset(): void;
}
