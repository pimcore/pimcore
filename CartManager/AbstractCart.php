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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager;

abstract class AbstractCart extends \Pimcore\Model\AbstractModel implements ICart {

    private $ignoreReadonly = false;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var \OnlineShop\Framework\CartManager\ICartItem[]
     */
    protected $items = null;

    /**
     * @var array
     */
    public $checkoutData = array();

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \DateTime
     */
    protected $creationDate;

    /**
     * @var int
     */
    protected $creationDateTimestamp;

    /**
     * @var \DateTime
     */
    protected $modificationDate;

    /**
     * @var int
     */
    protected $modificationDateTimestamp;

    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var \OnlineShop\Framework\CartManager\ICartItem[]
     */
    protected $giftItems = array();

    /**
     * @var \OnlineShop\Framework\CartManager\ICartPriceCalculator
     */
    protected $priceCalcuator;

    /**
     * @var int
     */
    protected $itemAmount;

    /**
     * @var int
     */
    protected $subItemAmount;

    /**
     * @var int
     */
    protected $itemCount;

    /**
     * @var int
     */
    protected $subItemCount;


    public function __construct() {
        $this->setIgnoreReadonly();
        $this->setCreationDate( new \DateTime() );
        $this->unsetIgnoreReadonly();
    }

    /**
     * @return string
     */
    protected abstract function getCartItemClassName();

    /**
     * @return string
     */
    protected abstract function getCartCheckoutDataClassName();


    protected function setIgnoreReadonly() {
        $this->ignoreReadonly = true;
    }

    protected function unsetIgnoreReadonly() {
        $this->ignoreReadonly = false;
    }


    public function isCartReadOnly() {
        $order = \OnlineShop\Framework\Factory::getInstance()->getOrderManager()->getOrderFromCart($this);
        return !empty($order) && !empty($order->getOrderState());
    }

    /**
     * @return bool
     * @throws \Exception
     */
    protected function checkCartIsReadOnly() {
        if(!$this->ignoreReadonly) {
            if($this->isCartReadOnly()) {
                throw new \Exception("Cart " . $this->getId() . " is readonly.");
            }
        }
        return false;
    }


    /**
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     * @param $count
     * @param null $itemKey
     * @param bool $replace
     * @param array $params
     * @param array $subProducts
     * @param null $comment
     * @return mixed
     */
    public function addItem(\OnlineShop\Framework\Model\ICheckoutable $product, $count, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null) {

        $this->checkCartIsReadOnly();

        if(empty($itemKey)) {
            $itemKey = $product->getId();

            if(!empty($subProducts)) {
                $itemKey = $itemKey . "_" . uniqid();
            }
        }
        return $this->updateItem($itemKey, $product, $count, $replace, $params, $subProducts, $comment);
    }

