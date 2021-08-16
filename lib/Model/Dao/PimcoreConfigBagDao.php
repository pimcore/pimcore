<?php

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

namespace Pimcore\Model\Dao;

use Pimcore\Config;
use Pimcore\Db\PhpArrayFileTable;
use Pimcore\File;
use Pimcore\Model\Tool\SettingsStore;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 */
abstract class PimcoreConfigBagDao implements DaoInterface
{
    use DaoTrait;

    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const DATA_SOURCE_LEGACY = 'legacy';

    private const DATA_SOURCE_CONFIG = 'config';

    private const DATA_SOURCE_SETTINGS_STORE = 'settings-store';

    public const WRITE_TARGET_DISABLED = 'disabled';

    private const WRITE_TARGET_YAML = 'yaml';

    private const WRITE_TARGET_SETTINGS_STORE = 'settings-store';

    private static array $cache = [];

    protected array $containerConfig = [];

    protected ?string $settingsStoreScope = null;

    protected ?string $dataSource = null;

    protected ?string $storageDirectory = null;

    protected ?string $writeTargetEnvVariableName = null;

    private ?string $id = null;

    /**
     * @deprecated Will be removed in Pimcore 11
     *
     * @var string|null
     */
    protected ?string $legacyConfigFile = null;

    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private static ?PhpArrayFileTable $legacyStore = null;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $params = func_get_arg(0);
        $this->containerConfig = $params['containerConfig'] ?? [];
        $this->settingsStoreScope = $params['settingsStoreScope'] ?? 'pimcore_config';
        $this->storageDirectory = $params['storageDirectory'] ?? null;
        $this->legacyConfigFile = $params['legacyConfigFile'] ?? null;
        $this->writeTargetEnvVariableName = $params['writeTargetEnvVariableName'] ?? null;

        if (!isset(self::$cache[$this->settingsStoreScope])) {
            // initialize runtime cache
            self::$cache[$this->settingsStoreScope] = [];
        }
    }

    /**
     * @param string $id
     *
     * @return mixed|null
     */
    protected function getDataByName(string $id)
    {
        $this->id = $id;

        if (isset(self::$cache[$this->settingsStoreScope][$id])) {
            return self::$cache[$this->settingsStoreScope][$id];
        }

        // try to load from container config
        $data = $this->getDataFromContainerConfig($id);

        // try to load from SettingsStore
        if (!$data) {
            $data = $this->getDataFromSettingsStore($id);
        }

        // try to load from legacy config
        if (!$data) {
            $data = $this->getDataFromLegacyConfig($id);
        }

        self::$cache[$this->settingsStoreScope][$id] = $data;

        return $data;
    }

    /**
     * @return array
     */
    protected function loadIdList(): array
    {
        return array_merge(
            SettingsStore::getIdsByScope($this->settingsStoreScope),
            array_keys($this->containerConfig),
            $this->legacyConfigFile ? array_keys($this->getLegacyStore()->fetchAll()) : [],
        );
    }

    /**
     * @param string $id
     *
     * @return mixed|null
     */
    private function getDataFromContainerConfig(string $id)
    {
        if (isset($this->containerConfig[$id])) {
            $this->dataSource = self::DATA_SOURCE_CONFIG;
        }

        return $this->containerConfig[$id] ?? null;
    }

    /**
     * @param string $id
     *
     * @return mixed|null
     */
    private function getDataFromSettingsStore(string $id)
    {
        $settingsStoreEntryData = null;
        $settingsStoreEntry = SettingsStore::get($id, $this->settingsStoreScope);
        if ($settingsStoreEntry) {
            $settingsStoreEntryData = json_decode($settingsStoreEntry->getData(), true);
            $this->dataSource = self::DATA_SOURCE_SETTINGS_STORE;
        }

        return $settingsStoreEntryData;
    }

    /**
     * @param string $id
     * @param array $data
     *
     * @throws \Exception
     */
    protected function saveData(string $id, $data)
    {
        $writeLocation = $this->getWriteTarget();

        if ($writeLocation === self::WRITE_TARGET_YAML) {
            $this->writeYaml($id, $data);
        } elseif ($writeLocation === self::WRITE_TARGET_SETTINGS_STORE) {
            $settingsStoreData = json_encode($data);
            SettingsStore::set($id, $settingsStoreData, 'string', $this->settingsStoreScope);
        }
    }

    /**
     * @param string $id
     * @param array $data
     *
     * @throws \Exception
     */
    protected function writeYaml(string $id, $data): void
    {
        $yamlFilename = $this->getVarConfigFile($id);

        if ($this->dataSource && !file_exists($yamlFilename) && $this->dataSource !== self::DATA_SOURCE_LEGACY) {
            // this configuration already exists so check if it is writeable
            // this is only the case if it comes from var/config or from the legacy file, or the settings-store
            // however, we never want to write it back to the legacy file

            throw new \Exception(sprintf('Configuration can only be written to %s, however the config comes from a different source', $yamlFilename));
        }

        File::put($yamlFilename, Yaml::dump($data, 50));

        // invalidate container config cache
        $systemConfigFile = Config::locateConfigFile('system.yml');
        if ($systemConfigFile) {
            touch($systemConfigFile);
        }
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
     * @param string $id
     *
     * @return string
     */
    private function getVarConfigFile(string $id): string
    {
        return $this->storageDirectory . '/' . $id . '.yaml';
    }

    /**
     * @return bool
     */
    public function isWriteable(): bool
    {
        if ($this->getWriteTarget() === self::WRITE_TARGET_DISABLED) {
            return false;
        } elseif ($this->dataSource === self::DATA_SOURCE_SETTINGS_STORE) {
            return true;
        } elseif ($this->dataSource === self::DATA_SOURCE_LEGACY) {
            return true;
        } elseif ($this->dataSource === self::DATA_SOURCE_CONFIG) {
            if (file_exists($this->getVarConfigFile($this->id))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $id
     *
     * @throws \Exception
     */
    protected function deleteData(string $id): void
    {
        if ($this->dataSource === self::DATA_SOURCE_CONFIG) {
            $filename = $this->getVarConfigFile($id);
            if (file_exists($filename)) {
                unlink($filename);
            } else {
                throw new \Exception('Only configurations inside the var/config directory can be deleted');
            }
        } elseif ($this->dataSource === self::DATA_SOURCE_SETTINGS_STORE) {
            SettingsStore::delete($id, $this->settingsStoreScope);
        } elseif ($this->dataSource === self::DATA_SOURCE_LEGACY) {
            $this->getLegacyStore()->delete($id);
        }
    }

    /**
     * @deprecated Will be removed in Pimcore 11
     *
     * @return PhpArrayFileTable
     */
    private function getLegacyStore(): PhpArrayFileTable
    {
        if (self::$legacyStore === null) {
            $file = Config::locateConfigFile($this->legacyConfigFile);
            self::$legacyStore = PhpArrayFileTable::get($file);
        }

        return self::$legacyStore;
    }

    /**
     * @deprecated Will be removed in Pimcore 11
     *
     * @param string $id
     *
     * @return mixed|null
     */
    private function getDataFromLegacyConfig(string $id)
    {
        if (!$this->legacyConfigFile) {
            return null;
        }

        $data = $this->getLegacyStore()->fetchAll();

        if (isset($data[$id])) {
            $this->dataSource = self::DATA_SOURCE_LEGACY;
        }

        return $data[$id] ?? null;
    }
}
