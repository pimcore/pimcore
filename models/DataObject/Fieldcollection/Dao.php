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

namespace Pimcore\Model\DataObject\Fieldcollection;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\LazyLoadingSupportInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface;

/**
 * @property \Pimcore\Model\DataObject\Fieldcollection $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return array
     */
    public function save(DataObject\Concrete $object, $params = [])
    {
        return $this->delete($object, true);
    }

    /**
     * @param DataObject\Concrete $object
     *
     * @return array
     */
    public function load(DataObject\Concrete $object)
    {
        /** @var DataObject\ClassDefinition\Data\Fieldcollections $fieldDef */
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname(), ['suppressEnrichment' => true]);
        $values = [];

        foreach ($fieldDef->getAllowedTypes() as $type) {
            if (!$definition = DataObject\Fieldcollection\Definition::getByKey($type)) {
                continue;
            }

            $tableName = $definition->getTableName($object->getClass());

            try {
                $results = $this->db->fetchAll('SELECT * FROM ' . $tableName . ' WHERE o_id = ? AND fieldname = ? ORDER BY `index` ASC', [$object->getId(), $this->model->getFieldname()]);
            } catch (\Exception $e) {
                $results = [];
            }

            $fieldDefinitions = $definition->getFieldDefinitions(['suppressEnrichment' => true]);
            $collectionClass = '\\Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst($type);
            $modelFactory = \Pimcore::getContainer()->get('pimcore.model.factory');

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
     * @param DataObject\Concrete $object
     * @param bool $saveMode true if called from save method
     *
     * @return array
     */
    public function delete(DataObject\Concrete $object, $saveMode = false)
    {
        // empty or create all relevant tables

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
                $this->db->delete($tableName, [
                    'o_id' => $object->getId(),
                    'fieldname' => $this->model->getFieldname(),
                ]);
            } catch (\Exception $e) {
                // create definition if it does not exist
                $definition->createUpdateTable($object->getClass());
            }

            if ($definition->getFieldDefinition('localizedfields', ['suppressEnrichment' => true])) {
                $tableName = $definition->getLocalizedTableName($object->getClass());

                try {
                    $this->db->delete($tableName, [
                        'ooo_id' => $object->getId(),
                        'fieldname' => $this->model->getFieldname(),
                    ]);
                } catch (\Exception $e) {
                    Logger::error($e);
                }
            }

            $childDefinitions = $definition->getFieldDefinitions(['suppressEnrichment' => true]);

            if (is_array($childDefinitions)) {
                foreach ($childDefinitions as $fd) {
                    if (!DataObject\AbstractObject::isDirtyDetectionDisabled() && $this->model instanceof Model\Element\DirtyIndicatorInterface) {
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
        if (!$this->model->isFieldDirty('_self') && !DataObject\AbstractObject::isDirtyDetectionDisabled()) {
            return [];
        }

        $whereLocalizedFields = "(ownertype = 'localizedfield' AND "
            . $this->db->quoteInto('ownername LIKE ?', '/fieldcollection~'
                . $this->model->getFieldname() . '/%')
            . ' AND ' . $this->db->quoteInto('src_id = ?', $object->getId()). ')';

        if ($saveMode) {
            if (!DataObject\AbstractObject::isDirtyDetectionDisabled() && !$this->model->hasDirtyFields() && $hasLocalizedFields) {
                // always empty localized fields
                $this->db->deleteWhere('object_relations_' . $object->getClassId(), $whereLocalizedFields);

                return ['saveLocalizedRelations' => true];
            }
        }

        $where = "(ownertype = 'fieldcollection' AND " . $this->db->quoteInto('ownername = ?', $this->model->getFieldname())
            . ' AND ' . $this->db->quoteInto('src_id = ?', $object->getId()) . ')'
            . ' OR ' . $whereLocalizedFields;

        // empty relation table
        $this->db->deleteWhere('object_relations_' . $object->getClassId(), $where);

        return ['saveFieldcollectionRelations' => true, 'saveLocalizedRelations' => true];
    }
}
