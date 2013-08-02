<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 30.07.13
 * Time: 16:51
 * To change this template use File | Settings | File Templates.
 */

abstract class OnlineShop_Framework_Impl_Checkout_AbstractPayment implements OnlineShop_Framework_ICheckoutPayment
{
    /**
     * @var OnlineShop_Framework_ICart
     */
    protected $cart;

    /**
     * @var array
     */
    protected $errors;


    /**
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

}