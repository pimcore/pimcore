<?php

class OnlineShop_Framework_Impl_Checkout_Payment_Status {

    private $status;
    private $internalPaymentId;
    private $paymentReference;

    function __construct($internalPaymentId, $paymentReference, $status)
    {
        $this->internalPaymentId = $internalPaymentId;
        $this->paymentReference = $paymentReference;
        $this->status = $status;
    }


    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $paymentReference
     */
    public function setPaymentReference($paymentReference)
    {
        $this->paymentReference = $paymentReference;
    }

    /**
     * @return mixed
     */
    public function getPaymentReference()
    {
        return $this->paymentReference;
    }

    /**
     * @param mixed $internalPaymentId
     */
    public function setInternalPaymentId($internalPaymentId)
    {
        $this->internalPaymentId = $internalPaymentId;
    }

    /**
     * @return mixed
     */
    public function getInternalPaymentId()
    {
        return $this->internalPaymentId;
    }



}