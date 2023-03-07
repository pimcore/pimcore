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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\PricingManagerTokenInformation;

/**
 * Interface for cart implementations of online shop framework
 */
interface CartInterface
{
    /**
     * count main items only, don't consider sub items
     */
    const COUNT_MAIN_ITEMS_ONLY = 'main';

    /**
     * count sub items if available, otherwise main items
     */
    const COUNT_MAIN_OR_SUB_ITEMS = 'main_or_sub';

    /**
     * count main and sub items
     */
    const COUNT_MAIN_AND_SUB_ITEMS = 'main_and_sub';

    public function getId(): int|string|null;

    public function setId(int|string $id): void;

    /**
     * @return CartItemInterface[]
     */
    public function getItems(): array;

    /**
     * @param CartItemInterface[]|null $items
     */
    public function setItems(?array $items): void;

    public function isEmpty(): bool;

    public function getItem(string $itemKey): ?CartItemInterface;

    /**
     * @return CartItemInterface[]
     */
    public function getGiftItems(): array;

    public function getGiftItem(string $itemKey): ?CartItemInterface;

    /**
     * @param CheckoutableInterface $product
     * @param int $count
     * @param string|null $itemKey
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param AbstractSetProductEntry[] $subProducts
     * @param string|null $comment
     *
     * @return string $itemKey
     */
    public function addItem(CheckoutableInterface $product, int $count, string $itemKey = null, bool $replace = false, array $params = [], array $subProducts = [], string $comment = null): string;

    /**
     * @param string $itemKey
     * @param CheckoutableInterface $product
     * @param int $count
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param array $subProducts
     * @param string|null $comment
     *
     * @return string $itemKey
     */
    public function updateItem(string $itemKey, CheckoutableInterface $product, int $count, bool $replace = false, array $params = [], array $subProducts = [], string $comment = null): string;

    /**
     * updates count of specific cart item
     *
     * @param string $itemKey
     * @param int $count
     *
     * @return mixed
     */
    public function updateItemCount(string $itemKey, int $count): mixed;

    /**
     * @param CheckoutableInterface $product
     * @param int $count
     * @param string|null $itemKey
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param AbstractSetProductEntry[] $subProducts
     * @param string|null $comment
     *
     * @return string $itemKey
     */
    public function addGiftItem(CheckoutableInterface $product, int $count, string $itemKey = null, bool $replace = false, array $params = [], array $subProducts = [], string $comment = null): string;

    /**
     * @param string $itemKey
     * @param CheckoutableInterface $product
     * @param int $count
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param array $subProducts
     * @param string|null $comment
     *
     * @return string $itemKey
     */
    public function updateGiftItem(string $itemKey, CheckoutableInterface $product, int $count, bool $replace = false, array $params = [], array $subProducts = [], string $comment = null): string;

    public function removeItem(string $itemKey): void;

    /**
     * clears all items of cart
     *
     * @return void
     */
    public function clear(): void;

    /**
     * calculates amount of items in cart
     *
     * @param string $countSubItems - use one of COUNT_MAIN_ITEMS_ONLY, COUNT_MAIN_OR_SUB_ITEMS, COUNT_MAIN_AND_SUB_ITEMS
     *
     * @return int
     */
    public function getItemAmount(string $countSubItems = self::COUNT_MAIN_ITEMS_ONLY): int;

    /**
     * counts items in cart (does not consider item amount)
     *
     * @param string $countSubItems - use one of COUNT_MAIN_ITEMS_ONLY, COUNT_MAIN_OR_SUB_ITEMS, COUNT_MAIN_AND_SUB_ITEMS
     *
     * @return int
     */
    public function getItemCount(string $countSubItems = self::COUNT_MAIN_ITEMS_ONLY): int;

    /**
     * @param int $count
     *
     * @return CartItemInterface[]
     */
    public function getRecentlyAddedItems(int $count): array;

    /**
     * returns price calculator of cart
     *
     * @return CartPriceCalculatorInterface
     */
    public function getPriceCalculator(): CartPriceCalculatorInterface;

    /**
     * executes necessary steps when cart is modified - e.g. updating modification timestamp, resetting cart price calculator etc.
     *
     * -> is called internally every time when cart has been changed.
     *
     * @return $this
     */
    public function modified(): static;

    /**
     * Set custom checkout data for cart.
     * can be used for delivery information, ...
     *
     * @param string $key
     * @param string $data
     */
    public function setCheckoutData(string $key, string $data): void;

    /**
     * Get custom checkout data for cart with given key.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getCheckoutData(string $key): ?string;

    /**
     * get name of cart.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * set name of cart.
     *
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name): void;

    /**
     * returns if cart is bookable.
     * default implementation checks if all products of cart a bookable.
     *
     * @return bool
     */
    public function getIsBookable(): bool;

    public function getCreationDate(): \DateTime;

    /**
     * @param null|\DateTime $creationDate
     *
     * @return void
     */
    public function setCreationDate(\DateTime $creationDate = null): void;

    public function getModificationDate(): ?\DateTime;

    /**
     * @param null|\DateTime $modificationDate
     *
     * @return void
     */
    public function setModificationDate(\DateTime $modificationDate = null): void;

    /**
     * sorts all items in cart according to a given callback function
     *
     * @param callable $value_compare_func
     *
     * @return $this
     */
    public function sortItems(callable $value_compare_func): static;

    /**
     * saves cart
     *
     * @return void
     */
    public function save(): void;

    /**
     * deletes cart
     *
     * @return void
     */
    public function delete(): void;

    /**
     * @static
     *
     * @param int $id
     *
     * @return CartInterface|null
     */
    public static function getById(int $id): ?CartInterface;

    /**
     * returns all carts for given userId
     *
     * @static
     *
     * @param int $userId
     *
     * @return CartInterface[]
     */
    public static function getAllCartsForUser(int $userId): array;

    /**
     * @param string $token
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function addVoucherToken(string $token): bool;

    public function removeVoucherToken(string $token): bool;

    /**
     * @return string[]
     */
    public function getVoucherTokenCodes(): array;

    /**
     * Returns detail information of added voucher codes and if they are considered by pricing rules
     *
     * @return PricingManagerTokenInformation[]
     */
    public function getPricingManagerTokenInformationDetails(): array;

    /**
     * Checks if an error code is a defined Voucher Error Code.
     */
    public function isVoucherErrorCode(int $errorCode): bool;
}
