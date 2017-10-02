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

namespace Pimcore\Bundle\AdminBundle\Controller\Reports;

use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Google;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/analytics")
 */
class AnalyticsController extends ReportsControllerBase implements EventedControllerInterface
{
    /**
     * @var \Google_Client
     */
    protected $service;

    /**
     * @Route("/deeplink")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function deeplinkAction(Request $request)
    {
        $config = Google\Analytics::getSiteConfig();

        $url = $request->get('url');
        $url = str_replace(['{accountId}', '{internalWebPropertyId}', '{id}'], [$config->accountid, $config->internalid, $config->profile], $url);
        $url = 'https://www.google.com/analytics/web/' . $url;

        return $this->redirect($url);
    }

    /**
     * @Route("/get-profiles")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getProfilesAction(Request $request)
    {
        try {
            $data = ['data' => []];
            $result = $this->service->management_accounts->listManagementAccounts();

            $accountIds = [];
            if (is_array($result['items'])) {
                foreach ($result['items'] as $account) {
                    $accountIds[] = $account['id'];
                }
            }

            foreach ($accountIds as $accountId) {
                $details = $this->service->management_profiles->listManagementProfiles($accountId, '~all');

                if (is_array($details['items'])) {
                    foreach ($details['items'] as $detail) {
                        $data['data'][] = [
                            'id' => $detail['id'],
                            'name' => $detail['name'],
                            'trackid' => $detail['webPropertyId'],
                            'internalid' => $detail['internalWebPropertyId'],
                            'accountid' => $detail['accountId']
                        ];
                    }
                }
            }

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(false);
        }
    }

    /**
     * @param Request $request
     *
     * @return \Pimcore\Model\Site|void
     */
    private function getSite(Request $request)
    {
        $siteId = $request->get('site');

        try {
            $site = Site::getById($siteId);
        } catch (\Exception $e) {
            return; //TODO: Shouldn't be null returned here?
        }

        return $site;
    }

    /**
     * @param Request $request
     *
     * @return mixed|string
     */
    protected function getFilterPath(Request $request)
    {
        if ($request->get('type') == 'document' && $request->get('id')) {
            $doc = Document::getById($request->get('id'));
            $path = $doc->getFullPath();

            if ($doc instanceof Document\Page && $doc->getPrettyUrl()) {
                $path = $doc->getPrettyUrl();
            }

            if ($request->get('site')) {
                $site = Site::getById($request->get('site'));
                $path = preg_replace('@^' . preg_quote($site->getRootPath(), '@') . '/@', '/', $path);
            }

            return $path;
        }

        return $request->get('path');
    }

