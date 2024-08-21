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

use InvalidArgumentException;
use Pimcore\Event\BundleManager\PathsEvent;
use Pimcore\Event\BundleManagerEvents;
use Pimcore\Extension\Bundle\Exception\BundleNotFoundException;
use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Pimcore\HttpKernel\BundleCollection\ItemInterface;
use Pimcore\Kernel;
use Pimcore\Routing\RouteReferenceInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class PimcoreBundleManager
{
    private static ?OptionsResolver $optionsResolver = null;

    protected PimcoreBundleLocator $bundleLocator;

    protected Kernel $kernel;

    protected EventDispatcherInterface $dispatcher;

    protected RouterInterface $router;

    /**
     * @var string[]|null
     */
    protected ?array $availableBundles = null;

    /**
     * @var array<string, array{enabled: bool, priority: int, environments: string[]}>|null
     */
    protected ?array $manuallyRegisteredBundles = null;

    public function __construct(
        PimcoreBundleLocator $bundleLocator,
        Kernel $kernel,
        EventDispatcherInterface $dispatcher,
        RouterInterface $router
    ) {
        $this->bundleLocator = $bundleLocator;
        $this->kernel = $kernel;
        $this->dispatcher = $dispatcher;
        $this->router = $router;
    }

    /**
     * List of currently active bundles from kernel. A bundle can be in this list, without being enabled via
     * config file, if it is registered manually on the kernel.
     *
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
     * @return string[]
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

    public function getManuallyRegisteredBundleState(string $bundleClass): array
    {
        $manuallyRegisteredBundles = $this->getManuallyRegisteredBundles();

        if (!isset($manuallyRegisteredBundles[$bundleClass])) {
            throw new InvalidArgumentException(sprintf('Bundle "%s" is not registered.
                Maybe you forgot to add it in the "config/bundles.php" or "Kernel::registerBundles()?', $bundleClass));
        }

        return $manuallyRegisteredBundles[$bundleClass];
    }

    /**
     * Returns names of manually registered bundles
     *
     * @return string[]
     */
    private function getManuallyRegisteredBundleNames(bool $onlyEnabled = false): array
    {
        $manuallyRegisteredBundles = $this->getManuallyRegisteredBundles();

        if (!$onlyEnabled) {
            return array_keys($manuallyRegisteredBundles);
        }

        $bundleNames = [];
        foreach ($manuallyRegisteredBundles as $bundleName => $options) {
            if ($options['enabled']) {
                $bundleNames[] = $bundleName;
            }
        }

        return $bundleNames;
    }

    /**
     * Builds state infos & return manually configured bundles
     *
     * @return array<string, array{enabled: bool, priority: int, environments: string[]}>
     */
    private function getManuallyRegisteredBundles(): array
    {
        if (null === $this->manuallyRegisteredBundles) {
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

                $bundles[$item->getBundleIdentifier()] = self::getOptionsResolver()->resolve([
                    'enabled' => in_array($item->getBundleIdentifier(), $enabledBundles),
                    'priority' => $item->getPriority(),
                    'environments' => $item->getEnvironments(),
                ]);
            }

            $this->manuallyRegisteredBundles = $bundles;
        }

        return $this->manuallyRegisteredBundles;
    }

    private static function getOptionsResolver(): OptionsResolver
    {
        if (null !== self::$optionsResolver) {
            return self::$optionsResolver;
        }

        $resolver = new OptionsResolver();

        $defaults = [
            'enabled' => false,
            'priority' => 10,
            'environments' => [],
        ];

        $resolver->setDefaults($defaults);

        $resolver->setRequired(array_keys($defaults));

        $resolver->setAllowedTypes('enabled', 'bool');
        $resolver->setAllowedTypes('priority', 'int');
        $resolver->setAllowedTypes('environments', 'array');

        $resolver->setNormalizer('environments', function (Options $options, $value) {
            // normalize to string and trim
            $value = array_map(fn ($item) => trim((string) $item), $value);

            // remove empty values
            return array_filter($value, fn ($item) => !empty($item));
        });

        self::$optionsResolver = $resolver;

        return self::$optionsResolver;
    }

    /**
     * Determines if a bundle exists
     *
     *
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
     *
     */
    public function isManuallyRegistered(string|PimcoreBundleInterface $bundle): bool
    {
        $identifier = $this->getBundleIdentifier($bundle);

        $this->validateBundleIdentifier($identifier);

        return in_array($identifier, $this->getManuallyRegisteredBundleNames(false));
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
     *
     */
    public function getInstaller(PimcoreBundleInterface $bundle, bool $throwException = false): ?Installer\InstallerInterface
    {
        return $this->loadBundleInstaller($bundle, $throwException);
    }

    /**
     * Runs install routine for a bundle
     *
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
     *
     */
    public function canBeInstalled(PimcoreBundleInterface $bundle): bool
    {
        if (null === $installer = $this->loadBundleInstaller($bundle)) {
            return false;
        }

        return $installer->canBeInstalled();
    }

    /**
     * Determines if a bundle can be uninstalled
     *
     *
     */
    public function canBeUninstalled(PimcoreBundleInterface $bundle): bool
    {
        if (null === $installer = $this->loadBundleInstaller($bundle)) {
            return false;
        }

        return $installer->canBeUninstalled();
    }

    /**
     * Determines if a bundle is installed
     *
     *
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
     *
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
     * @return string[]
     */
    public function getJsPaths(): array
    {
        $paths = $this->resolvePaths('js');

        return $this->resolveEventPaths($paths, BundleManagerEvents::JS_PATHS);
    }

    /**
     * Resolves all admin stylesheets to load
     *
     * @return string[]
     */
    public function getCssPaths(): array
    {
        $paths = $this->resolvePaths('css');

        return $this->resolveEventPaths($paths, BundleManagerEvents::CSS_PATHS);
    }

    /**
     * Resolves all editmode javascripts to load
     *
     * @return string[]
     */
    public function getEditmodeJsPaths(): array
    {
        $paths = $this->resolvePaths('js', 'editmode');

        return $this->resolveEventPaths($paths, BundleManagerEvents::EDITMODE_JS_PATHS);
    }

    /**
     * Resolves all editmode stylesheets to load
     *
     * @return string[]
     */
    public function getEditmodeCssPaths(): array
    {
        $paths = $this->resolvePaths('css', 'editmode');

        return $this->resolveEventPaths($paths, BundleManagerEvents::EDITMODE_CSS_PATHS);
    }

    /**
     * Iterates installed bundles and fetches asset paths
     *
     *
     * @return string[]
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
            if ($bundle instanceof PimcoreBundleAdminClassicInterface) {
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
        }

        return $result;
    }

    /**
     * Emits given path event
     *
     * @param string[] $paths
     *
     * @return string[]
     */
    protected function resolveEventPaths(array $paths, string $eventName): array
    {
        $event = new PathsEvent($paths);
        $this->dispatcher->dispatch($event, $eventName);

        return $event->getPaths();
    }
}
