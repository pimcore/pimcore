<?php

class OnlineShop_IndexController extends Pimcore_Controller_Action_Admin {

    public function getFieldsAction() {

        $indexService = OnlineShop_Framework_Factory::getInstance()->getIndexService();
        $indexColumns = $indexService->getIndexColumns();

        $fields = array();

        if($this->_getParam("add_empty") == "true") {
            $fields[] = array("key" => "", "name" => "(" . $this->view->translate("empty") . ")");
        }

//        p_r($this->view); die();

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


}
