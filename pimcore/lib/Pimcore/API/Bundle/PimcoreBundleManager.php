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

use Pimcore\API\Bundle\Exception\BundleNotFoundException;
use Pimcore\API\Bundle\Installer\Exception\InstallationException;
use Pimcore\Bundle\PimcoreBundle\Routing\RouteReferenceInterface;
use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Pimcore\ExtensionManager\Config;
use Pimcore\Config as PimcoreConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PimcoreBundleManager
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $availableBundles;

    /**
     * @var array
     */
    protected $enabledBundles;

    /**
     * We need to inject the container as the installer getter has access to the whole container.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config    = $container->get('pimcore.extension_manager.config');
    }

    /**
     * List of currently active bundles from kernel. A bundle can be in this list without being enabled via extensions
     * config file if it is registered manually on the kernel.
     *
     * @param bool $onlyInstalled
     *
     * @return PimcoreBundleInterface[]
     */
    public function getActiveBundles($onlyInstalled = true)
    {
        $bundles = [];
        foreach ($this->container->get('kernel')->getBundles() as $bundle) {
            if ($bundle instanceof PimcoreBundleInterface) {
                if ($onlyInstalled && !$this->isInstalled($bundle)) {
                    continue;
                }

                $bundles[get_class($bundle)] = $bundle;
            }
        }

        return $bundles;
    }

    /**
     * @param string $id
     * @param bool $onlyInstalled
     *
     * @return PimcoreBundleInterface
     */
    public function getActiveBundle($id, $onlyInstalled = true)
    {
        foreach ($this->getActiveBundles($onlyInstalled) as $bundle) {
            if ($this->getBundleIdentifier($bundle) === $id) {
                return $bundle;
            }
        }

        throw new BundleNotFoundException(sprintf('Bundle %s was not found', $id));
    }

    /**
     * List of available bundles from a defined set of paths
     *
     * @return array
     */
    public function getAvailableBundles()
    {
        if (null === $this->availableBundles) {
            // TODO build via DI
            $locator = new PimcoreBundleLocator([
                PIMCORE_PROJECT_ROOT . '/src'
            ]);

            $this->availableBundles = $locator->findBundles();
        }

        return $this->availableBundles;
    }

    /**
     * Loads bundles defined in configuration
     *
     * @return array
     */
    protected function getBundlesFromConfig()
    {
        $config = $this->config->loadConfig();
        if (!isset($config->bundles)) {
            return [];
        }

        return $config->bundles->toArray();
    }

    /**
     * Lists enabled bundles from config
     *
     * @return array
     */
    public function getEnabledBundles()
    {
        $result  = [];
        $bundles = $this->getBundlesFromConfig();

        foreach ($bundles as $bundleName => $state) {
            if ((bool) $state) {
                $result[] = $bundleName;
            }
        }

        return $result;
    }

    /**
     * @param string|PimcoreBundleInterface $bundle
     *
     * @return string
     */
    public function getBundleIdentifier($bundle)
    {
        $identifier = $bundle;
        if ($bundle instanceof PimcoreBundleInterface) {
            $identifier = get_class($bundle);
        }

        return $identifier;
    }

    /**
     * Validates bundle name against list if available and active bundles
     *
     * @param string $bundle
     */
    protected function validateBundleIdentifier($bundle)
    {
        $validNames = array_merge(
            array_keys($this->getActiveBundles(false)),
            $this->getAvailableBundles()
        );

        if (!in_array($bundle, $validNames)) {
            throw new BundleNotFoundException(sprintf('Bundle %s is no valid bundle identifier', $bundle));
        }
    }

    /**
     * Enables/disables a bundle
     *
     * @param string|PimcoreBundleInterface $bundle
     * @param bool $state
     */
    protected function setState($bundle, $state)
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);

        $config = $this->config->loadConfig();
        if (!isset($config->bundles)) {
            $config->bundles = new PimcoreConfig\Config([], true);
        }

        $config->bundles->$identifier = (bool) $state;

        $this->config->saveConfig($config);
    }

    /**
     * Enables a bundle
     *
     * @param string|PimcoreBundleInterface $bundle
     */
    public function enable($bundle)
    {
        $this->setState($bundle, true);
    }

    /**
     * Disables a bundle
     *
     * @param string|PimcoreBundleInterface $bundle
     */
    public function disable($bundle)
    {
        $this->setState($bundle, false);
    }

    /**
     * Determines if a bundle is enabled
     *
     * @param string|PimcoreBundleInterface $bundle
     *
     * @return bool
     */
    public function isEnabled($bundle)
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($bundle);

        return in_array($identifier, $this->getEnabledBundles());
    }

    /**
     * @param PimcoreBundleInterface $bundle
     * @param bool $throwException
     *
     * @return null|Installer\InstallerInterface
     */
    protected function loadBundleInstaller(PimcoreBundleInterface $bundle, $throwException = false)
    {
        if (null === $installer = $bundle->getInstaller($this->container)) {
            if ($throwException) {
                throw new InstallationException(sprintf('Bundle %s does not a define an installer', $bundle->getName()));
            }

            return null;
        }

        return $installer;
    }

    /**
     * Runs install routine for a bundle
     *
     * @param PimcoreBundleInterface $bundle
     *
     * @throws InstallationException If the bundle can not be installed or doesn't define an installer
     */
    public function install(PimcoreBundleInterface $bundle)
    {
        $installer = $this->loadBundleInstaller($bundle, true);

        if (!$installer->canBeInstalled()) {
            throw new InstallationException(sprintf('Bundle %s can not be installed', $bundle->getName()));
        }

        if ($installer->isInstalled()) {
            throw new InstallationException(sprintf('Bundle %s is already installed', $bundle->getName()));
        }

        $installer->install();
    }

    /**
     * Runs uninstall routine for a bundle
     *
     * @param PimcoreBundleInterface $bundle
     *
     * @throws InstallationException If the bundle can not be uninstalled or doesn't define an installer
     */
    public function uninstall(PimcoreBundleInterface $bundle)
    {
        $installer = $this->loadBundleInstaller($bundle, true);

        if (!$installer->canBeUninstalled()) {
            throw new InstallationException(sprintf('Bundle %s can not be uninstalled', $bundle->getName()));
        }

        if (!$installer->isInstalled()) {
            throw new InstallationException(sprintf('Bundle %s is not installed', $bundle->getName()));
        }

        $installer->uninstall();
    }

    /**
     * Determines if a bundle can be installed
     *
     * @param PimcoreBundleInterface $bundle
     *
     * @return bool
     */
    public function canBeInstalled(PimcoreBundleInterface $bundle)
    {
        if (null === $installer = $this->loadBundleInstaller($bundle)) {
            return false;
        }

        return $installer->canBeInstalled();
    }

    /**
     * Determines if a bundle can be uninstalled
     *
     * @param PimcoreBundleInterface $bundle
     *
     * @return bool
     */
    public function canBeUninstalled(PimcoreBundleInterface $bundle)
    {
        if (null === $installer = $this->loadBundleInstaller($bundle)) {
            return false;
        }

        return $installer->canBeUninstalled();
    }

    /**
     * Determines if a bundle is installed
     *
     * @param PimcoreBundleInterface $bundle
     *
     * @return bool
     */
    public function isInstalled(PimcoreBundleInterface $bundle)
    {
        if (null === $installer = $bundle->getInstaller($this->container)) {
            // bundle has no dedicated installer, so we can treat it as installed
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
        foreach ($this->getActiveBundles() as $bundle) {
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
