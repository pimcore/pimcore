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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Controller;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class IndexController
 * @Route("/index")
 */
class IndexController extends AdminController {

    /**
     * @Route("/get-filter-groups")
     * @return \Pimcore\Bundle\PimcoreAdminBundle\HttpFoundation\JsonResponse
     */
    public function getFilterGroupsAction() {
        $indexService = Factory::getInstance()->getIndexService();
        $tenants = Factory::getInstance()->getAllTenants();

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

        return $this->json(["data" => array_values($data)]);
    }

    /**
     * @Route("/get-values-for-filter-field")
     * @return \Pimcore\Bundle\PimcoreAdminBundle\HttpFoundation\JsonResponse
     */
    public function getValuesForFilterFieldAction(Request $request) {


        try {
            $data = array();
            $factory = Factory::getInstance();

            if($request->get("field")) {

                if($request->get("tenant")) {
                    Factory::getInstance()->getEnvironment()->setCurrentAssortmentTenant($request->get("tenant"));
                }

                $indexService = $factory->getIndexService();
                $filterService = $factory->getFilterService();

                $columnGroup = "";
                $filterGroups = $indexService->getAllFilterGroups();
                foreach($filterGroups as $filterGroup) {
                    $fields = $indexService->getIndexAttributesByFilterGroup($filterGroup);
                    foreach($fields as $field) {
                        if($field == $request->get("field")) {
                            $columnGroup = $filterGroup;
                            break 2;
                        }
                    }
                }

                $factory->getEnvironment()->setCurrentAssortmentSubTenant(null);
                $productList = $factory->getIndexService()->getProductListForCurrentTenant();
                $helper = $filterService->getFilterGroupHelper();
                $data = $helper->getGroupByValuesForFilterGroup($columnGroup, $productList, $request->get("field"));

            }


            return $this->json(["data" => array_values($data)]);

        } catch(\Exception $e) {
            return $this->json(["message" => $e->getMessage()]);
        }
    }

    /**
     * @Route("/get-fields")
     * @param Request $request
     * @return \Pimcore\Bundle\PimcoreAdminBundle\HttpFoundation\JsonResponse
     */
    public function getFieldsAction(Request $request) {

        $indexService = Factory::getInstance()->getIndexService();

        if($request->get("filtergroup")) {
            $filtergroups = $request->get("filtergroup");
            $filtergroups = explode(",", $filtergroups);

            $indexColumns = array();
            foreach($filtergroups as $filtergroup) {
                $indexColumns = array_merge($indexColumns, $indexService->getIndexAttributesByFilterGroup($filtergroup, $request->get("tenant")));
            }

        } else {
            if($request->get("show_all_fields") == "true") {
                $indexColumns = $indexService->getIndexAttributes(false, $request->get("tenant"));
            } else {
                $indexColumns = $indexService->getIndexAttributes(true, $request->get("tenant"));
            }
        }

        if(!$indexColumns) {
            $indexColumns = array();
        }


        $fields = array();

        $translator = \Pimcore::getContainer()->get("translator");

        if($request->get("add_empty") == "true") {
            $fields[" "] = array("key" => "", "name" => "(" . $translator->trans("empty", [], "messages") . ")");
        }

        foreach($indexColumns as $c) {
            $fields[$c] = array("key" => $c, "name" => $translator->trans($c, [], "admin"));
        }

        if($request->get("specific_price_field") == "true") {
            $fields[IProductList::ORDERKEY_PRICE] = [
                "key" => IProductList::ORDERKEY_PRICE,
                "name" => $translator->trans(IProductList::ORDERKEY_PRICE, [], "admin")
            ];
        }

        ksort($fields);

        return $this->json(["data" => array_values($fields)]);
    }

    /**
     * @Route("/get-all-tenants")
     * @return \Pimcore\Bundle\PimcoreAdminBundle\HttpFoundation\JsonResponse
     */
    public function getAllTenantsAction() {
        $translator = \Pimcore::getContainer()->get("translator");

        $tenants = Factory::getInstance()->getAllTenants();
        $data = array(array("key" => "", "name" => $translator->trans("default", [], "admin")));
        if($tenants) {
            foreach($tenants as $tenant) {
                $data[] = array("key" => $tenant, "name" => $translator->trans($tenant, [], "admin"));
            }
        }
        return $this->json(array("data" => $data));
    }


}
