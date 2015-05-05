<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 16.04.2015
 * Time: 13:58
 */

namespace OnlineShop\Framework\Impl\OrderManager\Order;

use OnlineShop\Framework\OrderManager\IOrderAgent;
use OnlineShop_Framework_Payment_IStatus;
use OnlineShop_Framework_IPayment;
use OnlineShop_Framework_Factory;
use Exception;

use Zend_Date;
use OnlineShop\Framework\Impl\OrderManager;
use OnlineShop_Framework_AbstractOrder as Order;
use OnlineShop_Framework_AbstractOrderItem as OrderItem;

use Pimcore\Model\Object\Fieldcollection;
use Pimcore\Model\Object\Fieldcollection\Data\PaymentInfo;


class Agent implements IOrderAgent
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var \Zend_EventManager_EventManager
     */
    protected $eventManager;

    /**
     * @var OnlineShop_Framework_IPayment
     */
    protected $paymentProvider;

    /**
     * @var OnlineShop_Framework_Factory
     */
    protected $factory;


    /**
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->eventManager = new \Zend_EventManager_EventManager( __CLASS__ );
        $this->factory = OnlineShop_Framework_Factory::getInstance();   // TODO über param?
    }


    public function addHook($event, callable $callback)
    {
        $this->eventManager->attach($event, $callback);
    }


    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }


    /**
     * cancel order item and refund payment
     *
     * @param OrderItem $item
     *
     * @return $this
     * @throws \Exception
     */
    public function itemCancel(OrderItem $item)
    {
        $item->setOrderState( Order::ORDER_STATE_CANCELLED )->save();


        // cancel complete order if all items are canceled
        $cancel = true;
        foreach($this->getOrder()->getItems() as $i)
        {
            /* @var OrderItem $i */
            if($i->getOrderState() != Order::ORDER_STATE_CANCELLED)
            {
                $cancel = false;
                break;
            }
        }


        // cancel complete order
        if($cancel)
        {
            $this->getOrder()->setOrderState( Order::ORDER_STATE_CANCELLED )->save();
        }

        return $this;
    }

    /**
     * change order item
     *
     * @param OrderItem $item
     * @param int $amount
     *
     * @return $this
     * @todo
     */
    public function itemChangeAmount(OrderItem $item, $amount)
    {
        $oldAmount = $item->getAmount();
        $item->setAmount( floatval($amount) );


        // general
        $note = new \Pimcore\Model\Element\Note();
        $note->setElement( $item );
        $note->setDate( time() );
        $note->setType( 'back-office' );
        $note->setTitle( 'itemChangeAmount' );
        $note->setDescription( '' );
//        $note->setUser( $this->getUser()->getId() );

        $note->addData('amount_old', 'number', $oldAmount);
        $note->addData('amount_new', 'number', $amount);


        // event hook
        $context = new \stdClass;
        $context->orderItem = $item;
        $context->note = $note;

        $this->eventManager->trigger('item.change.amount', $context);
//        $note->addData('comment', 'text', 'Some Text');


        // save
//        $item->save();
//        $note->save();
    }
    


    /**
     * @return bool
     */
    public function hasPayment()
    {
        return $this->getOrder()->getPaymentReference() != '' || $this->getOrder()->getPaymentInfo();
    }


    /**
     * @return \Zend_Currency
     */
    public function getCurrency()
    {
        return new \Zend_Currency($this->getOrder()->getCurrency(), $this->factory->getEnvironment()->getCurrencyLocale());
    }

    /**
     * @return OnlineShop_Framework_IPayment
     */
    public function getPaymentProvider()
    {
        if(!$this->paymentProvider)
        {
            // init
            $order = $this->getOrder();
            $authorizedData = [];
            foreach($order as $field => $value)
            {
                if(preg_match('#^paymentAuthorizedData_(?<name>\w+)$#i', $field, $match))
                {
                    $func = 'get' . $field;
                    $authorizedData[$match['name']] = $order->$func();
                }
            }


            // init payment
            $paymentProvider = $this->factory->getPaymentManager()->getProvider( $order->getPaymentProvider() );
            $paymentProvider->setAuthorizedData( $authorizedData );

            $this->paymentProvider = $paymentProvider;
        }

        return $this->paymentProvider;
    }

    /**
     * @param OnlineShop_Framework_IPayment $paymentProvider
     *
     * @return $this
     */
    public function setPaymentProvider(OnlineShop_Framework_IPayment $paymentProvider)
    {
        $this->paymentProvider = $paymentProvider;


        // save authorizedData
        $order = $this->getOrder();
        $authorizedData = $paymentProvider->getAuthorizedData();
        foreach($authorizedData as $field => $value)
        {
            $setter = 'setPaymentAuthorizedData_' . $field;
            if(method_exists($order, $setter))
            {
                $order->{$setter}( $value );
            }
        }

        $order->save();
//        $order->setPaymentProvider( $paymentProvider->getName() );    // TODO


        return $this;
    }


    /**
     * @param bool $forceNew
     *
     * @return PaymentInfo
     * @throws Exception
     */
    public function startPayment($forceNew = true)
    {
        $order = $this->getOrder();

        $paymentInformation = $order->getPaymentInfo();
        $currentPaymentInformation = null;
        if($paymentInformation) {
            foreach($paymentInformation as $paymentInfo) {
                if($paymentInfo->getPaymentState() == $order::ORDER_STATE_PAYMENT_PENDING) {
                    $currentPaymentInformation = $paymentInfo;
                    break;
                }
            }
        } else {
            $paymentInformation = new Fieldcollection();
            $order->setPaymentInfo($paymentInformation);
        }

        if(empty($currentPaymentInformation) && $forceNew) {
            $currentPaymentInformation = new PaymentInfo();
            $currentPaymentInformation->setPaymentStart( Zend_Date::now() );
            $currentPaymentInformation->setPaymentState( $order::ORDER_STATE_PAYMENT_PENDING );
            $currentPaymentInformation->setInternalPaymentId(uniqid("payment_") . "~" . $order->getId());

            $paymentInformation->add($currentPaymentInformation);

            $order->save();
        }

        return $currentPaymentInformation;
    }


    /**
     * @param OnlineShop_Framework_Payment_IStatus $status
     *
     * @return Order
     * @throws Exception
     */
    public function updatePayment(OnlineShop_Framework_Payment_IStatus $status)
    {
        $order = $this->getOrder();

        $paymentInformation = $order->getPaymentInfo();
        $currentPaymentInformation = null;
        foreach($paymentInformation as $paymentInfo) {
            if($paymentInfo->getInternalPaymentId() == $status->getInternalPaymentId()) {
                $currentPaymentInformation = $paymentInfo;
            }
        }

        if(empty($currentPaymentInformation)) {
            throw new Exception("Paymentinformation with internal id " . $status->getInternalPaymentId() . " not found.");
        }

        // save basic payment data
        $currentPaymentInformation->setPaymentFinish( Zend_Date::now() );
        $currentPaymentInformation->setPaymentReference( $status->getPaymentReference() );
        $currentPaymentInformation->setPaymentState( $status->getStatus() );
        $currentPaymentInformation->setMessage( $status->getMessage() );


        // save additional payment data
        foreach($status->getData() as $field => $value)
        {
            $setter = 'setProvider_' . $field;
            if(method_exists($currentPaymentInformation, $setter))
            {
                $currentPaymentInformation->$setter( $value );
            }
        }


        $order->save();

        return $order;
    }
}