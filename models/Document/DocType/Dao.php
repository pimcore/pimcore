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

namespace Pimcore\Model\Document\DocType;

use Exception;
use Pimcore\Config;
use Pimcore\Model;
use Symfony\Component\Uid\Uuid as Uid;

/**
 * @internal
 *
 * @property \Pimcore\Model\Document\DocType $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{
    private const CONFIG_KEY = 'document_types';

    public function configure(): void
    {
        $config = Config::getSystemConfiguration();

        $storageConfig = $config['config_location'][self::CONFIG_KEY];

        parent::configure([
            'containerConfig' => $config['documents']['doc_types']['definitions'],
            'settingsStoreScope' => 'pimcore_document_types',
            'storageConfig' => $storageConfig,
        ]);
    }

    /**
     * Get the data for the object from database for the given id
     *
     *
     * @throws Exception
     */
    public function getById(?string $id = null): void
    {
        $data = null;
        if ($id !== null) {
            $data = $this->getDataByName($id);
        }

        if (!$data) {
            throw new Model\Exception\NotFoundException(sprintf(
                'Document Type with ID "%s" does not exist.',
                $this->model->getId()
            ));
        }

        $data['id'] = $id;
        $this->assignVariablesToModel($data);
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
        $allowedProperties = ['name', 'group', 'controller',
            'template', 'type', 'priority', 'creationDate', 'modificationDate', 'staticGeneratorEnabled', ];

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
                'documents' => [
                    'doc_types' => [
                        'definitions' => [
                            $id => $data,
                        ],
                    ],
                ],
            ],
        ];
    }
}
