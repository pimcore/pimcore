<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 12.01.12
 * Time: 14:19
 * To change this template use File | Settings | File Templates.
 */


class OnlineShop_Framework_Impl_AttributePriceInfo extends OnlineShop_Framework_AbstractPriceInfo implements OnlineShop_Framework_IPriceInfo {


    private $config;

    public function __construct($params) {
        if (is_array($params)) {
            $params = current($params);

            if($params["product"]) {
                $this->product = $params["product"];
                $this->products = $params["products"];
                $this->quantityScale = $params["quantityScale"];
                $this->config = $params["config"];
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

        $getter = "get" . ucfirst($this->config->attributename);
        if(method_exists($this->product, $getter)) {

            if(!empty($this->products)) {
                $sum = 0;
                foreach($this->products as $p) {

                    if($p instanceof OnlineShop_Framework_AbstractSetProductEntry) {
                        $sum += $p->getProduct()->$getter() * $p->getQuantity();
                    } else {
                        $sum += $p->$getter();
                    }
                }
                return new OnlineShop_Framework_Impl_Price($sum, new Zend_Currency(Zend_Registry::get("Zend_Locale")), false);

            } else {
                return new OnlineShop_Framework_Impl_Price($this->product->$getter(), new Zend_Currency(Zend_Registry::get("Zend_Locale")), false);
            }
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
