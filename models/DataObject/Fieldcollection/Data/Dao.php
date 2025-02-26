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

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Exception;
use Pimcore\Db\Helper;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws Exception
     */
    public function save(Model\DataObject\Concrete $object, array $params = [], bool|array $saveRelationalData = true): void
    {
        $tableName = $this->model->getDefinition()->getTableName($object->getClass());
        $data = [
            'id' => $object->getId(),
            'index' => $this->model->getIndex(),
            'fieldname' => $this->model->getFieldname(),
        ];

        foreach ($this->model->getDefinition()->getFieldDefinitions() as $fieldName => $fd) {
            $getter = 'get' . ucfirst($fieldName);

            if ($fd instanceof CustomResourcePersistingInterface
                && $fd instanceof Model\DataObject\ClassDefinition\Data) {
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
                        'index' => $index,
                    ],
                ]);

                if ($fd instanceof Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations
                            && ($params['saveRelationalData']['saveFieldcollectionRelations'] ?? false)) {
                    $params['forceSave'] = true;
                }

                $fd->save(
                    $this->model, $params
                );
            }
            if ($fd instanceof ResourcePersistenceAwareInterface
                && $fd instanceof Model\DataObject\ClassDefinition\Data) {
                $fieldDefinitionParams = [
                    'owner' => $this->model, //\Pimcore\Model\DataObject\Fieldcollection\Data\Dao
                    'fieldname' => $fd->getName(),
                ];
                if (is_array($fd->getColumnType())) {
                    $insertDataArray = $fd->getDataForResource($this->model->$getter(), $object, $fieldDefinitionParams);
                    $data = array_merge($data, $insertDataArray);
                    $this->model->set($fieldName, $fd->getDataFromResource($insertDataArray, $object, $fieldDefinitionParams));
                } else {
                    $insertData = $fd->getDataForResource($this->model->$getter(), $object, $fieldDefinitionParams);
                    $data[$fd->getName()] = $insertData;
                    $this->model->set($fieldName, $fd->getDataFromResource($insertData, $object, $fieldDefinitionParams));
                }

                if ($this->model instanceof Model\Element\DirtyIndicatorInterface) {
                    $this->model->markFieldDirty($fieldName, false);
                }
            }
        }

        $this->db->insert($tableName, Helper::quoteDataIdentifiers($this->db, $data));
    }
}
