<?php

/**
 * Interface for checkout payment provider
 */
interface OnlineShop_Framework_IPayment
{
    /**
     * @param Zend_Config $xml
     */
    public function __construct(Zend_Config $xml);


    /**
     * start payment
     * @param OnlineShop_Framework_IPrice $price
     * @param array                       $config
     *
     * @return mixed
     */
    public function initPayment(OnlineShop_Framework_IPrice $price, array $config);


    /**
     * handle response / execute payment
     * @param mixed $response
     */
    public function handleResponse($response);


    /**
     * return the authorized data from payment provider
     * @return array
     */
    public function getAuthorizedData();


    /**
     * set authorized data from payment provider
     * @param array $getAuthorizedData
     */
    public function setAuthorizedData(array $getAuthorizedData);


    /**
     * execute payment
     * @param OnlineShop_Framework_IPrice $price
     * @param string                      $reference
     *
     * @return OnlineShop_Framework_Payment_IStatus
     */
    public function executeDebit(OnlineShop_Framework_IPrice $price = null, $reference = null);


    /**
     * execute credit
     * @param OnlineShop_Framework_IPrice $price
     * @param string                      $reference
     * @param                             $transactionId
     *
     * @return OnlineShop_Framework_Payment_IStatus
     */
    public function executeCredit(OnlineShop_Framework_IPrice $price, $reference, $transactionId);
}
