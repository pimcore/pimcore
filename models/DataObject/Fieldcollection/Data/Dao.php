<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    DataObject\Fieldcollection
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;
use Pimcore\Tool;

/**
 * @property \Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param Model\DataObject\Concrete $object
     * @param array $params
     * @param $saveRelationalData
     *
     * @throws \Exception
     */
    public function save(Model\DataObject\Concrete $object, $params = [], $saveRelationalData = true)
    {
        $tableName = $this->model->getDefinition()->getTableName($object->getClass());
        $data = [
            'o_id' => $object->getId(),
            'index' => $this->model->getIndex(),
            'fieldname' => $this->model->getFieldname()
        ];

        try {
            /** @var $fd Model\DataObject\ClassDefinition\Data */
            foreach ($this->model->getDefinition()->getFieldDefinitions() as $fd) {
                $getter = 'get' . ucfirst($fd->getName());

                if ($fd instanceof CustomResourcePersistingInterface || method_exists($fd, 'save')) {
                    if (!$fd instanceof CustomResourcePersistingInterface) {
                        Tool::triggerMissingInterfaceDeprecation(get_class($fd), 'save', CustomResourcePersistingInterface::class);
                    }
                    if (!$fd instanceof Model\DataObject\ClassDefinition\Data\Localizedfields && $fd->supportsDirtyDetection() && !$saveRelationalData) {
                        continue;
                    }

                    // for fieldtypes which have their own save algorithm eg. relational data types, ...
                    $index = $this->model->getIndex();
                    $params = array_merge($params, [
                        'saveRelationalData' => $saveRelationalData,
                        'context' => [
                            'containerType' => 'fieldcollection',
                            'containerKey' => $this->model->getType(),
                            'fieldname' => $this->model->getFieldname(),
                            'index' => $index
                        ]
                    ]);

                    $fd->save(
                        $this->model, $params

                    );
                }
                if ($fd instanceof ResourcePersistenceAwareInterface || method_exists($fd, 'getDataForResource')) {
                    if (!$fd instanceof ResourcePersistenceAwareInterface) {
                        Tool::triggerMissingInterfaceDeprecation(get_class($fd), 'getDataForResource', ResourcePersistenceAwareInterface::class);
                    }
                    if (is_array($fd->getColumnType())) {
                        $insertDataArray = $fd->getDataForResource($this->model->$getter(), $object, [
                            'context' => $this->model //\Pimcore\Model\DataObject\Fieldcollection\Data\Dao
                        ]);
                        $data = array_merge($data, $insertDataArray);
                    } else {
                        $data[$fd->getName()] = $fd->getDataForResource($this->model->$getter(), $object, [
                            'context' => $this->model //\Pimcore\Model\DataObject\Fieldcollection\Data\Dao
                        ]);
                    }
                }
            }

            $this->db->insert($tableName, $data);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
