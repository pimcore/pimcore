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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Controller\Reports;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\EcommerceFrameworkBundle\Reports\Piwik\PiwikReportsProvider;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reports/piwik")
 */
class PiwikController extends AdminController
{
    /**
     * @Route("/reports", name="pimcore_ecommerceframework_reports_piwik_reports", methods={"GET"})
     */
    public function reportsAction(PiwikReportsProvider $reportsProvider)
    {
        $this->checkPermission('piwik_reports');

        $reports = $reportsProvider->getPiwikEcommerceReports();

        return $this->json(['data' => $reports]);
    }
}
