<?php

/**
 * Class OnlineShop_Framework_Impl_CommitOrderProcessor
 */
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

    /**
     * @param int $id
     */
    public function setParentOrderFolder($id) {
        $this->parentFolderId = $id;
    }

    /**
     * @param string $classname
     */
    public function setOrderClass($classname) {
        $this->orderClass = $classname;
    }

    /**
     * @param string $classname
     */
    public function setOrderItemClass($classname) {
        $this->orderItemClass = $classname;
    }

    /**
     * @param string $confirmationMail
     */
    public function setConfirmationMail($confirmationMail) {
        if(!empty($confirmationMail)) {
            $this->confirmationMail = $confirmationMail;
        }
    }

    /**
     * @param OnlineShop_Framework_ICart $cart
     *
     * @return OnlineShop_Framework_AbstractOrder
     * @throws Exception
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getOrCreateOrder(OnlineShop_Framework_ICart $cart) {

        $orderListClass = $this->orderClass . "\\Listing";
        if(!\Pimcore\Tool::classExists($orderListClass)) {
            $orderListClass = $this->orderClass . "_List";
            if(!\Pimcore\Tool::classExists($orderListClass)) {
                throw new Exception("Class $orderListClass does not exist.");
            }
        }

        $cartId = get_class($cart) . "_" . $cart->getId();

        $orderList = new $orderListClass;
        $orderList->setCondition("cartId = ?", array($cartId));

        $orders = $orderList->load();
        if(count($orders) > 1) {
            throw new Exception("No unique order found for $cartId.");
        }

        if(count($orders) == 1) {
            $order = $orders[0];
        } else {
            //No Order found, create new one

            $tempOrdernumber = $this->createOrderNumber();

            $order = $this->getNewOrderObject();

            $order->setParent( \Pimcore\Model\Object\Folder::getById($this->parentFolderId) );
            $order->setCreationDate(Zend_Date::now()->get());
            $order->setKey( \Pimcore\File::getValidFilename($tempOrdernumber) );
            $order->setPublished(true);

            $order->setOrdernumber($tempOrdernumber);
            $order->setOrderdate(Zend_Date::now());
            $order->setCartId($cartId);
        }

        //check if pending payment. if one, do not update order from cart
        $orderAgent = OnlineShop_Framework_Factory::getInstance()->getOrderManager()->createOrderAgent( $order );
        $paymentInfo = $orderAgent->startPayment( false );
        if($paymentInfo) {
            return $order;
        }

        //update order from cart
        $order->setTotalPrice($cart->getPriceCalculator()->getGrandTotal()->getAmount());

        $modificationItems = new \Pimcore\Model\Object\Fieldcollection();
        foreach ($cart->getPriceCalculator()->getPriceModifications() as $name => $modification) {
            $modificationItem = new \Pimcore\Model\Object\Fieldcollection\Data\OrderPriceModifications();
            $modificationItem->setName($modification->getDescription() ? $modification->getDescription() : $name);
            $modificationItem->setAmount($modification->getAmount());
            $modificationItems->add($modificationItem);
        }

        $order->setPriceModifications($modificationItems);

        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();

        //sets customer to order - if available
        // TODO refactor
        if(@\Pimcore\Tool::classExists("\\Pimcore\\Model\\Object\\Customer")) {
            $customer = \Pimcore\Model\Object\Customer::getById($env->getCurrentUserId());
            $order->setCustomer($customer);
        }


        // set order currency
        $currency = $cart->getPriceCalculator()->getGrandTotal()->getCurrency();
        $order->setCurrency( $currency->getShortName() );


        $order->save();


        //for each cart item and cart sub item create corresponding order items
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

        return $order;
    }

    /**
     * hook for creating order number - can be overwritten
     *
     * @return string
     */
    protected function createOrderNumber() {
        return uniqid("ord_");
    }

    /**
     * @deprecated use orderManager instead
     * @return OnlineShop_Framework_AbstractPaymentInformation
     */
    public function getOrCreateActivePaymentInfo(OnlineShop_Framework_AbstractOrder $order, $createNew = true) {

        $orderAgent = OnlineShop_Framework_Factory::getInstance()->getOrderManager()->createOrderAgent( $order );
        return $orderAgent->startPayment( $createNew );
    }

    /**
     * @param OnlineShop_Framework_Payment_IStatus $status
     *
     * @deprecated use orderManager instead
     * @return OnlineShop_Framework_AbstractOrder
     * @throws Exception
     */
    public function updateOrderPayment(OnlineShop_Framework_Payment_IStatus $status) {

        // init
        $orderId = explode("~", $status->getInternalPaymentId());
        $orderId = $orderId[1];
        $orderClass = $this->orderClass;
        $order = $orderClass::getById($orderId);
        /* @var OnlineShop_Framework_AbstractOrder $order */

        $orderAgent = OnlineShop_Framework_Factory::getInstance()->getOrderManager()->createOrderAgent( $order );
        return $orderAgent->updatePayment( $status )->getOrder();
    }


    protected function applyVoucherTokens(OnlineShop_Framework_AbstractOrder $order, OnlineShop_Framework_ICart $cart){

        $voucherTokens = $cart->getVoucherTokenCodes();
        if (is_array($voucherTokens)) {
            $service = OnlineShop_Framework_Factory::getInstance()->getVoucherService();
            foreach ($voucherTokens as $code) {
                $service->applyToken($code, $cart, $order);
            }
        }
    }

    /**
     * @param OnlineShop_Framework_ICart $cart
     *
     * @return OnlineShop_Framework_AbstractOrder
     * @throws Exception
     */
    public function commitOrder(OnlineShop_Framework_ICart $cart) {
        $order = $this->getOrCreateOrder($cart);

        try {
            $this->processOrder($cart, $order);
            $order->setOrderState(OnlineShop_Framework_AbstractOrder::ORDER_STATE_COMMITTED);
            $this->applyVoucherTokens($order, $cart); // TODO check if right position in code
            $order->save();
        } catch(Exception $e) {
            $order->delete();
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

        $mail = new \Pimcore\Mail(array("document" => $this->confirmationMail, "params" => $params));
        if($order->getCustomer()) {
            $mail->addTo($order->getCustomer()->getEmail());
            $mail->send();
        } else {
            Logger::err("No Customer found!");
        }
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
     * implementation-specific processing of order, must be implemented in subclass (e.g. sending order to ERP-system)
     *
     * @param OnlineShop_Framework_ICart $cart
     * @param OnlineShop_Framework_AbstractOrder $order
     */
    protected function processOrder(OnlineShop_Framework_ICart $cart, OnlineShop_Framework_AbstractOrder $order) {
        //nothing to do
    }


    /**
     * @param \OnlineShop_Framework_ICartItem $item
     * @param OnlineShop_Framework_AbstractOrder |OnlineShop_Framework_AbstractOrderItem $parent
     *
     * @return OnlineShop_Framework_AbstractOrderItem
     * @throws Exception
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    protected function createOrderItem(OnlineShop_Framework_ICartItem $item,  $parent) {

        $orderItemListClass = $this->orderItemClass . "\\Listing";
        if(!class_exists($orderItemListClass)) {
            $orderItemListClass = $this->orderItemClass . "_List";
            if(!class_exists($orderItemListClass)) {
                throw new Exception("Class $orderItemListClass does not exist.");
            }
        }

        $key = \Pimcore\File::getValidFilename($item->getProduct()->getId() . "_" . $item->getItemKey());

        $orderItemList = new $orderItemListClass;
        $orderItemList->setCondition("o_parentId = ? AND o_key = ?", array($parent->getId(), $key));

        $orderItems = $orderItemList->load();
        if(count($orderItems) > 1) {
            throw new Exception("No unique order item found for $key.");
        }


        if(count($orderItems) == 1) {
            $orderItem = $orderItems[0];
        } else {
            $orderItem = $this->getNewOrderItemObject();
            $orderItem->setParent($parent);
            $orderItem->setPublished(true);
            $orderItem->setKey($key);
        }

        $orderItem->setAmount($item->getCount());
        $orderItem->setProduct($item->getProduct());
        if($item->getProduct()) {
            $orderItem->setProductName($item->getProduct()->getOSName());
            $orderItem->setProductNumber($item->getProduct()->getOSProductNumber());
        }
        $orderItem->setComment($item->getComment());

        $price = 0;
        if(is_object($item->getTotalPrice())) {
            $price = $item->getTotalPrice()->getAmount();
        }

        $orderItem->setTotalPrice($price);


        // save active pricing rules
        $priceInfo = $item->getPriceInfo();
        if($priceInfo instanceof OnlineShop_Framework_Pricing_IPriceInfo && method_exists($orderItem, 'setPricingRules'))
        {
            $priceRules = new \Pimcore\Model\Object\Fieldcollection();
            foreach($priceInfo->getRules() as $rule)
            {
                $priceRule = new \Pimcore\Model\Object\Fieldcollection\Data\PricingRule();
                $priceRule->setRuleId( $rule->getId() );
                $priceRule->setName( $rule->getName() );

                $priceRules->add( $priceRule );
            }

            $orderItem->setPricingRules( $priceRules );
            $orderItem->save();
        }


        return $orderItem;
    }

    /**
     * @throws Exception
     */
    public function cleanUpPendingOrders() {
        $orderListClass = $this->orderClass . "_List";
        if(!class_exists($orderListClass)) {
            throw new Exception("Class $orderListClass does not exist.");
        }

        $timestamp = Zend_Date::now()->sub(1, Zend_Date::HOUR)->get();

        //Abort orders with payment pending
        $list = new $orderListClass();
        $list->addFieldCollection("PaymentInfo");
        $list->setCondition("orderState = ? AND orderdate < ?", array(OnlineShop_Framework_AbstractOrder::ORDER_STATE_PAYMENT_PENDING, $timestamp));

        foreach($list as $order) {
            Logger::warn("Setting order " . $order->getId() . " to " . OnlineShop_Framework_AbstractOrder::ORDER_STATE_ABORTED);
            $order->setOrderState(OnlineShop_Framework_AbstractOrder::ORDER_STATE_ABORTED);
            $order->save();
        }

        //Abort payments with payment pending
        $list = new $orderListClass();
        $list->addFieldCollection("PaymentInfo", "paymentinfo");
        $list->setCondition("`PaymentInfo~paymentinfo`.paymentState = ? AND `PaymentInfo~paymentinfo`.paymentStart < ?", array(OnlineShop_Framework_AbstractOrder::ORDER_STATE_PAYMENT_PENDING, $timestamp));
        foreach($list as $order) {
            $payments = $order->getPaymentInfo();
            foreach($payments as $payment) {
                if($payment->getPaymentState() == OnlineShop_Framework_AbstractOrder::ORDER_STATE_PAYMENT_PENDING && $payment->getPaymentStart()->get() < $timestamp) {
                    Logger::warn("Setting order " . $order->getId() . " payment " . $payment->getInternalPaymentId() . " to " . OnlineShop_Framework_AbstractOrder::ORDER_STATE_ABORTED);
                    $payment->setPaymentState(OnlineShop_Framework_AbstractOrder::ORDER_STATE_ABORTED);
                }
            }
            $order->save();
        }

    }
}
