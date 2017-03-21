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
use Pimcore\Extension\Document\Areabrick\AreabrickManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ExtensionManagerController extends AdminController implements EventedControllerInterface
{
    /**
     * @var PimcoreBundleManager
     */
    protected $bundleManager;

    /**
     * @var AreabrickManager
     */
    protected $areabrickManager;

    /**
     * @inheritDoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $this->bundleManager    = $this->get('pimcore.extension.bundle_manager');
        $this->areabrickManager = $this->get('pimcore.area.brick_manager');
    }

    /**
     * @inheritDoc
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $this->checkPermission('plugins');
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
            $this->bundleManager->setState($id, $enable);
        } else if ($type === 'areabrick') {
            $reload = false;

            $this->areabrickManager->setState($id, $enable);
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
            /** @var BundleInterface $bundle */
            $bundle = new $bundleName();
            $bundle->setContainer($this->container);

            return $bundle;
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
        $bm = $this->bundleManager;

        $info = [
            'id'            => $bm->getBundleIdentifier($bundle),
            'type'          => 'bundle',
            'name'          => !empty($bundle->getNiceName()) ? $bundle->getNiceName() : $bundle->getName(),
            'description'   => $bundle->getDescription(),
            'active'        => $enabled,
            'installable'   => false,
            'uninstallable' => false,
            'updateable'    => false,
            'installed'     => $installed,
            'configuration' => $this->getIframePath($bundle),
            'version'       => $bundle->getVersion()
        ];

        // only check for installation specifics if the bundle is enabled
        if ($enabled) {
            $info = array_merge($info, [
                'installable'   => $bm->canBeInstalled($bundle),
                'uninstallable' => $bm->canBeUninstalled($bundle),
                'updateable'    => false, // TODO
            ]);
        }

        return $info;
    }

    /**
     * @param PimcoreBundleInterface $bundle
     *
     * @return string|null
     */
    private function getIframePath(PimcoreBundleInterface $bundle)
    {
        if ($iframePath = $bundle->getAdminIframePath()) {
            if ($iframePath instanceof RouteReferenceInterface) {
                return $this->get('router')->generate(
                    $iframePath->getRoute(),
                    $iframePath->getParameters(),
                    $iframePath->getType()
                );
            }

            return $iframePath;
        }
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
            'id'            => $brick->getId(),
            'type'          => 'areabrick',
            'name'          => $brick->getName(),
            'description'   => $brick->getDescription(),
            'installable'   => false,
            'uninstallable' => false,
            'updateable'    => false,
            'installed'     => true,
            'active'        => $this->areabrickManager->isEnabled($brick->getId()),
            'version'       => $brick->getVersion()
        ];
    }
}
