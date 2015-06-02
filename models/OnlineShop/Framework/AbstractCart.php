<?php
abstract class OnlineShop_Framework_AbstractCart extends \Pimcore\Model\AbstractModel implements OnlineShop_Framework_ICart {

    private $ignoreReadonly = false;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var OnlineShop_Framework_ICartItem[]
     */
    protected $items = array();

    /**
     * @var array
     */
    public $checkoutData = array();

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Zend_Date
     */
    protected $creationDate;

    /**
     * @var int
     */
    protected $creationDateTimestamp;

    /**
     * @var Zend_Date
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
     * @var OnlineShop_Framework_ICartItem[]
     */
    protected $giftItems = array();

    /**
     * @var OnlineShop_Framework_ICartPriceCalculator
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


    public function __construct() {
        $this->setIgnoreReadonly();
        $this->setCreationDate(Zend_Date::now());
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

    /**
     * @return bool
     * @throws Exception
     */
    protected function checkCartIsReadOnly() {
        if(!$this->ignoreReadonly) {
            $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
            $item = $env->getCustomItem(OnlineShop_Framework_Impl_CheckoutManager::CART_READONLY_PREFIX . "_" . $this->getId());
            if($item == "READONLY") {
                throw new Exception("Cart " . $this->getId() . " is readonly.");
            }
        }
        return false;
    }


    /**
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     * @param $count
     * @param null $itemKey
     * @param bool $replace
     * @param array $params
     * @param array $subProducts
     * @param null $comment
     * @return mixed
     */
    public function addItem(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product, $count, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null) {

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
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     * @param $count
     * @param bool $replace
     * @param array $params
     * @param array $subProducts
     * @param null $comment
     * @return mixed
     */
    public function updateItem($itemKey, OnlineShop_Framework_ProductInterfaces_ICheckoutable $product, $count, $replace = false, $params = array(), $subProducts = array(), $comment = null) {

        $this->checkCartIsReadOnly();

        $this->itemAmount = null;
        $this->subItemAmount = null;


        $item = $this->items[$itemKey];
        if (empty($item)) {
            $className = $this->getCartItemClassName();
            $item = new $className();
            $item->setCart($this);
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
        if($this->items[$itemKey]) {
            $this->items[$itemKey]->setCount($count);
        }
        return $this->items[$itemKey];
    }

    /**
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     * @param int                                  $count
     * @param null                                 $itemKey
     * @param bool                                 $replace
     * @param array                                $params
     * @param array                                $subProducts
     * @param null                                 $comment
     *
     * @return string
     */
    public function addGiftItem(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product, $count, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null)
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
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     * @param int                                  $count
     * @param bool                                 $replace
     * @param array                                $params
     * @param array                                $subProducts
     * @param null                                 $comment
     *
     * @return string
     */
    public function updateGiftItem($itemKey, OnlineShop_Framework_ProductInterfaces_ICheckoutable $product, $count, $replace = false, $params = array(), $subProducts = array(), $comment = null)
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
     * @return OnlineShop_Framework_ICartItem[]
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * @param string $itemKey
     *
     * @return OnlineShop_Framework_ICartItem
     */
    public function getItem($itemKey)
    {
        return array_key_exists($itemKey, $this->items) ? $this->items[ $itemKey ] : null;
    }


    /**
     * @return bool|void
     */
    public function isEmpty() {
        return count($this->getItems()) == 0;
    }

    /**
     * @return OnlineShop_Framework_ICartItem[]
     */
    public function getGiftItems()
    {
        return $this->giftItems;
    }


    /**
     * @param string $itemKey
     *
     * @return OnlineShop_Framework_ICartItem
     */
    public function getGiftItem($itemKey)
    {
        return array_key_exists($itemKey, $this->giftItems) ? $this->giftItems[ $itemKey ] : null;
    }


    /**
     * @param OnlineShop_Framework_ICartItem[] $items
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
     * @return Zend_Date
     */
    public function getCreationDate() {
        if(empty($this->creationDate) && $this->creationDateTimestamp) {
            $this->creationDate = new Zend_Date($this->creationDateTimestamp, Zend_Date::TIMESTAMP);
        }
        return $this->creationDate;
    }

    /**
     * @param Zend_Date $creationDate
     */
    public function setCreationDate(Zend_Date $creationDate = null) {
        $this->checkCartIsReadOnly();

        $this->creationDate = $creationDate;
        if($creationDate) {
            $this->creationDateTimestamp = $creationDate->get(Zend_Date::TIMESTAMP);
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
     * @return Zend_Date
     */
    public function getModificationDate() {
        if(empty($this->modificationDate) && $this->modificationDateTimestamp) {
            $this->modificationDate = new Zend_Date($this->modificationDateTimestamp);
        }

        return $this->modificationDate;
    }

    /**
     * @param Zend_Date $modificationDate
     */
    public function setModificationDate(Zend_Date $modificationDate = null) {
        $this->checkCartIsReadOnly();

        $this->modificationDate = $modificationDate;
        if($modificationDate) {
            $this->modificationDateTimestamp = $modificationDate->get(Zend_Date::TIMESTAMP);
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
        return $this->userId ?: OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrentUserId();
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
     * @return OnlineShop_Framework_ICartPriceCalculator
     */
    public function getPriceCalculator() {

        if(empty($this->priceCalcuator)) {
            $this->priceCalcuator = OnlineShop_Framework_Factory::getInstance()->getCartManager()->getCartPriceCalculator($this);
        }

        return $this->priceCalcuator;
    }

    /**
     * cart has been changed
     */
    protected function modified()
    {
        $this->setModificationDateTimestamp( time() );

        $this->validateVoucherTokenReservations();

        // apply pricing rules
        OnlineShop_Framework_Factory::getInstance()->getPricingManager()->applyCartRules($this);
    }


    /**
     * @param int $count
     *
     * @return OnlineShop_Framework_ProductInterfaces_ICheckoutable[]
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
     * @return OnlineShop_Framework_ICartItem[]
     */
    public function sortItems(callable $value_compare_func)
    {

    }

    /**
     * Adds a voucher token to the cart's checkout data and reserves it.
     *
     * @param OnlineShop_Framework_VoucherService_Token $code
     *
     * @return bool
     *
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     * @throws Exception
     */
    public function addVoucherToken($code){
        $service = OnlineShop_Framework_Factory::getInstance()->getVoucherService();
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
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     * @throws Exception
     *
     * @return bool
     */
    public function removeVoucherToken($code)
    {
        $service = OnlineShop_Framework_Factory::getInstance()->getVoucherService();
        $key = array_search($code, $this->getVoucherTokenCodes());

        if ($key !== false) {
            if ($service->releaseToken($code, $this)) {
                unset($this->checkoutData[$key]);
                $this->save();
                return true;
            }
        } else {
            throw new OnlineShop_Framework_Exception_VoucherServiceException("No Token with code " . $code . " in this cart." , 7);
        }
    }

    /**
     * Filters checkout data and returns an array of strings with the assigns tokens.
     *
     * @return array
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
        foreach($this->getVoucherTokenCodes() as $code){
            $reservation = OnlineShop_Framework_VoucherService_Reservation::get($code, $this);
            if(!$reservation->check($this->getId())){
                unset($this->checkoutData["voucher_".$code]);
            }
        }
    }
}