<?php

declare(strict_types=1);

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

namespace Pimcore\Extension\Bundle;

use Pimcore\Config as PimcoreConfig;
use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Pimcore\Extension\Bundle\Exception\BundleNotFoundException;
use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Pimcore\Extension\Bundle\Installer\Exception\UpdateException;
use Pimcore\Extension\Config;
use Pimcore\Routing\RouteReferenceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

class PimcoreBundleManager
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var PimcoreBundleLocator
     */
    protected $bundleLocator;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var array
     */
    protected $availableBundles;

    /**
     * @var array
     */
    protected $enabledBundles;

    /**
     * @param Config $config
     * @param PimcoreBundleLocator $bundleLocator
     * @param KernelInterface $kernel
     * @param EventDispatcherInterface $dispatcher
     * @param RouterInterface $router
     */
    public function __construct(
        Config $config,
        PimcoreBundleLocator $bundleLocator,
        KernelInterface $kernel,
        EventDispatcherInterface $dispatcher,
        RouterInterface $router
    ) {
        $this->config         = $config;
        $this->bundleLocator  = $bundleLocator;
        $this->kernel         = $kernel;
        $this->dispatcher     = $dispatcher;
        $this->router         = $router;
    }

    /**
     * List of currently active bundles from kernel. A bundle can be in this list without being enabled via extensions
     * config file if it is registered manually on the kernel.
     *
     * @param bool $onlyInstalled
     *
     * @return PimcoreBundleInterface[]
     */
    public function getActiveBundles(bool $onlyInstalled = true): array
    {
        $bundles = [];
        foreach ($this->kernel->getBundles() as $bundle) {
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
    public function getActiveBundle(string $id, bool $onlyInstalled = true): PimcoreBundleInterface
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
    public function getAvailableBundles(): array
    {
        if (null === $this->availableBundles) {
            $this->availableBundles = $this->bundleLocator->findBundles();
        }

        return $this->availableBundles;
    }

    /**
     * Loads bundles defined in configuration
     *
     * @return array
     */
    protected function getBundlesFromConfig(): array
    {
        $config = $this->config->loadConfig();
        if (!isset($config->bundle)) {
            return [];
        }

        return $config->bundle->toArray();
    }

    /**
     * Lists enabled bundles from config
     *
     * @return array
     */
    public function getEnabledBundles(): array
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
    public function getBundleIdentifier($bundle): string
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
    protected function validateBundleIdentifier(string $bundle)
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
    public function setState($bundle, bool $state)
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);

        $config = $this->config->loadConfig();
        if (!isset($config->bundle)) {
            $config->bundle = new PimcoreConfig\Config([], true);
        }

        $config->bundle->$identifier = (bool) $state;

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
    public function isEnabled($bundle): bool
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);

        return in_array($identifier, $this->getEnabledBundles());
    }

    /**
     * @param PimcoreBundleInterface $bundle
     * @param bool $throwException
     *
     * @return null|Installer\InstallerInterface
     */
    protected function loadBundleInstaller(PimcoreBundleInterface $bundle, bool $throwException = false)
    {
        if (null === $installer = $bundle->getInstaller()) {
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

        if (!$this->canBeInstalled($bundle)) {
            throw new InstallationException(sprintf('Bundle %s can not be installed', $bundle->getName()));
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

        if (!$this->canBeUninstalled($bundle)) {
            throw new InstallationException(sprintf('Bundle %s can not be uninstalled', $bundle->getName()));
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
    public function canBeInstalled(PimcoreBundleInterface $bundle): bool
    {
        if (!$this->isEnabled($bundle)) {
            return false;
        }

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
    public function canBeUninstalled(PimcoreBundleInterface $bundle): bool
    {
        if (!$this->isEnabled($bundle)) {
            return false;
        }

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
    public function isInstalled(PimcoreBundleInterface $bundle): bool
    {
        if (null === $installer = $bundle->getInstaller()) {
            // bundle has no dedicated installer, so we can treat it as installed
            return true;
        }

        return $installer->isInstalled();
    }

    /**
     * Determines if a reload is needed after installation
     *
     * @param PimcoreBundleInterface $bundle
     *
     * @return bool
     */
    public function needsReloadAfterInstall(PimcoreBundleInterface $bundle): bool
    {
        if (null === $installer = $bundle->getInstaller()) {
            // bundle has no dedicated installer
            return false;
        }

        return $installer->needsReloadAfterInstall();
    }

    /**
     * Determines if a bundle can be updated
     *
     * @param PimcoreBundleInterface $bundle
     *
     * @return bool
     */
    public function canBeUpdated(PimcoreBundleInterface $bundle): bool
    {
        if (!$this->isEnabled($bundle)) {
            return false;
        }

        if (null === $installer = $this->loadBundleInstaller($bundle)) {
            return false;
        }

        return $installer->canBeUpdated();
    }

    /**
     * Runs update routine for a bundle
     *
     * @param PimcoreBundleInterface $bundle
     *
     * @throws UpdateException If the bundle can not be updated or doesn't define an installer
     */
    public function update(PimcoreBundleInterface $bundle)
    {
        $installer = $this->loadBundleInstaller($bundle, true);

        if (!$installer->canBeUpdated()) {
            throw new UpdateException(sprintf('Bundle %s can not be updated', $bundle->getName()));
        }

        $installer->update();
    }

    /**
     * Resolves all admin javascripts to load
     *
     * @return array
     */
    public function getJsPaths(): array
    {
        $paths = $this->resolvePaths('js');

        return $this->resolveEventPaths($paths, BundleManagerEvents::JS_PATHS);
    }

    /**
     * Resolves all admin stylesheets to load
     *
     * @return array
     */
    public function getCssPaths(): array
    {
        $paths = $this->resolvePaths('css');

        return $this->resolveEventPaths($paths, BundleManagerEvents::CSS_PATHS);
    }

    /**
     * Resolves all editmode javascripts to load
     *
     * @return array
     */
    public function getEditmodeJsPaths(): array
    {
        $paths = $this->resolvePaths('js', 'editmode');

        return $this->resolveEventPaths($paths, BundleManagerEvents::EDITMODE_JS_PATHS);
    }

    /**
     * Resolves all editmode stylesheets to load
     *
     * @return array
     */
    public function getEditmodeCssPaths(): array
    {
        $paths = $this->resolvePaths('css', 'editmode');

        return $this->resolveEventPaths($paths, BundleManagerEvents::EDITMODE_CSS_PATHS);
    }

    /**
     * Iterates installed bundles and fetches asset paths
     *
     * @param string $type
     * @param string|null $mode
     *
     * @return array
     */
    protected function resolvePaths(string $type, string $mode = null): array
    {
        $type = ucfirst($type);

        if (null !== $mode) {
            $mode = ucfirst($mode);
        } else {
            $mode = '';
        }

        // getJsPaths, getEditmodeJsPaths
        $getter = sprintf('get%s%sPaths', $mode, $type);

        $result = [];
        foreach ($this->getActiveBundles() as $bundle) {
            $paths = $bundle->$getter();

            foreach ($paths as $path) {
                if ($path instanceof RouteReferenceInterface) {
                    $result[] = $this->router->generate(
                        $path->getRoute(),
                        $path->getParameters(),
                        $path->getType()
                    );
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
    protected function resolveEventPaths(array $paths, string $eventName): array
    {
        $event = new PathsEvent($paths);

        $this->dispatcher->dispatch($eventName, $event);

        return $event->getPaths();
    }
}
