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

namespace Pimcore\Model\DataObject\ClassDefinition\CustomLayout;

use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\Model;
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\Tool\Serialize;
use Symfony\Component\Uid\Uuid as Uid;
use Symfony\Component\Uid\UuidV4;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\ClassDefinition\CustomLayout $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{
    /**
     * @var Model\DataObject\ClassDefinition\CustomLayout
     */
    protected $model;

    public function configure()
    {
        $config = \Pimcore::getContainer()->getParameter('pimcore.config');

        parent::configure([
            'containerConfig' => $config['objects']['custom_layouts']['definitions'],
            'settingsStoreScope' => 'pimcore_object_custom_layout',
            'storageDirectory' => $_SERVER['PIMCORE_CONFIG_STORAGE_DIR_OBJECT_CUSTOM_LAYOUTS'] ?? PIMCORE_CONFIGURATION_DIRECTORY  . '/custom-layouts',
            'writeTargetEnvVariableName' => 'PIMCORE_WRITE_TARGET_OBJECT_CUSTOM_LAYOUTS',
        ]);
    }

    /**
     * @param string|null $id
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->getDataByName($this->model->getId());

        if ($data instanceof Model\DataObject\ClassDefinition\CustomLayout) {
            $this->assignVariablesToModel($data->getObjectVars());
        } else {
            if ($data && $id != null) {
                $data['id'] = $id;
            }

            if ($data && is_string($data['layoutDefinitions'] ?? null)) {
                $data['layoutDefinitions'] = unserialize($data['layoutDefinitions']);
            }

            if (!empty($data['id'])) {
                $this->assignVariablesToModel($data);
            } else {
                throw new Model\Exception\NotFoundException('Layout with ID ' . $id . " doesn't exist");
            }
        }
    }

    /**
     * @param string $name
     */
    public function getByName($name)
    {
        $list = new Listing();
        /** @var Model\DataObject\ClassDefinition\CustomLayout[] $definitions */
        $definitions = array_values(array_filter($list->getLayoutDefinitions(), function ($item) use ($name) {
            $return = true;
            if ($name && $item->getName() != $name) {
                $return = false;
            }

            return $return;
        }));

        if (count($definitions) && $definitions[0]->getId()) {
            $this->assignVariablesToModel($definitions[0]->getObjectVars());
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Predefined metadata config with name "%s" does not exist.', $name));
        }
    }

    /**
     * @param string $id
     *
     * @return string|null
     */
    public function getNameById($id)
    {
        $name = null;

        $list = new Listing();
        /** @var Model\DataObject\ClassDefinition\CustomLayout[] $definitions */
        $definitions = array_values(array_filter($list->getLayoutDefinitions(), function ($item) use ($id) {
            $return = true;
            if ($id && $item->getId() != $id) {
                $return = false;
            }

            return $return;
        }));

        if (count($definitions) && $definitions[0]->getId()) {
            $name = $definitions[0]->getName();
        }

        return $name;
    }

    /**
     * @deprecated
     *
     * @param string $name
     * @param string $classId
     *
     * @return string|null
     */
    public function getIdByNameAndClassId($name, $classId)
    {
        $id = null;

        $list = new Listing();
        /** @var Model\DataObject\ClassDefinition\CustomLayout[] $definitions */
        $definitions = array_values(array_filter($list->getLayoutDefinitions(), function ($item) use ($name, $classId) {
            $return = false;
            if ($item->getName() == $name && $item->getClassId() == $classId) {
                $return = true;
            }

            return $return;
        }));

        if (count($definitions) && $definitions[0] instanceof Model\DataObject\ClassDefinition\CustomLayout) {
            $id = $definitions[0]->getId();
        }

        return $id;
    }

    /**
     * @return UuidV4
     */
    public function getNewId()
    {
        $newId = Uid::v4();
        $this->model->setId((string) $newId);

        return $newId;
    }

    /**
     * Get latest identifier
     *
     * @param string $classId
     *
     * @return UuidV4
     */
    public function getLatestIdentifier($classId)
    {
        return Uid::v4();
    }

    /**
     * Save custom layout
     *
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

        $data = [];
        $allowedProperties = ['id', 'name', 'description', 'creationDate', 'modificationDate',
            'userOwner', 'userModification', 'classId', 'default', 'layoutDefinitions'];
        $dataRaw = $this->model->getObjectVars();
        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                if (is_array($value) || is_object($value)) {
                    $value = Serialize::serialize($value);
                } elseif (is_bool($value)) {
                    $value = (int)$value;
                }

                $data[$key] = $value;
            }
        }

        $this->saveData($this->model->getId(), $data);
    }

    /**
     * Deletes custom layout
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
                'objects' => [
                    'custom_layouts' => [
                        'definitions' => [
                            $id => $data
                        ]
                    ]
                ]
            ]
        ];
    }
}
