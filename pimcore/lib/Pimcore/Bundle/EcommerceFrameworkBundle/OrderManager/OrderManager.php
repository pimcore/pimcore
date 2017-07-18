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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IStatus;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPriceInfo;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\IVoucherService;
use Pimcore\File;
use Pimcore\Model\Object\Folder;
use Pimcore\Model\Object\Service;
use Pimcore\Tool;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderManager implements IOrderManager
{
    /**
     * @var IEnvironment
     */
    protected $environment;

    /**
     * @var IOrderAgentFactory
     */
    protected $orderAgentFactory;

    /**
     * @var IVoucherService
     */
    protected $voucherService;

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

    public function __construct(
        IEnvironment $environment,
        IOrderAgentFactory $orderAgentFactory,
        IVoucherService $voucherService,
        array $options = []
    )
    {
        $this->environment = $environment;
        $this->orderAgentFactory = $orderAgentFactory;
        $this->voucherService = $voucherService;

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
            'order_class'         => '\\Pimcore\\Model\\Object\\OnlineShopOrder',
            'order_item_class'    => '\\Pimcore\\Model\\Object\\OnlineShopOrderItem',
            'list_class'          => Listing::class,
            'list_item_class'     => Listing\Item::class,
            'parent_order_folder' => '/order/%Y/%m/%d'
        ]);

        foreach ($classProperties as $classProperty) {
            $resolver->setAllowedTypes($classProperty, 'string');
        }
    }

    /**
     * @return IOrderList
     */
    public function createOrderList()
    {
        /* @var IOrderList $orderList */
        $orderList = new $this->options['list_class'];
        $orderList->setItemClassName($this->options['list_item_class']);

        return $orderList;
    }

    /**
     * @param AbstractOrder $order
     *
     * @return IOrderAgent
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

    protected function getOrderParentFolder()
    {
        if (empty($this->orderParentFolder)) {
            // processing config and setting options
            $parentFolderId = (string)$this->options['order_parent_folder'];

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
     * @param ICart $cart
     *
     * @return string
     */
    protected function createCartId(ICart $cart)
    {
        return get_class($cart) . '_' . $cart->getId();
    }

    /**
     * @param ICart $cart
     *
     * @return null|AbstractOrder
     *
     * @throws \Exception
     */
    public function getOrderFromCart(ICart $cart)
    {
        $cartId = $this->createCartId($cart);

        $orderList = $this->buildOrderList();
        $orderList->setCondition('cartId = ?', [$cartId]);

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
     * @param ICart $cart
     *
     * @return AbstractOrder
     *
     * @throws \Exception
     * @throws UnsupportedException
     *
     */
    public function getOrCreateOrderFromCart(ICart $cart)
    {
        $order = $this->getOrderFromCart($cart);

        // no order found, create new one
        if (empty($order)) {
            $tempOrdernumber = $this->createOrderNumber();

            $order = $this->getNewOrderObject();

            $order->setParent($this->getOrderParentFolder());
            $order->setCreationDate(time());
            $order->setKey(File::getValidFilename($tempOrdernumber));
            $order->setPublished(true);

            $order->setOrdernumber($tempOrdernumber);
            $order->setOrderdate(new \DateTime());
            $order->setCartId($this->createCartId($cart));
        }

        // check if pending payment. if one, do not update order from cart
        if (!empty($order->getOrderState())) {
            return $order;
        }

        // update order from cart
        // TODO refine how amount is passed to order (asNumeric? asString?)
        $order->setTotalPrice($cart->getPriceCalculator()->getGrandTotal()->getGrossAmount()->asString());
        $order->setTotalNetPrice($cart->getPriceCalculator()->getGrandTotal()->getNetAmount()->asString());
        $order->setSubTotalPrice($cart->getPriceCalculator()->getSubTotal()->getAmount()->asString());
        $order->setSubTotalNetPrice($cart->getPriceCalculator()->getSubTotal()->getNetAmount()->asString());
        $order->setTaxInfo($this->buildTaxArray($cart->getPriceCalculator()->getGrandTotal()->getTaxEntries()));

        $modificationItems = new \Pimcore\Model\Object\Fieldcollection();
        foreach ($cart->getPriceCalculator()->getPriceModifications() as $name => $modification) {
            $modificationItem = new \Pimcore\Model\Object\Fieldcollection\Data\OrderPriceModifications();
            $modificationItem->setName($modification->getDescription() ? $modification->getDescription() : $name);
            $modificationItem->setAmount($modification->getGrossAmount()->asString());
            $modificationItem->setNetAmount($modification->getNetAmount()->asString());
            $modificationItems->add($modificationItem);
        }

        $order->setPriceModifications($modificationItems);

        $order = $this->setCurrentCustomerToOrder($order);

        // set order currency
        $currency = $cart->getPriceCalculator()->getGrandTotal()->getCurrency();
        $order->setCurrency($currency->getShortName());

        $order->save();

        // for each cart item and cart sub item create corresponding order items
        $orderItems = $this->applyOrderItems($cart->getItems(), $order);
        $order->setItems($orderItems);

        $this->applyVoucherTokens($order, $cart);

        // for each gift item create corresponding order item
        $orderGiftItems = $this->applyOrderItems($cart->getGiftItems(), $order, true);
        $order->setGiftItems($orderGiftItems);

        $order = $this->applyCustomCheckoutDataToOrder($cart, $order);
        $order->save();

        return $order;
    }

    /**
     * @param array $items
     * @param AbstractOrder $order
     *
     * @return array
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

    protected function applyVoucherTokens(AbstractOrder $order, ICart $cart)
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
     * @param ICart $cart
     * @param AbstractOrder $order
     *
     * @return AbstractOrder
     */
    protected function applyCustomCheckoutDataToOrder(ICart $cart, AbstractOrder $order)
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
     *
     * @throws UnsupportedException
     */
    protected function setCurrentCustomerToOrder(AbstractOrder $order)
    {
        // sets customer to order - if available
        if (@Tool::classExists('\\Pimcore\\Model\\Object\\Customer')) {
            $customer = \Pimcore\Model\Object\Customer::getById($this->environment->getCurrentUserId());
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

        return new $orderClassName();
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

        return new $orderItemClassName();
    }

    /**
     * @param ICartItem $item
     * @param $parent
     * @param bool $isGiftItem
     *
     * @return AbstractOrderItem
     *
     * @throws \Exception
     */
    protected function createOrderItem(ICartItem $item, $parent, $isGiftItem = false)
    {
        $key = $this->buildOrderItemKey($item);

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

        $price    = Decimal::zero();
        $netPrice = Decimal::zero();

        if (!$isGiftItem && is_object($item->getTotalPrice())) {
            $price    = $item->getTotalPrice()->getGrossAmount();
            $netPrice = $item->getTotalPrice()->getNetAmount();
        }

        // TODO refine how amount is passed to order item (asNumeric? asString?)
        $orderItem->setTotalPrice($price->asString());
        $orderItem->setTotalNetPrice($netPrice->asString());
        $orderItem->setTaxInfo($this->buildTaxArray($item->getTotalPrice()->getTaxEntries()));

        if (!$isGiftItem) {
            // save active pricing rules
            $priceInfo = $item->getPriceInfo();
            if ($priceInfo instanceof IPriceInfo && method_exists($orderItem, 'setPricingRules')) {
                $priceRules = new \Pimcore\Model\Object\Fieldcollection();
                foreach ($priceInfo->getRules() as $rule) {
                    if ($rule->hasProductActions()) {
                        $priceRule = new \Pimcore\Model\Object\Fieldcollection\Data\PricingRule();
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

        return $orderItem;
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
                $taxEntry->getAmount()->asString()
            ];
        }

        return $taxArray;
    }

    /**
     * Build order item key from cart item
     *
     * @param ICartItem $item
     *
     * @return string
     */
    protected function buildOrderItemKey(ICartItem $item)
    {
        $key = File::getValidFilename(sprintf(
            '%s_%s',
            $item->getProduct()->getId(),
            $item->getItemKey()
        ));

        return $key;
    }

    /**
     * Build list class name, try namespaced first and fall back to legacy naming
     *
     * @param $className
     *
     * @return mixed
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
     * @return \Pimcore\Model\Object\Listing\Concrete
     *
     * @throws \Exception
     */
    public function buildOrderList()
    {
        $orderListClass = $this->buildOrderListClassName();
        $orderList      = new $orderListClass;

        return $orderList;
    }

    /**
     * Build order item listing
     *
     * @return \Pimcore\Model\Object\Listing\Concrete
     *
     * @throws \Exception
     */
    public function buildOrderItemList()
    {
        $orderItemListClass = $this->buildOrderItemListClassName();
        $orderItemList      = new $orderItemListClass;

        return $orderItemList;
    }

    /**
     * @param IStatus $paymentStatus
     *
     * @return AbstractOrder
     */
    public function getOrderByPaymentStatus(IStatus $paymentStatus)
    {
        //this call is needed in order to really load most updated object from cache or DB (otherwise it could be loaded from process)
        \Pimcore::collectGarbage();

        $orderId = explode('~', $paymentStatus->getInternalPaymentId());
        $orderId = $orderId[1];
        $orderClass = $this->getOrderClassName();

        return $orderClass::getById($orderId);
    }
}
