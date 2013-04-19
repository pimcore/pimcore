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
    private $products;

    function __construct($params) {
        if (is_array($params)) {
            $params = current($params);

            if($params["product"]) {
                $this->product = $params["product"];
                $this->products = $params["products"];
                $this->quantityScale = $params["quantityScale"];
            } else {
                $this->product = current($params);
            }

        } else {
            $this->product = $params;
        }

    }


    /**
     * @return OnlineShop_Framework_Impl_Price
     */
    public function getPrice() {
        if(!empty($this->products)) {
            $sum = 0;
            foreach($this->products as $p) {
                if($p instanceof OnlineShop_Framework_AbstractSetProductEntry) {
                    $sum += $p->getProduct()->getOfferprice() * $p->getQuantity();
                } else {
                    $sum += $p->getOfferprice();
                }
            }
            return new OnlineShop_Framework_Impl_Price($sum, new Zend_Currency(new Zend_Locale('de_AT')), false);


        } else {
            return new OnlineShop_Framework_Impl_Price($this->product->getOfferprice(), new Zend_Currency(new Zend_Locale('de_AT')), false);
        }
    }

    public function getTotalPrice() {
        return new OnlineShop_Framework_Impl_Price($this->getPrice()->getAmount() * $this->getQuantity(), ($this->getPrice()->getCurrency()), false);
        //return new OnlineShop_Framework_Impl_Price(999, new Zend_Currency(new Zend_Locale('de_AT')), false);
    }

    public function __call($name, $arguments) {
        return $this->product->$name($arguments);
    }
}
