<?php

namespace Pimcore\Config;

use Pimcore\Config;
use Pimcore\Db\PhpArrayFileTable;
use Pimcore\File;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Component\Yaml\Yaml;

class LocationAwareConfigRepository
{

    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const DATA_SOURCE_LEGACY = 'legacy';

    private const DATA_SOURCE_CONFIG = 'config';

    private const DATA_SOURCE_SETTINGS_STORE = 'settings-store';

    public const WRITE_TARGET_DISABLED = 'disabled';

    private const WRITE_TARGET_YAML = 'yaml';

    private const WRITE_TARGET_SETTINGS_STORE = 'settings-store';

    protected array $containerConfig = [];

    protected ?string $settingsStoreScope = null;

    protected ?string $storageDirectory = null;

    protected ?string $writeTargetEnvVariableName = null;

    /**
     * @deprecated Will be removed in Pimcore 11
     *
     * @var string|null
     */
    protected ?string $legacyConfigFile = null;

    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private ?PhpArrayFileTable $legacyStore = null;


    /**
     * @param array $containerConfig
     * @param string|null $settingsStoreScope
     * @param string|null $storageDirectory
     * @param string|null $writeTargetEnvVariableName
     * @param string|null $legacyConfigFile
     */
    public function __construct(array $containerConfig, ?string $settingsStoreScope, ?string $storageDirectory, ?string $writeTargetEnvVariableName, ?string $legacyConfigFile = null)
    {
        $this->containerConfig = $containerConfig;
        $this->settingsStoreScope = $settingsStoreScope;
        $this->storageDirectory = $storageDirectory;
        $this->writeTargetEnvVariableName = $writeTargetEnvVariableName;
        $this->legacyConfigFile = $legacyConfigFile;
    }


    public function loadConfigByKey(string $key) {

        $dataSource = null;

        // try to load from container config
        $data = $this->getDataFromContainerConfig($key, $dataSource);

        // try to load from SettingsStore
        if (!$data) {
            $data = $this->getDataFromSettingsStore($key, $dataSource);
        }

        // try to load from legacy config
        if (!$data) {
            $data = $this->getDataFromLegacyConfig($key, $dataSource);
        }

        return [
            $data,
            $dataSource
        ];
    }


