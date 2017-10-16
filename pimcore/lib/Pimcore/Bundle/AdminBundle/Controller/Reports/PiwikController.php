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

use Pimcore\Analytics\Tracking\Piwik\ReportBroker;
use Pimcore\Analytics\Tracking\Piwik\WidgetBroker;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/piwik")
 */
class PiwikController extends ReportsControllerBase
{
    /**
     * @Route("/report/{report}")
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
     * @Route("/portal-widgets/{siteId}")
     *
     * @return JsonResponse
     */
    public function portalWidgetsAction(WidgetBroker $widgetBroker, $siteId)
    {
        $widgetReferences = $widgetBroker->getWidgetReferences((int)$siteId);

        return $this->jsonResponse($widgetReferences);
    }

    /**
     * @Route("/portal-widgets/{siteId}/{widgetId}")
     *
     * @return JsonResponse
     */
    public function portalWidgetAction(WidgetBroker $widgetBroker, $widgetId, $siteId)
    {
        $widgetConfig = $widgetBroker->getWidgetConfig($widgetId, (int)$siteId);

        return $this->jsonResponse($widgetConfig);
    }

    private function jsonResponse($data): JsonResponse
    {
        return $this->json($data, JsonResponse::HTTP_OK, [], [], false);
    }
}
