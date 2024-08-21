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

namespace Pimcore\Config;

use Exception;
use Pimcore;
use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Pimcore\Helper\StopMessengerWorkersTrait;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class LocationAwareConfigRepository
{
    use StopMessengerWorkersTrait;

    public const LOCATION_SYMFONY_CONFIG = 'symfony-config';

    public const LOCATION_SETTINGS_STORE = 'settings-store';

    public const LOCATION_DISABLED = 'disabled';

    public const READ_TARGET = 'read_target';

    public const WRITE_TARGET = 'write_target';

    public const CONFIG_LOCATION = 'config_location';

    public const TYPE = 'type';

    public const OPTIONS = 'options';

    public const DIRECTORY = 'directory';

    protected array $containerConfig = [];

    protected ?string $settingsStoreScope = null;

    protected ?array $storageConfig = null;

    public function __construct(
        array $containerConfig,
        ?string $settingsStoreScope,
        array $storageConfig,
    ) {
        $this->containerConfig = $containerConfig;
        $this->settingsStoreScope = $settingsStoreScope;
        $this->storageConfig = $storageConfig;
    }

    public function loadConfigByKey(string $key): array
    {
        $data = null;
        $dataSource = null;

        $loadType = $this->getReadTargets()[0] ?? null;
        if ($loadType === null) {
            // try to load from container config
            $data = $this->getDataFromContainerConfig($key, $dataSource);

            // try to load from SettingsStore
            if (!$data) {
                $data = $this->getDataFromSettingsStore($key, $dataSource);
            }
        } else {
            if ($loadType === self::LOCATION_SYMFONY_CONFIG) {
                $data = $this->getDataFromContainerConfig($key, $dataSource);
            } elseif ($loadType === self::LOCATION_SETTINGS_STORE) {
                $data = $this->getDataFromSettingsStore($key, $dataSource);
            }
        }

        return [
            $data,
            $dataSource,
        ];
    }

    private function getDataFromContainerConfig(string $key, ?string &$dataSource): mixed
    {
        if (isset($this->containerConfig[$key])) {
            $dataSource = self::LOCATION_SYMFONY_CONFIG;
        }

        return $this->containerConfig[$key] ?? null;
    }

    private function getDataFromSettingsStore(string $key, ?string &$dataSource): mixed
    {
        $settingsStoreEntryData = null;
        $settingsStoreEntry = SettingsStore::get($key, $this->settingsStoreScope);
        if ($settingsStoreEntry) {
            $settingsStoreEntryData = json_decode($settingsStoreEntry->getData(), true);
            $dataSource = self::LOCATION_SETTINGS_STORE;
        }

        return $settingsStoreEntryData;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function isWriteable(?string $key = null, ?string $dataSource = null): bool
    {
        $key = $key ?: uniqid('pimcore_random_key_', true);
        $writeTarget = $this->getWriteTarget();

        if ($writeTarget === self::LOCATION_SYMFONY_CONFIG && !Pimcore::getKernel()->isDebug()) {
            return false;
        } elseif ($writeTarget === self::LOCATION_DISABLED) {
            return false;
        } elseif ($dataSource === self::LOCATION_SYMFONY_CONFIG && !file_exists($this->getVarConfigFile($key))) {
            return false;
        } elseif ($dataSource && $dataSource !== $writeTarget) {
            return false;
        }

        return true;
    }

    /**
     * @return string Can be either yaml (var/config/...) or "settings-store". defaults to "yaml"
     *
     * @throws Exception
     */
    public function getWriteTarget(): string
    {
        $writeLocation = $this->storageConfig[self::WRITE_TARGET][self::TYPE];

        if (!in_array($writeLocation, [self::LOCATION_SETTINGS_STORE, self::LOCATION_SYMFONY_CONFIG, self::LOCATION_DISABLED])) {
            throw new Exception(sprintf('Invalid write location: %s', $writeLocation));
        }

        return $writeLocation;
    }

    public function getReadTargets(): array
    {
        if (!isset($this->storageConfig[self::READ_TARGET])) {
            return [];
        }

        $readLocation = $this->storageConfig[self::READ_TARGET][self::TYPE];

        if ($readLocation && !in_array($readLocation, [self::LOCATION_SETTINGS_STORE, self::LOCATION_SYMFONY_CONFIG, self::LOCATION_DISABLED])) {
            throw new Exception(sprintf('Invalid read location: %s', $readLocation));
        }

        return $readLocation ? [$readLocation] : [];
    }

    /**
     *
     * @throws Exception
     */
    public function saveConfig(string $key, mixed $data, callable $yamlStructureCallback = null): void
    {
        $writeLocation = $this->getWriteTarget();

        if ($writeLocation === self::LOCATION_SYMFONY_CONFIG) {
            if (is_callable($yamlStructureCallback)) {
                $data = $yamlStructureCallback($key, $data);
            }

            $this->writeYaml($key, $data);
        } elseif ($writeLocation === self::LOCATION_SETTINGS_STORE) {
            $settingsStoreData = json_encode($data);
            SettingsStore::set($key, $settingsStoreData, SettingsStore::TYPE_STRING, $this->settingsStoreScope);
        }

        $this->stopMessengerWorkers();
    }

    private function writeYaml(string $key, array $data): void
    {
        $yamlFilename = $this->getVarConfigFile($key);

        $this->searchAndReplaceMissingParameters($data);

        $filesystem = new Filesystem();
        $filesystem->dumpFile($yamlFilename, Yaml::dump($data, 50));

        $this->invalidateConfigCache();
    }

    private function searchAndReplaceMissingParameters(array &$data): void
    {
        $container = Pimcore::getContainer();

        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $this->searchAndReplaceMissingParameters($value);

                continue;
            }

            if (!is_scalar($value)) {
                continue;
            }

            if (preg_match('/%([^%\s]+)%/', (string) $value, $match)) {
                $key = $match[1];

                if (str_starts_with($key, 'env(') && str_ends_with($key, ')')  && 'env()' !== $key) {
                    continue;
                }

                if (!$container->hasParameter($key)) {
                    $value = preg_replace('/%([^%\s]+)%/', '%%$1%%', $value);
                }
            }
        }
    }

    private function getVarConfigFile(string $key): string
    {
        $directory = rtrim($this->storageConfig[self::WRITE_TARGET][self::OPTIONS][self::DIRECTORY], '/\\');

        return $directory . '/' . $key . '.yaml';
    }

    /**
     *
     * @throws Exception
     */
    public function deleteData(string $key, ?string $dataSource): void
    {
        if (!$this->isWriteable($key)) {
            throw new Exception('You are trying to delete a non-writable configuration.');
        }

        if ($dataSource === self::LOCATION_SYMFONY_CONFIG) {
            unlink($this->getVarConfigFile($key));
            $this->invalidateConfigCache();
        } elseif ($dataSource === self::LOCATION_SETTINGS_STORE) {
            SettingsStore::delete($key, $this->settingsStoreScope);
        }

        $this->stopMessengerWorkers();
    }

    public function fetchAllKeys(): array
    {
        return array_unique(array_merge(
            SettingsStore::getIdsByScope($this->settingsStoreScope),
            array_keys($this->containerConfig)
        ));
    }

    public function fetchAllKeysByReadTargets(): array
    {
        if ($this->storageConfig[self::READ_TARGET][self::TYPE] === self::LOCATION_SYMFONY_CONFIG) {
            return array_keys($this->containerConfig);
        }

        return array_unique(SettingsStore::getIdsByScope($this->settingsStoreScope));
    }

    private function invalidateConfigCache(): void
    {
        // invalidate container config cache if debug flag on kernel is set
        $servicesConfig = PIMCORE_PROJECT_ROOT . '/config/services.yaml';
        if (is_file($servicesConfig)) {
            touch($servicesConfig);
        }
    }

    public static function loadSymfonyConfigFiles(ContainerBuilder $container, string $containerKey, string $configKey): void
    {
        $containerConfig = ConfigurationHelper::getConfigNodeFromSymfonyTree($container, $containerKey);

        $readTargetConf = $containerConfig[self::CONFIG_LOCATION][$configKey][self::READ_TARGET] ?? null;
        $writeTargetConf = $containerConfig[self::CONFIG_LOCATION][$configKey][self::WRITE_TARGET];

        $configDir = null;
        if ($readTargetConf !== null) {
            if ($readTargetConf[self::TYPE] === LocationAwareConfigRepository::LOCATION_SETTINGS_STORE ||
                ($readTargetConf[self::TYPE] !== LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG && $writeTargetConf[self::TYPE] !== LocationAwareConfigRepository::LOCATION_SYMFONY_CONFIG)
            ) {
                return;
            }

            $configDir = $readTargetConf[self::OPTIONS][self::DIRECTORY];
        }

        if ($configDir === null) {
            $configDir = $writeTargetConf[self::OPTIONS][self::DIRECTORY];
        }

        $configLoader = new YamlFileLoader(
            $container,
            new FileLocator($configDir)
        );

        //load configs
        $configs = ConfigurationHelper::getSymfonyConfigFiles($configDir);
        foreach ($configs as $config) {
            $configLoader->load($config);
        }
    }
}
