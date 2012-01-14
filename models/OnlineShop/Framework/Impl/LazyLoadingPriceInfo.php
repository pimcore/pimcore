<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 12.01.12
 * Time: 14:19
 * To change this template use File | Settings | File Templates.
 */


class OnlineShop_Framework_Impl_LazyLoadingPriceInfo extends OnlineShop_Framework_AbstractPriceInfo implements OnlineShop_Framework_IPriceInfo {


     public static function getInstance() {
        return parent::getInstance();
    }

    private $priceRegistry = array();


    public function getPrice() {
        parent::getPrice();
    }

    function __call($name,$bla) {
        if (key_exists($name, $this->priceRegistry)) {
            return $this->priceRegistry[$name];
        } else {
            if (method_exists($this, "_" . $name)) {
                $priceInfo = $this->{
                "_" . $name
                }();

            } else if (method_exists($this->getPriceSystem(), $name)) {
                $method = $name;
                $priceInfo =  $this->getPriceSystem()->$method($this->getProduct(), $this->getQuantity(), $this->getProducts());

            }else {
                throw new OnlineShop_Framework_Exception_UnsupportedException($name  . " is not supported for " . get_class($this));
            }
            if ($priceInfo!=null){
            $priceInfo->setPriceSystem($this->getPriceSystem());
            }
            $this->priceRegistry[$name] = $priceInfo;
        }

        return $this->priceRegistry[$name];


    }


}