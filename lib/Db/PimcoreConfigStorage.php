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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Db;

use Pimcore\Config;
use Pimcore\Model\Tool\SettingsStore;
use Sabre\VObject\Settings;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 */
final class PimcoreConfigStorage
{
    /**
     * @var array
     */
    protected static $tables = [];

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string holds all keys defined in the legacy file
     */
    protected $legacyKey;


    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $legacyKeys = [];

    /**
     * @var int
     */
//    protected $lastInsertId;

    /**
     * PimcoreConfigTable constructor.
     * @param string $key
     * @param string|null $legacyKey
     * @throws \Exception
     */
    public function __construct(string $key, string $legacyKey = null)
    {
        $this->setConfig($key, $legacyKey);
    }

    /**
     * @param string $key e.g. "imagethumbnails"
     * @param string|null $legacyKey "image-thumbnails"
     * @throws \Exception
     */
    public function setConfig(string $key, string $legacyKey = null)
    {
        $this->key = $key;
        $this->legacyKey = $legacyKey;
        $this->load();
    }

    /**
     *
     */
    protected function load()
    {
        // load settingsstore data
        $settingsStoreData = [];
        $scope = $this->buildSettingsStoreScope();
        $settingsStoreDataIds = SettingsStore::getIdsByScope($scope);
        foreach ($settingsStoreDataIds as $settingsStoreDataId) {
            $settingsStoreEntry = SettingsStore::get($settingsStoreDataId, $scope);
            $settingsStoreEntryData =
            $settingsStoreEntryData = json_decode($settingsStoreEntry->getData(), true);

            //TODO name clash possible ?
            $settingsStoreEntryData['writeable'] = true;
            $settingsStoreData[$settingsStoreDataId] = $settingsStoreEntryData;
        }

        // load container data
        $container = \Pimcore::getContainer();
        $config = $container->getParameter("pimcore.config");
        $containerDataItems = $config[$this->key];

        foreach ($containerDataItems as $id => &$containerDataItem) {
            $yamlFilename = $this->getVarConfigFile($id);
            if (file_exists($yamlFilename)) {
                $containerDataItem['writeable'] = true;
            }
        }

        // load legacy data
        $legacyData = [];
        $legacyFile = Config::locateConfigFile($this->legacyKey . '.php');
        if (file_exists($legacyFile)) {
            $legacyData = include($legacyFile);
            foreach ($legacyData as &$legacyDataItem) {
                $legacyDataItem['writeable'] = true;
            }
        }

        $this->legacyKeys = array_keys($legacyData);

        /*
         * Merge it - loading order:
         * - Legacy
         * - yaml Configs
         * - Settings store Configs
         */
        $this->data = array_merge($settingsStoreData, $containerDataItems, $legacyData);
    }

    /**
     * @return string|null
     */
    protected function buildSettingsStoreScope(): ?string
    {
        return 'pimcore_' . $this->key;
    }

    /**
     * @param string $id
     * @return string
     */
    private function getVarConfigFile(string $id): string
    {
        return $this->getVarConfigDir($id) . "/" . $id . ".yaml";
    }

    /**
     * @param string $id
     * @return string
     */
    private function getVarConfigDir(string $id): string
    {
        return PIMCORE_CONFIGURATION_DIRECTORY . "/" . $this->key;
    }

    /**
     * @param string|int $id
     */
    public function delete($id)
    {
        if (isset($this->data[$id])) {
            $filename = $this->getVarConfigFile($id);

            $writeLocation = $this->getWriteLocation();

            if ($writeLocation == "yaml") {
                if (file_exists($filename)) {
                    unlink($filename);
                    unset($this->data[$id]);
                } else {
                    throw new \Exception("Only configurations inside the var/config directory can be deleted");
                }
            } else {
                SettingsStore::delete($this->buildSettingsStoreKey($id), $this->buildSettingsStoreScope());
            }

        } else {
            throw new \Exception("Configuration does not exist");
        }
    }