    /**
     * @param $itemKey
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     * @param $count
     * @param bool $replace
     * @param array $params
     * @param array $subProducts
     * @param null $comment
     * @return mixed
     */
    public function updateItem($itemKey, \OnlineShop\Framework\Model\ICheckoutable $product, $count, $replace = false, $params = array(), $subProducts = array(), $comment = null) {

        $this->checkCartIsReadOnly();

        //load items first in order to lazyload items (if they are lazy loaded)
        $this->getItems();

        $this->itemAmount = null;
        $this->subItemAmount = null;

        if (!array_key_exists($itemKey, $this->items)) {
            $className = $this->getCartItemClassName();
            $item = new $className();
            $item->setCart($this);
        } else {
            $item = $this->items[$itemKey];
        }

        $item->setProduct($product);
        $item->setItemKey($itemKey);
        if($comment !== null) {
            $item->setComment($comment);
        }
        if($replace) {
            $item->setCount($count);
        } else {
            $item->setCount($item->getCount() + $count);
        }


        if(!empty($subProducts)) {
            $subItems = array();
            foreach($subProducts as $subProduct) {
                if($subItems[$subProduct->getProduct()->getId()]) {
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
     * @param $itemKey
     * @param $count
     */
    public function updateItemCount($itemKey, $count) {
        //load items first in order to lazyload items (if they are lazy loaded)
        $this->getItems();

        if($this->items[$itemKey]) {
            $this->items[$itemKey]->setCount($count);
        }
        return $this->items[$itemKey];
    }

    /**
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     * @param int                                  $count
     * @param null                                 $itemKey
     * @param bool                                 $replace
     * @param array                                $params
     * @param array                                $subProducts
     * @param null                                 $comment
     *
     * @return string
     */
    public function addGiftItem(\OnlineShop\Framework\Model\ICheckoutable $product, $count, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null)
    {
        $this->checkCartIsReadOnly();

        if(empty($itemKey)) {
            $itemKey = $product->getId();

            if(!empty($subProducts)) {
                $itemKey = $itemKey . "_" . uniqid();
            }
        }

        return $this->updateGiftItem($itemKey, $product, $count, $replace, $params, $subProducts, $comment);
    }

    /**
     * @param string                               $itemKey
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     * @param int                                  $count
     * @param bool                                 $replace
     * @param array                                $params
     * @param array                                $subProducts
     * @param null                                 $comment
     *
     * @return string
     */
    public function updateGiftItem($itemKey, \OnlineShop\Framework\Model\ICheckoutable $product, $count, $replace = false, $params = array(), $subProducts = array(), $comment = null)
    {
        $this->checkCartIsReadOnly();

        // item already exists?
        if(!array_key_exists($itemKey, $this->giftItems))
        {
            $className = $this->getCartItemClassName();
            $item = new $className();
            $item->setCart($this);
        }
        else
        {
            $item = $this->giftItems[$itemKey];
        }

        // update item
        $item->setProduct($product);
        $item->setItemKey($itemKey);
        $item->setComment($comment);
        if($replace) {
            $item->setCount($count);
        } else {
            $item->setCount($item->getCount() + $count);
        }

        // handle sub products
        if(!empty($subProducts)) {
            $subItems = array();
            foreach($subProducts as $subProduct) {
                if($subItems[$subProduct->getProduct()->getId()]) {
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



    public function clear() {
        $this->checkCartIsReadOnly();

        $this->itemAmount = null;
        $this->subItemAmount = null;

        $this->items = array();
        $this->giftItems = array();

        $this->removeAllVoucherTokens();

        // trigger cart has been modified
        $this->modified();

    }


    /**
     * @param bool $countSubItems
     *
     * @return int
     */
    public function getItemAmount($countSubItems = false) {
        if($countSubItems) {
            if($this->subItemAmount == null) {
                $count = 0;
                $items = $this->getItems();
                if(!empty($items)) {
                    foreach($items as $item) {
                        $subItems = $item->getSubItems();
                        if($subItems) {
                            foreach($subItems as $subItem) {
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
        } else {
            if($this->itemAmount == null) {
                $count = 0;
                $items = $this->getItems();
                if(!empty($items)) {
                    foreach($items as $item) {
                        $count += $item->getCount();
                    }
                }
                $this->itemAmount = $count;
            }
            return $this->itemAmount;
        }
    }

    /**
     * @param bool|false $countSubItems
     * @return int
     */
    public function getItemCount($countSubItems = false) {
        if($countSubItems) {
            if($this->subItemCount == null) {

                $items = $this->getItems();
                $count = count($items);

                if(!empty($items)) {
                    foreach($items as $item) {
                        $subItems = $item->getSubItems();
                        $count += count($subItems);
                    }
                }
                $this->subItemCount = $count;
            }
            return $this->subItemCount;
        } else {
            if($this->itemCount == null) {
                $items = $this->getItems();
                $this->itemCount = count($items);
            }
            return $this->itemCount;
        }
    }


    /**
     * @return \OnlineShop\Framework\CartManager\ICartItem[]
     */
    public function getItems() {
        $this->items = $this->items ? $this->items : [];
        return $this->items;
    }

    /**
     * @param string $itemKey
     *
     * @return \OnlineShop\Framework\CartManager\ICartItem|null
     */
    public function getItem($itemKey)
    {
        //load items first in order to lazyload items (if they are lazy loaded)
        $this->getItems();
        return array_key_exists($itemKey, $this->items) ? $this->items[ $itemKey ] : null;
    }


    /**
     * @return bool|void
     */
    public function isEmpty() {
        return count($this->getItems()) == 0;
    }

    /**
     * @return \OnlineShop\Framework\CartManager\ICartItem[]
     */
    public function getGiftItems()
    {
        return $this->giftItems;
    }


    /**
     * @param string $itemKey
     *
     * @return \OnlineShop\Framework\CartManager\ICartItem
     */
    public function getGiftItem($itemKey)
    {
        return array_key_exists($itemKey, $this->giftItems) ? $this->giftItems[ $itemKey ] : null;
    }


    /**
     * @param \OnlineShop\Framework\CartManager\ICartItem[] $items
     */
    public function setItems($items) {
        $this->checkCartIsReadOnly();


        $this->itemAmount = null;
        $this->subItemAmount = null;

        $this->items = $items;

        // trigger cart has been modified
        $this->modified();

    }

    /**
     * @param string $itemKey
     */
    public function removeItem($itemKey) {
        $this->checkCartIsReadOnly();

        //load items first in order to lazyload items (if they are lazy loaded)
        $this->getItems();

        $this->itemAmount = null;
        $this->subItemAmount = null;

        unset($this->items[$itemKey]);

        // trigger cart has been modified
        $this->modified();
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function getIsBookable() {
        foreach($this->getItems() as $item) {
            if(!$item->getProduct()->getOSIsBookable($item->getCount(), $item->getSetEntries())) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate() {
        if(empty($this->creationDate) && $this->creationDateTimestamp) {
            $this->creationDate = new \DateTime('@'.$this->creationDateTimestamp);
        }
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     */
    public function setCreationDate(\DateTime $creationDate = null) {
        $this->checkCartIsReadOnly();

        $this->creationDate = $creationDate;
        if($creationDate) {
            $this->creationDateTimestamp = $creationDate->getTimezone();
        } else {
            $this->creationDateTimestamp = null;
        }
    }


    /**
     * @param $creationDateTimestamp
     */
    public function setCreationDateTimestamp($creationDateTimestamp) {
        $this->checkCartIsReadOnly();

        $this->creationDateTimestamp = $creationDateTimestamp;
        $this->creationDate = null;
    }

    /**
     * @return int
     */
    public function getCreationDateTimestamp() {
        return $this->creationDateTimestamp;
    }


    /**
     * @return \DateTime
     */
    public function getModificationDate() {
        if(empty($this->modificationDate) && $this->modificationDateTimestamp) {
            $this->modificationDate = new \DateTime('@' . $this->modificationDateTimestamp);
        }

        return $this->modificationDate;
    }

    /**
     * @param \DateTime $modificationDate
     */
    public function setModificationDate(\DateTime $modificationDate = null) {
        $this->checkCartIsReadOnly();

        $this->modificationDate = $modificationDate;
        if($modificationDate) {
            $this->modificationDateTimestamp = $modificationDate->getTimestamp();
        } else {
            $this->modificationDateTimestamp = null;
        }

    }

    /**
     * @param $modificationDateTimestamp
     */
    public function setModificationDateTimestamp($modificationDateTimestamp) {
        $this->checkCartIsReadOnly();

        $this->modificationDateTimestamp = $modificationDateTimestamp;
        $this->modificationDate = null;
    }

    /**
     * @return mixed
     */
    public function getModificationDateTimestamp() {
        return $this->modificationDateTimestamp;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId ?: \OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrentUserId();
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
    public abstract function save();

    /**
     * @return void
     */
    public abstract function delete();


    /**
     * @param  $key string
     * @return string
     */
    public function getCheckoutData($key) {
        $entry = $this->checkoutData[$key];
        if($entry) {
            return $this->checkoutData[$key]->getData();
        } else {
            return null;
        }
    }

    /**
     * @param  $key string
     * @param  $data string
     * @return void
     */
    public function setCheckoutData($key, $data) {
        $className = $this->getCartCheckoutDataClassName();
        $value = new $className();
        $value->setCart($this);
        $value->setKey($key);
        $value->setData($data);
        $this->checkoutData[$key] = $value;
    }



    /**
     * @return \OnlineShop\Framework\CartManager\ICartPriceCalculator
     */
    public function getPriceCalculator() {

        if(empty($this->priceCalcuator)) {
            $this->priceCalcuator = \OnlineShop\Framework\Factory::getInstance()->getCartManager()->getCartPriceCalculator($this);
        }

        return $this->priceCalcuator;
    }

    /**
     * cart has been changed
     */
    protected function modified()
    {
        $this->setModificationDateTimestamp( time() );

        //don't use getter here because reset is only necessary if price calculator is already there
        if($this->priceCalcuator) {
            $this->priceCalcuator->reset();
        }

        $this->validateVoucherTokenReservations();

        $this->giftItems = array();
        // apply pricing rules
        \OnlineShop\Framework\Factory::getInstance()->getPricingManager()->applyCartRules($this);
    }


    /**
     * @param int $count
     *
     * @return \OnlineShop\Framework\Model\ICheckoutable[]
     */
    public function getRecentlyAddedItems($count)
    {
        // get last items
        $index = array();
        foreach($this->getItems() as $item)
        {
            $index[ $item->getAddedDate()->getTimestamp() ] = $item;
        }
        krsort($index);

        return array_slice($index, 0, $count);
    }


    /**
     * sorts all items in cart according to a given callback function
     *
     * @param $value_compare_func
     * @return \OnlineShop\Framework\CartManager\ICartItem[]
     */
    public function sortItems(callable $value_compare_func)
    {

    }

    /**
     * Adds a voucher token to the cart's checkout data and reserves it.
     *
     * @param string $code
     *
     * @return bool
     *
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     * @throws \Exception
     */
    public function addVoucherToken($code){
        $this->checkCartIsReadOnly();

        $service = \OnlineShop\Framework\Factory::getInstance()->getVoucherService();
        if($service->checkToken($code, $this)){
            if($service->reserveToken($code, $this)){
                $index = 'voucher_' . $code;
                $this->setCheckoutData($index, $code);
                $this->save();
                return true;
            }
//            \Pimcore\Log\Simple::log('VoucherService', 'Token Reservation failed for code ' . $code);
        }
        return false;
    }

    /**
     * Checks if an error code is a defined Voucher Error Code.
     *
     * @param $errorCode
     * @return bool
     */
    public function isVoucherErrorCode($errorCode){
        return $errorCode > 0 && $errorCode < 10;
    }

    /**
     * Removes all tokens form cart and releases the token reservations.
     */
    public function removeAllVoucherTokens(){
        foreach($this->getVoucherTokenCodes() as $code){
            $this->removeVoucherToken($code);
        }
    }

    /**
     * Removes a token from cart and releases token reservation.
     *
     * @param string $code
     *
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     * @throws \Exception
     *
     * @return bool
     */
    public function removeVoucherToken($code)
    {
        $this->checkCartIsReadOnly();

        $service = \OnlineShop\Framework\Factory::getInstance()->getVoucherService();
        $key = array_search($code, $this->getVoucherTokenCodes());

        if ($key !== false) {
            if ($service->releaseToken($code, $this)) {
                unset($this->checkoutData["voucher_" . $code]);
                $this->save();
                return true;
            }
        } else {
            throw new \OnlineShop\Framework\Exception\VoucherServiceException("No Token with code " . $code . " in this cart." , 7);
        }
    }

    /**
     * Filters checkout data and returns an array of strings with the assigns tokens.
     *
     * @return string[]
     */
    public function getVoucherTokenCodes(){
        $tokens = [];
        foreach($this->checkoutData as $key => $value){
            $exp_key = explode('_', $key);
            if($exp_key[0] == 'voucher'){
                $tokens[] = $value->getData();
            }
        }
        return $tokens;
    }

    /**
     * Checks if checkout data voucher tokens are valid reservations
     */
    protected function validateVoucherTokenReservations(){

        if($this->getVoucherTokenCodes()) {

            $order = \OnlineShop\Framework\Factory::getInstance()->getOrderManager()->getOrderFromCart($this);
            $appliedVoucherCodes = [];
            if($order) {
                foreach($order->getVoucherTokens() as $voucherToken) {
                    $appliedVoucherCodes[$voucherToken->getToken()] = $voucherToken->getToken();
                }
            }

            //check for each voucher token if reservation is valid or it is already applied to order
            foreach($this->getVoucherTokenCodes() as $code){
                $reservation = \OnlineShop\Framework\VoucherService\Reservation::get($code, $this);
                if(!$reservation->check($this->getId()) && !array_key_exists($code, $appliedVoucherCodes)){
                    unset($this->checkoutData["voucher_".$code]);
                }
            }
        }

    }



    /**
     * Should be added to the cart
     *
     * @param \OnlineShop\Framework\CartManager\ICartItem $item
     * @return bool
     */
    protected static function isValidCartItem(\OnlineShop\Framework\CartManager\ICartItem $item){
        if ($item->getProduct() != null) {
            return true;
        }else {
            \Logger::warn("product " . $item->getProductId() . " not found");
            return false;
        }
    }

}