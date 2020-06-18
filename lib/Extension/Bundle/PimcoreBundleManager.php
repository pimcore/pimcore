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

use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Pimcore\Extension\Bundle\Config\StateConfig;
use Pimcore\Extension\Bundle\Exception\BundleNotFoundException;
use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Pimcore\Extension\Bundle\Installer\Exception\UpdateException;
use Pimcore\HttpKernel\BundleCollection\ItemInterface;
use Pimcore\Kernel;
use Pimcore\Routing\RouteReferenceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;

class PimcoreBundleManager
{
    /**
     * @var StateConfig
     */
    protected $stateConfig;

    /**
     * @var PimcoreBundleLocator
     */
    protected $bundleLocator;

    /**
     * @var Kernel
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
     * @var array
     */
    protected $manuallyRegisteredBundleState;

    /**
     * @param StateConfig $stateConfig
     * @param PimcoreBundleLocator $bundleLocator
     * @param Kernel $kernel
     * @param EventDispatcherInterface $dispatcher
     * @param RouterInterface $router
     */
    public function __construct(
        StateConfig $stateConfig,
        PimcoreBundleLocator $bundleLocator,
        Kernel $kernel,
        EventDispatcherInterface $dispatcher,
        RouterInterface $router
    ) {
        $this->stateConfig = $stateConfig;
        $this->bundleLocator = $bundleLocator;
        $this->kernel = $kernel;
        $this->dispatcher = $dispatcher;
        $this->router = $router;
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
            $bundles = $this->getManuallyRegisteredBundleNames(false);

            foreach ($this->bundleLocator->findBundles() as $locatedBundle) {
                if (!in_array($locatedBundle, $bundles)) {
                    $bundles[] = $locatedBundle;
                }
            }

            sort($bundles);
            $this->availableBundles = $bundles;
        }

