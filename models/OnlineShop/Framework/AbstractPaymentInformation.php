<?php

/**
 * Abstract base class for payment information field collection
 */
abstract class OnlineShop_Framework_AbstractPaymentInformation extends \Pimcore\Model\Object\Fieldcollection\Data\AbstractData {

    /**
     * @return Zend_Date
     */
    public abstract function getPaymentStart ();

    /**
     * @param Zend_Date $paymentStart
     * @return void
     */
    public abstract function setPaymentStart ($paymentStart);

    /**
     * @return Zend_Date
     */
    public abstract function getPaymentFinish ();

    /**
     * @param Zend_Date $paymentStart
     * @return void
     */
    public abstract function setPaymentFinish ($paymentFinish);

    /**
     * @return string
     */
    public abstract function getPaymentReference ();

    /**
     * @param string $paymentReference
     * @return void
     */
    public abstract function setPaymentReference ($paymentReference);

    /**
     * @return string
     */
    public abstract function getPaymentState ();

    /**
     * @param string $paymentState
     * @return void
     */
    public abstract function setPaymentState ($paymentState);

    /**
     * @return string
     */
    public abstract function getInternalPaymentId ();

    /**
     * @param string $internalPaymentId
     * @return void
     */
    public abstract function setInternalPaymentId ($internalPaymentId);

}
