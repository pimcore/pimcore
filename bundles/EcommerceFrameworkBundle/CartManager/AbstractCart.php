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

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\VoucherServiceException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\MockProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\PricingManagerTokenInformation;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Reservation;
use Pimcore\Logger;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\DataObject\Concrete;

abstract class AbstractCart extends AbstractModel implements CartInterface
{
    /**
     * @var bool
     */
    private $ignoreReadonly = false;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var CartItemInterface[]|null
     */
    protected $items;

    /**
     * @var array
     */
    public $checkoutData = [];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \DateTime|null
     */
    protected $creationDate;

    /**
     * @var int|null
     */
    protected $creationDateTimestamp;

    /**
     * @var \DateTime|null
     */
    protected $modificationDate;

    /**
     * @var int|null
     */
    protected $modificationDateTimestamp;

    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var CartItemInterface[]
     */
    protected $giftItems = [];

    /**
     * @var CartPriceCalculatorInterface|null
     */
    protected $priceCalculator;

    /**
     * @var int|null
     */
    protected $itemAmount;

    /**
     * @var int|null
     */
    protected $subItemAmount;

    /**
     * @var int|null
     */
    protected $mainAndSubItemAmount;

    /**
     * @var int|null
     */
    protected $itemCount;

    /**
     * @var int|null
     */
    protected $subItemCount;

    /**
     * @var int|null
     */
    protected $mainAndSubItemCount;

    public function __construct()
    {
        $this->setCreationDate(new \DateTime());
    }

    /**
     * @return string
     */
    abstract protected function getCartItemClassName();

    /**
     * @return string
     */
    abstract protected function getCartCheckoutDataClassName();

    /**
     * @param CheckoutableInterface&Concrete $product
     * @param int $count
     * @param string|null $itemKey
     * @param bool $replace
     * @param array $params
     * @param AbstractSetProductEntry[] $subProducts
     * @param string|null $comment
     *
     * @return mixed
     */
    public function addItem(CheckoutableInterface $product, $count, $itemKey = null, $replace = false, $params = [], $subProducts = [], $comment = null)
    {
        if (empty($itemKey)) {
            $itemKey = $product->getId();

            if (!empty($subProducts)) {
                $itemKey = $itemKey . '_' . uniqid();
            }
        }

        return $this->updateItem($itemKey, $product, $count, $replace, $params, $subProducts, $comment);
    }

    /**
     * @param string $itemKey
     * @param CheckoutableInterface&Concrete $product
     * @param int $count
     * @param bool $replace
     * @param array $params
     * @param AbstractSetProductEntry[] $subProducts
     * @param string|null $comment
     *
     * @return string
     */
    public function updateItem($itemKey, CheckoutableInterface $product, $count, $replace = false, $params = [], $subProducts = [], $comment = null)
    {
        //load items first in order to lazyload items (if they are lazy loaded)
        $this->getItems();

        if (!array_key_exists($itemKey, $this->items)) {
            $className = $this->getCartItemClassName();
            $item = new $className();
            $item->setCart($this);
        } else {
            $item = $this->items[$itemKey];
        }

        $item->setProduct($product);
        $item->setItemKey($itemKey);
        if ($comment !== null) {
            $item->setComment($comment);
        }
        if ($replace) {
            $item->setCount($count);
        } else {
            $item->setCount($item->getCount() + $count);
        }

        if (!empty($subProducts)) {
            $subItems = [];
            foreach ($subProducts as $subProduct) {
                if (array_key_exists($subProduct->getProduct()->getId(), $subItems)) {
                    $subItem = $subItems[$subProduct->getProduct()->getId()];
                    $subItem->setCount($subItem->getCount() + $subProduct->getQuantity());
                } else {
                    $className = $this->getCartItemClassName();
                    $subItem = new $className();
                    $subItem->setCart($this);
                    $subItem->setItemKey($subProduct->getProduct()->getId());
                    $subItem->setProduct($subProduct->getProduct());
                    $subItem->setCount($subProduct->getQuantity());
                    $subItems[$subProduct->getProduct()->getId()] = $subItem;
                }
            }
            $item->setSubItems($subItems);
        }

        $this->items[$itemKey] = $item;

        // trigger cart has been modified
        $this->modified();

        return $itemKey;
    }