        return $this->availableBundles;
    }

    /**
     * Lists enabled bundle names
     *
     * @return array
     */
    public function getEnabledBundleNames(): array
    {
        $bundleNames = array_merge(
            $this->getManuallyRegisteredBundleNames(true),
            $this->stateConfig->getEnabledBundleNames()
        );

        $bundleNames = array_unique($bundleNames);
        sort($bundleNames);

        return $bundleNames;
    }

    /**
     * Returns names of manually registered bundles (not registered via extension manager)
     *
     * @param bool $onlyEnabled
     *
     * @return array
     */
    private function getManuallyRegisteredBundleNames(bool $onlyEnabled = false): array
    {
        $state = $this->getManuallyRegisteredBundleState();

        if (!$onlyEnabled) {
            return array_keys($state);
        }

        $bundleNames = [];
        foreach ($state as $bundleName => $options) {
            if ($options['enabled']) {
                $bundleNames[] = $bundleName;
            }
        }

        return $bundleNames;
    }

    /**
     * Builds state infos about manually configured bundles (not registered via extension manager)
     *
     * @return array
     */
    private function getManuallyRegisteredBundleState()
    {
        if (null === $this->manuallyRegisteredBundleState) {
            $collection = $this->kernel->getBundleCollection();
            $enabledBundles = array_keys($this->getActiveBundles(false));

            $bundles = [];
            foreach ($collection->getItems() as $item) {
                if (!$item->isPimcoreBundle()) {
                    continue;
                }

                if ($item->getSource() === ItemInterface::SOURCE_EXTENSION_MANAGER_CONFIG) {
                    continue;
                }

                $bundles[$item->getBundleIdentifier()] = $this->stateConfig->normalizeOptions([
                    'enabled' => in_array($item->getBundleIdentifier(), $enabledBundles),
                    'priority' => $item->getPriority(),
                    'environments' => $item->getEnvironments(),
                ]);
            }

            $this->manuallyRegisteredBundleState = $bundles;
        }

        return $this->manuallyRegisteredBundleState;
    }

    /**
     * Determines if a bundle exists (is enabled or can be enabled)
     *
     * @param string|PimcoreBundleInterface $bundle
     *
     * @return bool
     */
    public function exists($bundle): bool
    {
        $identifier = $this->getBundleIdentifier($bundle);

        return $this->isValidBundleIdentifier($identifier);
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
     * @param string $identifier
     *
     * @return bool
     */
    protected function isValidBundleIdentifier(string $identifier): bool
    {
        $validNames = array_merge(
            array_keys($this->getActiveBundles(false)),
            $this->getAvailableBundles()
        );

        return in_array($identifier, $validNames);
    }

    /**
     * Validates bundle name against list if available and active bundles
     *
     * @param string $identifier
     */
    protected function validateBundleIdentifier(string $identifier)
    {
        if (!$this->isValidBundleIdentifier($identifier)) {
            throw new BundleNotFoundException(sprintf('Bundle "%s" is no valid bundle identifier', $identifier));
        }
    }

    /**
     * Determines if the bundle was programatically registered (not via extension manager)
     *
     * @param string|PimcoreBundleInterface $bundle
     *
     * @return bool
     */
    public function isManuallyRegistered($bundle): bool
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);

        return in_array($identifier, $this->getManuallyRegisteredBundleNames(false));
    }

    /**
     * Checks if a state change (enable/disable, priority, environments) is possible
     *
     * @param string $identifier
     */
    protected function validateStateChange(string $identifier)
    {
        if ($this->isManuallyRegistered($identifier)) {
            throw new \LogicException(sprintf(
                'Can\'t change state for bundle "%s" as it is programatically registered',
                $identifier
            ));
        }
    }

    /**
     * Determines if bundle is allowed to change state (can be enabled/disabled)
     *
     * @param string|PimcoreBundleInterface $bundle
     *
     * @return bool
     */
    public function canChangeState($bundle): bool
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);

        return !$this->isManuallyRegistered($bundle);
    }

    /**
     * Reads bundle state from config
     *
     * @param string|PimcoreBundleInterface $bundle
     *
     * @return array
     */
    public function getState($bundle): array
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);

        if ($this->isManuallyRegistered($identifier)) {
            return $this->getManuallyRegisteredBundleState()[$identifier];
        }

        return $this->stateConfig->getState($identifier);
    }

    /**
     * Updates state for a bundle and writes it to config
     *
     * @param string|PimcoreBundleInterface $bundle
     * @param array $options
     */
    public function setState($bundle, array $options)
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);
        $this->validateStateChange($identifier);

        $this->stateConfig->setState($identifier, $options);
    }

    /**
     * Batch updates bundle states
     *
     * @param array $states
     */
    public function setStates(array $states)
    {
        $updates = [];

        foreach ($states as $bundle => $options) {
            $identifier = $this->getBundleIdentifier($bundle);

            $this->validateBundleIdentifier($identifier);
            $this->validateStateChange($identifier);

            $updates[$identifier] = $options;
        }

        $this->stateConfig->setStates($updates);
    }

    /**
     * Enables a bundle
     *
     * @param string|PimcoreBundleInterface $bundle
     * @param array $state Optional additional state config (see StateConfig)
     */
    public function enable($bundle, array $state = [])
    {
        $state = array_merge($state, [
            'enabled' => true,
        ]);

        $this->setState($bundle, $state);
    }

    /**
     * Disables a bundle
     *
     * @param string|PimcoreBundleInterface $bundle
     */
    public function disable($bundle)
    {
        $this->setState($bundle, ['enabled' => false]);
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

        return in_array($identifier, $this->getEnabledBundleNames());
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
     * Returns the bundle installer if configured
     *
     * @param PimcoreBundleInterface $bundle
     * @param bool $throwException
     *
     * @return null|Installer\InstallerInterface
     */
    public function getInstaller(PimcoreBundleInterface $bundle, bool $throwException = false)
    {
        return $this->loadBundleInstaller($bundle, $throwException);
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
