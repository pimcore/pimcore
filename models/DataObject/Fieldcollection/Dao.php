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

namespace Pimcore\Model\DataObject\Fieldcollection;

use Exception;
use Pimcore;
use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\LazyLoadingSupportInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;

/**
 * @internal
 *
 * @property \Pimcore\Model\DataObject\Fieldcollection $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public function save(DataObject\Concrete $object, array $params = []): array
    {
        return $this->delete($object, true);
    }

    /**
     * @return DataObject\Fieldcollection\Data\AbstractData[]
     */
    public function load(DataObject\Concrete $object): array
    {
        /** @var DataObject\ClassDefinition\Data\Fieldcollections $fieldDef */
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname(), ['suppressEnrichment' => true]);
        $object->__objectAwareFields[$this->model->getFieldname()] = true;

        $values = [];

        foreach ($fieldDef->getAllowedTypes() as $type) {
            if (!$definition = DataObject\Fieldcollection\Definition::getByKey($type)) {
                continue;
            }

            $tableName = $definition->getTableName($object->getClass());

            try {
                $results = $this->db->fetchAllAssociative('SELECT * FROM ' . $tableName . ' WHERE id = ? AND fieldname = ? ORDER BY `index` ASC', [$object->getId(), $this->model->getFieldname()]);
            } catch (Exception $e) {
                $results = [];
            }

            $fieldDefinitions = $definition->getFieldDefinitions(['suppressEnrichment' => true]);
            $collectionClass = '\\Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst($type);
            $modelFactory = Pimcore::getContainer()->get('pimcore.model.factory');

            foreach ($results as $result) {
                /** @var DataObject\Fieldcollection\Data\AbstractData $collection */
                $collection = $modelFactory->build($collectionClass);
                $collection->setIndex($result['index']);
                $collection->setFieldname($result['fieldname']);
                $collection->setObject($object);

                foreach ($fieldDefinitions as $key => $fd) {
                    $params = [
                        'context' => [
                            'object' => $object,
                            'containerType' => 'fieldcollection',
                            'containerKey' => $type,
                            'fieldname' => $this->model->getFieldname(),
                            'index' => $result['index'],
                        ],
                        'owner' => $collection,
                        'fieldname' => $key,
                    ];

                    if ($fd instanceof CustomResourcePersistingInterface) {
                        $doLoad = true;
                        if ($fd instanceof LazyLoadingSupportInterface) {
                            if ($fd->getLazyLoading()) {
                                $doLoad = false;
                            }
                        }

                        if ($doLoad) {
                            // datafield has it's own loader
                            $value = $fd->load(
                                $collection,
                                $params
                            );

                            if ($value === 0 || !empty($value)) {
                                $collection->setValue($key, $value);

                                if ($collection instanceof Model\Element\DirtyIndicatorInterface) {
                                    $collection->markFieldDirty($key, false);
                                }
                            }
                        }
                    }
                    if ($fd instanceof ResourcePersistenceAwareInterface) {
                        if (is_array($fd->getColumnType())) {
                            $multidata = [];
                            foreach ($fd->getColumnType() as $fkey => $fvalue) {
                                $multidata[$key . '__' . $fkey] = $result[$key . '__' . $fkey];
                            }
                            $collection->setValue($key, $fd->getDataFromResource($multidata, $object, $params));
                        } else {
                            $collection->setValue($key, $fd->getDataFromResource($result[$key], $object, $params));
                        }
                    }
                }

                $values[] = $collection;
            }
        }

        $orderedValues = [];
        foreach ($values as $value) {
            $orderedValues[$value->getIndex()] = $value;
        }

        ksort($orderedValues);

        $this->model->setItems($orderedValues);

        return $orderedValues;
    }

    /**
     * @param bool $saveMode true if called from save method
     *
     * @return array{saveLocalizedRelations?: true, saveFieldcollectionRelations?: true}
     */
    public function delete(DataObject\Concrete $object, bool $saveMode = false): array
    {
        /** @var DataObject\ClassDefinition\Data\Fieldcollections $fieldDef */
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname(), ['suppressEnrichment' => true]);
        $hasLocalizedFields = false;

        foreach ($fieldDef->getAllowedTypes() as $type) {
            if (!$definition = DataObject\Fieldcollection\Definition::getByKey($type)) {
                continue;
            }

            if ($definition->getFieldDefinition('localizedfields')) {
                $hasLocalizedFields = true;
            }

            $tableName = $definition->getTableName($object->getClass());

            try {
                $dataExists = $this->db->fetchOne('SELECT `id` FROM `'.$tableName."` WHERE `id` = '".$object->getId()."' AND `fieldname` = '".$this->model->getFieldname()."' LIMIT 1");
                if ($dataExists) {
                    $this->db->delete($tableName, [
                        'id' => $object->getId(),
                        'fieldname' => $this->model->getFieldname(),
                    ]);
                }
            } catch (Exception $e) {
                // create definition if it does not exist
                $definition->createUpdateTable($object->getClass());
            }

            if ($definition->getFieldDefinition('localizedfields', ['suppressEnrichment' => true])) {
                $tableName = $definition->getLocalizedTableName($object->getClass());

                try {
                    $dataExists = $this->db->fetchOne('SELECT `ooo_id` FROM `'.$tableName."` WHERE
         `ooo_id` = '".$object->getId()."' AND `fieldname` = '".$this->model->getFieldname()."' LIMIT 1 ");
                    if ($dataExists) {
                        $this->db->delete($tableName, [
                            'ooo_id' => $object->getId(),
                            'fieldname' => $this->model->getFieldname(),
                        ]);
                    }
                } catch (Exception $e) {
                    Logger::error((string) $e);
                }
            }

            $childDefinitions = $definition->getFieldDefinitions(['suppressEnrichment' => true]);

            foreach ($childDefinitions as $fd) {
                if (!DataObject::isDirtyDetectionDisabled() && $this->model instanceof Model\Element\DirtyIndicatorInterface) {
                    if ($fd instanceof DataObject\ClassDefinition\Data\Relations\AbstractRelations && !$this->model->isFieldDirty(
                        '_self'
                    )) {
                        continue;
                    }
                }

                if ($fd instanceof CustomResourcePersistingInterface) {
                    $fd->delete(
                        $object,
                        [
                            'isUpdate' => $saveMode,
                            'context' => [
                                'containerType' => 'fieldcollection',
                                'containerKey' => $type,
                                'fieldname' => $this->model->getFieldname(),
                            ],
                        ]
                    );
                }
            }
        }

        $isDirty = $this->model->isFieldDirty('_self');
        if (!$isDirty) {
            if ($items = $this->model->getItems()) {
                /** @var Model\Element\DirtyIndicatorInterface $item */
                foreach ($items as $item) {
                    if ($item->hasDirtyFields()) {
                        $this->model->markFieldDirty('_self');

                        break;
                    }
                }
            }
        }
        if (!$this->model->isFieldDirty('_self') && !DataObject::isDirtyDetectionDisabled()) {
            return [];
        }

        $whereLocalizedFields = "(ownertype = 'localizedfield' AND "
            . Helper::quoteInto($this->db, 'ownername LIKE ?', '/fieldcollection~'
                . $this->model->getFieldname() . '/%')
            . ' AND ' . Helper::quoteInto($this->db, 'src_id = ?', $object->getId()). ')';

        if ($saveMode) {
            if (!DataObject::isDirtyDetectionDisabled() && !$this->model->hasDirtyFields() && $hasLocalizedFields) {
                // always empty localized fields
                $this->db->executeStatement('DELETE FROM object_relations_' . $object->getClassId() . ' WHERE ' . $whereLocalizedFields);

                return ['saveLocalizedRelations' => true];
            }
        }

        $where = "(ownertype = 'fieldcollection' AND " . Helper::quoteInto($this->db, 'ownername = ?', $this->model->getFieldname())
            . ' AND ' . Helper::quoteInto($this->db, 'src_id = ?', $object->getId()) . ')';

        // empty relation table
        $this->db->executeStatement('DELETE FROM object_relations_' . $object->getClassId() . ' WHERE ' . $where);
        $this->db->executeStatement('DELETE FROM object_relations_' . $object->getClassId() . ' WHERE ' . $whereLocalizedFields);

        return ['saveFieldcollectionRelations' => true, 'saveLocalizedRelations' => true];
    }
}
