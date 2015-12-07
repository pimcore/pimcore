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


namespace OnlineShop\Framework\Impl;

use OnlineShop\Framework\IOrderManager;
use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderAgent;
use \OnlineShop\Framework\Model\AbstractOrder as Order;
use Zend_Config;

class OrderManager implements IOrderManager
{
    /**
     * @var Zend_Config
     */
    protected $config;

    /**
     * @var \Pimcore\Model\Object\Folder
     */
    protected $orderParentFolder;

    /**
     * @var string
     */
    protected $orderClassName = "";

    /**
     * @var string
     */
    protected $orderItemClassName = "";


    /**
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config)
    {
        $this->config = new \OnlineShop\Framework\Tools\Config\HelperContainer($config, "ordermanager");
    }

    /**
     * @return IOrderList
     */
    public function createOrderList()
    {
        $orderList = new $this->config->orderList->class();
        /* @var IOrderList $orderList */
        $orderList->setItemClassName( $this->config->orderList->classItem );

        return $orderList;
    }

    /**
     * @param Order $order
     *
     * @return IOrderAgent
     */
    public function createOrderAgent(Order $order)
    {
        return new $this->config->orderAgent->class( \OnlineShop\Framework\Factory::getInstance(), $order );
    }


    /**
     * @param string $classname
     */
    public function setOrderClass($classname)
    {
        $this->orderClassName = $classname;
    }

    /**
     * @return string
     */
    protected function getOrderClassName() {
        if(empty($this->orderClassName)) {
            $this->orderClassName = (string) $this->config->orderstorage->orderClass;
        }
        return $this->orderClassName;
    }


    /**
     * @param string $classname
     */
    public function setOrderItemClass($classname)
    {
        $this->orderItemClassName = $classname;
    }

    /**
     * @return string
     */
    protected function getOrderItemClassName() {
        if(empty($this->orderItemClassName)) {
            $this->orderItemClassName = (string)$this->config->orderstorage->orderItemClass;
        }
        return $this->orderItemClassName;
    }

    /**
     * @param int $id
     */
    public function setParentOrderFolder($id)
    {
        if(is_numeric($id)) {
            $this->orderParentFolder = \Pimcore\Model\Object\Folder::getById($id);
        }
    }


    protected function getOrderParentFolder() {
        if(empty($this->orderParentFolder)) {
            //processing config and setting options
            $parentFolderId = (string)$this->config->parentorderfolder;
            if (is_numeric($parentFolderId)) {
                $parentFolderId = (int)$parentFolderId;
            } else {
                $p = \Pimcore\Model\Object\Service::createFolderByPath(strftime($parentFolderId, time()));
                $parentFolderId = $p->getId();
                unset($p);
            }
            $this->orderParentFolder = \Pimcore\Model\Object\Folder::getById($parentFolderId);
        }
        return $this->orderParentFolder;
    }

    /**
     * returns cart id for order object
     *
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return string
     */
    protected function createCartId(\OnlineShop\Framework\CartManager\ICart $cart) {
        return get_class($cart) . "_" . $cart->getId();
    }

    /**
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return null|\OnlineShop\Framework\Model\AbstractOrder
     * @throws \Exception
     */
    public function getOrderFromCart(\OnlineShop\Framework\CartManager\ICart $cart) {
        $orderListClass = $this->getOrderClassName() . "\\Listing";
        if(!\Pimcore\Tool::classExists($orderListClass)) {
            $orderListClass = $this->getOrderClassName() . "_List";
            if(!\Pimcore\Tool::classExists($orderListClass)) {
                throw new \Exception("Class $orderListClass does not exist.");
            }
        }

        $cartId = $this->createCartId($cart);

        $orderList = new $orderListClass;
        $orderList->setCondition("cartId = ?", array($cartId));

        $orders = $orderList->load();
        if(count($orders) > 1) {
            throw new \Exception("No unique order found for $cartId.");
        }

        if(count($orders) == 1) {
            return $orders[0];
        }
        return null;
    }

    /**
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return \OnlineShop\Framework\Model\AbstractOrder
     * @throws \Exception
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     *
     */
    public function getOrCreateOrderFromCart(\OnlineShop\Framework\CartManager\ICart $cart) {
        $order = $this->getOrderFromCart($cart);

        //No Order found, create new one
        if(empty($order)) {

            $tempOrdernumber = $this->createOrderNumber();

            $order = $this->getNewOrderObject();

            $order->setParent( $this->getOrderParentFolder() );
            $order->setCreationDate(\Zend_Date::now()->get());
            $order->setKey( \Pimcore\File::getValidFilename($tempOrdernumber) );
            $order->setPublished(true);

            $order->setOrdernumber($tempOrdernumber);
            $order->setOrderdate(\Zend_Date::now());
            $order->setCartId($this->createCartId($cart));
        }

        //check if pending payment. if one, do not update order from cart
        if(!empty($order->getOrderState())) {
            return $order;
        }

        //update order from cart
        $order->setTotalPrice($cart->getPriceCalculator()->getGrandTotal()->getAmount());
        $order->setSubTotalPrice($cart->getPriceCalculator()->getSubTotal()->getAmount());

        $modificationItems = new \Pimcore\Model\Object\Fieldcollection();
        foreach ($cart->getPriceCalculator()->getPriceModifications() as $name => $modification) {
            $modificationItem = new \Pimcore\Model\Object\Fieldcollection\Data\OrderPriceModifications();
            $modificationItem->setName($modification->getDescription() ? $modification->getDescription() : $name);
            $modificationItem->setAmount($modification->getAmount());
            $modificationItems->add($modificationItem);
        }

        $order->setPriceModifications($modificationItems);

        $order = $this->setCurrentCustomerToOrder($order);

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

        $this->applyVoucherTokens($order, $cart);

        $order = $this->applyCustomCheckoutDataToOrder($cart, $order);
        $order->save();

        return $order;
    }


