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

namespace Pimcore\API\Bundle;

use Pimcore\Bundle\PimcoreBundle\Routing\RouteReferenceInterface;
use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PimcoreBundleManager
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * We need to inject the container as the installer getter has access to the whole container.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return PimcoreBundleInterface[]
     */
    public function getBundles()
    {
        $bundles = [];
        foreach ($this->container->get('kernel')->getBundles() as $bundle) {
            if ($bundle instanceof PimcoreBundleInterface) {
                $bundles[] = $bundle;
            }
        }

        return $bundles;
    }

    /**
     * @return PimcoreBundleInterface[]
     */
    public function getInstalledBundles()
    {
        return array_filter($this->getBundles(), [$this, 'isInstalled']);
    }

    /**
     * @param PimcoreBundleInterface $bundle
     *
     * @return bool
     */
    public function isInstalled(PimcoreBundleInterface $bundle)
    {
        if (null === $installer = $bundle->getInstaller($this->container)) {
            // bundle has no dedicated installed, so we can treat it as installed
            return true;
        }

        return $installer->isInstalled();
    }

    /**
     * Resolves all admin javascripts to load
     *
     * @return array
     */
    public function getJsPaths()
    {
        $paths = $this->resolvePaths('js');

        return $this->resolveEventPaths($paths, BundleManagerEvents::JS_PATHS);
    }

    /**
     * Resolves all admin stylesheets to load
     *
     * @return array
     */
    public function getCssPaths()
    {
        $paths = $this->resolvePaths('css');

        return $this->resolveEventPaths($paths, BundleManagerEvents::CSS_PATHS);
    }

    /**
     * Resolves all editmode javascripts to load
     *
     * @return array
     */
    public function getEditmodeJsPaths()
    {
        $paths = $this->resolvePaths('js', 'editmode');

        return $this->resolveEventPaths($paths, BundleManagerEvents::EDITMODE_JS_PATHS);
    }

    /**
     * Resolves all editmode stylesheets to load
     *
     * @return array
     */
    public function getEditmodeCssPaths()
    {
        $paths = $this->resolvePaths('css', 'editmode');

        return $this->resolveEventPaths($paths, BundleManagerEvents::EDITMODE_CSS_PATHS);
    }

    /**
     * Iterates installed bundles and fetches asset paths
     *
     * @param      $type
     * @param null $mode
     *
     * @return array
     */
    protected function resolvePaths($type, $mode = null)
    {
        $type = ucfirst($type);

        if (null !== $mode) {
            $mode = ucfirst($mode);
        } else {
            $mode = '';
        }

        // getJsPaths, getEditmodeJsPaths
        $getter = sprintf('get%s%sPaths', $mode, $type);

        $router = $this->container->get('router');

        $result = [];
        foreach ($this->getInstalledBundles() as $bundle) {
            $paths = $bundle->$getter();

            foreach ($paths as $path) {
                if ($path instanceof RouteReferenceInterface) {
                    $result[] = $router->generate($path->getRoute(), $path->getParameters(), $path->getType());
                } else {
                    $result[] = $path;
                }
            }
        }

        return $result;
    }

    /**
     * Emits given path event
     *
     * @param array  $paths
     * @param string $eventName
     *
     * @return array
     */
    protected function resolveEventPaths(array $paths, $eventName)
    {
        $event = new PathsEvent($paths);

        $this->container->get('event_dispatcher')->dispatch($eventName, $event);

        return $event->getPaths();
    }
}
