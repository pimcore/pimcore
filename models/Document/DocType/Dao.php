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
    public function configure()
    {
        $config = \Pimcore::getContainer()->getParameter('pimcore.config');

        parent::configure([
            'containerConfig' => $config['documents']['doc_types']['definitions'],
            'settingsStoreScope' => 'pimcore_document_types',
            'storageDirectory' => $_SERVER['PIMCORE_CONFIG_STORAGE_DIR_DOCUMENT_TYPES'] ?? PIMCORE_CONFIGURATION_DIRECTORY . '/document-types',
            'writeTargetEnvVariableName' => 'PIMCORE_WRITE_TARGET_DOCUMENT_TYPES',
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
    protected function prepareDataStructureForYaml(string $id, mixed $data)
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
