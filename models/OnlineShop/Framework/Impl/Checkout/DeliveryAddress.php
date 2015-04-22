<?php

/**
 * Class OnlineShop_Framework_Impl_Checkout_DeliveryAddress
 *
 * sample implementation for delivery address
 */
class OnlineShop_Framework_Impl_Checkout_DeliveryAddress extends OnlineShop_Framework_Impl_Checkout_AbstractStep implements OnlineShop_Framework_ICheckoutStep {

    /**
     * namespace key
     */
    const PRIVATE_NAMESPACE = 'delivery_address';


    /**
     * @return string
     */
    public function getName() {
        return "deliveryaddress";
    }

    /**
     * sets delivered data and commits step
     *
     * @param  $data
     * @return bool
     */
    public function commit($data) {
        $this->cart->setCheckoutData(self::PRIVATE_NAMESPACE, json_encode($data));
        return true;
    }

    /**
     * returns saved data of step
     *
     * @return mixed
     */
    public function getData() {
        $data = json_decode($this->cart->getCheckoutData(self::PRIVATE_NAMESPACE));
        return $data;
    }
}
