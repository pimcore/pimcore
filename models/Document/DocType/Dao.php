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

use Pimcore\Model;
use Symfony\Component\Uid\Uuid as Uid;

/**
 * @internal
 *
 * @property \Pimcore\Model\Document\DocType $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{
    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const STORAGE_DIR = 'PIMCORE_CONFIG_STORAGE_DIR_DOCUMENT_TYPES';

    /**
     * @deprecated Will be removed in Pimcore 11
     */
    private const WRITE_TARGET = 'PIMCORE_WRITE_TARGET_DOCUMENT_TYPES';

    private const CONFIG_KEY = 'document_types';

    public function configure()
    {
        $config = \Pimcore::getContainer()->getParameter('pimcore.config');

        $storageDirectory = null;
        if(array_key_exists('directory', $config['storage'][self::CONFIG_KEY])) {
            $storageDirectory = $config['storage'][self::CONFIG_KEY]['directory'];
        } elseif (array_key_exists(self::STORAGE_DIR, $_SERVER)) {
            $storageDirectory = $_SERVER[self::STORAGE_DIR];
            trigger_deprecation('pimcore/pimcore', '10.6',
                sprintf('Setting storage directory (%s) in the .env file is deprecated, instead use the symfony config. It will be removed in Pimcore 11.',  self::STORAGE_DIR));
        } else {
            $storageDirectory = PIMCORE_CONFIGURATION_DIRECTORY . '/' . self::CONFIG_KEY;
        }

        $writeTarget = null;
        if(array_key_exists('target', $config['storage'][self::CONFIG_KEY])) {
            $writeTarget = $config['storage'][self::CONFIG_KEY]['target'];
        } elseif (array_key_exists(self::WRITE_TARGET, $_SERVER)) {
            $writeTarget = $_SERVER[self::WRITE_TARGET];
            trigger_deprecation('pimcore/pimcore', '10.6',
                sprintf('Setting write targets (%s) in the .env file is deprecated, instead use the symfony config. It will be removed in Pimcore 11.',  self::WRITE_TARGET));
        }

        parent::configure([
            'containerConfig' => $config['documents']['doc_types']['definitions'],
            'settingsStoreScope' => 'pimcore_document_types',
            'storageDirectory' => $storageDirectory,
            'legacyConfigFile' => 'document-types.php',
            'writeTargetEnvVariableName' => self::WRITE_TARGET,
            'writeTarget' => $writeTarget
        ]);
    }

    /**
     * Get the data for the object from database for the given id
     *
     * @param string|null $id
     *
     * @throws \Exception
     */
    public function getById(?string $id = null): void
    {
        $data = null;
        if ($id !== null) {
            $data = $this->getDataByName($id);
        }

        if (empty($data)) {
            throw new Model\Exception\NotFoundException(sprintf(
                'Document Type with ID "%s" does not exist.',
                $this->model->getId()
            ));
        }

        $data['id'] = $id;
        $this->assignVariablesToModel($data);
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->model->getId()) {
            $this->model->setId(Uid::v4());
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
    public function delete()
    {
        $this->deleteData($this->model->getId());
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareDataStructureForYaml(string $id, $data)
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
