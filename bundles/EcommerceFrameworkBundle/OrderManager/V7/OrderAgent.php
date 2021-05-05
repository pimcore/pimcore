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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\V7;

use Carbon\Carbon;
use Exception;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\PaymentNotAllowedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\PaymentManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\PaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\RecurringPaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Event\Ecommerce\OrderAgentEvents;
use Pimcore\Event\Model\Ecommerce\OrderAgentEvent;
use Pimcore\Log\Simple;
use Pimcore\Logger;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\PaymentInfo;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Pimcore\Model\DataObject\OnlineShopOrder\PaymentProvider;
use Pimcore\Model\Element\Note;
use Pimcore\Model\Element\Note\Listing as NoteListing;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OrderAgent implements OrderAgentInterface
{
    public const PAYMENT_PROVIDER_BRICK_PREFIX = 'PaymentProvider';

    /**
     * @var AbstractOrder
     */
    protected $order;

    /**
     * @var EnvironmentInterface
     */
    protected $environment;

    /**
     * @var PaymentManagerInterface
     */
    protected $paymentManager;

    /**
     * @var PaymentInterface|null
     */
    protected $paymentProvider;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var Note[]|null
     */
    protected $fullChangeLog;

    public function __construct(
        AbstractOrder $order,
        EnvironmentInterface $environment,
        PaymentManagerInterface $paymentManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->order = $order;
        $this->environment = $environment;
        $this->paymentManager = $paymentManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return AbstractOrder
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * cancel order item and refund payment
     *
     * @param AbstractOrderItem $item
     *
     * @return Note
     *
     * @throws \Exception
     */
    public function itemCancel(AbstractOrderItem $item)
    {
        // add log note
        $note = $this->createNote($item);
        $note->setTitle(__FUNCTION__);

        // change item
        $item->setOrderState(AbstractOrder::ORDER_STATE_CANCELLED);

        // cancel complete order if all items are canceled
        //        $cancel = true;
        //        foreach($this->getOrder()->getItems() as $i)
        //        {
        //            /* @var OrderItem $i */
        //            if($i->getOrderState() != Order::ORDER_STATE_CANCELLED)
        //            {
        //                $cancel = false;
        //                break;
        //            }
        //        }
        //
        //
        //        // cancel complete order
        //        if($cancel)
        //        {
        //            $this->getOrder()->setOrderState( Order::ORDER_STATE_CANCELLED )->save();
        //        }

        // commit changes
        $item->save();
        $note->save();

        return $note;
    }

    /**
     * change order item
     *
     * @param AbstractOrderItem $item
     * @param float $amount
     *
     * @return Note
     *
     * @throws Exception
     */
    public function itemChangeAmount(AbstractOrderItem $item, $amount)
    {
        // init
        $amount = (float)$amount;

        // add log note
        $note = $this->createNote($item);
        $note->setTitle(__FUNCTION__);
        $oldAmount = $item->getAmount();
        $note->addData('amount.old', 'text', $oldAmount);
        $note->addData('amount.new', 'text', $amount);

        // change
        $item->setAmount($amount);

        // save
        $item->save();
        $note->save();

        return $note;
    }

    /**
     * start item complaint
     *
     * @param AbstractOrderItem $item
     * @param float     $quantity
     *
     * @return Note
     */
    public function itemComplaint(AbstractOrderItem $item, $quantity)
    {
        // add log note
        $note = $this->createNote($item);
        $note->setTitle(__FUNCTION__);
        $note->addData('quantity', 'text', $quantity);

        // save
        $note->save();

        return $note;
    }

    /**
     * set a item state
     *
     * @param AbstractOrderItem $item
     * @param string $state
     *
     * @return Note
     *
     * @throws Exception
     */
    public function itemSetState(AbstractOrderItem $item, $state)
    {
        // add log note
        $note = $this->createNote($item);
        $note->setTitle(__FUNCTION__);

        $oldState = $item->getOrderState();
        $note->addData('state.old', 'text', $oldState);
        $note->addData('state.new', 'text', $state);

        // change
        $item->setOrderState($state);

        // save
        $item->save();
        $note->save();

        return $note;
    }

    /**
     * @param Concrete $object
     *
     * @return Note
     */
    protected function createNote(Concrete $object)
    {
        // general
        $note = new Note();
        $note->setElement($object);
        $note->setDate(time());

        $note->setType('order-agent');

        return $note;
    }

    /**
     * @return bool
     */
    public function hasPayment()
    {
        $paymentInfo = $this->getOrder()->getPaymentInfo();
        if (!$paymentInfo || empty($paymentInfo->getItems())) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->environment->getDefaultCurrency();
    }

    /**
     * @return PaymentInterface
     */
    public function getPaymentProvider()
    {
        if (!$this->paymentProvider) {
            // init
            $order = $this->getOrder();

            // get first available provider
            foreach ($order->getPaymentProvider()->getBrickGetters() as $method) {
                $providerData = $order->getPaymentProvider()->{$method}();
                if ($providerData) {
                    /* @var AbstractData $providerData */

                    // get provider data
                    if (method_exists($providerData, 'getConfigurationKey') && $providerData->getConfigurationKey()) {
                        $name = $providerData->getConfigurationKey();
                    } else {
                        $name = strtolower(str_replace(OrderAgent::PAYMENT_PROVIDER_BRICK_PREFIX, '', $providerData->getType()));
                    }
                    $authorizedData = [];
                    foreach ($providerData->getObjectVars() as $field => $value) {
                        if (preg_match('#^auth_(?<name>\w+)$#i', $field, $match)) {
                            $func = 'get' . $field;
                            $authorizedData[$match['name']] = $providerData->$func();
                        }
                    }

                    // init payment
                    $paymentProvider = $this->paymentManager->getProvider($name);
                    $paymentProvider->setAuthorizedData($authorizedData);

                    $this->paymentProvider = $paymentProvider;

                    break;
                }
            }
        }

        return $this->paymentProvider;
    }

    /**
     * @param PaymentInterface $paymentProvider
     * @param AbstractOrder|null $sourceOrder
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setPaymentProvider(PaymentInterface $paymentProvider, AbstractOrder $sourceOrder = null)
    {
        $this->paymentProvider = $paymentProvider;

        // save provider data
        $order = $this->getOrder();

        $provider = $order->getPaymentProvider();
        /* @var PaymentProvider $provider */

        // load existing
        $providerDataGetter = 'getPaymentProvider' . $paymentProvider->getName();
        $providerData = $provider->{$providerDataGetter}();

        if (!$providerData) {
            // create new
            $class = '\Pimcore\Model\DataObject\Objectbrick\Data\PaymentProvider' . $paymentProvider->getName();
            $providerData = new $class($order);
            $provider->{'setPaymentProvider' . $paymentProvider->getName()}($providerData);
        }

        // update authorizedData
        $authorizedData = $paymentProvider->getAuthorizedData();
        foreach ((array)$authorizedData as $field => $value) {
            $setter = 'setAuth_' . $field;
            if (method_exists($providerData, $setter)) {
                $providerData->{$setter}($value);
            }
        }

        if (method_exists($providerData, 'setPaymentFinished')) {
            $providerData->setPaymentFinished(new \DateTime());
        }

        if (method_exists($providerData, 'setConfigurationKey')) {
            $providerData->setConfigurationKey($paymentProvider->getConfigurationKey());
        }

        /* recurring payment data */
        if ($sourceOrder && $paymentProvider instanceof RecurringPaymentInterface) {
            $paymentProvider->setRecurringPaymentSourceOrderData($sourceOrder, $providerData);
        }

        $order->save(['versionNote' => 'OrderAgent::setPaymentProvider.']);

        return $this;
    }

    /**
     * @return null|AbstractPaymentInformation
     */
    public function getCurrentPendingPaymentInfo()
    {
        $order = $this->getOrder();

        $paymentInformation = $order->getPaymentInfo();
        $currentPaymentInformation = null;
        if ($paymentInformation) {
            foreach ($paymentInformation as $paymentInfo) {
                if ($paymentInfo->getPaymentState() == $order::ORDER_STATE_PAYMENT_PENDING || $paymentInfo->getPaymentState() == $order::ORDER_STATE_PAYMENT_INIT) {
                    return $paymentInfo;
                }
            }
        }

        return null;
    }

    /**
     * @param AbstractOrder $order
     * @param string $paymentState
     *
     * @return PaymentInfo
     */
    protected function createNewOrderInformation(AbstractOrder $order, string $paymentState)
    {
        $paymentInformationCollection = $order->getPaymentInfo();
        if (empty($paymentInformationCollection)) {
            $paymentInformationCollection = new Fieldcollection();
            $order->setPaymentInfo($paymentInformationCollection);
        }

        $currentPaymentInformation = new PaymentInfo();
        $currentPaymentInformation->setPaymentStart(new Carbon());
        $currentPaymentInformation->setPaymentState($paymentState);
        $currentPaymentInformation->setInternalPaymentId($this->generateInternalPaymentId($paymentInformationCollection->getCount() + 1));

        $paymentInformationCollection->add($currentPaymentInformation);

        return $currentPaymentInformation;
    }

    /**
     * {@inheritdoc}
     *
     * @throws PaymentNotAllowedException
     * @throws Exception
     */
    public function initPayment()
    {
        $currentPaymentInformation = $this->getCurrentPendingPaymentInfo();
        $order = $this->getOrder();

        $event = new OrderAgentEvent($this, ['currentPaymentInformation' => $currentPaymentInformation]);
        $this->eventDispatcher->dispatch($event, OrderAgentEvents::PRE_INIT_PAYMENT);
        $currentPaymentInformation = $event->getArgument('currentPaymentInformation');

        if ($currentPaymentInformation) {
            if ($currentPaymentInformation->getPaymentState() == AbstractOrder::ORDER_STATE_PAYMENT_PENDING) {
                throw new PaymentNotAllowedException(
                    'Init payment not allowed because there is currently a payment pending. Cancel payment or recreate order.',
                    $order
                );
            }

            if ($currentPaymentInformation->getPaymentState() == AbstractOrder::ORDER_STATE_PAYMENT_INIT) {
                $internalPaymentIdForCurrentOrderVersion = $this->generateInternalPaymentId();

                //if order fingerprint changed, abort initialized payment and create new payment information (so set it to null)
                if ($currentPaymentInformation->getInternalPaymentId() != $internalPaymentIdForCurrentOrderVersion) {
                    $currentPaymentInformation->setPaymentState($order::ORDER_STATE_ABORTED);
                    $currentPaymentInformation->setMessage($currentPaymentInformation->getMessage() . ' - aborted be because order changed after payment was initialized.');
                    $order->save(['versionNote' => 'Agent::initPayment - save order to abort existing PaymentInformation.']);

                    $currentPaymentInformation = null;
                }
            }
        }

        //if no payment information available, create new one
        if (empty($currentPaymentInformation)) {
            $currentPaymentInformation = $this->createNewOrderInformation($order, AbstractOrder::ORDER_STATE_PAYMENT_INIT);
            $order->save(['versionNote' => 'Agent::initPayment - save order to add new PaymentInformation.']);
        }

        $this->eventDispatcher->dispatch(new OrderAgentEvent($this, ['currentPaymentInformation' => $currentPaymentInformation]), OrderAgentEvents::POST_INIT_PAYMENT);

        return $currentPaymentInformation;
    }

    /**
     * @return null|AbstractPaymentInformation|PaymentInfo
     *
     * @throws Exception
     * @throws UnsupportedException
     */
    public function startPayment()
    {
        //initialize payment (if not already done before)
        $currentPaymentInformation = $this->initPayment();

        $event = new OrderAgentEvent($this, ['currentPaymentInformation' => $currentPaymentInformation]);
        $this->eventDispatcher->dispatch($event, OrderAgentEvents::PRE_START_PAYMENT);
        $currentPaymentInformation = $event->getArgument('currentPaymentInformation');

        $order = $this->getOrder();

        //set payment information state to pending
        $currentPaymentInformation->setPaymentState($order::ORDER_STATE_PAYMENT_PENDING);
        $order->save(['versionNote' => 'Agent::startPayment - save order to update PaymentInformation.']);

        $this->eventDispatcher->dispatch(new OrderAgentEvent($this, ['currentPaymentInformation' => $currentPaymentInformation]), OrderAgentEvents::POST_START_PAYMENT);

        return $currentPaymentInformation;
    }

    /**
     * generates internal payment id for current order
     *
     * @param null $paymentInfoCount
     *
     * @return string
     */
    protected function generateInternalPaymentId($paymentInfoCount = null)
    {
        $order = $this->getOrder();
        if ($paymentInfoCount === null) {
            $paymentInfoCount = $order->getPaymentInfo() ? $order->getPaymentInfo()->getCount() : 0;
        }

        return 'payment_' . $this->getFingerprintOfOrder() . '-' . $paymentInfoCount . '~' . $order->getId();
    }

    /**
     * creates fingerprint of order to check, if order has changed
     * consider:
     *  - total price
     *  - creation date
     *  - all product numbers
     *
     * @return int
     */
    protected function getFingerprintOfOrder()
    {
        $order = $this->getOrder();
        $fingerprintParts = [];
        $fingerprintParts[] = Decimal::create($order->getTotalPrice())->asString();
        $fingerprintParts[] = $order->getCreationDate();
        foreach ($order->getItems() as $item) {
            $fingerprintParts[] = $item->getProductNumber();
            $fingerprintParts[] = $item->getId();
            $fingerprintParts[] = $item->getAmount();
        }

        $fingerPrint = crc32(strtolower(implode('.', $fingerprintParts)));

        $event = new OrderAgentEvent($this, ['fingerPrint' => $fingerPrint, 'fingerPrintParts' => $fingerprintParts]);
        $this->eventDispatcher->dispatch($event, OrderAgentEvents::PRE_INIT_PAYMENT);

        return $event->getArgument('fingerPrint');
    }

    /**
     * @return AbstractOrder
     *
     * @throws Exception
     * @throws UnsupportedException
     */
    public function cancelStartedOrderPayment()
    {
        $order = $this->getOrder();
        $currentPaymentInformation = $this->getCurrentPendingPaymentInfo();

        $event = new OrderAgentEvent($this, ['currentPaymentInformation' => $currentPaymentInformation]);
        $this->eventDispatcher->dispatch($event, OrderAgentEvents::PRE_CANCEL_PAYMENT);
        $currentPaymentInformation = $event->getArgument('currentPaymentInformation');

        if ($currentPaymentInformation) {
            $currentPaymentInformation->setPaymentState($order::ORDER_STATE_CANCELLED);
            $currentPaymentInformation->setMessage("Payment cancelled by 'cancelStartedOrderPayment'");
            $order->setOrderState(null);
            $order->save(['versionNote' => 'OrderAgent::cancelStartedOrderPayment - empty order state.']);
        } else {
            throw new UnsupportedException('Cancel started order payment not possible');
        }

        $this->eventDispatcher->dispatch(new OrderAgentEvent($this, ['currentPaymentInformation' => $currentPaymentInformation]), OrderAgentEvents::POST_CANCEL_PAYMENT);

        return $order;
    }

    /**
     * @param StatusInterface $status
     *
     * @return $this
     *
     * @throws Exception
     * @throws UnsupportedException
     */
    public function updatePayment(StatusInterface $status)
    {
        //log this for documentation
        Simple::log('update-payment', 'Update payment called with status: ' . print_r($status, true));

        $event = new OrderAgentEvent($this, ['status' => $status]);
        $this->eventDispatcher->dispatch($event, OrderAgentEvents::PRE_UPDATE_PAYMENT);
        $status = $event->getArgument('status');

        $order = $this->getOrder();
        $currentOrderFingerPrint = null;

        $paymentInformationCollection = $order->getPaymentInfo();

        /** @var PaymentInfo $currentPaymentInformation */
        $currentPaymentInformation = null;
        if (empty($paymentInformationCollection)) {
            $paymentInformationCollection = new Fieldcollection();
            $order->setPaymentInfo($paymentInformationCollection);
        }

        foreach ($paymentInformationCollection as $paymentInfoIndex => $paymentInfo) {
            if ($paymentInfo->getInternalPaymentId() == $status->getInternalPaymentId()) {
                $currentPaymentInformation = $paymentInfo;

                $currentOrderFingerPrint = $this->generateInternalPaymentId($paymentInfoIndex + 1);
                break;
            }
        }

        //check if current payment info already aborted - if so create new one to log information and throw exception
        //because something wired is going on
        $abortedByResponseReceived = false;
        if ($currentPaymentInformation && $currentPaymentInformation->getPaymentState() == AbstractOrder::ORDER_STATE_ABORTED) {
            $abortedByResponseReceived = true;

            //set current payment info to null to create a new one
            $currentPaymentInformation = null;
        }

        if (empty($currentPaymentInformation)) {
            Logger::warn('Payment information with id ' . $status->getInternalPaymentId() . ' not found, creating new one.');

            //if payment information not found, create a new in order to document all payment updates
            $currentPaymentInformation = new PaymentInfo();
            $currentPaymentInformation->setInternalPaymentId($status->getInternalPaymentId());
            $paymentInformationCollection->add($currentPaymentInformation);
        }

        // save basic payment data
        $currentPaymentInformation->setPaymentFinish(new Carbon());
        $currentPaymentInformation->setPaymentReference($status->getPaymentReference());
        $currentPaymentInformation->setPaymentState($status->getStatus());
        $currentPaymentInformation->setMessage($currentPaymentInformation->getMessage() . ' ' . $status->getMessage());
        $currentPaymentInformation->setProviderData(json_encode($status->getData()));

        // opt. save additional payment data separately
        foreach ($status->getData() as $field => $value) {
            $setter = 'setProvider_' . $field;
            if (method_exists($currentPaymentInformation, $setter)) {
                $currentPaymentInformation->$setter($value);
            }
        }
        $this->extractAdditionalPaymentInformation($status, $currentPaymentInformation);

        $event = new OrderAgentEvent($this, ['status' => $status]);
        $this->eventDispatcher->dispatch($event, OrderAgentEvents::POST_UPDATE_PAYMENT);

        if ($abortedByResponseReceived) {
            // if we got an response even if payment state was already aborted throw exception
            $paymentStateBackup = $currentPaymentInformation->getPaymentState();
            $currentPaymentInformation->setPaymentState(AbstractOrder::ORDER_PAYMENT_STATE_ABORTED_BUT_RESPONSE);
            $currentPaymentInformation->setMessage(
                $currentPaymentInformation->getMessage() .
                ' -> got response although payment state was already aborted, new payment state was "' .
                $paymentStateBackup . '". throwing exception!'
            );
            $order->save(['versionNote' => 'OrderAgent::updatePayment - aborted response received.']);
            throw new UnsupportedException('Got response although payment state was already aborted, new payment state was ' . $paymentStateBackup);
        } elseif ($currentOrderFingerPrint != $status->getInternalPaymentId()) {
            // check, if order finger print has changed since start payment - if so, throw exception because something wired is going on
            // but finish update order first in order to have logging information

            $currentPaymentInformation->setMessage($currentPaymentInformation->getMessage() . ' -> order fingerprint changed since start payment. throwing exception!');
            $order->setOrderState(null);
            $order->save(['versionNote' => 'OrderAgent::updatePayment - finger print of order changed.']);
            throw new UnsupportedException('order fingerprint changed since start payment. Old internal status = ' . $status->getInternalPaymentId() . ' -> current internal status id = ' . $currentOrderFingerPrint);
        } else {
            $order->save(['versionNote' => 'OrderAgent::updatePayment.']);
        }

        return $this;
    }

    /**
     * Hook to extract and save additional information in payment information
     *
     * @param StatusInterface $status
     * @param PaymentInfo $currentPaymentInformation
     */
    protected function extractAdditionalPaymentInformation(StatusInterface $status, PaymentInfo $currentPaymentInformation)
    {
    }

    /**
     * @return Note[]
     */
    public function getFullChangeLog()
    {
        if (!$this->fullChangeLog) {
            // init
            $order = $this->getOrder();

            // load order events
            $noteList = new NoteListing();
            /* @var NoteListing $noteList */

            $cid = [ $order->getId() ];
            foreach ($order->getItems() as $item) {
                $cid[] = $item->getId();
                foreach ($item->getSubItems() as $subItem) {
                    $cid[] = $subItem->getId();
                }
            }

            $noteList->addConditionParam('type = ?', 'order-agent');
            $noteList->addConditionParam(sprintf('cid in(%s)', implode(',', $cid)), '');

            $noteList->setOrderKey('date');
            $noteList->setOrder('desc');

            $this->fullChangeLog = $noteList->load();
        }

        return $this->fullChangeLog;
    }
}
