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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token;

/**
 * Interface for cart implementations of online shop framework
 */
interface ICart
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @param $id int
     *
     * @return void
     */
    public function setId($id);

    /**
     * @return ICartItem[]
     */
    public function getItems();

    /**
     * @return bool
     */
    public function isEmpty();

    /**
     * returns if cart is read only
     * default implementation checks if order object exists and if order state is PAYMENT_PENDING
     *
     * @return bool
     */
    public function isCartReadOnly();

    /**
     * @param string $itemKey
     *
     * @return ICartItem
     */
    public function getItem($itemKey);

    /**
     * @return ICartItem[]
     */
    public function getGiftItems();

    /**
     * @param string $itemKey
     *
     * @return ICartItem
     */
    public function getGiftItem($itemKey);

    /**
     * @param ICheckoutable $product
     * @param int $count
     * @param null $itemKey
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param AbstractSetProductEntry[] $subProducts
     * @param string $comment
     *
     * @return string $itemKey
     */
    public function addItem(ICheckoutable $product, $count, $itemKey = null, $replace = false, $params = [], $subProducts = [], $comment = null);

    /**
     * @param string $itemKey
     * @param ICheckoutable $product
     * @param int $count
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param array $subProducts
     * @param null $comment
     *
     * @return string $itemKey
     */
    public function updateItem($itemKey, ICheckoutable $product, $count, $replace = false, $params = [], $subProducts = [], $comment = null);

    /**
     * updates count of specific cart item
     *
     * @param $itemKey
     * @param $count
     *
     * @return mixed
     */
    public function updateItemCount($itemKey, $count);

    /**
     * @param ICheckoutable $product
     * @param int $count
     * @param null $itemKey
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param AbstractSetProductEntry[] $subProducts
     * @param string $comment
     *
     * @return string $itemKey
     */
    public function addGiftItem(ICheckoutable $product, $count, $itemKey = null, $replace = false, $params = [], $subProducts = [], $comment = null);

    /**
     * @param string $itemKey
     * @param ICheckoutable $product
     * @param int $count
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param array $subProducts
     * @param null $comment
     *
     * @return string $itemKey
     */
    public function updateGiftItem($itemKey, ICheckoutable $product, $count, $replace = false, $params = [], $subProducts = [], $comment = null);

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
     * @param bool $countSubItems
     *
     * @return int
     */
    public function getItemAmount($countSubItems = false);

    /**
     * counts items in cart (does not consider item amount)
     *
     * @param bool|false $countSubItems
     *
     * @return int
     */
    public function getItemCount($countSubItems = false);

    /**
     * @param int $count
     *
     * @return ICartItem[]
     */
    public function getRecentlyAddedItems($count);

    /**
     * returns price calculator of cart
     *
     * @return ICartPriceCalculator
     */
    public function getPriceCalculator();

    /**
     * Set custom checkout data for cart.
     * can be used for delivery information, ...
     *
     * @param  $key string
     * @param  $data string
     *
     * @return void
     */
    public function setCheckoutData($key, $data);

    /**
     * Get custom checkout data for cart with given key.
     *
     * @param  $key string
     *
     * @return string
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
     * @param $name
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
     * @return ICart
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
     * @param $id
     *
     * @return ICart
     */
    public static function getById($id);

    /**
     * returns all carts for given userId
     *
     * @static
     * @param $userId
     *
     * @return ICart[]
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
     * @param Token $token
     *
     * @return bool
     */
    public function removeVoucherToken($token);

    /**
     * @return string[]
     */
    public function getVoucherTokenCodes();

    /**
     * @return bool
     */
    public function isVoucherErrorCode($errorCode);
}