    /**
     * @return string Can be either yaml (var/config/...) or "settingsstore". defaults to "yaml"
     */
    protected function getWriteLocation(): string
    {
        $writeLocation = null;

        $env = getenv('PIMCORE_CONFIG_WRITE_LOCATION');
        if ($env) {
            $writeLocation = $env;
        } else {
            $writeLocation = "settingsstore";
        }
        if (!in_array($writeLocation, ["settingsstore", "yaml"])) {
            throw new \Exception("invalid write location");
        }

        return $writeLocation;
    }

    /**
     * @param string $id
     * @return string
     */
    protected function buildSettingsStoreKey(string $id): string
    {
        return $this->key . "-" . $id;
    }

    /**
     * @param callable|null $filter
     * @param callable|null $order
     *
     * @return array
     */
    public function fetchAll($filter = null, $order = null)
    {
        $data = $this->data;

        if (is_callable($filter)) {
            $filteredData = [];
            foreach ($data as $row) {
                if ($filter($row)) {
                    $filteredData[] = $row;
                }
            }

            $data = $filteredData;
        }

        if (is_callable($order)) {
            usort($data, $order);
        }

        return $data;
    }

    /**
     * @param string $key
     * @param string|null $legacyKey
     * @return mixed|PimcoreConfigStorage
     * @throws \Exception
     */
    public static function get(string $key, ?string $legacyKey = null)
    {
        if (!isset(self::$tables[$key])) {
            self::$tables[$key] = new self($key, $legacyKey);
        }

        return self::$tables[$key];
    }

    /**
     * @param string|int $id
     *
     * @return array|null
     */
    public function getById($id)
    {
        if (isset($this->data[$id])) {
            return $this->data[$id];
        }

        return null;
    }

    /**
     * @param array $data
     * @param string|int $id
     *
     * @throws \Exception
     */
    public function insertOrUpdate($data, $id)
    {
        $data['id'] = $id;

        $this->save($id, $data);
        $this->data[$id] = $data;
    }

    protected function save(string $id, $data = [])
    {
        $yamlFilename = $this->getVarConfigFile($id);

        if (isset($this->data[$id])) {
            // this configuration already exists so check if it is writeable
            // this is only the case if it comes from var/config or from the legacy file, or the settingsstore
            // however, we never want to write it back to the legacy file

            $writeLocation = $this->getWriteLocation();
            if ($writeLocation == "yaml") {
                if (!file_exists($yamlFilename) && (!in_array($id, $this->legacyKeys))) {
                    throw new \Exception("Configuration can only be written to " . $yamlFilename);
                }
            } else {
                $settingsStoreKey = $this->buildSettingsStoreKey($id);
                $settingsStoreData = SettingsStore::get($settingsStoreKey);
                //TODO discussion point. what if you switch the target from yaml to settingsstore. it would be never be writable in
                // case the source is yaml
//                if ($settingsStoreData == null && (!in_array($id, $this->legacyKeys))) {
//                    throw new \Exception("...");
//                }
            }
        } else {
            // new configs are always writeable
        }

        $writeLocation = $this->getWriteLocation();

        if ($writeLocation === "yaml") {

            $newYamlData = ["pimcore" =>
                [$this->key => [
                    $id => $data]
                ]
            ];

            // question: do we really need a dirty detection. because of hardcoded modificationDate?
            if (file_exists($yamlFilename)) {
                $currentYamlData = Yaml::parseFile($yamlFilename);
            } else {
                $currentYamlData = [];
            }
            $currentYamlData = $currentYamlData["pimcore"][$this->key][$id] ?? [];
            unset($currentYamlData['modificationDate']);
            $cleanedUpNewYamlData = $data;
            unset($cleanedUpNewYamlData['modificationDate']);

            if (json_encode($currentYamlData) != json_encode($cleanedUpNewYamlData)) {
                $newYamlData = Yaml::dump($newYamlData, 5);

                $dirname = $this->getVarConfigDir($id);

                if (!is_dir($dirname)) {
                    mkdir($dirname, 0775, true);
                }

                file_put_contents($yamlFilename, $newYamlData);
            }
        } else if ($writeLocation === "settingsstore") {
            $settingsStoreData = json_encode($data);
            SettingsStore::set($this->buildSettingsStoreKey($id), $settingsStoreData, 'string', $this->buildSettingsStoreScope());
        }
    }
}
