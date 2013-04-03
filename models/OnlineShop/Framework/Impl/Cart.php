<?php

class OnlineShop_Framework_Impl_Cart extends Pimcore_Model_Abstract implements OnlineShop_Framework_ICart {

    protected $items = array();
    public $checkoutData = array();
    protected $name;
    protected $creationDate;
    protected $creationDateTimestamp;
    protected $id;

    public function __construct() {
        $this->setCreationDate(Zend_Date::now());
    }

    /**
     * @var OnlineShop_Framework_ICartPriceCalculator
     */
    protected $priceCalcuator;

    public function addItem(OnlineShop_Framework_AbstractProduct $product, $count, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null) {

        if(empty($itemKey)) {
            $itemKey = $product->getId();

            if(!empty($subProducts)) {
                $itemKey = $itemKey . "_" . uniqid();
            }
        }

        return $this->updateItem($itemKey, $product, $count, $replace, $params, $subProducts, $comment);
    }

    public function updateItem($itemKey, OnlineShop_Framework_AbstractProduct $product, $count, $replace = false, $params = array(), $subProducts = array(), $comment = null) {
        $this->itemAmount = null;
        $this->subItemAmount = null;

        $item = $this->items[$itemKey];
        if (empty($item)) {
            $item = new OnlineShop_Framework_Impl_CartItem();
            $item->setCart($this);
        }

        $item->setProduct($product);
        $item->setItemKey($itemKey);
        $item->setComment($comment);
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
        return $itemKey;
    }

    public function clear() {
        $this->itemAmount = null;
        $this->subItemAmount = null;

        $this->items = array();
    }

    protected $itemAmount;
    protected $subItemAmount;

    /**
     * @param bool $countSubItems
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

    public function setItems($items) {
        $this->itemAmount = null;
        $this->subItemAmount = null;

        $this->items = $items;
    }

    public function removeItem($itemKey) {
        $this->itemAmount = null;
        $this->subItemAmount = null;

        unset($this->items[$itemKey]);
    }

    public function setName($name) {
        $this->name = $name;
    }

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
        $this->creationDate = $creationDate;
        if($creationDate) {
            $this->creationDateTimestamp = $creationDate->get(Zend_Date::TIMESTAMP);
        } else {
            $this->creationDateTimestamp = null;
        }
    }



    public function setCreationDateTimestamp($creationDateTimestamp) {
        $this->creationDateTimestamp = $creationDateTimestamp;
        $this->creationDate = null;
    }

    public function getCreationDateTimestamp() {
        return $this->creationDateTimestamp;
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

                Zend_Registry::set($cacheKey, $cart);
            } catch (Exception $ex) {
                echo $ex;
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
}
