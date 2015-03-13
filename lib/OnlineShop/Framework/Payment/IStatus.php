<?php

/**
 * Interface OnlineShop_Framework_Payment_IStatus
 */
interface OnlineShop_Framework_Payment_IStatus
{
    /**
     * payment reference from payment provider
     *
     * @return string
     */
    public function getPaymentReference();

    /**
     * pimcore internal payment id, necessary to identify payment information in order object
     *
     * @return string
     */
    public function getInternalPaymentId();

    /**
     * payment message provided from payment provider - e.g. error message on error
     *
     * @return string
     */
    public function getMessage();

    /**
     * internal pimcore order status - see also constants OnlineShop_Framework_AbstractOrder::ORDER_STATE_*
     *
     * @return string
     */
    public function getStatus();

    /**
     * additional payment data
     *
     * @return array
     */
    public function getData();
}