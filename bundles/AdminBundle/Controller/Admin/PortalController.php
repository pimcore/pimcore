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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Analytics\Google\Config\SiteConfigProvider;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/portal")
 */
class PortalController extends AdminController implements EventedControllerInterface
{
    /**
     * @var \Pimcore\Helper\Dashboard
     */
    protected $dashboardHelper = null;

    /**
     * @param Request $request
     *
     * @return mixed
     */
    protected function getCurrentConfiguration(Request $request)
    {
        return $this->dashboardHelper->getDashboard($request->get('key'));
    }

    /**
     * @param Request $request
     * @param array $config
     */
    protected function saveConfiguration(Request $request, $config)
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
    public function dashboardListAction(Request $request)
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
    public function createDashboardAction(Request $request)
    {
        $dashboards = $this->dashboardHelper->getAllDashboards();
        $key = trim($request->get('key'));

        if ($dashboards[$key]) {
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
    public function deleteDashboardAction(Request $request)
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
    public function getConfigurationAction(Request $request)
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
    public function removeWidgetAction(Request $request)
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
    public function addWidgetAction(Request $request)
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
    public function reorderWidgetAction(Request $request)
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

        array_splice($newConfig[$request->get('column')], $request->get('row'), 0, [$toMove]);

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
    public function updatePortletConfigAction(Request $request)
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
    public function portletModifiedDocumentsAction(Request $request)
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
            /**
             * @var Document $doc
             */
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
    public function portletModifiedAssetsAction(Request $request)
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
    public function portletModifiedObjectsAction(Request $request)
    {
        $list = DataObject::getList([
            'limit' => 10,
            'order' => 'DESC',
            'orderKey' => 'o_modificationDate',
            'condition' => "o_userModification = '".$this->getAdminUser()->getId()."'",
        ]);

        $response = [];
        $response['objects'] = [];

        foreach ($list as $object) {
            /**
             * @var DataObject $object
             */
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
    public function portletModificationStatisticsAction(Request $request)
    {
        $db = \Pimcore\Db::get();

        $days = 31;
        $startDate = mktime(23, 59, 59, date('m'), date('d'), date('Y'));

        $data = [];

        for ($i = 0; $i < $days; $i++) {
            // documents
            $end = $startDate - ($i * 86400);
            $start = $end - 86399;

            $o = $db->fetchOne('SELECT COUNT(*) AS count FROM objects WHERE o_modificationDate > '.$start . ' AND o_modificationDate < '.$end);
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

    /**
     * @Route("/portlet-analytics-sites", name="pimcore_admin_portal_portletanalyticssites", methods={"GET"})
     *
     * @param TranslatorInterface $translator
     * @param SiteConfigProvider $siteConfigProvider
     *
     * @return JsonResponse
     */
    public function portletAnalyticsSitesAction(
        TranslatorInterface $translator,
        SiteConfigProvider $siteConfigProvider
    ) {
        $sites = new Site\Listing();
        $data = [
            [
                'id' => 0,
                'site' => $translator->trans('main_site', [], 'admin'),
            ],
        ];

        /** @var Site $site */
        foreach ($sites->load() as $site) {
            if ($siteConfigProvider->isSiteReportingConfigured($site)) {
                $data[] = [
                    'id' => $site->getId(),
                    'site' => $site->getMainDomain(),
                ];
            }
        }

        return $this->adminJson(['data' => $data]);
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        $this->dashboardHelper = new \Pimcore\Helper\Dashboard($this->getAdminUser());
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
