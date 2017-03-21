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

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\ExtensionManager;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreAdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Bundle\PimcoreBundle\Controller\EventedControllerInterface;
use Pimcore\Bundle\PimcoreBundle\Routing\RouteReferenceInterface;
use Pimcore\Extension\Bundle\Exception\BundleNotFoundException;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Extension\Document\Areabrick\AreabrickInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ExtensionManagerController extends AdminController implements EventedControllerInterface
{
    /**
     * @var PimcoreBundleManager
     */
    protected $bundleManager;

    /**
     * @inheritDoc
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $this->checkPermission('plugins');

        $this->bundleManager = $this->get('pimcore.extension.bundle_manager');
    }

    /**
     * @inheritDoc
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // noop
    }

    /**
     * @Route("/admin/get-extensions")
     */
    public function getExtensionsAction()
    {
        $extensions = array_merge(
            $this->getBundleList(),
            $this->getBrickList()
        );

        if (\Pimcore::isLegacyModeAvailable()) {
            // TODO
        }

        return $this->json(['extensions' => $extensions]);
    }

    /**
     * @Route("/admin/toggle-extension-state")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function toggleExtensionStateAction(Request $request)
    {
        $type   = $request->get('type');
        $id     = $request->get('id');
        $enable = $request->get('method', 'enable') === 'enable' ? true : false;
        $reload = true;

        if ($type === 'bundle') {
            if ($enable) {
                $this->bundleManager->enable($id);
            } else {
                $this->bundleManager->disable($id);
            }
        } else if ($type === 'areabrick') {

        }

        if ($type === 'areabrick') {
            $reload = false;
        }

        return $this->json([
            'success' => true,
            'reload'  => $reload
        ]);
    }

    /**
     * @Route("/admin/install")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function installAction(Request $request)
    {
        return $this->handleInstallation($request, true);
    }

    /**
     * @Route("/admin/uninstall")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uninstallAction(Request $request)
    {
        return $this->handleInstallation($request, false);
    }

    /**
     * @param Request $request
     * @param bool $install
     *
     * @return JsonResponse
     */
    private function handleInstallation(Request $request, $install = true)
    {
        try {
            $bundle = $this->bundleManager->getActiveBundle($request->get('id'), false);

            if ($install) {
                $this->bundleManager->install($bundle);
            } else {
                $this->bundleManager->uninstall($bundle);
            }

            return $this->json(['success' => true]);
        } catch (BundleNotFoundException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @return array
     */
    private function getBundleList()
    {
        $bm = $this->bundleManager;

        $results = [];
        foreach ($bm->getEnabledBundles() as $className) {
            $bundle = $bm->getActiveBundle($className, false);

            $results[$bm->getBundleIdentifier($bundle)] = $this->buildBundleInfo($bundle, true, $bm->isInstalled($bundle));
        }

        foreach ($bm->getAvailableBundles() as $className) {
            // bundle is enabled
            if (array_key_exists($className, $results)) {
                continue;
            }

            $bundle = $this->buildBundleInstance($className);
            if ($bundle) {
                $results[$bm->getBundleIdentifier($bundle)] = $this->buildBundleInfo($bundle);
            }
        }

        return array_values($results);
    }

    private function buildBundleInstance($bundleName)
    {
        try {
            return new $bundleName();
        } catch (\Exception $e) {
            $this->get('monolog.logger.pimcore')->error('Failed to build instance of bundle {bundle}: {error}', [
                'bundle' => $bundleName,
                'error'  => $e->getMessage()
            ]);
        }
    }

    /**
     * @param PimcoreBundleInterface $bundle
     * @param bool $enabled
     * @param bool $installed
     *
     * @return array
     */
    private function buildBundleInfo(PimcoreBundleInterface $bundle, $enabled = false, $installed = false)
    {
        $iframePath = null;
        if ($iframePath = $bundle->getAdminIframePath()) {
            if ($iframePath instanceof RouteReferenceInterface) {
                $iframePath = $this->get('router')->generate(
                    $iframePath->getRoute(),
                    $iframePath->getParameters(),
                    $iframePath->getType()
                );
            }
        }

        $info = [
            'id'            => $this->bundleManager->getBundleIdentifier($bundle),
            'type'          => 'bundle',
            'name'          => !empty($bundle->getNiceName()) ? $bundle->getNiceName() : $bundle->getName(),
            'description'   => $bundle->getDescription(),
            'active'        => $enabled,
            'installed'     => $installed,
            'configuration' => $iframePath,
            'updateable'    => false,
            'version'       => $bundle->getVersion()
        ];

        return $info;
    }

    /**
     * @return array
     */
    private function getBrickList()
    {
        $am = $this->get('pimcore.area.brick_manager');

        $results = [];
        foreach ($am->getBricks() as $brick) {
            $results[] = $this->buildBrickInfo($brick);
        }

        return $results;
    }

    /**
     * @param AreabrickInterface $brick
     *
     * @return array
     */
    private function buildBrickInfo(AreabrickInterface $brick)
    {
        return [
            'id'          => $brick->getId(),
            'type'        => 'areabrick',
            'name'        => $brick->getName(),
            'description' => $brick->getDescription(),
            'installed'   => true,
            'active'      => true,
            'updateable'  => false,
            'version'     => $brick->getVersion()
        ];
    }
}
