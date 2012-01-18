<?php

class OnlineShop_Framework_Impl_CommitOrderProcessor implements OnlineShop_Framework_ICommitOrderProcessor {

    /**
     * @var int
     */
    protected $parentFolderId = 1;

    /**
     * @var string
     */
    protected $orderClass = "";

    /**
     * @var string
     */
    protected $orderItemClass = "";

    /**
     * @var string
     */
    protected $confirmationMail = "/emails/order-confirmation";

    public function setParentOrderFolder($id) {
        $this->parentFolderId = $id;
    }

    public function setOrderClass($classname) {
        $this->orderClass = $classname;
    }

    public function setOrderItemClass($classname) {
        $this->orderItemClass = $classname;
    }

    public function setConfirmationMail($confirmationMail) {
        if(!empty($confirmationMail)) {
            $this->confirmationMail = $confirmationMail;
        }
    }

     /**
     * @param OnlineShop_Framework_ICart $cart
     * @return OnlineShop_Framework_AbstractOrder
     */
    public function commitOrder(OnlineShop_Framework_ICart $cart) {

        $tempOrdernumber = uniqid("ord_");

        $order = $this->getNewOrderObject();

        $order->setParent(Object_Folder::getById($this->parentFolderId));
        $order->setCreationDate(Zend_Date::now());
        $order->setKey($tempOrdernumber);
        $order->setPublished(true);

        $order->setOrdernumber($tempOrdernumber);
        $order->setOrderdate(Zend_Date::now());

        //TODO save price modifications somehow
        $order->setTotalPrice($cart->getPriceCalculator()->getGrandTotal()->getAmount());

        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();

        $customer = CustomerDb_Customer::getById($env->getCurrentUserId());
        $order->setCustomer($customer);

        $order->save();

        $orderItems = array();
        $i = 0;
        foreach($cart->getItems() as $item) {
            $i++;

            $orderItem = $this->createOrderItem($item, $order);
            $orderItem->save();

            $subItems = $item->getSubItems();
            if(!empty($subItems)) {
                $orderSubItems = array();

                foreach($subItems as $subItem) {
                    $orderSubItem = $this->createOrderItem($subItem, $orderItem);
                    $orderSubItem->save();

                    $orderSubItems[] = $orderSubItem;
                }

                $orderItem->setSubItems($orderSubItems);
                $orderItem->save();
            }

            $orderItems[] = $orderItem;

        }

        $order->setItems($orderItems);

        $event = new CustomerDb_Events_OrderEvent();
        $event->setCustomer($env->getCurrentUserId());
        $event->setUser(0);
        $event->setTitle("Order: " . $order->getOrdernumber());
        $event->setOrderObject($order);
        $event->setTimestamp(time());
        $event->save();

        try {
            $this->processOrder($cart, $order);
            $order->save();
        } catch(Exception $e) {
            $order->delete();
            $event->delete();
            throw $e;
        }

        try {
            $this->sendConfirmationMail($cart, $order);
        } catch(Exception $e) {
            Logger::err("Error during sending confirmation e-mail", $e);
        }
        $cart->delete();
        return $order;
    }

    protected function sendConfirmationMail(OnlineShop_Framework_ICart $cart, OnlineShop_Framework_AbstractOrder $order) {
        $params = array();
        $params["cart"] = $cart;
        $params["order"] = $order;
        $params["customer"] = $order->getCustomer();
        $params["ordernumber"] = $order->getOrdernumber();

        //TODO multilanguage with mailing framework?
        $mail = new Pimcore_Mail(array("document" => $this->confirmationMail, "params" => $params));
        $mail->addTo($order->getCustomer()->getEmail());
        $mail->send();
    }

    /**
     * @return OnlineShop_Framework_AbstractOrder
     * @throws Exception
     */
    protected function getNewOrderObject() {
        if(!class_exists($this->orderClass)) {
            throw new Exception("Order Class" . $this->orderClass . " does not exist.");
        }
        return new $this->orderClass();
    }

    /**
     * @return OnlineShop_Framework_AbstractOrderItem
     * @throws Exception
     */
    protected function getNewOrderItemObject() {
        if(!class_exists($this->orderItemClass)) {
            throw new Exception("OrderItem Class" . $this->orderItemClass . " does not exist.");
        }
        return new $this->orderItemClass();
    }

    /**
     * implemtation-specific processing of order, must be implemented in subclass (e.g. sending order to ERP-system)
     * @abstract
     * @param Object_OnlineShopOrder $order
     * @return void
     */
    protected function processOrder(OnlineShop_Framework_ICart $cart, OnlineShop_Framework_AbstractOrder $order) {
        //nothing to do
    }


    /**
     * @param \OnlineShop_Framework_ICartItem $item
     * @param OnlineShop_Framework_AbstractOrder |OnlineShop_Framework_AbstractOrderItem $parent
     * @return Object_OnlineShopOrderItem
     */
    protected function createOrderItem(OnlineShop_Framework_ICartItem $item,  $parent) {
        $orderItem = $this->getNewOrderItemObject();
        $orderItem->setParent($parent);
        $orderItem->setPublished(true);
        $orderItem->setKey($item->getProduct()->getId() . "_" . $item->getItemKey());

        $orderItem->setAmount($item->getCount());
        $orderItem->setProduct($item->getProduct());
        if($item->getProduct()) {
            $orderItem->setProductName($item->getProduct()->getOSName());
            $orderItem->setProductNumber($item->getProduct()->getOSProductNumber());
        }

        $price = 0;
        if($item->getPrice($item->getCount())) {
            $price = $item->getPrice($item->getCount())->getAmount() * $item->getCount();
        }

        $orderItem->setTotalPrice($price);

        return $orderItem;
    }
}
