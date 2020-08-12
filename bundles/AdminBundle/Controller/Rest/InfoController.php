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

namespace Pimcore\Bundle\AdminBundle\Controller\Rest;

use Pimcore\Config;
use Pimcore\Tool\Console;
use Pimcore\Version;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @deprecated
 *
 * Contains actions to gather information about the API. The /user endpoint
 * is used in tests.
 */
class InfoController extends AbstractRestController
{
    /**
     * @Route("/system-clock", name="pimcore_api_rest_info_systemclock", methods={"GET"})
     */
    public function systemClockAction()
    {
        return $this->createSuccessResponse(time());
    }

    /**
     * @Route("/user", name="pimcore_api_rest_info_user", methods={"GET"})
     */
    public function userAction()
    {
        // serialize user to JSON and de-serialize to drop sensitive properties
        // TODO implement JsonSerializable on model when applicable - currently it breaks admin responses
        $userData = $this->decodeJson($this->encodeJson($this->getAdminUser()));
        foreach (['password', 'apiKey'] as $property) {
            unset($userData[$property]);
        }

        return $this->createSuccessResponse($userData);
    }

    /**
     * @Route("/server-info", name="pimcore_api_rest_info_serverinfo", methods={"GET"})
     *
     * Returns a list of all class definitions.
     */
    public function serverInfoAction(Config $config)
    {
        $this->checkPermission('system_settings');

        $system = [
            'currentTime' => time(),
            'phpCli' => Console::getPhpCli(),
        ];

        $pimcoreConstants = []; // only Pimcore_ constants
        foreach ((array)get_defined_constants() as $constant => $value) {
            if (strpos($constant, 'PIMCORE_') === 0) {
                $pimcoreConstants[$constant] = $value;
            }
        }

        $pimcore = [
            'version' => Version::getVersion(),
            'revision' => Version::getRevision(),
            'instanceIdentifier' => $config['general']['instance_identifier'],
            'constants' => $pimcoreConstants,
        ];

        // TODO add new bundles here

        return $this->createSuccessResponse([
            'system' => $system,
            'pimcore' => $pimcore,
        ], false);
    }

    /**
     * @Route("/translations", name="pimcore_api_rest_info_translations", methods={"GET"})
     */
    public function translationsAction(Request $request)
    {
        $this->checkPermission('translations');

        try {
            $type = $request->get('type');
            $params = $request->query->all();
            $result = $this->service->getTranslations($type, $params);

            return $this->createCollectionSuccessResponse($result);
        } catch (\Exception $e) {
            $this->getLogger()->error($e);

            return $this->createErrorResponse($e->getMessage());
        }
    }
}