    protected function applyVoucherTokens(\OnlineShop\Framework\Model\AbstractOrder $order, \OnlineShop\Framework\CartManager\ICart $cart){

        $voucherTokens = $cart->getVoucherTokenCodes();
        if (is_array($voucherTokens)) {
            $flippedVoucherTokens = array_flip($voucherTokens);

            $service = \OnlineShop\Framework\Factory::getInstance()->getVoucherService();

            if($tokenObjects = $order->getVoucherTokens()) {
                foreach ($tokenObjects as $tokenObject) {
                    if (!array_key_exists($tokenObject->getToken(), $flippedVoucherTokens)) {
                        //remove applied tokens which are not in the cart anymore
                        $service->removeAppliedTokenFromOrder($tokenObject, $order);
                    } else {
                        //if token already in token objects, nothing has to be done
                        //but remove it from $flippedVoucherTokens so they don't get added again
                        unset($flippedVoucherTokens[$tokenObject->getToken()]);
                    }
                }
            }

            //add new tokens - which are the remaining entries of $flippedVoucherTokens
            foreach ($flippedVoucherTokens as $code => $x) {
                $service->applyToken($code, $cart, $order);
            }
        }
    }

    /**
     * hook to save individual data into order object
     *
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @param Order $order
     */
    protected function applyCustomCheckoutDataToOrder(\OnlineShop\Framework\CartManager\ICart $cart, Order $order) {
        return $order;
    }

    /**
     * hook to set customer into order
     * default implementation gets current customer from environment and sets it into order
     *
     * @param Order $order
     * @return Order
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    protected function setCurrentCustomerToOrder(\OnlineShop\Framework\Model\AbstractOrder $order) {
        //sets customer to order - if available
        $env = \OnlineShop\Framework\Factory::getInstance()->getEnvironment();

        if(@\Pimcore\Tool::classExists("\\Pimcore\\Model\\Object\\Customer")) {
            $customer = \Pimcore\Model\Object\Customer::getById($env->getCurrentUserId());
            $order->setCustomer($customer);
        }

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
     * @return \OnlineShop\Framework\Model\AbstractOrder
     * @throws \Exception
     */
    protected function getNewOrderObject() {
        $orderClassName = $this->getOrderClassName();
        if(!class_exists($orderClassName)) {
            throw new \Exception("Order Class" . $orderClassName . " does not exist.");
        }
        return new $orderClassName();
    }

    /**
     * @return \OnlineShop\Framework\Model\AbstractOrderItem
     * @throws \Exception
     */
    protected function getNewOrderItemObject() {
        $orderItemClassName = $this->getOrderItemClassName();
        if(!class_exists($orderItemClassName)) {
            throw new \Exception("OrderItem Class" . $orderItemClassName . " does not exist.");
        }
        return new $orderItemClassName();
    }


    /**
     * @param \OnlineShop\Framework\CartManager\ICartItem $item
     * @param \OnlineShop\Framework\Model\AbstractOrder | \OnlineShop\Framework\Model\AbstractOrderItem $parent
     *
     * @return \OnlineShop\Framework\Model\AbstractOrderItem
     * @throws \Exception
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    protected function createOrderItem(\OnlineShop\Framework\CartManager\ICartItem $item,  $parent) {

        $orderItemListClass = $this->getOrderItemClassName() . "\\Listing";
        if(!class_exists($orderItemListClass)) {
            $orderItemListClass = $this->getOrderItemClassName() . "_List";
            if(!class_exists($orderItemListClass)) {
                throw new \Exception("Class $orderItemListClass does not exist.");
            }
        }

        $key = \Pimcore\File::getValidFilename($item->getProduct()->getId() . "_" . $item->getItemKey());

        $orderItemList = new $orderItemListClass;
        $orderItemList->setCondition("o_parentId = ? AND o_key = ?", array($parent->getId(), $key));

        $orderItems = $orderItemList->load();
        if(count($orderItems) > 1) {
            throw new \Exception("No unique order item found for $key.");
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
        if($priceInfo instanceof \OnlineShop\Framework\PricingManager\IPriceInfo && method_exists($orderItem, 'setPricingRules'))
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
     * @param \OnlineShop_Framework_Payment_IStatus $paymentStatus
     * @return \OnlineShop\Framework\Model\AbstractOrder
     */
    public function getOrderByPaymentStatus(\OnlineShop_Framework_Payment_IStatus $paymentStatus)
    {
        $orderId = explode("~", $paymentStatus->getInternalPaymentId());
        $orderId = $orderId[1];
        $orderClass = $this->getOrderClassName();
        return $orderClass::getById($orderId);
    }
}