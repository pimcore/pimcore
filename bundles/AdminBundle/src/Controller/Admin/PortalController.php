<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\Helper\Dashboard;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/portal")
 *
 * @internal
 */
class PortalController extends AdminController implements KernelControllerEventInterface
{
    protected ?Dashboard $dashboardHelper = null;

    protected function getCurrentConfiguration(Request $request): array
    {
        return $this->dashboardHelper->getDashboard($request->get('key'));
    }

    protected function saveConfiguration(Request $request, array $config): void
    {
        $this->dashboardHelper->saveDashboard($request->get('key'), $config);
    }

    /**
     * @Route("/dashboard-list", name="pimcore_admin_portal_dashboardlist", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function dashboardListAction(Request $request): JsonResponse
    {
        $dashboards = $this->dashboardHelper->getAllDashboards();

        $data = [];
        foreach ($dashboards as $key => $config) {
            if ($key != 'welcome') {
                $data[] = $key;
            }
        }

        return $this->adminJson($data);
    }

    /**
     * @Route("/create-dashboard", name="pimcore_admin_portal_createdashboard", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createDashboardAction(Request $request): JsonResponse
    {
        $dashboards = $this->dashboardHelper->getAllDashboards();
        $key = trim($request->request->get('key', ''));

        if (isset($dashboards[$key])) {
            return $this->adminJson(['success' => false, 'message' => 'name_already_in_use']);
        } elseif (!empty($key)) {
            $this->dashboardHelper->saveDashboard($key);

            return $this->adminJson(['success' => true]);
        } else {
            return $this->adminJson(['success' => false, 'message' => 'empty']);
        }
    }

    /**
     * @Route("/delete-dashboard", name="pimcore_admin_portal_deletedashboard", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteDashboardAction(Request $request): JsonResponse
    {
        $key = $request->get('key');
        $this->dashboardHelper->deleteDashboard($key);

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/get-configuration", name="pimcore_admin_portal_getconfiguration", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getConfigurationAction(Request $request): JsonResponse
    {
        return $this->adminJson($this->getCurrentConfiguration($request));
    }

    /**
     * @Route("/remove-widget", name="pimcore_admin_portal_removewidget", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function removeWidgetAction(Request $request): JsonResponse
    {
        $config = $this->getCurrentConfiguration($request);
        $newConfig = [[], []];
        $colCount = 0;

        foreach ($config['positions'] as $col) {
            foreach ($col as $row) {
                if ($row['id'] != $request->get('id')) {
                    $newConfig[$colCount][] = $row;
                }
            }
            $colCount++;
        }

        $config['positions'] = $newConfig;
        $this->saveConfiguration($request, $config);

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/add-widget", name="pimcore_admin_portal_addwidget", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addWidgetAction(Request $request): JsonResponse
    {
        $config = $this->getCurrentConfiguration($request);

        $nextId = 0;
        foreach ($config['positions'] as $col) {
            foreach ($col as $row) {
                $nextId = ($row['id'] > $nextId ? $row['id'] : $nextId);
            }
        }

        $nextId = $nextId + 1;
        $config['positions'][0][] = ['id' => $nextId, 'type' => $request->get('type'), 'config' => null];

        $this->saveConfiguration($request, $config);

        return $this->adminJson(['success' => true, 'id' => $nextId]);
    }

    /**
     * @Route("/reorder-widget", name="pimcore_admin_portal_reorderwidget", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function reorderWidgetAction(Request $request): JsonResponse
    {
        $config = $this->getCurrentConfiguration($request);
        $newConfig = [[], []];
        $colCount = 0;
        $toMove = null;

        foreach ($config['positions'] as $col) {
            foreach ($col as $row) {
                if ($row['id'] != $request->get('id')) {
                    $newConfig[$colCount][] = $row;
                } else {
                    $toMove = $row;
                }
            }
            $colCount++;
        }

        array_splice($newConfig[$request->get('column')], $request->request->getInt('row'), 0, [$toMove]);

        $config['positions'] = $newConfig;
        $this->saveConfiguration($request, $config);

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/update-portlet-config", name="pimcore_admin_portal_updateportletconfig", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updatePortletConfigAction(Request $request): JsonResponse
    {
        $key = $request->get('key');
        $id = $request->get('id');
        $configuration = $request->get('config');

        $dashboard = $this->dashboardHelper->getDashboard($key);
        foreach ($dashboard['positions'] as &$col) {
            foreach ($col as &$portlet) {
                if ($portlet['id'] == $id) {
                    $portlet['config'] = $configuration;

                    break;
                }
            }
        }
        $this->dashboardHelper->saveDashboard($key, $dashboard);

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/portlet-modified-documents", name="pimcore_admin_portal_portletmodifieddocuments", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function portletModifiedDocumentsAction(Request $request): JsonResponse
    {
        $list = Document::getList([
            'limit' => 10,
            'order' => 'DESC',
            'orderKey' => 'modificationDate',
            'condition' => "userModification = '".$this->getAdminUser()->getId()."'",
        ]);

        $response = [];
        $response['documents'] = [];

        foreach ($list as $doc) {
            if ($doc->isAllowed('view')) {
                $response['documents'][] = [
                    'id' => $doc->getId(),
                    'type' => $doc->getType(),
                    'path' => $doc->getRealFullPath(),
                    'date' => $doc->getModificationDate(),
                ];
            }
        }

        return $this->adminJson($response);
    }

    /**
     * @Route("/portlet-modified-assets", name="pimcore_admin_portal_portletmodifiedassets", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function portletModifiedAssetsAction(Request $request): JsonResponse
    {
        $list = Asset::getList([
            'limit' => 10,
            'order' => 'DESC',
            'orderKey' => 'modificationDate',
            'condition' => "userModification = '".$this->getAdminUser()->getId()."'",
        ]);

        $response = [];
        $response['assets'] = [];

        foreach ($list as $doc) {
            /**
             * @var Asset $doc
             */
            if ($doc->isAllowed('view')) {
                $response['assets'][] = [
                    'id' => $doc->getId(),
                    'type' => $doc->getType(),
                    'path' => $doc->getRealFullPath(),
                    'date' => $doc->getModificationDate(),
                ];
            }
        }

        return $this->adminJson($response);
    }

    /**
     * @Route("/portlet-modified-objects", name="pimcore_admin_portal_portletmodifiedobjects", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function portletModifiedObjectsAction(Request $request): JsonResponse
    {
        $list = DataObject::getList([
            'limit' => 10,
            'order' => 'DESC',
            'orderKey' => 'modificationDate',
            'condition' => "userModification = '".$this->getAdminUser()->getId()."'",
        ]);

        $response = [];
        $response['objects'] = [];

        foreach ($list as $object) {
            if ($object->isAllowed('view')) {
                $response['objects'][] = [
                    'id' => $object->getId(),
                    'type' => $object->getType(),
                    'path' => $object->getRealFullPath(),
                    'date' => $object->getModificationDate(),
                ];
            }
        }

        return $this->adminJson($response);
    }

    /**
     * @Route("/portlet-modification-statistics", name="pimcore_admin_portal_portletmodificationstatistics", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function portletModificationStatisticsAction(Request $request): JsonResponse
    {
        $db = \Pimcore\Db::get();

        $days = 31;
        $startDate = mktime(23, 59, 59, (int) date('m'), (int) date('d'), (int) date('Y'));

        $data = [];

        for ($i = 0; $i < $days; $i++) {
            // documents
            $end = $startDate - ($i * 86400);
            $start = $end - 86399;

            $o = $db->fetchOne('SELECT COUNT(*) AS count FROM objects WHERE modificationDate > '.$start . ' AND modificationDate < '.$end);
            $a = $db->fetchOne('SELECT COUNT(*) AS count FROM assets WHERE modificationDate > '.$start . ' AND modificationDate < '.$end);
            $d = $db->fetchOne('SELECT COUNT(*) AS count FROM documents WHERE modificationDate > '.$start . ' AND modificationDate < '.$end);

            $date = new \DateTime();
            $date->setTimestamp($start);

            $data[] = [
                'timestamp' => $start,
                'datetext' => $date->format('Y-m-d'),
                'objects' => (int) $o,
                'documents' => (int) $d,
                'assets' => (int) $a,
            ];
        }

        $data = array_reverse($data);

        return $this->adminJson(['data' => $data]);
    }

    public function onKernelControllerEvent(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->dashboardHelper = new Dashboard($this->getAdminUser());
    }
}
