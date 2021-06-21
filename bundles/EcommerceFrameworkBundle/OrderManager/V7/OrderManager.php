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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\V7;

use Carbon\Carbon;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\OrderUpdateNotPossibleException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentFactoryInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Exception\ProviderNotFoundException;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\RecurringPaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\VoucherServiceInterface;
use Pimcore\Event\Ecommerce\OrderManagerEvents;
use Pimcore\Event\Model\Ecommerce\OrderManagerEvent;
use Pimcore\Event\Model\Ecommerce\OrderManagerItemEvent;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\PricingRule;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\DataObject\Listing\Concrete;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\FactoryInterface;
use Pimcore\Tool;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OrderManager implements OrderManagerInterface
{
    /**
     * @var EnvironmentInterface
     */
    protected $environment;

    /**
     * @var OrderAgentFactoryInterface
     */
    protected $orderAgentFactory;

    /**
     * @var VoucherServiceInterface
     */
    protected $voucherService;

    /**
     * @var FactoryInterface
     */
    protected $modelFactory;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var Folder
     */
    protected $orderParentFolder;

    /**
     * @var string
     */
    protected $orderClassName;

    /**
     * @var string
     */
    protected $orderItemClassName;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        EnvironmentInterface $environment,
        OrderAgentFactoryInterface $orderAgentFactory,
        VoucherServiceInterface $voucherService,
        EventDispatcherInterface $eventDispatcher,
        FactoryInterface $modelFactory,
        array $options = []
    ) {
        $this->eventDispatcher = $eventDispatcher;

        $this->environment = $environment;
        $this->orderAgentFactory = $orderAgentFactory;
        $this->voucherService = $voucherService;
        $this->modelFactory = $modelFactory;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->processOptions($resolver->resolve($options));
    }

    protected function processOptions(array $options)
    {
        $this->orderClassName = $options['order_class'];
        $this->orderItemClassName = $options['order_item_class'];
        $this->options = $options;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $classProperties = ['order_class', 'order_item_class', 'list_class', 'list_item_class'];

        $resolver->setRequired($classProperties);

        $resolver->setDefaults([
            'order_class' => '\\Pimcore\\Model\\DataObject\\OnlineShopOrder',
            'order_item_class' => '\\Pimcore\\Model\\DataObject\\OnlineShopOrderItem',
            'list_class' => Listing::class,
            'list_item_class' => Listing\Item::class,
            'parent_order_folder' => '/order/%Y/%m/%d',
        ]);

        foreach ($classProperties as $classProperty) {
            $resolver->setAllowedTypes($classProperty, 'string');
        }
    }

    /**
     * @param CartInterface $cart
     *
     * @return AbstractOrder
     *
     * @throws \Exception
     *
     */
    public function getOrCreateOrderFromCart(CartInterface $cart)
    {
        $order = $this->getOrderFromCart($cart);

        $event = new OrderManagerEvent($cart, $order, $this);
        $this->eventDispatcher->dispatch($event, OrderManagerEvents::PRE_GET_OR_CREATE_ORDER_FROM_CART);
        $order = $event->getOrder();

        // no order found, create new one
        if (empty($order)) {
            $tempOrdernumber = $this->createOrderNumber();

            $order = $this->getNewOrderObject();

            $order->setParent($this->getOrderParentFolder());
            $order->setCreationDate(time());
            $order->setKey(File::getValidFilename($tempOrdernumber));
            $order->setPublished(true);

            $order->setOrdernumber($tempOrdernumber);
            $order->setOrderdate(new Carbon());

            $cartId = $this->createCartId($cart);
            if (strlen($cartId) > 190) {
                throw new \Exception('CartId cannot be longer than 190 characters');
            }

            $order->setCartId($cartId);
        }

        // check if pending payment. if one, do not update order from cart

        $cartIsLockedDueToPayments = $this->cartHasPendingPayments($cart);
        $orderNeedsUpdate = $this->orderNeedsUpdate($cart, $order);

        $event = new OrderManagerEvent($cart, $order, $this, [
            'cartIsLockedDueToPayments' => $cartIsLockedDueToPayments,
            'orderNeedsUpdate' => $orderNeedsUpdate,
        ]);
        $this->eventDispatcher->dispatch($event, OrderManagerEvents::PRE_UPDATE_ORDER);

        $cartIsLockedDueToPayments = $event->getArgument('cartIsLockedDueToPayments');
        $orderNeedsUpdate = $event->getArgument('orderNeedsUpdate');

        if ($orderNeedsUpdate && $cartIsLockedDueToPayments) {
            throw new OrderUpdateNotPossibleException('Order cannot be updated from cart due to pending payments. Cancel payment or recreate order.');
        }

        if (!$orderNeedsUpdate) {
            return $order;
        }

        // update order from cart
        $order->setTotalPrice($cart->getPriceCalculator()->getGrandTotal()->getGrossAmount()->asString());
        $order->setTotalNetPrice($cart->getPriceCalculator()->getGrandTotal()->getNetAmount()->asString());
        $order->setSubTotalPrice($cart->getPriceCalculator()->getSubTotal()->getAmount()->asString());
        $order->setSubTotalNetPrice($cart->getPriceCalculator()->getSubTotal()->getNetAmount()->asString());
        $order->setTaxInfo($this->buildTaxArray($cart->getPriceCalculator()->getGrandTotal()->getTaxEntries()));

        $modificationItems = new Fieldcollection();
        foreach ($cart->getPriceCalculator()->getPriceModifications() as $name => $modification) {
            $modificationItem = new Fieldcollection\Data\OrderPriceModifications();
            $modificationItem->setName($modification->getDescription() ? $modification->getDescription() : $name);
            $modificationItem->setAmount($modification->getGrossAmount()->asString());
            $modificationItem->setNetAmount($modification->getNetAmount()->asString());

            if ($rule = $modification->getRule()) {
                $modificationItem->setPricingRuleId($rule->getId());
            } else {
                $modificationItem->setPricingRuleId(null);
            }

            $modificationItems->add($modificationItem);
        }

        $order->setPriceModifications($modificationItems);
        $order->setCartHash($this->calculateCartHash($cart));

        $order = $this->setCurrentCustomerToOrder($order);

        // set order currency
        $currency = $cart->getPriceCalculator()->getGrandTotal()->getCurrency();
        $order->setCurrency($currency->getShortName());

        $order->save(['versionNote' => 'OrderManager::getOrCreateOrderFromCart - save order to add items.']);

        // for each cart item and cart sub item create corresponding order items
        $orderItems = $this->applyOrderItems($cart->getItems(), $order);
        $order->setItems($orderItems);

        $this->applyVoucherTokens($order, $cart);

        // for each gift item create corresponding order item
        $orderGiftItems = $this->applyOrderItems($cart->getGiftItems(), $order, true);
        $order->setGiftItems($orderGiftItems);

        $order = $this->applyCustomCheckoutDataToOrder($cart, $order);
        $order->save(['versionNote' => 'OrderManager::getOrCreateOrderFromCart - final save.']);

        $this->cleanupZombieOrderItems($order);

        $this->eventDispatcher->dispatch(new OrderManagerEvent($cart, $order, $this), OrderManagerEvents::POST_UPDATE_ORDER);

        return $order;
    }

    /**
     * @param CartInterface $cart
     * @param AbstractOrder $order
     *
     * @return bool
     */
    public function orderNeedsUpdate(CartInterface $cart, AbstractOrder $order): bool
    {
        return $this->calculateCartHash($cart) !== $order->getCartHash();
    }

    /**
     * @param CartInterface $cart
     *
     * @return int
     */
    protected function calculateCartHash(CartInterface $cart): int
    {
        $hashString = '';

        $hashString .= $cart->getPriceCalculator()->getGrandTotal()->getAmount()->asString();
        $hashString .= $cart->getItemCount();
        $hashString .= $cart->getItemAmount();
        $hashString .= count($cart->getGiftItems());
        $hashString .= implode($cart->getVoucherTokenCodes());

        return crc32($hashString);
    }

    /**
     * @param CartInterface $cart
     *
     * @return AbstractOrder|null
     *
     * @throws \Exception
     */
    public function getOrderFromCart(CartInterface $cart)
    {
        $cartId = $this->createCartId($cart);

        $orderList = $this->buildOrderList();
        $orderList->setCondition('cartId = ? AND IFNULL(successorOrder__id , "") = ""', [$cartId]);

        /** @var AbstractOrder[] $orders */
        $orders = $orderList->load();
        if (count($orders) > 1) {
            throw new \Exception("No unique order found for $cartId.");
        }

        if (count($orders) === 1) {
            return $orders[0];
        }

        return null;
    }

    /**
     * @param CartInterface $cart
     *
     * @return AbstractOrder
     *
     * @throws \Exception
     */
    public function recreateOrder(CartInterface $cart): AbstractOrder
    {
        $sourceOrder = $this->getOrderFromCart($cart);

        if ($sourceOrder) {
            //create new order object
            $tempOrdernumber = $this->createOrderNumber();
            $order = $this->getNewOrderObject();

            $order->setParent($sourceOrder->getParent());
            $order->setCreationDate(time());
            $order->setKey(File::getValidFilename($tempOrdernumber));
            $order->setPublished(true);

            $order->setOrdernumber($tempOrdernumber);
            $order->setOrderdate(new Carbon());
            $order->setCartId($sourceOrder->getCartId());

            $order->save(['versionNote' => 'OrderManager::recreateOrder.']);

            $sourceOrder->setSuccessorOrder($order);
            $sourceOrder->save(['versionNote' => 'OrderManager::recreateOrder - save successor order.']);
        }

        return $this->getOrCreateOrderFromCart($cart);
    }

    /**
     * @param AbstractOrder $sourceOrder
     *
     * @return AbstractOrder
     *
     * @throws \Exception
     */
    public function recreateOrderBasedOnSourceOrder(AbstractOrder $sourceOrder): AbstractOrder
    {
        $tempOrdernumber = $this->createOrderNumber();
        $order = clone $sourceOrder;

        $order->setId(null);
        $order->setParent($sourceOrder->getParent());
        $order->setCreationDate(time());
        $order->setKey(File::getValidFilename($tempOrdernumber));
        $order->setPublished(true);

        $order->setOrdernumber($tempOrdernumber);
        $order->setOrderdate(new Carbon());
        $order->setCartId($sourceOrder->getCartId());

        $order->save(['versionNote' => 'OrderManager::recreateOrderBasedOnSourceOrder - initial save.']);

        $sourceOrder->setSuccessorOrder($order);
        $sourceOrder->save(['versionNote' => 'OrderManager::recreateOrderBasedOnSourceOrder - save successor order.']);

        $order->setItems($this->cloneItems($sourceOrder->getItems(), $order));
        $order->setGiftItems($this->cloneItems($sourceOrder->getGiftItems(), $order));
        $order->save(['versionNote' => 'OrderManager::recreateOrderBasedOnSourceOrder - final save.']);

        return $order;
    }

    /**
     * @param array $sourceItems
     * @param AbstractOrder $newOrder
     *
     * @return array
     */
    protected function cloneItems(array $sourceItems, AbstractOrder $newOrder): array
    {
        $items = [];
        foreach ($sourceItems as $sourceItem) {
            $newItem = clone $sourceItem;
            $newItem->setId(null);

            $newItem->setParent($newOrder);
            $newItem->setSubItems($this->cloneItems($sourceItem->getSubItems(), $newOrder));
            $newItem->save();

            $items[] = $newItem;
        }

        return $items;
    }

    /**
     * @param CartInterface $cart
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function cartHasPendingPayments(CartInterface $cart): bool
    {
        $order = $this->getOrderFromCart($cart);
        if ($order) {
            if ($order->getOrderState() == AbstractOrder::ORDER_STATE_PAYMENT_PENDING) {
                return true;
            }

            $orderAgent = $this->createOrderAgent($order);
            $paymentInfo = $orderAgent->getCurrentPendingPaymentInfo();

            if ($paymentInfo) {
                if ($paymentInfo->getPaymentState() == AbstractOrder::ORDER_STATE_PAYMENT_PENDING) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param CartItemInterface $item
     * @param AbstractObject $parent
     * @param bool $isGiftItem
     *
     * @return AbstractOrderItem
     *
     * @throws \Exception
     */
    protected function createOrderItem(CartItemInterface $item, $parent, $isGiftItem = false)
    {
        $key = $this->buildOrderItemKey($item, $isGiftItem);

        $orderItemList = $this->buildOrderItemList();
        $orderItemList->setCondition('o_parentId = ? AND o_key = ?', [$parent->getId(), $key]);

        /** @var AbstractOrderItem[] $orderItems */
        $orderItems = $orderItemList->load();
        if (count($orderItems) > 1) {
            throw new \Exception("No unique order item found for $key.");
        }

        if (count($orderItems) == 1) {
            $orderItem = $orderItems[0];
        } else {
            $orderItem = $this->getNewOrderItemObject();
            $orderItem->setParent($parent);
            $orderItem->setPublished(true);
            $orderItem->setKey($key);
        }

        $orderItem->setAmount($item->getCount());
        $orderItem->setProduct($item->getProduct());
        if ($item->getProduct()) {
            $orderItem->setProductName($item->getProduct()->getOSName());
            $orderItem->setProductNumber($item->getProduct()->getOSProductNumber());
        }
        $orderItem->setComment($item->getComment());

        $price = Decimal::zero();
        $netPrice = Decimal::zero();

        if (!$isGiftItem && is_object($item->getTotalPrice())) {
            $price = $item->getTotalPrice()->getGrossAmount();
            $netPrice = $item->getTotalPrice()->getNetAmount();
        }

        // TODO refine how amount is passed to order item (asNumeric? asString?)
        $orderItem->setTotalPrice($price->asString());
        $orderItem->setTotalNetPrice($netPrice->asString());
        $orderItem->setTaxInfo($this->buildTaxArray($item->getTotalPrice()->getTaxEntries()));

        if (!$isGiftItem) {
            // save active pricing rules
            $priceInfo = $item->getPriceInfo();
            if ($priceInfo instanceof PriceInfoInterface && method_exists($orderItem, 'setPricingRules')) {
                $priceRules = new Fieldcollection();
                foreach ($priceInfo->getRules() as $rule) {
                    if ($rule->hasProductActions()) {
                        $priceRule = new PricingRule();
                        $priceRule->setRuleId($rule->getId());

                        foreach (Tool::getValidLanguages() as $language) {
                            $priceRule->setName($rule->getLabel(), $language);
                        }

                        $priceRules->add($priceRule);
                    }
                }

                $orderItem->setPricingRules($priceRules);
                $orderItem->save();
            }
        }

        $event = new OrderManagerItemEvent($item, $isGiftItem, $orderItem);
        $this->eventDispatcher->dispatch($event, OrderManagerEvents::POST_CREATE_ORDER_ITEM);

        return $event->getOrderItem();
    }

    protected function buildOrderItemKey(CartItemInterface $item, bool $isGiftItem = false)
    {
        $itemKey = File::getValidFilename(sprintf(
            '%s_%s%s',
            $item->getProduct()->getId(),
            $item->getItemKey(),
            $isGiftItem ? '_gift' : ''
        ));

        $event = new OrderManagerItemEvent($item, $isGiftItem, null, ['itemKey' => $itemKey]);
        $this->eventDispatcher->dispatch($event, OrderManagerEvents::BUILD_ORDER_ITEM_KEY);

        return $event->getArgument('itemKey');
    }

    protected function buildModelClass($className, array $params = [])
    {
        if (null === $this->modelFactory) {
            throw new \RuntimeException('Model factory is not set. Please either configure the order manager service to be autowired or add a call to setModelFactory');
        }

        return $this->modelFactory->build($className, $params);
    }

    /**
     * @return OrderListInterface
     */
    public function createOrderList()
    {
        // @var OrderListInterface $orderList
        $orderList = new $this->options['list_class'];
        $orderList->setItemClassName($this->options['list_item_class']);

        return $orderList;
    }

    /**
     * @param AbstractOrder $order
     *
     * @return OrderAgentInterface
     */
    public function createOrderAgent(AbstractOrder $order)
    {
        return $this->orderAgentFactory->createAgent($order);
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
    protected function getOrderClassName()
    {
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
    protected function getOrderItemClassName()
    {
        return $this->orderItemClassName;
    }

    /**
     * @param int|Folder $orderParentFolder
     *
     * @throws \Exception
     */
    public function setParentOrderFolder($orderParentFolder)
    {
        if ($orderParentFolder instanceof Folder) {
            $this->orderParentFolder = $orderParentFolder;
        } elseif (is_numeric($orderParentFolder)) {
            $folder = Folder::getById($orderParentFolder);

            if ($folder) {
                $this->orderParentFolder = $folder;
            } else {
                throw new \InvalidArgumentException(sprintf('Folder with ID "%s" was not found', $orderParentFolder));
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid argument for parent order folder. Expected either int or Folder, but got %s',
            is_object($orderParentFolder) ? get_class($orderParentFolder) : gettype($orderParentFolder)
        ));
    }

    /**
     * @return Folder
     *
     * @throws \Exception
     */
    protected function getOrderParentFolder()
    {
        if (empty($this->orderParentFolder)) {
            // processing config and setting options
            $parentFolderId = (string)$this->options['parent_order_folder'];

            if (is_numeric($parentFolderId)) {
                $parentFolderId = (int)$parentFolderId;
            } else {
                $p = Service::createFolderByPath(strftime($parentFolderId, time()));
                $parentFolderId = $p->getId();
                unset($p);
            }

            $this->orderParentFolder = Folder::getById($parentFolderId);
        }

        return $this->orderParentFolder;
    }

    /**
     * returns cart id for order object
     *
     * @param CartInterface $cart
     *
     * @return string
     */
    protected function createCartId(CartInterface $cart)
    {
        return get_class($cart) . '_' . $cart->getId();
    }

    /**
     * @param AbstractOrder $order
     *
     * @throws \Exception
     */
    protected function cleanupZombieOrderItems(AbstractOrder $order)
    {
        $validItemIds = [];
        foreach ($order->getItems() ?: [] as $item) {
            $validItemIds[] = $item->getId();
        }
        foreach ($order->getGiftItems() ?: [] as $giftItem) {
            $validItemIds[] = $giftItem->getId();
        }

        $orderItemChildren = $order->getChildren();
        foreach ($orderItemChildren ?: [] as $orderItemChild) {
            if ($orderItemChild instanceof AbstractOrderItem) {
                if (!in_array($orderItemChild->getId(), $validItemIds)) {
                    if (!$orderItemChild->getDependencies()->getRequiredBy(null, 1)) {
                        $orderItemChild->delete();
                    } else {
                        Logger::info('orderItem ('.$orderItemChild->getId().') was not removed because it still has remaining dependencies');
                    }
                }
            }
        }
    }

    /**
     * @param array $items
     * @param AbstractOrder $order
     * @param bool $giftItems
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function applyOrderItems(array $items, AbstractOrder $order, $giftItems = false)
    {
        $orderItems = [];
        foreach ($items as $item) {
            $orderItem = $this->createOrderItem($item, $order, $giftItems);

            $orderSubItems = [];
            $subItems = $item->getSubItems();
            if (!empty($subItems)) {
                foreach ($subItems as $subItem) {
                    $orderSubItem = $this->createOrderItem($subItem, $orderItem, $giftItems);
                    $orderSubItem->save();

                    $orderSubItems[] = $orderSubItem;
                }
            }

            $orderItem->setSubItems($orderSubItems);
            $orderItem->save();

            $orderItems[] = $orderItem;
        }

        return $orderItems;
    }

    protected function applyVoucherTokens(AbstractOrder $order, CartInterface $cart)
    {
        $voucherTokens = $cart->getVoucherTokenCodes();
        if (is_array($voucherTokens)) {
            $flippedVoucherTokens = array_flip($voucherTokens);

            if ($tokenObjects = $order->getVoucherTokens()) {
                foreach ($tokenObjects as $tokenObject) {
                    if (!array_key_exists($tokenObject->getToken(), $flippedVoucherTokens)) {
                        //remove applied tokens which are not in the cart anymore
                        $this->voucherService->removeAppliedTokenFromOrder($tokenObject, $order);
                    } else {
                        //if token already in token objects, nothing has to be done
                        //but remove it from $flippedVoucherTokens so they don't get added again
                        unset($flippedVoucherTokens[$tokenObject->getToken()]);
                    }
                }
            }

            //add new tokens - which are the remaining entries of $flippedVoucherTokens
            foreach ($flippedVoucherTokens as $code => $x) {
                $this->voucherService->applyToken($code, $cart, $order);
            }
        }
    }

    /**
     * hook to save individual data into order object
     *
     * @param CartInterface $cart
     * @param AbstractOrder $order
     *
     * @return AbstractOrder
     */
    protected function applyCustomCheckoutDataToOrder(CartInterface $cart, AbstractOrder $order)
    {
        return $order;
    }

    /**
     * hook to set customer into order
     * default implementation gets current customer from environment and sets it into order
     *
     * @param AbstractOrder $order
     *
     * @return AbstractOrder
     */
    protected function setCurrentCustomerToOrder(AbstractOrder $order)
    {
        // sets customer to order - if available
        if (@Tool::classExists('\\Pimcore\\Model\\DataObject\\Customer')) {
            /**
             * @var $customer \Pimcore\Model\DataObject\Customer
             */
            $customer = \Pimcore\Model\DataObject\Customer::getById($this->environment->getCurrentUserId());
            $order->setCustomer($customer);
        }

        return $order;
    }

    /**
     * hook for creating order number - can be overwritten
     *
     * @return string
     */
    protected function createOrderNumber()
    {
        return uniqid('ord_');
    }

    /**
     * @return AbstractOrder
     *
     * @throws \Exception
     */
    protected function getNewOrderObject()
    {
        $orderClassName = $this->getOrderClassName();
        if (!Tool::classExists($orderClassName)) {
            throw new \Exception('Order Class' . $orderClassName . ' does not exist.');
        }

        return $this->buildModelClass($orderClassName);
    }

    /**
     * Get list of valid source orders to perform recurring payment on.
     *
     * @param string $customerId
     * @param RecurringPaymentInterface $paymentProvider
     * @param string|null $paymentMethod
     * @param string $orderId
     *
     * @throws ProviderNotFoundException
     * @throws \Exception
     *
     * @return Concrete
     */
    public function getRecurringPaymentSourceOrderList(string $customerId, RecurringPaymentInterface $paymentProvider, $paymentMethod = null, $orderId = '')
    {
        $orders = $this->buildOrderList();
        $orders->addConditionParam('customer__id = ?', $customerId);
        $orders->addConditionParam('orderState IS NOT NULL');

        // Check if provider is registered
        $paymentProviderName = $paymentProvider->getName();
        Factory::getInstance()->getPaymentManager()->getProvider(strtolower($paymentProviderName));

        if ($orderId) {
            $orders->setCondition("oo_id = '{$orderId}'");
        }

        // Apply provider specific condition
        $paymentProvider->applyRecurringPaymentCondition($orders, ['paymentMethod' => $paymentMethod]);

        if (empty($orders->getOrderKey())) {
            $orders->setOrderKey('o_creationDate');
            $orders->setOrder('DESC');
        }

        return $orders;
    }

    /**
     * Get source order for performing recurring payment
     *
     * @param string $customerId
     * @param RecurringPaymentInterface $paymentProvider
     * @param string|null $paymentMethod
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getRecurringPaymentSourceOrder(string $customerId, RecurringPaymentInterface $paymentProvider, $paymentMethod = null)
    {
        if (!$paymentProvider->isRecurringPaymentEnabled()) {
            return null;
        }

        $orders = $this->getRecurringPaymentSourceOrderList($customerId, $paymentProvider, $paymentMethod);
        $orders->setLimit(1);

        return $orders->current();
    }

    /**
     * @param AbstractOrder $order
     * @param RecurringPaymentInterface $payment
     * @param string $customerId
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function isValidOrderForRecurringPayment(AbstractOrder $order, RecurringPaymentInterface $payment, $customerId = '')
    {
        $orders = $this->getRecurringPaymentSourceOrderList($customerId, $payment, null, $order->getId());

        return !empty($orders->current());
    }

    /**
     * @return AbstractOrderItem
     *
     * @throws \Exception
     */
    protected function getNewOrderItemObject()
    {
        $orderItemClassName = $this->getOrderItemClassName();
        if (!Tool::classExists($orderItemClassName)) {
            throw new \Exception('OrderItem Class' . $orderItemClassName . ' does not exist.');
        }

        return $this->buildModelClass($orderItemClassName);
    }

    /**
     * @param TaxEntry[] $taxItems
     *
     * @return array
     */
    protected function buildTaxArray(array $taxItems)
    {
        $taxArray = [];
        foreach ($taxItems as $taxEntry) {
            $taxArray[] = [
                $taxEntry->getEntry()->getName(),
                $taxEntry->getPercent() . '%',
                $taxEntry->getAmount()->asString(),
            ];
        }

        return $taxArray;
    }

    /**
     * Build list class name, try namespaced first and fall back to legacy naming
     *
     * @param string $className
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function buildListClassName($className)
    {
        $listClassName = sprintf('%s\\Listing', $className);
        if (!Tool::classExists($listClassName)) {
            $listClassName = sprintf('%s_List', $className);
            if (!Tool::classExists($listClassName)) {
                throw new \Exception(sprintf('Class %s does not exist.', $listClassName));
            }
        }

        return $listClassName;
    }

    /**
     * Build class name for order list
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function buildOrderListClassName()
    {
        return $this->buildListClassName($this->getOrderClassName());
    }

    /**
     * Build class name for order item list
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function buildOrderItemListClassName()
    {
        return $this->buildListClassName($this->getOrderItemClassName());
    }

    /**
     * Build order listing
     *
     * @return Concrete
     *
     * @throws \Exception
     */
    public function buildOrderList()
    {
        $orderListClass = $this->buildOrderListClassName();

        return $this->buildModelClass($orderListClass);
    }

    /**
     * Build order item listing
     *
     * @return Concrete
     *
     * @throws \Exception
     */
    public function buildOrderItemList()
    {
        $orderItemListClass = $this->buildOrderItemListClassName();

        return $this->buildModelClass($orderItemListClass);
    }

    /**
     * @param StatusInterface $paymentStatus
     *
     * @return AbstractOrder|null
     */
    public function getOrderByPaymentStatus(StatusInterface $paymentStatus)
    {
        //this call is needed in order to really load most updated object from cache or DB (otherwise it could be loaded from process)
        \Pimcore::collectGarbage();

        $orderId = explode('~', $paymentStatus->getInternalPaymentId());
        $orderId = $orderId[1];
        $orderClass = $this->getOrderClassName();

        return $orderClass::getById($orderId);
    }
}
