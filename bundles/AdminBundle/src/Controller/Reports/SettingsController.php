<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Reports;

use Exception;
use Pimcore\Config\ReportConfigWriter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/settings")
 *
 * @internal
 */
class SettingsController extends ReportsControllerBase
{
    /**
     * @Route("/get", name="pimcore_admin_reports_settings_get", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $this->checkPermission('system_settings');
        $config = $this->getConfig();

        $response = [
            'values' => $config,
            'config' => [],
        ];

        return $this->adminJson($response);
    }

    /**
     * @Route("/save", name="pimcore_admin_reports_settings_save", methods={"PUT"})
     *
     * @param Request $request
     * @param ReportConfigWriter $configWriter
     *
     * @return JsonResponse
     */
    public function saveAction(Request $request, ReportConfigWriter $configWriter)
    {
        $this->checkPermission('system_settings');

        $values = $this->decodeJson($request->get('data'));
        if (!is_array($values)) {
            $values = [];
        }

        try {
            $configWriter->write($values);
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'errors' => [$e->getMessage()],
            ];

            return $this->adminJson($result);
        }

        return $this->adminJson(['success' => true]);
    }
}
