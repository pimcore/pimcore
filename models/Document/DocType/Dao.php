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

/**
 * @internal
 *
 * @property \Pimcore\Model\Document\DocType $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{

    use Model\Dao\AutoIncrementTrait;

    public function configure()
    {
        $config = \Pimcore::getContainer()->getParameter("pimcore.config");

        parent::configure([
            'containerConfig' => $config['documents']['doctype']['definitions'],
            'settingsStoreScope' => 'pimcore_document_types',
            'storageDirectory' => PIMCORE_CONFIGURATION_DIRECTORY . '/document-types',
            'legacyConfigFile' => 'document-types.php',
            'writeTargetEnvVariableName' => 'PIMCORE_WRITE_TARGET_DOCUMENT_TYPES',
        ]);
    }

    /**
     * Get the data for the object from database for the given id
     *
     * @param int|null $id
     *
     * @throws \Exception
     */
    public function getById($id = null)
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
                'Document Type with ID "%s" does not exist.',
                $this->model->getId()
            ));
        }
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->model->getId()) {
            $listing = new Listing();
            $listing = $listing->load();
            $listing = array_map(function(Model\Document\DocType $item) {
                return $item->getId();
            }, $listing);
            $id = $this->getNextId($listing);
            $this->model->setId($id);
        }
        $ts = time();
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];
        $allowedProperties = ['id', 'name', 'group', 'module', 'controller',
            'action', 'template', 'type', 'priority', 'creationDate', 'modificationDate', 'staticGeneratorEnabled' ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }
        $this->saveData($this->model->getId(), $data);

        if (!$this->model->getId()) {
            $this->model->setId($this->db->getLastInsertId());
        }
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->deleteData($this->model->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareDataStructureForYaml(string $id, $data)
    {
        return [
            'pimcore' => [
                'documents' => [
                    'doctype' => [
                        'definitions' => [
                            $id => $data
                        ]
                    ]
                ]
            ]
        ];
    }

}
