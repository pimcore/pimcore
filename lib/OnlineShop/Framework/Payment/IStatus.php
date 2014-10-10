<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 03.10.2014
 * Time: 15:25
 */

interface OnlineShop_Framework_Payment_IStatus
{
    /**
     * @return string
     */
    public function getPaymentReference();

    /**
     * @return string
     */
    public function getInternalPaymentId();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @return array
     */
    public function getData();
}