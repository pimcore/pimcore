<?php

class OnlineShop_Framework_Impl_Checkout_Payment_Status {

    /**
     * internal pimcore order status - see also constants OnlineShop_Framework_AbstractOrder::ORDER_STATE_*
     *
     * @var string
     */
    private $status;

    /**
     * pimcore internal payment id, necessary to identify payment information in order object
     *
     * @var string
     */
    private $internalPaymentId;

    /**
     * payment reference from payment provider
     *
     * @var string
     */
    private $paymentReference;

    /**
     * payment message provided from payment provider - e.g. error message on error
     *
     * @var string
     */
    private $message;

    /**
     * payment type provided from payment provider
     *
     * @var string
     */
    private $paymentType;

    /**
     * payment state provided from payment provider
     *
     * @var string
     */
    private $paymentState;

    /**
     * anonymous pan from payment provider
     *
     * @var null
     */
    private $anonymousPan;

    /**
     * @var null
     */
    private $authenticated;

    function __construct($internalPaymentId, $paymentReference, $status, $paymentState = null, $message = null, $paymentType = null, $anonymousPan = null, $authenticated = null)
    {
        $this->anonymousPan = $anonymousPan;
        $this->authenticated = $authenticated;
        $this->internalPaymentId = $internalPaymentId;
        $this->message = $message;
        $this->paymentReference = $paymentReference;
        $this->paymentState = $paymentState;
        $this->paymentType = $paymentType;
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

    /**
     * @param mixed $anonymousPan
     */
    public function setAnonymousPan($anonymousPan)
    {
        $this->anonymousPan = $anonymousPan;
    }

    /**
     * @return mixed
     */
    public function getAnonymousPan()
    {
        return $this->anonymousPan;
    }

    /**
     * @param mixed $authenticated
     */
    public function setAuthenticated($authenticated)
    {
        $this->authenticated = $authenticated;
    }

    /**
     * @return mixed
     */
    public function getAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $paymentState
     */
    public function setPaymentState($paymentState)
    {
        $this->paymentState = $paymentState;
    }

    /**
     * @return mixed
     */
    public function getPaymentState()
    {
        return $this->paymentState;
    }

    /**
     * @param mixed $paymentType
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;
    }

    /**
     * @return mixed
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }




}
