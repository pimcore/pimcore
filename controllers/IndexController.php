<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


class EcommerceFramework_IndexController extends Pimcore\Controller\Action\Admin {

    public function getFilterGroupsAction() {
        $indexService = \OnlineShop\Framework\Factory::getInstance()->getIndexService();
        $tenants = \OnlineShop\Framework\Factory::getInstance()->getAllTenants();

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
            $factory = \OnlineShop\Framework\Factory::getInstance();

            if($this->getParam("field")) {

                if($this->getParam("tenant")) {
                    \OnlineShop\Framework\Factory::getInstance()->getEnvironment()->setCurrentAssortmentTenant($this->getParam("tenant"));
                }

                $indexService = $factory->getIndexService();
                $filterService = $factory->getFilterService($this->view);

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

                $factory->getEnvironment()->setCurrentAssortmentSubTenant(null);
                $productList = $factory->getIndexService()->getProductListForCurrentTenant();
                $helper = $filterService->getFilterGroupHelper();
                $data = $helper->getGroupByValuesForFilterGroup($columnGroup, $productList, $this->getParam("field"));

            }


            $this->_helper->json(array("data" => array_values($data)));

        } catch(Exception $e) {
            $this->_helper->json(array("message" => $e->getMessage()));
        }
    }

    public function getFieldsAction() {

        $indexService = \OnlineShop\Framework\Factory::getInstance()->getIndexService();

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
            $fields[\OnlineShop\Framework\IndexService\ProductList\IProductList::ORDERKEY_PRICE] = array("key" => \OnlineShop\Framework\IndexService\ProductList\IProductList::ORDERKEY_PRICE, "name" => $adminTranslator->translateAdmin(\OnlineShop\Framework\IndexService\ProductList\IProductList::ORDERKEY_PRICE));
        }

        ksort($fields);

        $this->_helper->json(array("data" => array_values($fields)));
    }


    public function getAllTenantsAction() {
        $adminTranslator = new Pimcore\View\Helper\TranslateAdmin();

        $tenants = \OnlineShop\Framework\Factory::getInstance()->getAllTenants();
        $data = array(array("key" => "", "name" => $adminTranslator->translateAdmin("default")));
        if($tenants) {
            foreach($tenants as $tenant) {
                $data[] = array("key" => $tenant, "name" => $adminTranslator->translateAdmin($tenant));
            }
        }
        $this->_helper->json(array("data" => $data));
    }


}
