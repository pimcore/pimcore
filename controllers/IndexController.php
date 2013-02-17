<?php

class OnlineShop_IndexController extends Pimcore_Controller_Action_Admin {

    public function getFieldsAction() {

        $indexService = OnlineShop_Framework_Factory::getInstance()->getIndexService();

        if($this->_getParam("show_all_fields") == "true") {
            $indexColumns = $indexService->getIndexColumns(false, $this->getParam("tenant"));
        } else {
            $indexColumns = $indexService->getIndexColumns(true, $this->getParam("tenant"));
        }

        $fields = array();

        if($this->_getParam("add_empty") == "true") {
            $fields[] = array("key" => "", "name" => "(" . $this->view->translate("empty") . ")");
        }

        $_REQUEST['systemLocale'] = $this->getUser()->getLanguage();
        $adminTranslator = new Pimcore_View_Helper_TranslateAdmin();

        foreach($indexColumns as $c) {
                $fields[] = array("key" => $c, "name" => $adminTranslator->translateAdmin($c));
        }

        if($this->_getParam("specific_price_field") == "true") {
            $fields[] = array("key" => OnlineShop_Framework_ProductList::ORDERKEY_PRICE, "name" => $adminTranslator->translateAdmin(OnlineShop_Framework_ProductList::ORDERKEY_PRICE));
        }
        $this->_helper->json(array("data" => $fields));
    }

    public function getAllTenantsAction() {
        $adminTranslator = new Pimcore_View_Helper_TranslateAdmin();

        $tenants = OnlineShop_Framework_Factory::getInstance()->getAllTenants();
        $data = array(array("key" => "", "name" => $adminTranslator->translateAdmin("default")));
        if($tenants) {
            foreach($tenants as $tenant) {
                $data[] = array("key" => $tenant, "name" => $adminTranslator->translateAdmin($tenant));
            }
        }
        $this->_helper->json(array("data" => $data));
    }


}
