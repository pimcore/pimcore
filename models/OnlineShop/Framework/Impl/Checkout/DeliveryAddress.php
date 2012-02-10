<?php

class OnlineShop_Framework_Impl_Checkout_DeliveryAddress extends OnlineShop_Framework_Impl_Checkout_AbstractStep implements OnlineShop_Framework_ICheckoutStep {

    const LINE1 = "delivery_address_line1";
    const LINE2 = "delivery_address_line2";
    const LINE3 = "delivery_address_line3";
    const LINE4 = "delivery_address_line4";
    const LINE5 = "delivery_address_line5";
    const LINE6 = "delivery_address_line6";
    const LINE7 = "delivery_address_line7";
    const LINE8 = "delivery_address_line8";

    /**
     * @return string
     */
    public function getName() {
        return "deliveryaddress";
    }

    /**
     * commits step and sets delivered data
     * @param  $data
     * @return bool
     */
    public function commit($data) {
        $this->cart->setCheckoutData(self::LINE1, $data->line1);
        $this->cart->setCheckoutData(self::LINE2, $data->line2);
        $this->cart->setCheckoutData(self::LINE3, $data->line3);
        $this->cart->setCheckoutData(self::LINE4, $data->line4);
        $this->cart->setCheckoutData(self::LINE5, $data->line5);
        $this->cart->setCheckoutData(self::LINE6, $data->line6);
        $this->cart->setCheckoutData(self::LINE7, $data->line7);
        $this->cart->setCheckoutData(self::LINE8, $data->line8);
        return true;
    }

    /**
     * @return mixed
     */
    public function getData() {
        $data = new stdClass();

        if($this->cart->getCheckoutData(self::LINE1)) {
            $data->line1 = $this->cart->getCheckoutData(self::LINE1);
            $data->isSet = true;
        }
        if($this->cart->getCheckoutData(self::LINE2)) {
            $data->line2 = $this->cart->getCheckoutData(self::LINE2);
            $data->isSet = true;
        }
        if($this->cart->getCheckoutData(self::LINE3)) {
            $data->line3 = $this->cart->getCheckoutData(self::LINE3);
            $data->isSet = true;
        }
        if($this->cart->getCheckoutData(self::LINE4)) {
            $data->line4 = $this->cart->getCheckoutData(self::LINE4);
            $data->isSet = true;
        }
        if($this->cart->getCheckoutData(self::LINE5)) {
            $data->line5 = $this->cart->getCheckoutData(self::LINE5);
            $data->isSet = true;
        }
        if($this->cart->getCheckoutData(self::LINE6)) {
            $data->line6 = $this->cart->getCheckoutData(self::LINE6);
            $data->isSet = true;
        }
        if($this->cart->getCheckoutData(self::LINE7)) {
            $data->line7 = $this->cart->getCheckoutData(self::LINE7);
            $data->isSet = true;
        }
        if($this->cart->getCheckoutData(self::LINE8)) {
            $data->line9 = $this->cart->getCheckoutData(self::LINE8);
            $data->isSet = true;
        }
        return $data;
    }
}
