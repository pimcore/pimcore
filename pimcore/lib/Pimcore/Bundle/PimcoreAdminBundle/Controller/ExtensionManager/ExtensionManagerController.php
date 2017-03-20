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

use Pimcore\API\Bundle\PimcoreBundleInterface;
use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreBundle\Routing\RouteReferenceInterface;
use Pimcore\Document\Area\AreabrickInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ExtensionManagerController extends AdminController
{
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
     * @return array
     */
    private function getBundleList()
    {
        $bm = $this->get('pimcore.extension.bundle_manager');

        $results = [];
        foreach ($bm->getEnabledBundles() as $bundle) {
            $results[get_class($bundle)] = $this->buildBundleInfo($bundle, true, $bm->isInstalled($bundle));
        }

        foreach ($bm->getAvailableBundles() as $className) {
            // bundle is enabled
            if (array_key_exists($className, $results)) {
                continue;
            }

            try {
                $results[$className] = $this->buildBundleInfo(new $className());
            } catch (\Exception $e) {
                $this->get('monolog.logger.pimcore')->error('Failed to build instance of bundle {bundle}: {error}', [
                    'bundle' => $className,
                    'error'  => $e->getMessage()
                ]);
            }
        }

        return array_values($results);
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
            'id'            => $bundle->getName(),
            'type'          => 'bundle',
            'name'          => $bundle->getName(), // TODO what to do with nice name and description?
            'description'   => '',
            'active'        => $enabled,
            'installed'     => $installed,
            'configuration' => $iframePath,
            'updateable'    => false,
            'version'       => null
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
            'version'     => null
        ];
    }
}
