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

    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     */
    public function setId($id);

    /**
     * @return CartItemInterface[]
     */
    public function getItems();

    /**
     * @param CartItemInterface[]|null $items
     */
    public function setItems($items);

    /**
     * @return bool
     */
    public function isEmpty();

    /**
     * @param string $itemKey
     *
     * @return CartItemInterface|null
     */
    public function getItem($itemKey);

    /**
     * @return CartItemInterface[]
     */
    public function getGiftItems();

    /**
     * @param string $itemKey
     *
     * @return CartItemInterface|null
     */
    public function getGiftItem($itemKey);

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
    public function addItem(CheckoutableInterface $product, $count, $itemKey = null, $replace = false, $params = [], $subProducts = [], $comment = null);

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
    public function updateItem($itemKey, CheckoutableInterface $product, $count, $replace = false, $params = [], $subProducts = [], $comment = null);

    /**
     * updates count of specific cart item
     *
     * @param string $itemKey
     * @param int $count
     *
     * @return mixed
     */
    public function updateItemCount($itemKey, $count);

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
    public function addGiftItem(CheckoutableInterface $product, $count, $itemKey = null, $replace = false, $params = [], $subProducts = [], $comment = null);

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
    public function updateGiftItem($itemKey, CheckoutableInterface $product, $count, $replace = false, $params = [], $subProducts = [], $comment = null);

    /**
     * @param string $itemKey
     *
     * @return void
     */
    public function removeItem($itemKey);

    /**
     * clears all items of cart
     *
     * @return void
     */
    public function clear();

    /**
     * calculates amount of items in cart
     *
     * @param string $countSubItems - use one of COUNT_MAIN_ITEMS_ONLY, COUNT_MAIN_OR_SUB_ITEMS, COUNT_MAIN_AND_SUB_ITEMS
     *
     * @return int
     */
    public function getItemAmount(string $countSubItems = self::COUNT_MAIN_ITEMS_ONLY);

    /**
     * counts items in cart (does not consider item amount)
     *
     * @param string $countSubItems - use one of COUNT_MAIN_ITEMS_ONLY, COUNT_MAIN_OR_SUB_ITEMS, COUNT_MAIN_AND_SUB_ITEMS
     *
     * @return int
     */
    public function getItemCount(string $countSubItems = self::COUNT_MAIN_ITEMS_ONLY);

    /**
     * @param int $count
     *
     * @return CartItemInterface[]
     */
    public function getRecentlyAddedItems($count);

    /**
     * returns price calculator of cart
     *
     * @return CartPriceCalculatorInterface
     */
    public function getPriceCalculator();

    /**
     * executes necessary steps when cart is modified - e.g. updating modification timestamp, resetting cart price calculator etc.
     *
     * -> is called internally every time when cart has been changed.
     *
     * @return $this
     */
    public function modified();

    /**
     * Set custom checkout data for cart.
     * can be used for delivery information, ...
     *
     * @param string $key
     * @param string $data
     */
    public function setCheckoutData($key, $data);

    /**
     * Get custom checkout data for cart with given key.
     *
     * @param string $key
     *
     * @return string|null
     */
    public function getCheckoutData($key);

    /**
     * get name of cart.
     *
     * @return string
     */
    public function getName();

    /**
     * set name of cart.
     *
     * @param string $name
     *
     * @return void
     */
    public function setName($name);

    /**
     * returns if cart is bookable.
     * default implementation checks if all products of cart a bookable.
     *
     * @return bool
     */
    public function getIsBookable();

    /**
     * @return \DateTime
     */
    public function getCreationDate();

    /**
     * @param null|\DateTime $creationDate
     *
     * @return void
     */
    public function setCreationDate(\DateTime $creationDate = null);

    /**
     * @return \DateTime
     */
    public function getModificationDate();

    /**
     * @param null|\DateTime $modificationDate
     *
     * @return void
     */
    public function setModificationDate(\DateTime $modificationDate = null);

    /**
     * sorts all items in cart according to a given callback function
     *
     * @param callable $value_compare_func
     *
     * @return CartInterface
     */
    public function sortItems(callable $value_compare_func);

    /**
     * saves cart
     *
     * @return void
     */
    public function save();

    /**
     * deletes cart
     *
     * @return void
     */
    public function delete();

    /**
     * @static
     *
     * @param int $id
     *
     * @return CartInterface
     */
    public static function getById($id);

    /**
     * returns all carts for given userId
     *
     * @static
     *
     * @param int $userId
     *
     * @return CartInterface[]
     */
    public static function getAllCartsForUser($userId);

    /**
     * @param string $token
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function addVoucherToken($token);

    /**
     * @param string $token
     *
     * @return bool
     */
    public function removeVoucherToken($token);

    /**
     * @return string[]
     */
    public function getVoucherTokenCodes();

    /**
     * Returns detail information of added voucher codes and if they are considered by pricing rules
     *
     * @return PricingManagerTokenInformation[]
     */
    public function getPricingManagerTokenInformationDetails(): array;

    /**
     * @return bool
     */
    public function isVoucherErrorCode($errorCode);
}
