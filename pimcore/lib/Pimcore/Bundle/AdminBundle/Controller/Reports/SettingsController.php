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

use Pimcore\Config;
use Pimcore\Config\ReportConfigWriter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/settings")
 */
class SettingsController extends ReportsControllerBase
{
    /**
     * @Route("/get")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $this->checkPermission('system_settings');

        // special piwik handling - as the piwik settings tab is on the same page as the other settings
        // we need to check here if we want to include the piwik config in the response
        $config = $this->getConfig()->toArray();
        if (!$this->getUser()->isAllowed('piwik_settings') && isset($config['piwik'])) {
            unset($config['piwik']);
        }

        $response = [
            'values' => $config,
            'config' => []
        ];

        return $this->json($response);
    }

    /**
     * @Route("/save")
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

        // special piwik handling - if the user is not allowed to save piwik settings
        // force override the settings to write with the current config and ignore the
        // submitted values
        if (!$this->getUser()->isAllowed('piwik_settings')) {
            $currentConfig = Config::getReportConfig()->toArray();
            $piwikConfig   = $currentConfig['piwik'] ?? [];

            // override piwik settings with current config
            $values['piwik'] = $piwikConfig;
        }

        $configWriter->write($values);

        return $this->json(['success' => true]);
    }
}
