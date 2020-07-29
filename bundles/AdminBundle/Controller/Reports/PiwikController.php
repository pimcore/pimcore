<?php

declare(strict_types=1);

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

use Pimcore\Analytics\Piwik\Api\SitesManager;
use Pimcore\Analytics\Piwik\Config\ConfigProvider;
use Pimcore\Analytics\Piwik\ReportBroker;
use Pimcore\Analytics\Piwik\WidgetBroker;
use Pimcore\Analytics\SiteId\SiteIdProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/piwik")
 */
class PiwikController extends ReportsControllerBase
{
    /**
     * @Route("/reports", name="pimcore_admin_reports_piwik_reports", methods={"GET"})
     *
     * @param ReportBroker $reportBroker
     *
     * @return JsonResponse
     */
    public function reportsAction(ReportBroker $reportBroker)
    {
        $this->checkPermission('piwik_reports');

        $reports = $reportBroker->getReports();

        return $this->json($reports);
    }

    /**
     * @Route("/reports/{report}", name="pimcore_admin_reports_piwik_report", methods={"GET"})
     *
     * @param ReportBroker $reportBroker
     *
     * @return JsonResponse
     */
    public function reportAction(ReportBroker $reportBroker, $report)
    {
        $this->checkPermission('piwik_reports');

        try {
            $report = $reportBroker->getReport((string)$report);

            return $this->json($report);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    /**
     * @Route("/iframe-integration", name="pimcore_admin_reports_piwik_iframeintegration", methods={"GET"})
     *
     * @param ConfigProvider $configProvider
     *
     * @return JsonResponse
     */
    public function iframeIntegrationAction(ConfigProvider $configProvider)
    {
        $this->checkPermission('piwik_reports');

        $config = $configProvider->getConfig();

        $data = [
            'configured' => false,
        ];

        if ($config->isIframeIntegrationConfigured()) {
            $data = [
                'configured' => true,
                'url' => $config->generateIframeUrl(),
            ];
        }

        return $this->json($data);
    }

    /**
     * @Route("/config/configured-sites", name="pimcore_admin_reports_piwik_sites", methods={"GET"})
     *
     * @param SiteIdProvider $siteConfigProvider
     * @param ConfigProvider $configProvider
     * @param TranslatorInterface $translator
     *
     * @return JsonResponse
     */
    public function sitesAction(
        SiteIdProvider $siteConfigProvider,
        ConfigProvider $configProvider,
        TranslatorInterface $translator
    ) {
        $this->checkPermission('piwik_reports');

        $siteConfigs = $siteConfigProvider->getSiteIds();
        $config = $configProvider->getConfig();

        $sites = [];
        foreach ($siteConfigs as $siteConfig) {
            if (!$config->isSiteConfigured($siteConfig->getConfigKey())) {
                continue;
            }

            $sites[] = [
                'id' => $siteConfig->getConfigKey(),
                'title' => $siteConfig->getTitle($translator),
            ];
        }

        return $this->json(['data' => $sites]);
    }

    /**
     * @Route("/portal-widgets/{configKey}", name="pimcore_admin_reports_piwik_portalwidgets", methods={"GET"})
     *
     * @param WidgetBroker $widgetBroker
     * @param string $configKey
     *
     * @return JsonResponse
     */
    public function portalWidgetsAction(WidgetBroker $widgetBroker, string $configKey)
    {
        $this->checkPermission('piwik_reports');

        $widgetReferences = $widgetBroker->getWidgetReferences($configKey);

        return $this->json(['data' => $widgetReferences]);
    }

    /**
     * @Route("/portal-widgets/{configKey}/{widgetId}", name="pimcore_admin_reports_piwik_portalwidget", methods={"GET"})
     *
     * @param Request $request
     * @param WidgetBroker $widgetBroker
     * @param string $configKey
     * @param string $widgetId
     *
     * @return JsonResponse
     */
    public function portalWidgetAction(Request $request, WidgetBroker $widgetBroker, string $configKey, string $widgetId)
    {
        $this->checkPermission('piwik_reports');

        $params = [];
        foreach (['date', 'period'] as $param) {
            if ($request->get($param)) {
                $params[$param] = urlencode($request->get($param));
            }
        }

        try {
            $widgetConfig = $widgetBroker->getWidgetConfig($widgetId, $configKey, null, $params);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($widgetConfig);
    }

    /**
     * @Route("/api/site/{configKey}", name="pimcore_admin_reports_piwik_apisitecreate", methods={"POST"})
     *
     * @param string $configKey
     * @param SiteIdProvider $siteConfigProvider
     * @param SitesManager $sitesManager
     *
     * @return JsonResponse
     */
    public function apiSiteCreateAction(
        string $configKey,
        SiteIdProvider $siteConfigProvider,
        SitesManager $sitesManager
    ) {
        $this->checkPermission('piwik_settings');

        $siteConfig = $siteConfigProvider->getSiteId($configKey);
        $siteId = $sitesManager->addSite($siteConfig);

        return $this->json([
            'site_id' => $siteId,
        ]);
    }

    /**
     * @Route("/api/site/{configKey}", name="pimcore_admin_reports_piwik_apisiteupdate", methods={"PUT"})
     *
     * @param string $configKey
     * @param SiteIdProvider $siteConfigProvider
     * @param SitesManager $sitesManager
     *
     * @return JsonResponse
     */
    public function apiSiteUpdateAction(
        string $configKey,
        SiteIdProvider $siteConfigProvider,
        SitesManager $sitesManager
    ) {
        $this->checkPermission('piwik_settings');

        $siteConfig = $siteConfigProvider->getSiteId($configKey);
        $siteId = $sitesManager->updateSite($siteConfig);

        return $this->json([
            'site_id' => $siteId,
        ]);
    }
}
