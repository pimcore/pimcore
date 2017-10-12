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

            return $this->json($report, JsonResponse::HTTP_OK, [], [], false);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }
}
