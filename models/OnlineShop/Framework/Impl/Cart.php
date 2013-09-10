<?php

class OnlineShop_Framework_Impl_Cart extends OnlineShop_Framework_AbstractCart implements OnlineShop_Framework_ICart {

    protected $items = array();
    public $checkoutData = array();
    protected $name;
    protected $creationDate;
    protected $creationDateTimestamp;
    protected $id;

    /**
     * @var array
     */
    protected $giftItems = array();


    public function __construct() {
        $this->setIgnoreReadonly();
        $this->setCreationDate(Zend_Date::now());
        $this->unsetIgnoreReadonly();
    }

    /**
     * @var OnlineShop_Framework_ICartPriceCalculator
     */
    protected $priceCalcuator;

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

    public function updateItem($itemKey, OnlineShop_Framework_ProductInterfaces_ICheckoutable $product, $count, $replace = false, $params = array(), $subProducts = array(), $comment = null) {

        $this->checkCartIsReadOnly();

        $this->itemAmount = null;
        $this->subItemAmount = null;

        $item = $this->items[$itemKey];
        if (empty($item)) {
            $item = new OnlineShop_Framework_Impl_CartItem();
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
                    $subItem = new OnlineShop_Framework_Impl_CartItem();
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
            $item = new OnlineShop_Framework_Impl_CartItem();
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
                    $subItem = new OnlineShop_Framework_Impl_CartItem();
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

        // trigger cart has been modified
        $this->modified();
    }

    protected $itemAmount;
    protected $subItemAmount;

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

    public function getIsBookable() {
        foreach($this->getItems() as $item) {
            if(!$item->getProduct()->getOSIsBookable($item->getCount(), $item->getSetEntries())) {
                return false;
            }
        }
        return true;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }

    public function getCreationDate() {
        if(empty($this->creationDate) && $this->creationDateTimestamp) {
            $this->creationDate = new Zend_Date($this->creationDateTimestamp, Zend_Date::TIMESTAMP);
        }
        return $this->creationDate;
    }

    public function setCreationDate(Zend_Date $creationDate = null) {
        $this->checkCartIsReadOnly();

        $this->creationDate = $creationDate;
        if($creationDate) {
            $this->creationDateTimestamp = $creationDate->get(Zend_Date::TIMESTAMP);
        } else {
            $this->creationDateTimestamp = null;
        }
    }



    public function setCreationDateTimestamp($creationDateTimestamp) {
        $this->checkCartIsReadOnly();

        $this->creationDateTimestamp = $creationDateTimestamp;
        $this->creationDate = null;
    }

    public function getCreationDateTimestamp() {
        return $this->creationDateTimestamp;
    }

    public function getModificationDate() {
        if(empty($this->modificationDate) && $this->modificationDateTimestamp) {
            $this->modificationDate = new Zend_Date($this->modificationDateTimestamp);
        }

        return $this->modificationDate;
    }

    public function setModificationDate($date) {
        $this->checkCartIsReadOnly();

        $this->modificationDate = $date;
    }

    public function setModificationDateTimestamp($modificationDateTimestamp) {
        $this->checkCartIsReadOnly();

        $this->modificationDateTimestamp = $modificationDateTimestamp;
    }

    public function getModificationDateTimestamp() {
        return $this->modificationDateTimestamp;
    }


    public function getUserId() {
        return OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrentUserId();
    }


    public function save() {
        $this->getResource()->save();
        OnlineShop_Framework_Impl_CartItem::removeAllFromCart($this->getId());
        foreach ($this->items as $item) {
            $item->save();
        }

        OnlineShop_Framework_Impl_CartCheckoutData::removeAllFromCart($this->getId());
        foreach ($this->checkoutData as $data) {
            $data->save();
        }
    }

    /**
     * @return void
     */
    public function delete() {
        $cacheKey = OnlineShop_Framework_Impl_Cart_Resource::TABLE_NAME . "_" . $this->getId();
        Zend_Registry::set($cacheKey, null);

        OnlineShop_Framework_Impl_CartItem::removeAllFromCart($this->getId());
        OnlineShop_Framework_Impl_CartCheckoutData::removeAllFromCart($this->getId());

        $this->clear();
        $this->getResource()->delete();
    }


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
        $value = new OnlineShop_Framework_Impl_CartCheckoutData();
        $value->setCart($this);
        $value->setKey($key);
        $value->setData($data);
        $this->checkoutData[$key] = $value;
    }


    /**
     * @param int $id
     * @return OnlineShop_Framework_Impl_Cart
     */
    public static function getById($id) {
        $cacheKey = OnlineShop_Framework_Impl_Cart_Resource::TABLE_NAME . "_" . $id;
        try {
            $cart = Zend_Registry::get($cacheKey);
        }
        catch (Exception $e) {

            try {
                $cartClass = get_called_class();
                $cart = new $cartClass;
                $cart->setIgnoreReadonly();
                $cart->getResource()->getById($id);

                $itemList = new OnlineShop_Framework_Impl_CartItem_List();
                $db = Pimcore_Resource::get();
                $itemList->setCondition("cartId = " . $db->quote($cart->getId()) . " AND parentItemKey = ''");
                $items = array();
                foreach ($itemList->getCartItems() as $item) {
                    if ($item->getProduct() != null) {
                        $items[$item->getItemKey()] = $item;
//                        $cart->addItem($item->getProduct(), $item->getCount(), $item->getItemKey(), array(), $item->getSubItems());
                    }else {
                        Logger::warn("product " . $item->getProductId() . " not found");
                    }
                }
                $cart->setItems($items);

                $dataList = new OnlineShop_Framework_Impl_CartCheckoutData_List();
                $dataList->setCondition("cartId = " . $db->quote($cart->getId()));


                foreach ($dataList->getCartCheckoutDataItems() as $data) {
                    $cart->setCheckoutData($data->getKey(), $data->getData());
                }

                $cart->unsetIgnoreReadonly();

                Zend_Registry::set($cacheKey, $cart);
            } catch (Exception $ex) {
                Logger::debug($ex->getMessage());
                return null;
            }

        }

        return $cart;
    }

    /**
     * @static
     * @param int $userId
     * @return array
     */
    public static function getAllCartsForUser($userId) {
        $list = new OnlineShop_Framework_Impl_Cart_List();
        $db = Pimcore_Resource::get();
        $list->setCondition("userid = " . $db->quote($userId));
        $list->setCartClass( get_called_class() );
        return $list->getCarts();
    }

    /**
     * @return OnlineShop_Framework_ICartPriceCalculator
     */
    public function getPriceCalculator() {

        if(empty($this->priceCalcuator)) {
            $this->priceCalcuator = OnlineShop_Framework_Factory::getInstance()->getCartManager()->getCartPriceCalcuator($this);
        }

        return $this->priceCalcuator;
    }


    /**
     * cart has been changed
     */
    protected function modified()
    {
        // apply pricing rules
        OnlineShop_Framework_Factory::getInstance()->getPricingManager()->applyCartRules($this);
    }
}
