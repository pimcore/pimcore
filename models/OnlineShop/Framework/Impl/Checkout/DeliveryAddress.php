<?php

class OnlineShop_Framework_Impl_Checkout_DeliveryAddress extends OnlineShop_Framework_Impl_Checkout_AbstractStep implements OnlineShop_Framework_ICheckoutStep {

    const DELIVERY_ADDRESS = "delivery_address";

    const FIRSTNAME = "delivery_firstname";
    const LASTNAME = "delivery_lastname";
    const COMPANY = "delivery_company";
    const ADDRESS = "delivery_address";
    const ZIP = "delivery_zip";
    const CITY = "delivery_city";
    const COUNTRY = "delivery_country";

    const IS_ALTERNATIVE = "delivery_is_alternative";


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
        $this->cart->setCheckoutData(self::DELIVERY_ADDRESS, json_encode($data));
//        $this->cart->setCheckoutData(self::LINE1, $data->line1);
//        $this->cart->setCheckoutData(self::LINE2, $data->line2);
//        $this->cart->setCheckoutData(self::LINE3, $data->line3);
//        $this->cart->setCheckoutData(self::LINE4, $data->line4);
//        $this->cart->setCheckoutData(self::LINE5, $data->line5);
//        $this->cart->setCheckoutData(self::LINE6, $data->line6);
//        $this->cart->setCheckoutData(self::LINE7, $data->line7);
//        $this->cart->setCheckoutData(self::LINE8, $data->line8);
        return true;
    }

    /**
     * @return mixed
     */
    public function getData() {
        $data = json_decode($this->cart->getCheckoutData(self::DELIVERY_ADDRESS));
        return $data;

//        $data = new stdClass();
//
//        if($this->cart->getCheckoutData(self::LINE1)) {
//            $data->line1 = $this->cart->getCheckoutData(self::LINE1);
//            $data->isSet = true;
//        }
//        if($this->cart->getCheckoutData(self::LINE2)) {
//            $data->line2 = $this->cart->getCheckoutData(self::LINE2);
//            $data->isSet = true;
//        }
//        if($this->cart->getCheckoutData(self::LINE3)) {
//            $data->line3 = $this->cart->getCheckoutData(self::LINE3);
//            $data->isSet = true;
//        }
//        if($this->cart->getCheckoutData(self::LINE4)) {
//            $data->line4 = $this->cart->getCheckoutData(self::LINE4);
//            $data->isSet = true;
//        }
//        if($this->cart->getCheckoutData(self::LINE5)) {
//            $data->line5 = $this->cart->getCheckoutData(self::LINE5);
//            $data->isSet = true;
//        }
//        if($this->cart->getCheckoutData(self::LINE6)) {
//            $data->line6 = $this->cart->getCheckoutData(self::LINE6);
//            $data->isSet = true;
//        }
//        if($this->cart->getCheckoutData(self::LINE7)) {
//            $data->line7 = $this->cart->getCheckoutData(self::LINE7);
//            $data->isSet = true;
//        }
//        if($this->cart->getCheckoutData(self::LINE8)) {
//            $data->line9 = $this->cart->getCheckoutData(self::LINE8);
//            $data->isSet = true;
//        }
//        if($this->cart->getCheckoutData(self::IS_ALTERNATIVE)) {
//            $data->isAlternative = $this->cart->getCheckoutData(self::IS_ALTERNATIVE);
//            $data->isSet = $data->isAlternative;
//        }
//        return $data;
    }
}
