<?php

class OnlineShop_AdminController extends \Pimcore\Controller\Action\Admin {

    public function settingsAction() {
        if($this->getRequest()->isPost()) {
            OnlineShop_Plugin::setConfig($this->_getParam("onlineshop_config_file"));
            $this->view->onlineshop_config_file = OnlineShop_Plugin::getConfig()->onlineshop_config_file;
        } else {
            $this->view->onlineshop_config_file = OnlineShop_Plugin::getConfig()->onlineshop_config_file;
        }
    }

    public function clearCacheAction() {
        \Pimcore\Model\Cache::clearTag("ecommerceconfig");
        exit;
    }

}
