<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 12.01.12
 * Time: 14:19
 * To change this template use File | Settings | File Templates.
 */


class OnlineShop_Framework_Impl_AttributePriceInfo extends OnlineShop_Framework_AbstractPriceInfo implements OnlineShop_Framework_IPriceInfo {


    private $product;

    function __construct($product) {
        if (is_array($product)) {
            $this->product = current($product);
        } else {
            $this->product = $product;
        }

    }


    public function getPrice() {
        return new OnlineShop_Framework_Impl_Price($this->product->getOfferprice(), new Zend_Currency(new Zend_Locale('de_AT')), false);
    }

    function __call($name, $arguments) {
        return $this->product->$name($arguments);

    }


}