    /**
     * updates count of specific cart item
     *
     * @param string $itemKey
     * @param int $count
     *
     * @return CartItemInterface
     */
    public function updateItemCount($itemKey, $count)
    {
        //load items first in order to lazyload items (if they are lazy loaded)
        $this->getItems();

        if ($this->items[$itemKey]) {
            $this->items[$itemKey]->setCount($count);
        }

        return $this->items[$itemKey];
    }

    /**
     * @param CheckoutableInterface&Concrete $product
     * @param int $count
     * @param string|null $itemKey
     * @param bool $replace
     * @param array $params
     * @param array $subProducts
     * @param string|null $comment
     *
     * @return string
     */
    public function addGiftItem(CheckoutableInterface $product, $count, $itemKey = null, $replace = false, $params = [], $subProducts = [], $comment = null)
    {
        if (empty($itemKey)) {
            $itemKey = $product->getId();

            if (!empty($subProducts)) {
                $itemKey = $itemKey . '_' . uniqid();
            }
        }

        return $this->updateGiftItem($itemKey, $product, $count, $replace, $params, $subProducts, $comment);
    }

    /**
     * @param string $itemKey
     * @param CheckoutableInterface&Concrete $product
     * @param int $count
     * @param bool $replace
     * @param array $params
     * @param array $subProducts
     * @param string|null $comment
     *
     * @return string
     */
    public function updateGiftItem($itemKey, CheckoutableInterface $product, $count, $replace = false, $params = [], $subProducts = [], $comment = null)
    {
        // item already exists?
        if (!array_key_exists($itemKey, $this->giftItems)) {
            $className = $this->getCartItemClassName();
            $item = new $className();
            $item->setCart($this);
        } else {
            $item = $this->giftItems[$itemKey];
        }

        // update item
        $item->setProduct($product, false);
        $item->setItemKey($itemKey);
        $item->setComment($comment);
        if ($replace) {
            $item->setCount($count, false);
        } else {
            $item->setCount($item->getCount() + $count, false);
        }

        // handle sub products
        if (!empty($subProducts)) {
            $subItems = [];
            foreach ($subProducts as $subProduct) {
                if ($subItems[$subProduct->getProduct()->getId()]) {
                    $subItem = $subItems[$subProduct->getProduct()->getId()];
                    $subItem->setCount($subItem->getCount() + $subProduct->getQuantity());
                } else {
                    $className = $this->getCartItemClassName();
                    $subItem = new $className();
                    $subItem->setCart($this);
                    $subItem->setItemKey($subProduct->getProduct()->getId());
                    $subItem->setProduct($subProduct->getProduct());
                    $subItem->setCount($subProduct->getQuantity());
                    $subItems[$subProduct->getProduct()->getId()] = $subItem;
                }
            }
            $item->setSubItems($subItems);
        }

        $this->giftItems[$itemKey] = $item;

        return $itemKey;
    }

    public function clear()
    {
        $this->items = [];
        $this->giftItems = [];

        $this->removeAllVoucherTokens();

        // trigger cart has been modified
        $this->modified();
    }

