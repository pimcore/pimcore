<?php

class OnlineShop_IndexController extends Pimcore_Controller_Action_Admin {

    public function getFilterGroupsAction() {
        $indexService = OnlineShop_Framework_Factory::getInstance()->getIndexService();
        $tenants = OnlineShop_Framework_Factory::getInstance()->getAllTenants();

        $filterGroups = $indexService->getAllFilterGroups();
        if($tenants) {
            foreach($tenants as $tenant) {
                $filterGroups = array_merge($filterGroups, $indexService->getAllFilterGroups($tenant));
            }
        }

        $data = array();
        if($filterGroups) {
            sort($filterGroups);
            foreach($filterGroups as $group) {
                $data[$group] = array("data" => $group);
            }
        }

        $this->_helper->json(array("data" => array_values($data)));
    }

    public function getValuesForFilterFieldAction() {


        try {
            $data = array();

            if($this->getParam("field")) {

                if($this->getParam("tenant")) {
                    OnlineShop_Framework_Factory::getInstance()->getEnvironment()->setCurrentAssortmentTenant($this->getParam("tenant"));
                }

                $indexService = OnlineShop_Framework_Factory::getInstance()->getIndexService();
                $filterService = OnlineShop_Framework_Factory::getInstance()->getFilterService($this->view);

                $columnGroup = "";
                $filterGroups = $indexService->getAllFilterGroups();
                foreach($filterGroups as $filterGroup) {
                    $fields = $indexService->getIndexColumnsByFilterGroup($filterGroup);
                    foreach($fields as $field) {
                        if($field == $this->getParam("field")) {
                            $columnGroup = $filterGroup;
                            break 2;
                        }
                    }
                }

                $productList = OnlineShop_Framework_Factory::getInstance()->getIndexService()->getProductListForCurrentTenant();
                $helper = $filterService->getFilterGroupHelper();
                $data = $helper->getGroupByValuesForFilterGroup($columnGroup, $productList, $this->getParam("field"));

            }


            $this->_helper->json(array("data" => array_values($data)));

        } catch(Exception $e) {
            $this->_helper->json(array("message" => $e->getMessage()));
        }
    }

    public function getFieldsAction() {

        $indexService = OnlineShop_Framework_Factory::getInstance()->getIndexService();

        if($this->getParam("filtergroup")) {
            $filtergroups = $this->getParam("filtergroup");
            $filtergroups = explode(",", $filtergroups);

            $indexColumns = array();
            foreach($filtergroups as $filtergroup) {
                $indexColumns = array_merge($indexColumns, $indexService->getIndexColumnsByFilterGroup($filtergroup, $this->getParam("tenant")));
            }

        } else {
            if($this->getParam("show_all_fields") == "true") {
                $indexColumns = $indexService->getIndexColumns(false, $this->getParam("tenant"));
            } else {
                $indexColumns = $indexService->getIndexColumns(true, $this->getParam("tenant"));
            }
        }

        if(!$indexColumns) {
            $indexColumns = array();
        }


        $fields = array();

        if($this->_getParam("add_empty") == "true") {
            $fields[" "] = array("key" => "", "name" => "(" . $this->view->translate("empty") . ")");
        }

        $_REQUEST['systemLocale'] = $this->getUser()->getLanguage();
        $adminTranslator = new Pimcore_View_Helper_TranslateAdmin();

        foreach($indexColumns as $c) {
            $fields[$c] = array("key" => $c, "name" => $adminTranslator->translateAdmin($c));
        }

        if($this->_getParam("specific_price_field") == "true") {
            $fields[OnlineShop_Framework_IProductList::ORDERKEY_PRICE] = array("key" => OnlineShop_Framework_IProductList::ORDERKEY_PRICE, "name" => $adminTranslator->translateAdmin(OnlineShop_Framework_IProductList::ORDERKEY_PRICE));
        }

        ksort($fields);

        $this->_helper->json(array("data" => array_values($fields)));
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
