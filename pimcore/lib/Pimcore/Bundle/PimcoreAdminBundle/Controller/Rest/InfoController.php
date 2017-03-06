<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Rest;

use Pimcore\ExtensionManager;
use Pimcore\Tool\Console;
use Pimcore\Version;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contains actions to gather information about the API. The /user endpoint
 * is used in tests.
 */
class InfoController extends AbstractRestController
{
    /**
     * @Method("GET")
     * @Route("/system-clock")
     */
    public function systemClockAction()
    {
        return $this->createSuccessResponse(time());
    }

    /**
     * @Method("GET")
     * @Route("/user")
     */
    public function userAction()
    {
        // serialize user to JSON and de-serialize to drop sensitive properties
        // TODO implement JsonSerializable on model when applicable - currently it breaks admin responses
        $userData = $this->decodeJson($this->encodeJson($this->getUser()));
        foreach (['password', 'apiKey'] as $property) {
            unset($userData[$property]);
        }

        return $this->createSuccessResponse($userData);
    }

    /**
     * @Method("GET")
     * @Route("/server-info")
     *
     * Returns a list of all class definitions.
     */
    public function serverInfoAction()
    {
        $this->checkPermission('system_settings');

        $systemSettings = \Pimcore\Config::getSystemConfig()->toArray();
        $system         = [
            'currentTime' => time(),
            'phpCli'      => Console::getPhpCli(),
        ];

        $pimcoreConstants = []; // only Pimcore_ constants
        foreach ((array)get_defined_constants() as $constant => $value) {
            if (strpos($constant, 'PIMCORE_') === 0) {
                $pimcoreConstants[$constant] = $value;
            }
        }

        $pimcore = [
            'version'            => Version::getVersion(),
            'revision'           => Version::getRevision(),
            'instanceIdentifier' => $systemSettings['general']['instanceIdentifier'],
            'constants'          => $pimcoreConstants,
        ];

        // TODO add new bundles here
        $plugins = ExtensionManager::getPluginConfigs();

        return $this->createSuccessResponse([
            'system' => $system,
            'pimcore' => $pimcore,
            'plugins' => $plugins
        ], false);
    }

    /**
     * @Method("GET")
     * @Route("/translations")
     */
    public function translationsAction(Request $request)
    {
        $this->checkPermission('translations');

        try {
            $type   = $request->get('type');
            $params = $request->query->all();
            $result = $this->service->getTranslations($type, $params);

            return $this->createCollectionSuccessResponse($result);
        } catch (\Exception $e) {
            $this->getLogger()->error($e);

            return $this->createErrorResponse($e->getMessage());
        }
    }
}
