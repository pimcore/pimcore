<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\CartManager;

/**
 * interface for cart implementations of online shop framework
 */
interface ICart {

    /**
     * @abstract
     * @return int
     */
    public function getId();

    /**
     * @param $id int
     * @return void
     */
    public function setId($id);

    /**
     * @abstract
     * @return \OnlineShop\Framework\CartManager\ICartItem[]
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
     * @return \OnlineShop\Framework\CartManager\ICartItem
     */
    public function getItem($itemKey);

    /**
     * @return \OnlineShop\Framework\CartManager\ICartItem[]
     */
    public function getGiftItems();

    /**
     * @param string $itemKey
     *
     * @return \OnlineShop\Framework\CartManager\ICartItem
     */
    public function getGiftItem($itemKey);

    /**
     * @abstract
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     * @param int $count
     * @param null $itemKey
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param \OnlineShop\Framework\Model\AbstractSetProductEntry[] $subProducts
     * @param string $comment
     * @return string $itemKey
     */
    public function addItem(\OnlineShop\Framework\Model\ICheckoutable $product, $count, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null);


    /**
     * @abstract
     * @param string $itemKey
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     * @param int $count
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param array $subProducts
     * @param null $comment
     * @return string $itemKey
     */
    public function updateItem($itemKey, \OnlineShop\Framework\Model\ICheckoutable $product, $count, $replace = false, $params = array(), $subProducts = array(), $comment = null);

    /**
     * updates count of specific cart item
     *
     * @param $itemKey
     * @param $count
     * @return mixed
     */
    public function updateItemCount($itemKey, $count);

    /**
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     * @param int $count
     * @param null $itemKey
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param \OnlineShop\Framework\Model\AbstractSetProductEntry[] $subProducts
     * @param string $comment
     * @return string $itemKey
     */
    public function addGiftItem(\OnlineShop\Framework\Model\ICheckoutable $product, $count, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null);


    /**
     * @param string $itemKey
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     * @param int $count
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param array $subProducts
     * @param null $comment
     * @return string $itemKey
     */
    public function updateGiftItem($itemKey, \OnlineShop\Framework\Model\ICheckoutable $product, $count, $replace = false, $params = array(), $subProducts = array(), $comment = null);

    /**
     * @abstract
     * @param string $itemKey
     * @return void
     */
    public function removeItem($itemKey);

    /**
     * clears all items of cart
     *
     * @abstract
     * @return void
     */
    public function clear();

    /**
     * calculates amount of items in cart
     *
     * @abstract
     * @param bool $countSubItems
     *
     * @return int
     */
    public function getItemAmount($countSubItems = false);


    /**
     * @param int $count
     *
     * @return \OnlineShop\Framework\CartManager\ICartItem[]
     */
    public function getRecentlyAddedItems($count);


    /**
     * returns price calculator of cart
     *
     * @abstract
     * @return \OnlineShop\Framework\CartManager\ICartPriceCalculator
     */
    public function getPriceCalculator();

    /**
     * Set custom checkout data for cart.
     * can be used for delivery information, ...
     *
     * @abstract
     * @param  $key string
     * @param  $data string
     * @return void
     */
    public function setCheckoutData($key, $data);

    /**
     * Get custom checkout data for cart with given key.
     *
     * @abstract
     * @param  $key string
     * @return string
     */
    public function getCheckoutData($key);

    /**
     * get name of cart.
     *
     * @abstract
     * @return string
     */
    public function getName();

    /**
     * set name of cart.
     *
     * @abstract
     * @param $name
     * @return void
     */
    public function setName($name);

    /**
     * returns if cart is bookable.
     * default implementation checks if all products of cart a bookable.
     *
     * @abstract
     * @return bool
     */
    public function getIsBookable();

    /**
     * @abstract
     * @return \Zend_Date
     */
    public function getCreationDate();

    /**
     * @abstract
     * @param null|\Zend_Date $creationDate
     * @return void
     */
    public function setCreationDate(\Zend_Date $creationDate = null);

    /**
     * @abstract
     * @return \Zend_Date
     */
    public function getModificationDate();

    /**
     * @abstract
     * @param null|\Zend_Date $modificationDate
     * @return void
     */
    public function setModificationDate(\Zend_Date $modificationDate = null);

    /**
     * sorts all items in cart according to a given callback function
     *
     * @param callable $value_compare_func
     * @return ICart
     */
    public function sortItems(callable $value_compare_func);

    /**
     * saves cart
     *
     * @abstract
     * @return void
     */
    public function save();

    /**
     * deletes cart
     *
     * @abstract
     * @return void
     */
    public function delete();


    /**
     * @static
     * @abstract
     * @param $id
     * @return ICart
     */
    public static function getById($id);

    /**
     * returns all carts for given userId
     *
     * @static
     * @abstract
     * @param $userId
     * @return ICart[]
     */
    public static function getAllCartsForUser($userId);

    /**
     * @param \OnlineShop\Framework\VoucherService\Token $token
     * @throws \Exception
     * @return bool
     */
    public function addVoucherToken($token);

    /**
     * @param \OnlineShop\Framework\VoucherService\Token $token
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
