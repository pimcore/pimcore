<?php

/**
 * Interface for checkout payment provider
 */
interface OnlineShop_Framework_ICheckoutPayment
{
    /**
     * @param Zend_Config                $xml
     * @param OnlineShop_Framework_ICart $cart
     */
    public function __construct(Zend_Config $xml, OnlineShop_Framework_ICart $cart);


    /**
     * start payment
     * @param array $config
     *
     * @return mixed|bool
     */
    public function initPayment(array $config);


    /**
     * handle response / execute payment
     * @param mixed $response
     * @return OnlineShop_Framework_Impl_Checkout_Payment_Status
     */
    public function handleResponse($response);


    /**
     * @return string|null
     */
    public function getPayReference();


    /**
     * @return bool
     */
    public function isPaid();

    /**
     * @return bool
     */
    public function hasErrors();

    /**
     * @return array
     */
    public function getErrors();
}
