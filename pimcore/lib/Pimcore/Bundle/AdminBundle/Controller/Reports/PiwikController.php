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

use Pimcore\Analytics\Tracking\Piwik\Config\ConfigProvider;
use Pimcore\Analytics\Tracking\Piwik\ReportBroker;
use Pimcore\Analytics\Tracking\Piwik\WidgetBroker;
use Pimcore\Analytics\Tracking\SiteConfig\SiteConfigProvider;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
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
     * @Route("/config/configured-sites")
     *
     * @param SiteConfigProvider $siteConfigProvider
     * @param ConfigProvider $configProvider
     * @param TranslatorInterface $translator
     *
     * @return JsonResponse
     */
    public function sitesAction(
        SiteConfigProvider $siteConfigProvider,
        ConfigProvider $configProvider,
        TranslatorInterface $translator
    )
    {
        $siteConfigs = $siteConfigProvider->getSiteConfigs();
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

    private function jsonResponse($data): JsonResponse
    {
        return $this->json($data, JsonResponse::HTTP_OK, [], [], false);
    }
}