    /**
     * @Route("/chartmetricdata")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function chartmetricdataAction(Request $request)
    {
        $config = Google\Analytics::getSiteConfig($this->getSite($request));
        $startDate = date('Y-m-d', (time() - (86400 * 31)));
        $endDate = date('Y-m-d');

        if ($request->get('dateFrom') && $request->get('dateTo')) {
            $startDate = date('Y-m-d', strtotime($request->get('dateFrom')));
            $endDate = date('Y-m-d', strtotime($request->get('dateTo')));
        }

        $metrics = ['ga:pageviews'];
        if ($request->get('metric')) {
            $metrics = [];

            if (is_array($request->get('metric'))) {
                foreach ($request->get('metric') as $m) {
                    $metrics[] = 'ga:' . $m;
                }
            } else {
                $metrics[] = 'ga:' . $request->get('metric');
            }
        }

        $filters = [];

        if ($filterPath = $this->getFilterPath($request)) {
            $filters[] = 'ga:pagePath=='.$filterPath;
        }

        if ($request->get('filters')) {
            $filters[] = $request->get('filters');
        }

        $opts = [
            'dimensions' => 'ga:date'
        ];

        if (!empty($filters)) {
            $opts['filters'] = implode(';', $filters);
        }

        $result = $this->service->data_ga->get(
            'ga:' . $config->profile,
            $startDate,
            $endDate,
            implode(',', $metrics),
            $opts
        );

        $data = [];

        foreach ($result['rows'] as $row) {
            $date = $row[0];

            $tmpData = [
                'timestamp' => strtotime($date),
                'datetext' => $this->formatDimension('date', $date)
            ];

            foreach ($result['columnHeaders'] as $index => $metric) {
                if (!$request->get('dataField')) {
                    $tmpData[str_replace('ga:', '', $metric['name'])] = $row[$index];
                } else {
                    $tmpData[$request->get('dataField')] = $row[$index];
                }
            }

            $data[] = $tmpData;
        }

        return $this->json(['data' => $data]);
    }

    /**
     * @Route("/summary")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function summaryAction(Request $request)
    {
        $config = Google\Analytics::getSiteConfig($this->getSite($request));
        $startDate = date('Y-m-d', (time() - (86400 * 31)));
        $endDate = date('Y-m-d');

        if ($request->get('dateFrom') && $request->get('dateTo')) {
            $startDate = date('Y-m-d', strtotime($request->get('dateFrom')));
            $endDate = date('Y-m-d', strtotime($request->get('dateTo')));
        }

        if ($filterPath = $this->getFilterPath($request)) {
            $filters[] = 'ga:pagePath=='.$filterPath;
        }

        $opts = [
            'dimensions' => 'ga:date'
        ];

        if (!empty($filters)) {
            $opts['filters'] = implode(';', $filters);
        }

        $result = $this->service->data_ga->get(
            'ga:' . $config->profile,
            $startDate,
            $endDate,
            'ga:uniquePageviews,ga:pageviews,ga:exits,ga:bounces,ga:entrances',
            $opts
        );

        $data = [];
        $dailyDataGrouped = [];

        foreach ($result['rows'] as $row) {
            foreach ($result['columnHeaders'] as $index => $metric) {
                if ($index) {
                    $dailyDataGrouped[$metric['name']][] = $row[$index];
                    $data[$metric['name']] += $row[$index];
                }
            }
        }

        $order = [
            'ga:pageviews' => 0,
            'ga:uniquePageviews' => 1,
            'ga:exits' => 2,
            'ga:entrances' => 3,
            'ga:bounces' => 4
        ];

        $outputData = [];
        foreach ($data as $key => $value) {
            $outputData[$order[$key]] = [
                'label' => str_replace('ga:', '', $key),
                'value' => round($value, 2),
                'chart' => \Pimcore\Helper\ImageChart::lineSmall($dailyDataGrouped[$key]),
                'metric' => str_replace('ga:', '', $key)
            ];
        }

        ksort($outputData);

        return $this->json(['data' => $outputData]);
    }

    /**
     * @Route("/source")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sourceAction(Request $request)
    {
        $config = Google\Analytics::getSiteConfig($this->getSite($request));
        $startDate = date('Y-m-d', (time() - (86400 * 31)));
        $endDate = date('Y-m-d');

        if ($request->get('dateFrom') && $request->get('dateTo')) {
            $startDate = date('Y-m-d', strtotime($request->get('dateFrom')));
            $endDate = date('Y-m-d', strtotime($request->get('dateTo')));
        }

        if ($filterPath = $this->getFilterPath($request)) {
            $filters[] = 'ga:pagePath=='.$filterPath;
        }

        $opts = [
            'dimensions' => 'ga:source',
            'max-results' => '10',
            'sort' => '-ga:pageviews'
        ];

        if (!empty($filters)) {
            $opts['filters'] = implode(';', $filters);
        }

        $result = $this->service->data_ga->get(
            'ga:' . $config->profile,
            $startDate,
            $endDate,
            'ga:pageviews',
            $opts
        );

        $data = [];

        foreach ((array) $result['rows'] as $row) {
            $data[] = [
                'pageviews' => $row[1],
                'source' => $row[0]
            ];
        }

        return $this->json(['data' => $data]);
    }

    /**
     * @Route("/data-explorer")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function dataExplorerAction(Request $request)
    {
        $config = Google\Analytics::getSiteConfig($this->getSite($request));
        $startDate = date('Y-m-d', (time() - (86400 * 31)));
        $endDate = date('Y-m-d');
        $metric = 'ga:pageviews';
        $dimension = 'ga:date';
        $descending = true;
        $limit = 10;

        if ($request->get('dateFrom') && $request->get('dateTo')) {
            $startDate = date('Y-m-d', strtotime($request->get('dateFrom')));
            $endDate = date('Y-m-d', strtotime($request->get('dateTo')));
        }
        if ($request->get('dimension')) {
            $dimension = $request->get('dimension');
        }
        if ($request->get('metric')) {
            $metric = $request->get('metric');
        }
        if ($request->get('sort')) {
            if ($request->get('sort') == 'asc') {
                $descending = false;
            }
        }
        if ($request->get('limit')) {
            $limit = $request->get('limit');
        }

        if ($filterPath = $this->getFilterPath($request)) {
            $filters[] = 'ga:pagePath=='.$filterPath;
        }

        $opts = [
            'dimensions' => $dimension,
            'max-results' => $limit,
            'sort' => ($descending ? '-' : '') . $metric
        ];

        if (!empty($filters)) {
            $opts['filters'] = implode(';', $filters);
        }

        $result = $this->service->data_ga->get(
            'ga:' . $config->profile,
            $startDate,
            $endDate,
            $metric,
            $opts
        );

        $data = [];
        foreach ($result['rows'] as $row) {
            $data[] = [
                'dimension' => $this->formatDimension($dimension, $row[0]),
                'metric' => (float) $row[1]
            ];
        }

        return $this->json(['data' => $data]);
    }

    /**
     * @Route("/get-dimensions")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDimensionsAction(Request $request)
    {
        return $this->json(['data' => Google\Api::getAnalyticsDimensions()]);
    }

    /**
     * @Route("/get-metrics")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getMetricsAction(Request $request)
    {
        return $this->json(['data' => Google\Api::getAnalyticsMetrics()]);
    }

    /**
     * @Route("/get-segments")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getSegmentsAction(Request $request)
    {
        $result = $this->service->management_segments->listManagementSegments();

        $data = [];

        foreach ($result['items'] as $row) {
            $data[] = [
                'id' => $row['segmentId'],
                'name' => $row['name']
            ];
        }

        return $this->json(['data' => $data]);
    }

    /**
     * @param $type
     * @param $value
     *
     * @return string
     */
    protected function formatDimension($type, $value)
    {
        if (strpos($type, 'date') !== false) {
            $date = new \DateTime();
            $date->setTimestamp(strtotime($value));

            return $date->format('Y-m-d');
        }

        return $value;
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

        $client = Google\Api::getServiceClient();
        if (!$client) {
            die('Google Analytics is not configured');
        }

        $this->service = new \Google_Service_Analytics($client);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
