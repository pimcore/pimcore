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

namespace Pimcore\Model\Dao;

use Exception;
use Pimcore\Config;

abstract class PimcoreLocationAwareConfigDao implements DaoInterface
{
    use DaoTrait;

    private static array $cache = [];

    protected ?string $settingsStoreScope = null;

    protected ?string $dataSource = null;

    private ?string $id = null;

    private Config\LocationAwareConfigRepository $locationAwareConfigRepository;

    public function configure(): void
    {
        $params = func_get_arg(0);
        $this->settingsStoreScope = $params['settingsStoreScope'] ?? 'pimcore_config';

        if (!isset(self::$cache[$this->settingsStoreScope])) {
            // initialize runtime cache
            self::$cache[$this->settingsStoreScope] = [];
        }

        $this->locationAwareConfigRepository = new Config\LocationAwareConfigRepository(
            $params['containerConfig'] ?? [],
            $this->settingsStoreScope,
            $params['storageConfig'] ?? null
        );
    }

    protected function getDataByName(string $id): mixed
    {
        $this->id = $id;

        if (isset(self::$cache[$this->settingsStoreScope][$id])) {
            $this->dataSource = self::$cache[$this->settingsStoreScope][$id]['datasource'];

            return self::$cache[$this->settingsStoreScope][$id]['data'];
        }

        [$data, $this->dataSource] = $this->locationAwareConfigRepository->loadConfigByKey($id);

        if ($data) {
            self::$cache[$this->settingsStoreScope][$id] = [
                'datasource' => $this->dataSource,
                'data' => $data,
            ];
        }

        return $data;
    }

    protected function loadIdList(): array
    {
        return $this->locationAwareConfigRepository->fetchAllKeys();
    }

    protected function loadIdListByReadTargets(): array
    {
        return $this->locationAwareConfigRepository->fetchAllKeysByReadTargets();
    }

    /**
     * Removes config with corresponding id from the cache.
     * A new cache entry will be generated upon requesting the config again.
     *
     */
    protected function invalidateCache(string $id): void
    {
        unset(self::$cache[$this->settingsStoreScope][$id]);
    }

    /**
     *
     * @throws Exception
     */
    protected function saveData(string $id, array $data): void
    {
        $dao = $this;
        $this->invalidateCache($id);
        $this->locationAwareConfigRepository->saveConfig($id, $data, function ($id, $data) use ($dao) {
            return $dao->prepareDataStructureForYaml($id, $data);
        });
    }

    /**
     * Hook to prepare config data structure for yaml
     *
     *
     */
    protected function prepareDataStructureForYaml(string $id, mixed $data): mixed
    {
        return $data;
    }

    /**
     * @return string Can be either yaml (var/config/...) or "settings-store". defaults to "yaml"
     *
     * @throws Exception
     */
    public function getWriteTarget(): string
    {
        return $this->locationAwareConfigRepository->getWriteTarget();
    }

    public function isWriteable(): bool
    {
        return $this->locationAwareConfigRepository->isWriteable($this->id, $this->dataSource);
    }

    /**
     *
     * @throws Exception
     */
    protected function deleteData(string $id): void
    {
        $this->invalidateCache($id);
        $this->locationAwareConfigRepository->deleteData($id, $this->dataSource);
    }
}