    /**
     * @param string $key
     *
     * @return mixed|null
     */
    private function getDataFromContainerConfig(string $key, ?string &$dataSource)
    {
        if (isset($this->containerConfig[$key])) {
            $dataSource = self::DATA_SOURCE_CONFIG;
        }

        return $this->containerConfig[$key] ?? null;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    private function getDataFromSettingsStore(string $key, ?string &$dataSource)
    {
        $settingsStoreEntryData = null;
        $settingsStoreEntry = SettingsStore::get($key, $this->settingsStoreScope);
        if ($settingsStoreEntry) {
            $settingsStoreEntryData = json_decode($settingsStoreEntry->getData(), true);
            $dataSource = self::DATA_SOURCE_SETTINGS_STORE;
        }

        return $settingsStoreEntryData;
    }

    /**
     * @deprecated Will be removed in Pimcore 11
     *
     * @param string $key
     *
     * @return mixed|null
     */
    private function getDataFromLegacyConfig(string $key, ?string &$dataSource)
    {
        if (!$this->legacyConfigFile) {
            return null;
        }

        $data = $this->getLegacyStore()->fetchAll();

        if (isset($data[$key])) {
            $dataSource = self::DATA_SOURCE_LEGACY;
        }

        return $data[$key] ?? null;
    }

    /**
     * @return bool
     */
    public function isWriteable(string $key, ?string $dataSource): bool
    {
        if ($this->getWriteTarget() === self::WRITE_TARGET_DISABLED) {
            return false;
        } elseif ($dataSource === self::DATA_SOURCE_SETTINGS_STORE) {
            return true;
        } elseif ($dataSource === self::DATA_SOURCE_LEGACY) {
            return true;
        } elseif ($dataSource === self::DATA_SOURCE_CONFIG) {
            if (file_exists($this->getVarConfigFile($key))) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return string Can be either yaml (var/config/...) or "settings-store". defaults to "yaml"
     *
     * @throws \Exception
     */
    public function getWriteTarget(): string
    {
        $env = $this->writeTargetEnvVariableName ? $_ENV[$this->writeTargetEnvVariableName] ?? null : null;
        if ($env) {
            $writeLocation = $env;
        } else {
            $writeLocation = self::WRITE_TARGET_YAML;
        }

        if (!in_array($writeLocation, [self::WRITE_TARGET_SETTINGS_STORE, self::WRITE_TARGET_YAML, self::WRITE_TARGET_DISABLED])) {
            throw new \Exception(sprintf('Invalid write location: %s', $writeLocation));
        }

        return $writeLocation;
    }

    /**
     * @return bool
     */
    public function needsContainerRebuildOnWrite(): bool
    {
        return !\Pimcore::getKernel()->isDebug() && ($this->getWriteTarget() === self::WRITE_TARGET_YAML);
    }

    /**
     * @param string $key
     * @param mixed $data
     * @param null|callable $yamlStructureCallback
     * @throws \Exception
     */
    public function saveConfig(string $key, $data, $yamlStructureCallback = null) {

        $writeLocation = $this->getWriteTarget();

        if ($writeLocation === self::WRITE_TARGET_YAML) {

            if(is_callable($yamlStructureCallback)) {
                $data = $yamlStructureCallback($key, $data);
            }

            $this->writeYaml($key, $data);
        } elseif ($writeLocation === self::WRITE_TARGET_SETTINGS_STORE) {
            $settingsStoreData = json_encode($data);
            SettingsStore::set($key, $settingsStoreData, 'string', $this->settingsStoreScope);
        }

    }

    /**
     * @param string $key
     * @param array $data
     *
     * @throws \Exception
     */
    private function writeYaml(string $key, $data): void
    {
        $yamlFilename = $this->getVarConfigFile($key);

        if(!file_exists($yamlFilename)) {

            list($existingData, $dataSource) = $this->loadConfigByKey($key);
            if ($dataSource && $dataSource !== self::DATA_SOURCE_LEGACY) {
                // this configuration already exists so check if it is writeable
                // this is only the case if it comes from var/config or from the legacy file, or the settings-store
                // however, we never want to write it back to the legacy file

                throw new \Exception(sprintf('Configuration can only be written to %s, however the config comes from a different source', $yamlFilename));
            }

        }

        File::put($yamlFilename, Yaml::dump($data, 50));

        // invalidate container config cache
        $systemConfigFile = Config::locateConfigFile('system.yml');
        if ($systemConfigFile) {
            touch($systemConfigFile);
        }
    }


    /**
     * @param string $key
     *
     * @return string
     */
    private function getVarConfigFile(string $key): string
    {
        return $this->storageDirectory . '/' . $key . '.yaml';
    }


    /**
     * @deprecated Will be removed in Pimcore 11
     *
     * @return PhpArrayFileTable
     */
    private function getLegacyStore(): PhpArrayFileTable
    {
        if ($this->legacyStore === null) {
            $file = Config::locateConfigFile($this->legacyConfigFile);
            $this->legacyStore = PhpArrayFileTable::get($file);
        }

        return $this->legacyStore;
    }


    /**
     * @param string $key
     *
     * @throws \Exception
     */
    public function deleteData(string $key, ?string $dataSource): void
    {
        if ($dataSource === self::DATA_SOURCE_CONFIG) {
            $filename = $this->getVarConfigFile($key);
            if (file_exists($filename)) {
                unlink($filename);
            } else {
                throw new \Exception('Only configurations inside the var/config directory can be deleted');
            }
        } elseif ($dataSource === self::DATA_SOURCE_SETTINGS_STORE) {
            SettingsStore::delete($key, $this->settingsStoreScope);
        } elseif ($dataSource === self::DATA_SOURCE_LEGACY) {
            $this->getLegacyStore()->delete($key);
        }
    }

    /**
     * @return array
     */
    public function fetchAllKeys(): array
    {
        return array_merge(
            SettingsStore::getIdsByScope($this->settingsStoreScope),
            array_keys($this->containerConfig),
            $this->legacyConfigFile ? array_keys($this->getLegacyStore()->fetchAll()) : [],
        );
    }
}