    /**
     * @param string $countSubItems - use one of COUNT_MAIN_ITEMS_ONLY, COUNT_MAIN_OR_SUB_ITEMS, COUNT_MAIN_AND_SUB_ITEMS
     *
     * @return int
     */
    public function getItemAmount(string $countSubItems = self::COUNT_MAIN_ITEMS_ONLY)
    {
        switch ($countSubItems) {
            case self::COUNT_MAIN_OR_SUB_ITEMS:

                if ($this->subItemAmount == null) {
                    $count = 0;
                    $items = $this->getItems();
                    if (!empty($items)) {
                        foreach ($items as $item) {
                            $subItems = $item->getSubItems();
                            if ($subItems) {
                                foreach ($subItems as $subItem) {
                                    $count += ($subItem->getCount() * $item->getCount());
                                }
                            } else {
                                $count += $item->getCount();
                            }
                        }
                    }
                    $this->subItemAmount = $count;
                }

                return $this->subItemAmount;

            case self::COUNT_MAIN_AND_SUB_ITEMS:

                if ($this->mainAndSubItemAmount == null) {
                    $count = 0;
                    $items = $this->getItems();
                    if (!empty($items)) {
                        foreach ($items as $item) {
                            $subItems = $item->getSubItems();
                            if ($subItems) {
                                foreach ($subItems as $subItem) {
                                    $count += ($subItem->getCount() * $item->getCount());
                                }
                            }
                            $count += $item->getCount();
                        }
                    }
                    $this->mainAndSubItemAmount = $count;
                }

                return $this->mainAndSubItemAmount;

            case self::COUNT_MAIN_ITEMS_ONLY:

                if ($this->itemAmount == null) {
                    $count = 0;
                    $items = $this->getItems();
                    if (!empty($items)) {
                        foreach ($items as $item) {
                            $count += $item->getCount();
                        }
                    }
                    $this->itemAmount = $count;
                }

                return $this->itemAmount;

            default:
                throw new InvalidConfigException('Invalid value for $countSubItems: ' . $countSubItems);
        }
    }

    /**
     * @param string $countSubItems - use one of COUNT_MAIN_ITEMS_ONLY, COUNT_MAIN_OR_SUB_ITEMS, COUNT_MAIN_AND_SUB_ITEMS
     *
     * @return int
     */
    public function getItemCount(string $countSubItems = self::COUNT_MAIN_ITEMS_ONLY)
    {
        switch ($countSubItems) {
            case self::COUNT_MAIN_OR_SUB_ITEMS:

                if ($this->subItemCount == null) {
                    $items = $this->getItems();
                    $count = 0;

                    if (!empty($items)) {
                        foreach ($items as $item) {
                            $subItems = $item->getSubItems();
                            if (!empty($subItems)) {
                                $count += count($subItems);
                            } else {
                                $count++;
                            }
                        }
                    }
                    $this->subItemCount = $count;
                }

                return $this->subItemCount;

            case self::COUNT_MAIN_AND_SUB_ITEMS:

                if ($this->mainAndSubItemCount == null) {
                    $items = $this->getItems();
                    $count = count($items);

                    if (!empty($items)) {
                        foreach ($items as $item) {
                            $subItems = $item->getSubItems();
                            $count += count($subItems);
                        }
                    }
                    $this->mainAndSubItemCount = $count;
                }

                return $this->mainAndSubItemCount;

            case self::COUNT_MAIN_ITEMS_ONLY:

                if ($this->itemCount == null) {
                    $items = $this->getItems();
                    $this->itemCount = count($items);
                }

                return $this->itemCount;

            default:
                throw new InvalidConfigException('Invalid value for $countSubItems: ' . $countSubItems);
        }
    }

    /**
     * @return CartItemInterface[]
     */
    public function getItems()
    {
        $this->items = $this->items ? $this->items : [];

        return $this->items;
    }

