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
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/piwik")
 */
class PiwikController extends ReportsControllerBase
{
    /**
     * @Route("/reports")
     *
     * @param ReportBroker $reportBroker
     *
     * @return JsonResponse
     */
    public function reportsAction(ReportBroker $reportBroker)
    {
        $reports = $reportBroker->getReports();

        return $this->jsonResponse($reports);
    }

    /**
     * @Route("/reports/{report}")
     *
     * @param ReportBroker $reportBroker
     *
     * @return JsonResponse
     */
    public function reportAction(ReportBroker $reportBroker, $report)
    {
        try {
            $report = $reportBroker->getReport((string)$report);

            return $this->jsonResponse($report);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    /**
     * @Route("/iframe-integration")
     *
     * @param ConfigProvider $configProvider
     *
     * @return JsonResponse
     */
    public function iframeIntegrationAction(ConfigProvider $configProvider)
    {
        $config = $configProvider->getConfig();

        $data = [
            'configured' => false
        ];

        if ($config->isIframeIntegrationConfigured()) {
            $data = [
                'configured' => true,
                'url'        => $config->generateIframeUrl()
            ];
        }

        return $this->jsonResponse($data);
    }

    /**
     * @Route("/config/configured-sites")
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
    )
    {
        $siteConfigs = $siteConfigProvider->getSiteIds();
        $config      = $configProvider->getConfig();

        $sites = [];
        foreach ($siteConfigs as $siteConfig) {
            if (!$config->isSiteConfigured($siteConfig->getConfigKey())) {
                continue;
            }

            $sites[] = [
                'id'    => $siteConfig->getConfigKey(),
                'title' => $siteConfig->getTitle($translator)
            ];
        }

        return $this->jsonResponse(['data' => $sites]);
    }

    /**
     * @Route("/portal-widgets/{configKey}")
     *
     * @param WidgetBroker $widgetBroker
     * @param string $configKey
     *
     * @return JsonResponse
     */
    public function portalWidgetsAction(WidgetBroker $widgetBroker, string $configKey)
    {
        $widgetReferences = $widgetBroker->getWidgetReferences($configKey);

        return $this->jsonResponse(['data' => $widgetReferences]);
    }

    /**
     * @Route("/portal-widgets/{configKey}/{widgetId}")
     *
     * @param WidgetBroker $widgetBroker
     * @param string $configKey
     * @param string $widgetId
     *
     * @return JsonResponse
     */
    public function portalWidgetAction(WidgetBroker $widgetBroker, string $configKey, string $widgetId)
    {
        $widgetConfig = $widgetBroker->getWidgetConfig($widgetId, $configKey);

        return $this->jsonResponse($widgetConfig);
    }

    /**
     * @Route("/api/site/{configKey}")
     * @Method("POST")
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
    )
    {
        try {
            $siteConfig = $siteConfigProvider->getSiteId($configKey);
            $siteId     = $sitesManager->addSite($siteConfig);

            return $this->json([
                'site_id' => $siteId
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/site/{configKey}")
     * @Method("PUT")
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
    )
    {
        try {
            $siteConfig = $siteConfigProvider->getSiteId($configKey);
            $siteId     = $sitesManager->updateSite($siteConfig);

            return $this->json([
                'site_id' => $siteId
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Serializes JSON data through Symfony's serializer, not the Pimcore admin one
     * to make use of all serializer features.
     *
     * @param $data
     * @param int $status
     * @param array $headers
     * @param array $context
     *
     * @return JsonResponse
     */
    private function jsonResponse($data, int $status = JsonResponse::HTTP_OK, array $headers = [], array $context = []): JsonResponse
    {
        return $this->json($data, $status, $headers, $context, false);
    }
}
