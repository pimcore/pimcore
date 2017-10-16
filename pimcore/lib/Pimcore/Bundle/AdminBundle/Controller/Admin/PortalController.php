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

use FeedIo\Adapter\Guzzle\Client;
use FeedIo\FeedIo;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

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
     * @param $config
     */
    protected function saveConfiguration(Request $request, $config)
    {
        $this->dashboardHelper->saveDashboard($request->get('key'), $config);
    }

    /**
     * @Route("/dashboard-list")
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

        return $this->json($data);
    }

    /**
     * @Route("/create-dashboard")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createDashboardAction(Request $request)
    {
        $this->protectCsrf($request);

        $dashboards = $this->dashboardHelper->getAllDashboards();
        $key = trim($request->get('key'));

        if ($dashboards[$key]) {
            return $this->json(['success' => false, 'message' => 'dashboard_already_exists']);
        } elseif (!empty($key)) {
            $this->dashboardHelper->saveDashboard($key);

            return $this->json(['success' => true]);
        } else {
            return $this->json(['success' => false, 'message' => 'empty']);
        }
    }

    /**
     * @Route("/delete-dashboard")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteDashboardAction(Request $request)
    {
        $key = $request->get('key');
        $this->dashboardHelper->deleteDashboard($key);

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/get-configuration")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getConfigurationAction(Request $request)
    {
        return $this->json($this->getCurrentConfiguration($request));
    }

    /**
     * @Route("/remove-widget")
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

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/add-widget")
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

        return $this->json(['success' => true, 'id' => $nextId]);
    }

    /**
     * @Route("/reorder-widget")
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

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/update-portlet-config")
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

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/portlet-feed")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function portletFeedAction(Request $request)
    {
        $dashboard = $this->getCurrentConfiguration($request);
        $id = $request->get('id');

        $portlet = [];
        foreach ($dashboard['positions'] as $col) {
            foreach ($col as $row) {
                if ($row['id'] == $id) {
                    $portlet = $row;
                }
            }
        }

        $feedUrl = $portlet['config'];

        // get feedio
        $feedIoClient = new Client(new \GuzzleHttp\Client());
        $feedIo = new FeedIo($feedIoClient, $this->container->get('logger'));

        $feed = null;
        if (!empty($feedUrl)) {
            try {
                $feed = $feedIo->read($feedUrl)->getFeed();
            } catch (\Exception $e) {
                Logger::error($e);
            }
        }

        $count = 0;
        $entries = [];

        if ($feed) {
            foreach ($feed as $entry) {

                // display only the latest 11 entries
                $count++;
                if ($count > 10) {
                    break;
                }

                $entry = [
                    'title' => $entry->getTitle(),
                    'description' => $entry->getDescription(),
                    'authors' => $entry->getValue('author'),
                    'link' => $entry->getLink(),
                    'content' => $entry->getDescription()
                ];

                foreach ($entry as &$content) {
                    $content = strip_tags($content, '<h1><h2><h3><h4><h5><p><br><a><img><div><b><strong><i>');
                    $content = preg_replace('/on([a-z]+)([ ]+)?=/i', 'data-on$1=', $content);
                }

                $entries[] = $entry;
            }
        }

        return $this->json([
            'entries' => $entries
        ]);
    }

    /**
     * @Route("/portlet-modified-documents")
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
            'orderKey' => 'modificationDate'
        ]);

        $response = [];
        $response['documents'] = [];

        foreach ($list as $doc) {
            $response['documents'][] = [
                'id' => $doc->getId(),
                'type' => $doc->getType(),
                'path' => $doc->getRealFullPath(),
                'date' => $doc->getModificationDate(),
                'condition' => "userModification = '".$this->getUser()->getId()."'"
            ];
        }

        return $this->json($response);
    }

    /**
     * @Route("/portlet-modified-assets")
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
            'orderKey' => 'modificationDate'
        ]);

        $response = [];
        $response['assets'] = [];

        foreach ($list as $doc) {
            $response['assets'][] = [
                'id' => $doc->getId(),
                'type' => $doc->getType(),
                'path' => $doc->getRealFullPath(),
                'date' => $doc->getModificationDate(),
                'condition' => "userModification = '".$this->getUser()->getId()."'"
            ];
        }

        return $this->json($response);
    }

    /**
     * @Route("/portlet-modified-objects")
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
            'condition' => "o_userModification = '".$this->getUser()->getId()."'"
        ]);

        $response = [];
        $response['objects'] = [];

        foreach ($list as $object) {
            $response['objects'][] = [
                'id' => $object->getId(),
                'type' => $object->getType(),
                'path' => $object->getRealFullPath(),
                'date' => $object->getModificationDate()
            ];
        }

        return $this->json($response);
    }

    /**
     * @Route("/portlet-modification-statistics")
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

        for ($i=0; $i < $days; $i++) {
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
                'assets' => (int) $a
            ];
        }

        $data = array_reverse($data);

        return $this->json(['data' => $data]);
    }

    /**
     * @Route("/portlet-analytics-sites")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function portletAnalyticsSitesAction(Request $request)
    {
        $translator = \Pimcore::getContainer()->get('translator');

        $sites = new Site\Listing();
        $data = [
            [
                'id' => 0,
                'site' => $translator->trans('main_site', [], 'admin')
            ]
        ];

        foreach ($sites->load() as $site) {
            if (\Pimcore\Google\Analytics::isConfigured($site)) {
                $data[] = [
                    'id' => $site->getId(),
                    'site' => $site->getMainDomain()
                ];
            }
        }

        return $this->json(['data' => $data]);
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

        $this->dashboardHelper = new \Pimcore\Helper\Dashboard($this->getUser());
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
