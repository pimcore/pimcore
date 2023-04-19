<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Controller;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Event\Ecommerce\AdminEvents;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class IndexController
 *
 * @Route("/index")
 *
 * @internal
 */
class IndexController extends UserAwareController
{
    use JsonHelperTrait;

    public function __construct(protected TranslatorInterface $translator)
    {
    }

    /**
     * @Route("/get-filter-groups", name="pimcore_ecommerceframework_index_getfiltergroups", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function getFilterGroupsAction()
    {
        $indexService = Factory::getInstance()->getIndexService();
        $tenants = Factory::getInstance()->getAllTenants();

        $filterGroups = $indexService->getAllFilterGroups();
        if ($tenants) {
            foreach ($tenants as $tenant) {
                $filterGroups = array_merge($filterGroups, $indexService->getAllFilterGroups($tenant));
            }
        }

        $data = [];
        if ($filterGroups) {
            sort($filterGroups);
            foreach ($filterGroups as $group) {
                $data[$group] = ['data' => $group];
            }
        }

        return $this->jsonResponse(['data' => array_values($data)]);
    }

    /**
     * @Route("/get-values-for-filter-field", name="pimcore_ecommerceframework_index_getvaluesforfilterfield", methods={"GET"})
     */
    public function getValuesForFilterFieldAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        try {
            $data = [];
            $factory = Factory::getInstance();

            if ($request->get('field')) {
                if ($request->get('tenant')) {
                    Factory::getInstance()->getEnvironment()->setCurrentAssortmentTenant($request->get('tenant'));
                }

                $indexService = $factory->getIndexService();
                $filterService = $factory->getFilterService();

                $columnGroup = '';
                $filterGroups = $indexService->getAllFilterGroups();
                foreach ($filterGroups as $filterGroup) {
                    $fields = $indexService->getIndexAttributesByFilterGroup($filterGroup);
                    foreach ($fields as $field) {
                        if ($field == $request->get('field')) {
                            $columnGroup = $filterGroup;

                            break 2;
                        }
                    }
                }

                $factory->getEnvironment()->setCurrentAssortmentSubTenant(null);
                $productList = $factory->getIndexService()->getProductListForCurrentTenant();
                $helper = $filterService->getFilterGroupHelper();
                $data = $helper->getGroupByValuesForFilterGroup($columnGroup, $productList, $request->get('field'));
            }

            $event = new GenericEvent(null, ['data' => $data, 'field' => $request->get('field')]);
            $eventDispatcher->dispatch($event, AdminEvents::GET_VALUES_FOR_FILTER_FIELD_PRE_SEND_DATA);
            $data = $event->getArgument('data');

            return $this->jsonResponse(['data' => array_values($data)]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/get-fields", name="pimcore_ecommerceframework_index_getfields", methods={"GET"})
     *
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     */
    public function getFieldsAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        $indexService = Factory::getInstance()->getIndexService();

        if ($request->get('filtergroup')) {
            $filtergroups = $request->get('filtergroup');

            $indexColumns = [];
            foreach ($filtergroups as $filtergroup) {
                $indexColumns = array_merge($indexColumns, $indexService->getIndexAttributesByFilterGroup($filtergroup, $request->get('tenant')));
            }
        } else {
            if ($request->get('show_all_fields') == 'true') {
                $indexColumns = $indexService->getIndexAttributes(false, $request->get('tenant'));
            } else {
                $indexColumns = $indexService->getIndexAttributes(true, $request->get('tenant'));
            }
        }

        if (!$indexColumns) {
            $indexColumns = [];
        }

        $fields = [];

        if ($request->get('add_empty') == 'true') {
            $fields[' '] = ['key' => '', 'name' => '(' . $this->translator->trans('empty', [], 'messages') . ')'];
        }

        foreach ($indexColumns as $c) {
            $fields[$c] = ['key' => $c, 'name' => $this->translator->trans($c, [], 'admin')];
        }

        if ($request->get('specific_price_field') == 'true') {
            $fields[ProductListInterface::ORDERKEY_PRICE] = [
                'key' => ProductListInterface::ORDERKEY_PRICE,
                'name' => $this->translator->trans(ProductListInterface::ORDERKEY_PRICE, [], 'admin'),
            ];
        }

        ksort($fields);

        $event = new GenericEvent(null, ['data' => $fields]);
        $eventDispatcher->dispatch($event, AdminEvents::GET_INDEX_FIELD_NAMES_PRE_SEND_DATA);
        $data = $event->getArgument('data');

        return $this->jsonResponse(['data' => array_values($data)]);
    }

    /**
     * @Route("/get-all-tenants", name="pimcore_ecommerceframework_index_getalltenants", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function getAllTenantsAction()
    {
        $tenants = Factory::getInstance()->getAllTenants();
        $data = [];

        if ($tenants) {
            foreach ($tenants as $tenant) {
                $data[] = ['key' => $tenant, 'name' => $this->translator->trans($tenant, [], 'admin')];
            }
        }

        return $this->jsonResponse(['data' => $data]);
    }
}
