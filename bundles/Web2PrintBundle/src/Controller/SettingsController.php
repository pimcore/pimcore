<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\Web2PrintBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\Web2PrintBundle\Config;
use Pimcore\Bundle\Web2PrintBundle\Processor;
use Pimcore\Bundle\Web2PrintBundle\Processor\HeadlessChrome;
use Pimcore\Bundle\Web2PrintBundle\Processor\PdfReactor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/settings")
 *
 * @internal
 */
class SettingsController extends AdminController
{
    /**
     * @Route("/get-web2print", name="pimcore_web2print_settings_getweb2print", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getWeb2printAction(Request $request): JsonResponse
    {
        $this->checkPermission('web2print_settings');

        $valueArray = Config::getWeb2PrintConfig();

        $response = [
            'values' => $valueArray,
        ];

        return $this->adminJson($response);
    }

    /**
     * @Route("/set-web2print", name="pimcore_web2print_settings_setweb2print", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function setWeb2printAction(Request $request): JsonResponse
    {
        $this->checkPermission('web2print_settings');

        $values = $this->decodeJson($request->get('data'));

        unset($values['documentation']);
        unset($values['additions']);
        unset($values['json_converter']);

        \Pimcore\Bundle\Web2PrintBundle\Config::save($values);

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/test-web2print", name="pimcore_web2print_settings_testweb2print", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function testWeb2printAction(Request $request): Response
    {
        $this->checkPermission('web2print_settings');

        $response = $this->render('@Web2Print/settings/test_web2print.html.twig');
        $html = $response->getContent();

        $adapter = Processor::getInstance();
        $params = [];

        if ($adapter instanceof PdfReactor) {
            $params['adapterConfig'] = [
                'javaScriptMode' => 0,
                'addLinks' => true,
                'appendLog' => true,
                'enableDebugMode' => true,
            ];
        } elseif ($adapter instanceof HeadlessChrome) {
            $params = Config::getWeb2PrintConfig();

            $params = $params['headlessChromeSettings'];
            $params = json_decode($params, true);

            if(!is_array($params)) {
                $params = [];
            }
        }

        $responseOptions = [
            'Content-Type' => 'application/pdf',
        ];

        $pdfData = $adapter->getPdfFromString($html, $params);

        return new Response(
            $pdfData,
            200,
            $responseOptions

        );
    }
}
