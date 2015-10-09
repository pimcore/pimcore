<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


class OnlineShop_IndexController extends Pimcore\Controller\Action\Admin {

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
                    $fields = $indexService->getIndexAttributesByFilterGroup($filterGroup);
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
                $indexColumns = array_merge($indexColumns, $indexService->getIndexAttributesByFilterGroup($filtergroup, $this->getParam("tenant")));
            }

        } else {
            if($this->getParam("show_all_fields") == "true") {
                $indexColumns = $indexService->getIndexAttributes(false, $this->getParam("tenant"));
            } else {
                $indexColumns = $indexService->getIndexAttributes(true, $this->getParam("tenant"));
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
        $adminTranslator = new Pimcore\View\Helper\TranslateAdmin();

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
        $adminTranslator = new Pimcore\View\Helper\TranslateAdmin();

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
