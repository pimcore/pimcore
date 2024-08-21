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

namespace Pimcore\Model\Property\Predefined;

use Exception;
use Pimcore\Config;
use Pimcore\Model;
use Symfony\Component\Uid\Uuid as Uid;

/**
 * @internal
 *
 * @property \Pimcore\Model\Property\Predefined $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{
    private const CONFIG_KEY = 'predefined_properties';

    public function configure(): void
    {
        $config = Config::getSystemConfiguration();

        $storageConfig = $config['config_location'][self::CONFIG_KEY];

        parent::configure([
            'containerConfig' => $config['properties']['predefined']['definitions'],
            'settingsStoreScope' => 'pimcore_predefined_properties',
            'storageConfig' => $storageConfig,
        ]);
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(string $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->getDataByName($this->model->getId());

        if ($data && $id != null) {
            $data['id'] = $id;
        }

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Predefined property with ID "%s" does not exist.',
                $this->model->getId()
            ));
        }
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByKey(string $key = null): void
    {
        if ($key != null) {
            $this->model->setKey($key);
        }
        $key = $this->model->getKey();

        $list = new Listing();
        /** @var Model\Property\Predefined[] $properties */
        $properties = array_values(array_filter($list->getProperties(), function ($item) use ($key) {
            return $item->getKey() == $key;
        }
        ));

        if (count($properties) && $properties[0]->getId()) {
            $this->assignVariablesToModel($properties[0]->getObjectVars());
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Predefined property with key "%s" does not exist.',
                $this->model->getKey()
            ));
        }
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        if (!$this->model->getId()) {
            $this->model->setId((string)Uid::v4());
        }
        $ts = time();
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];
        $allowedProperties = ['name', 'description', 'key', 'type', 'data',
            'config', 'ctype', 'inheritable', 'creationDate', 'modificationDate', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }
        $this->saveData($this->model->getId(), $data);
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->deleteData($this->model->getId());
    }

    protected function prepareDataStructureForYaml(string $id, mixed $data): mixed
    {
        return [
            'pimcore' => [
                'properties' => [
                    'predefined' => [
                        'definitions' => [
                            $id => $data,
                        ],
                    ],
                ],
            ],
        ];
    }
}