    /**
     * @param string $itemKey
     *
     * @return CartItemInterface|null
     */
    public function getItem($itemKey)
    {
        //load items first in order to lazyload items (if they are lazy loaded)
        $this->getItems();

        return array_key_exists($itemKey, $this->items) ? $this->items[$itemKey] : null;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->getItems()) === 0;
    }

    /**
     * @return CartItemInterface[]
     */
    public function getGiftItems()
    {
        //make sure that cart is calculated
        if (!$this->getPriceCalculator()->isCalculated()) {
            $this->getPriceCalculator()->calculate();
        }

        return $this->giftItems;
    }

    /**
     * @param string $itemKey
     *
     * @return CartItemInterface|null
     */
    public function getGiftItem($itemKey)
    {
        //make sure that cart is calculated
        if (!$this->getPriceCalculator()->isCalculated()) {
            $this->getPriceCalculator()->calculate();
        }

        return array_key_exists($itemKey, $this->giftItems) ? $this->giftItems[$itemKey] : null;
    }

    /**
     * @param CartItemInterface[]|null $items
     */
    public function setItems($items)
    {
        $this->items = $items;

        // trigger cart has been modified
        $this->modified();
    }

    /**
     * @param string $itemKey
     */
    public function removeItem($itemKey)
    {
        //load items first in order to lazyload items (if they are lazy loaded)
        $this->getItems();

        unset($this->items[$itemKey]);

        // trigger cart has been modified
        $this->modified();
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function getIsBookable()
    {
        foreach ($this->getItems() as $item) {
            if (!$item->getProduct()->getOSIsBookable($item->getCount())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        if (empty($this->creationDate) && $this->creationDateTimestamp) {
            $this->creationDate = new \DateTime();
            $this->creationDate->setTimestamp($this->creationDateTimestamp);
        }

        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     */
    public function setCreationDate(\DateTime $creationDate = null)
    {
        $this->creationDate = $creationDate;
        if ($creationDate) {
            $this->creationDateTimestamp = $creationDate->getTimestamp();
        } else {
            $this->creationDateTimestamp = null;
        }
    }

    /**
     * @param int $creationDateTimestamp
     */
    public function setCreationDateTimestamp($creationDateTimestamp)
    {
        $this->creationDateTimestamp = $creationDateTimestamp;
        $this->creationDate = null;
    }

    /**
     * @return int
     */
    public function getCreationDateTimestamp()
    {
        return $this->creationDateTimestamp;
    }

    /**
     * @return \DateTime
     */
    public function getModificationDate()
    {
        if (empty($this->modificationDate) && $this->modificationDateTimestamp) {
            $this->modificationDate = new \DateTime();
            $this->modificationDate->setTimestamp($this->modificationDateTimestamp);
        }

        return $this->modificationDate;
    }

    /**
     * @param \DateTime $modificationDate
     */
    public function setModificationDate(\DateTime $modificationDate = null)
    {
        $this->modificationDate = $modificationDate;
        if ($modificationDate) {
            $this->modificationDateTimestamp = $modificationDate->getTimestamp();
        } else {
            $this->modificationDateTimestamp = null;
        }
    }

    /**
     * @param int $modificationDateTimestamp
     */
    public function setModificationDateTimestamp($modificationDateTimestamp)
    {
        $this->modificationDateTimestamp = $modificationDateTimestamp;
        $this->modificationDate = null;
    }

    /**
     * @return mixed
     */
    public function getModificationDateTimestamp()
    {
        return $this->modificationDateTimestamp;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId ?: Factory::getInstance()->getEnvironment()->getCurrentUserId();
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = (int)$userId;
    }

    /**
     * @return void
     */
    abstract public function save();

    /**
     * @return void
     */
    abstract public function delete();

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function getCheckoutData($key)
    {
        $entry = $this->checkoutData[$key] ?? null;
        if ($entry) {
            return $this->checkoutData[$key]->getData();
        }

        return null;
    }

    /**
     * @param string $key
     * @param string $data
     */
    public function setCheckoutData($key, $data)
    {
        $className = $this->getCartCheckoutDataClassName();
        $value = new $className();
        $value->setCart($this);
        $value->setKey($key);
        $value->setData($data);
        $this->checkoutData[$key] = $value;
    }

    /**
     * @return CartPriceCalculatorInterface
     */
    public function getPriceCalculator()
    {
        if (empty($this->priceCalculator)) {
            $this->priceCalculator = Factory::getInstance()->getCartManager()->getCartPriceCalculator($this);
        }

        return $this->priceCalculator;
    }

    /**
     * @param CartPriceCalculatorInterface $priceCalculator
     */
    public function setPriceCalculator(CartPriceCalculatorInterface $priceCalculator)
    {
        $this->priceCalculator = $priceCalculator;
    }

    /**
     * @return $this
     */
    public function modified()
    {
        $this->setModificationDateTimestamp(time());

        $this->itemAmount = null;
        $this->subItemAmount = null;
        $this->mainAndSubItemAmount = null;
        $this->itemCount = null;
        $this->subItemCount = null;
        $this->mainAndSubItemCount = null;

        //don't use getter here because reset is only necessary if price calculator is already there
        if ($this->priceCalculator) {
            $this->priceCalculator->reset();
        }

        $this->validateVoucherTokenReservations();

        $this->giftItems = [];

        return $this;
    }

    /**
     * @param int $count
     *
     * @return array<int, CartItemInterface>
     */
    public function getRecentlyAddedItems($count)
    {
        // get last items
        $index = [];
        foreach ($this->getItems() as $item) {
            $index[$item->getAddedDate()->getTimestamp()] = $item;
        }
        krsort($index);

        return array_slice($index, 0, $count);
    }

    /**
     * sorts all items in cart according to a given callback function
     *
     * @param callable $value_compare_func
     *
     * @return $this
     */
    public function sortItems(callable $value_compare_func)
    {
        return $this;
    }

    /**
     * Adds a voucher token to the cart's checkout data and reserves it.
     *
     * @param string $code
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function addVoucherToken($code)
    {
        $service = Factory::getInstance()->getVoucherService();
        if ($service->checkToken($code, $this)) {
            if ($service->reserveToken($code, $this)) {
                $index = 'voucher_' . $code;
                $this->setCheckoutData($index, $code);
                $this->save();

                $this->modified();

                return true;
            }
        }

        return false;
    }

    /**
     * Checks if an error code is a defined Voucher Error Code.
     *
     * @param int $errorCode
     *
     * @return bool
     */
    public function isVoucherErrorCode($errorCode)
    {
        return $errorCode > 0 && $errorCode < 10;
    }

    /**
     * Removes all tokens form cart and releases the token reservations.
     *
     * @throws InvalidConfigException
     */
    public function removeAllVoucherTokens()
    {
        foreach ($this->getVoucherTokenCodes() as $code) {
            $this->removeVoucherToken($code);
        }
    }

    /**
     * Removes a token from cart and releases token reservation.
     *
     * @param string $code
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function removeVoucherToken($code)
    {
        $service = Factory::getInstance()->getVoucherService();
        $key = array_search($code, $this->getVoucherTokenCodes());

        if ($key !== false) {
            if ($service->releaseToken($code, $this)) {
                unset($this->checkoutData['voucher_' . $code]);
                $this->save();

                $this->modified();

                return true;
            }
        } else {
            throw new VoucherServiceException('No Token with code ' . $code . ' in this cart.', VoucherServiceException::ERROR_CODE_NOT_FOUND_IN_CART);
        }

        return false;
    }

    /**
     * Filters checkout data and returns an array of strings with the assigns tokens.
     *
     * @return string[]
     */
    public function getVoucherTokenCodes()
    {
        $tokens = [];
        foreach ($this->checkoutData as $key => $value) {
            $exp_key = explode('_', $key);
            if ($exp_key[0] == 'voucher') {
                $tokens[] = $value->getData();
            }
        }

        return $tokens;
    }

    /**
     * @return PricingManagerTokenInformation[]
     */
    public function getPricingManagerTokenInformationDetails(): array
    {
        $voucherService = Factory::getInstance()->getVoucherService();

        return $voucherService->getPricingManagerTokenInformationDetails($this);
    }

    /**
     * Checks if checkout data voucher tokens are valid reservations
     */
    protected function validateVoucherTokenReservations()
    {
        if ($this->getVoucherTokenCodes()) {
            $order = Factory::getInstance()->getOrderManager()->getOrderFromCart($this);
            $appliedVoucherCodes = [];
            if ($order) {
                foreach ($order->getVoucherTokens() as $voucherToken) {
                    $appliedVoucherCodes[$voucherToken->getToken()] = $voucherToken->getToken();
                }
            }

            //check for each voucher token if reservation is valid or it is already applied to order
            foreach ($this->getVoucherTokenCodes() as $code) {
                $reservation = Reservation::get($code, $this);
                if (!$reservation->check($this->getId()) && !array_key_exists($code, $appliedVoucherCodes)) {
                    unset($this->checkoutData['voucher_'.$code]);
                }
            }
        }
    }

    /**
     * Should be added to the cart
     *
     * @param CartItemInterface $item
     *
     * @return bool
     */
    protected static function isValidCartItem(CartItemInterface $item)
    {
        $product = $item->getProduct();
        if ($product instanceof CheckoutableInterface && !$product instanceof MockProduct) {
            return true;
        }

        Logger::warn('Product ' . $item->getProduct()->getId() . ' not found');

        return false;
    }
}
