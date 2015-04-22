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
     * @return mixed - either an url for a link the user has to follow to (e.g. paypal) or
     *                 an zend form which needs to submitted (e.g. datatrans and wirecard)
     */
    public function initPayment(OnlineShop_Framework_IPrice $price, array $config);


    /**
     * handle response / execute payment
     * @param OnlineShop_Framework_Payment_IStatus $response
     *
     * @return OnlineShop_Framework_Payment_IStatus
     */
    public function handleResponse($response);


    /**
     * return the authorized data from payment provider
     * @return array
     */
    public function getAuthorizedData();


    /**
     * set authorized data from payment provider
     * @param array $authorizedData
     */
    public function setAuthorizedData(array $authorizedData);


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
