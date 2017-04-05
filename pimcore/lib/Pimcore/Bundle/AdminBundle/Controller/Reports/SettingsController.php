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

use Pimcore\File;
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
     * @param Request $request
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $this->checkPermission("system_settings");

        $conf = $this->getConfig();

        $response = [
            "values" => $conf->toArray(),
            "config" => []
        ];

        return $this->json($response);
    }

    /**
     * @Route("/save")
     * @param Request $request
     * @return JsonResponse
     */
    public function saveAction(Request $request)
    {
        $this->checkPermission("system_settings");

        $values = $this->decodeJson($request->get("data"));

        $configFile = \Pimcore\Config::locateConfigFile("reports.php");
        File::putPhpFile($configFile, to_php_data_file_format($values));

        return $this->json(["success" => true]);
    }
}
