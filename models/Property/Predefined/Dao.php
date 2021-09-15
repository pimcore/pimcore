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

use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Property\Predefined $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{
    use Model\Dao\AutoIncrementTrait;

    public function configure()
    {
        $config = \Pimcore::getContainer()->getParameter("pimcore.config");

        parent::configure([
            'containerConfig' => $config['properties']['predefined']['definitions'] ?? [],
            'settingsStoreScope' => 'pimcore_predefined_properties',
            'storageDirectory' => PIMCORE_CONFIGURATION_DIRECTORY . '/predefined-properties',
            'legacyConfigFile' => 'predefined-properties.php',
            'writeTargetEnvVariableName' => 'PIMCORE_WRITE_TARGET_PREDEFINED_PROPERTIES',
        ]);
    }

    /**
     * @param string|null $id
     *
     * @throws \Exception
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->getDataByName($this->model->getId());

        if($data && $id != null) {
            $data['id'] = $id;
        }

        if($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Predefined property with ID "%s" does not exist.',
                $this->model->getId()
            ));
        }
    }

    /**
     * @param string|null $key
     *
     * @throws \Exception
     */
    public function getByKey($key = null)
    {
        if ($key != null) {
            $this->model->setKey($key);
        }
        $key = $this->model->getKey();

        $list = new Listing();
        $properties = array_filter($list->getProperties(), function($item) use($key) {
                return $item->getKey() == $key;
            }
        );

        if (count($properties) && $properties[0]->getId()) {
                $this->assignVariablesToModel($properties[0]);
        } else {
            throw new \Exception('Route with name: ' . $this->model->getName() . ' does not exist');
        }
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->model->getId()) {
            $id = $this->getNextId(Listing::class);
            $this->model->setId($id);
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
                'properties' => [
                    'predefined' => [
                        'definitions' => [
                            $id => $data
                        ]
                    ]
                ]
            ]
        ];
    }
}
