<?php

class OnlineShop_AdminController extends Pimcore_Controller_Action_Admin {

    public function settingsAction() {
        if($this->getRequest()->isPost()) {
            OnlineShop_Plugin::setConfig($this->_getParam("onlineshop_config_file"));
            $this->view->onlineshop_config_file = OnlineShop_Plugin::getConfig()->onlineshop_config_file;
        } else {
            $this->view->onlineshop_config_file = OnlineShop_Plugin::getConfig()->onlineshop_config_file;
        }
    }

}
