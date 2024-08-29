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

namespace Pimcore\Model\Asset\Image\Thumbnail\Config;

use Exception;
use Pimcore;
use Pimcore\Config;
use Pimcore\Messenger\CleanupThumbnailsMessage;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Asset\Image\Thumbnail\Config $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{
    private const CONFIG_KEY = 'image_thumbnails';

    public function configure(): void
    {
        $config = Config::getSystemConfiguration();

        $storageConfig = $config['config_location'][self::CONFIG_KEY];

        parent::configure([
            'containerConfig' => $config['assets']['image']['thumbnails']['definitions'],
            'settingsStoreScope' => 'pimcore_image_thumbnails',
            'storageConfig' => $storageConfig,
        ]);
    }

    /**
     *
     * @throws Exception
     */
    public function getByName(string $id = null): void
    {
        if ($id != null) {
            $this->model->setName($id);
        }

        $data = $this->getDataByName($this->model->getName());

        if ($data && $id != null) {
            $data['id'] = $id;
        }

        if ($data) {
            $this->assignVariablesToModel($data);
            $this->model->setName($data['id']);
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Thumbnail with ID "%s" does not exist.',
                $this->model->getName()
            ));
        }
    }

    public function exists(string $name): bool
    {
        return (bool) $this->getDataByName($this->model->getName());
    }

    /**
     * @param bool $forceClearTempFiles force removing generated thumbnail files of saved thumbnail config
     *
     * @throws Exception
     */
    public function save(bool $forceClearTempFiles = false): void
    {
        $ts = time();
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];
        $allowedProperties = ['name', 'description', 'group', 'items', 'medias', 'format',
            'quality', 'highResolution', 'creationDate', 'modificationDate', 'preserveColor', 'preserveMetaData',
            'rasterizeSVG', 'downloadable', 'preserveAnimation', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }

        $this->saveData($this->model->getName(), $data);

        if ($forceClearTempFiles) {
            $this->model->clearTempFiles();
        } elseif ($this->dataSource) {
            // thumbnail already existed
            $this->autoClearTempFiles();
        }

        $this->clearDatabaseCache();
    }

    protected function prepareDataStructureForYaml(string $id, mixed $data): mixed
    {
        return [
            'pimcore' => [
                'assets' => [
                    'image' => [
                        'thumbnails' => [
                            'definitions' => [
                                $id => $data,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Deletes object from database
     *
     * @param bool $forceClearTempFiles force removing generated thumbnail files of saved thumbnail config
     */
    public function delete(bool $forceClearTempFiles = false): void
    {
        $this->deleteData($this->model->getName());

        if ($forceClearTempFiles) {
            $this->model->clearTempFiles();
        } else {
            $this->autoClearTempFiles();
        }

        $this->clearDatabaseCache();
    }

    private function clearDatabaseCache(): void
    {
        \Pimcore\Db::get()->delete('assets_image_thumbnail_cache', [
            'name' => $this->model->getName(),
        ]);

        Model\Asset\Dao::$thumbnailStatusCache = [];
    }

    protected function autoClearTempFiles(): void
    {
        $enabled = Config::getSystemConfiguration('assets')['image']['thumbnails']['auto_clear_temp_files'];
        if ($enabled) {
            Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
                new CleanupThumbnailsMessage('image', $this->model->getName())
            );
        }
    }
}
