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

use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Tracking\Piwik\ReportBroker;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

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
        return $this->json($reportBroker->getReports(), JsonResponse::HTTP_OK, [], [], false);
    }
}
