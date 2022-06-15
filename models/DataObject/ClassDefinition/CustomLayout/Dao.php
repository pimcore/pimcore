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

use Pimcore\Bundle\DataHubFileExportBundle\Model\Dao\AbstractDao;
use Pimcore\Config\LocationAwareConfigRepository;
use Pimcore\Model;
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

        // @deprecated legacy will be removed in Pimcore 11
        $loadLegacyConfigCallback = function ($legacyRepo, &$dataSource) {
            $file = PIMCORE_CUSTOMLAYOUT_DIRECTORY . '/custom_definition_'. $this->model->getId() .'.php';
            if (is_file($file)) {
                $content = @include $file;
                if ($content instanceof Model\DataObject\ClassDefinition\CustomLayout) {
                    $dataSource = LocationAwareConfigRepository::LOCATION_LEGACY;

                    return $content;
                }
            }

            return null;
        };

        parent::configure([
            'containerConfig' => $config['object']['custom_layout']['definitions'],
            'settingsStoreScope' => 'pimcore_object_custom_layout',
            'storageDirectory' => $_SERVER['PIMCORE_CONFIG_STORAGE_DIR_OBJECT_CUSTOM_LAYOUTS'] ?? PIMCORE_CONFIGURATION_DIRECTORY  . '/object-custom-layouts',
            'writeTargetEnvVariableName' => 'PIMCORE_WRITE_TARGET_OBJECT_CUSTOM_LAYOUTS',
            'loadLegacyConfigCallback' => $loadLegacyConfigCallback
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

            if (!empty($data['id'])) {
                $this->assignVariablesToModel($data);
            } else {
                throw new Model\Exception\NotFoundException('Layout with ID ' . $id . " doesn't exist");
            }
        }
    }

    /**
     * @param string $name
     *
     * @return mixed|null
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

        try {
            if (!empty($id)) {
                $name = $this->db->fetchOne('SELECT name FROM custom_layouts WHERE id = ?', $id);
            }
        } catch (\Exception $e) {
        }

        return $name;
    }

    /**
     * @param string $name
     * @param string $classId
     *
     * @return string|null
     */
    public function getIdByNameAndClassId($name, $classId)
    {
        $id = null;

        try {
            if (!empty($name) && !empty($classId)) {
                $id = $this->db->fetchOne('SELECT id FROM custom_layouts WHERE name = ? AND classId = ?', [$name, $classId]);
            }
        } catch (\Exception $e) {
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
        $validColumns = (new AbstractDao())->getValidTableColumns('custom_layouts');
        $dataRaw = $this->model->getObjectVars();
        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $validColumns)) {
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
                    'custom_layout' => [
                        'definitions' => [
                            $id => $data
                        ]
                    ]
                ]
            ]
        ];
    }
}
