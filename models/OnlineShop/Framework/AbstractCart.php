<?php
abstract class OnlineShop_Framework_AbstractCart extends Pimcore_Model_Abstract {

    private $ignoreReadonly = false;

    abstract function getId();

    protected function setIgnoreReadonly() {
        $this->ignoreReadonly = true;
    }

    protected function unsetIgnoreReadonly() {
        $this->ignoreReadonly = false;
    }



    protected function checkCartIsReadOnly() {
        if(!$this->ignoreReadonly) {
            $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
            $item = $env->getCustomItem(OnlineShop_Framework_Impl_CheckoutManager::CART_READONLY_PREFIX . "_" . $this->getId());
            if($item == "READONLY") {
                throw new Exception("Cart " . $this->getId() . " is readonly.");
            }
        }
        return false;
    }


}