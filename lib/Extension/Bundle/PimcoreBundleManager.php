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

namespace Pimcore\Extension\Bundle;

use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Pimcore\Extension\Bundle\Config\StateConfig;
use Pimcore\Extension\Bundle\Exception\BundleNotFoundException;
use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Pimcore\HttpKernel\BundleCollection\ItemInterface;
use Pimcore\Kernel;
use Pimcore\Routing\RouteReferenceInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class PimcoreBundleManager
{
    /**
     * @deprecated
     *
     * @var StateConfig
     */
    protected StateConfig $stateConfig;

    protected PimcoreBundleLocator $bundleLocator;

    protected Kernel $kernel;

    protected EventDispatcherInterface $dispatcher;

    protected RouterInterface $router;

    protected array $availableBundles;

    /**
     * @deprecated
     *
     * @var array
     */
    protected array $enabledBundles;

    protected array $manuallyRegisteredBundleState;

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
     * @deprecated
     *
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
     */
    private function getManuallyRegisteredBundleState(): array
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
     * Determines if a bundle exists
     *
     * @param string|PimcoreBundleInterface $bundle
     *
     * @return bool
     */
    public function exists(string|PimcoreBundleInterface $bundle): bool
    {
        $identifier = $this->getBundleIdentifier($bundle);

        return $this->isValidBundleIdentifier($identifier);
    }

    public function getBundleIdentifier(string|PimcoreBundleInterface $bundle): string
    {
        $identifier = $bundle;
        if ($bundle instanceof PimcoreBundleInterface) {
            $identifier = get_class($bundle);
        }

        return $identifier;
    }

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
    protected function validateBundleIdentifier(string $identifier): void
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
    public function isManuallyRegistered(string|PimcoreBundleInterface $bundle): bool
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);

        return in_array($identifier, $this->getManuallyRegisteredBundleNames(false));
    }

    /**
     * @deprecated
     *
     * Checks if a state change (enable/disable, priority, environments) is possible
     *
     * @param string $identifier
     */
    protected function validateStateChange(string $identifier): void
    {
        if ($this->isManuallyRegistered($identifier)) {
            throw new \LogicException(sprintf(
                'Can\'t change state for bundle "%s" as it is programatically registered',
                $identifier
            ));
        }
    }

    /**
     * @param string|PimcoreBundleInterface $bundle
     *
     * @return bool
     *@deprecated
     *
     * Determines if bundle is allowed to change state (can be enabled/disabled)
     *
     */
    public function canChangeState(string|PimcoreBundleInterface $bundle): bool
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);

        return !$this->isManuallyRegistered($bundle);
    }

    /**
     * @param string|PimcoreBundleInterface $bundle
     *
     * @return array
     *@deprecated
          *
          * Reads bundle state from config
     *
     */
    public function getState(string|PimcoreBundleInterface $bundle): array
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);

        if ($this->isManuallyRegistered($identifier)) {
            return $this->getManuallyRegisteredBundleState()[$identifier];
        }

        return $this->stateConfig->getState($identifier);
    }

    /**
     * @param string|PimcoreBundleInterface $bundle
     * @param array $options
     *@deprecated
     *
     * Updates state for a bundle and writes it to config
     *
     */
    public function setState(string|PimcoreBundleInterface $bundle, array $options): void
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);
        $this->validateStateChange($identifier);

        $this->stateConfig->setState($identifier, $options);
    }

    /**
     * @deprecated
     *
     * Batch updates bundle states
     *
     * @param array $states
     */
    public function setStates(array $states): void
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
     * @param string|PimcoreBundleInterface $bundle
     * @param array $state Optional additional state config (see StateConfig)
     *@deprecated
     *
     * Enables a bundle
     *
     */
    public function enable(string|PimcoreBundleInterface $bundle, array $state = []): void
    {
        $state = array_merge($state, [
            'enabled' => true,
        ]);

        $this->setState($bundle, $state);
    }

    /**
     * @param string|PimcoreBundleInterface $bundle
     *@deprecated
     *
          * Disables a bundle
     *
     */
    public function disable(string|PimcoreBundleInterface $bundle): void
    {
        $this->setState($bundle, ['enabled' => false]);
    }

    /**
     * @param string|PimcoreBundleInterface $bundle
     *
     * @return bool
     *@deprecated
          *
          * Determines if a bundle is enabled
     *
     */
    public function isEnabled(string|PimcoreBundleInterface $bundle): bool
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);

        return in_array($identifier, $this->getEnabledBundleNames());
    }

    protected function loadBundleInstaller(PimcoreBundleInterface $bundle, bool $throwException = false): ?Installer\InstallerInterface
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
    public function getInstaller(PimcoreBundleInterface $bundle, bool $throwException = false): ?Installer\InstallerInterface
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
    public function install(PimcoreBundleInterface $bundle): void
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
    public function uninstall(PimcoreBundleInterface $bundle): void
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
        $this->dispatcher->dispatch($event, $eventName);

        return $event->getPaths();
    }
}
