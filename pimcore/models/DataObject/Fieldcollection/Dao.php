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

use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @property \Pimcore\Model\DataObject\Fieldcollection $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param DataObject\Concrete $object
     */
    public function save(DataObject\Concrete $object)
    {
        $this->delete($object);
    }

    /**
     * @param DataObject\Concrete $object
     *
     * @return array
     */
    public function load(DataObject\Concrete $object)
    {
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname(), ['suppressEnrichment' => true]);
        $values = [];

        foreach ($fieldDef->getAllowedTypes() as $type) {
            try {
                $definition = DataObject\Fieldcollection\Definition::getByKey($type);
            } catch (\Exception $e) {
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
                $collection = $modelFactory->build($collectionClass);
                $collection->setIndex($result['index']);
                $collection->setFieldname($result['fieldname']);
                $collection->setObject($object);

                foreach ($fieldDefinitions as $key => $fd) {
                    if (method_exists($fd, 'load')) {
                        // datafield has it's own loader
                        $value = $fd->load(
                            $collection,
                            [
                                'context' => [
                                    'containerType' => 'fieldcollection',
                                    'containerKey' => $type,
                                    'fieldname' => $this->model->getFieldname(),
                                    'index' => $result['index']
                            ]]
                        );
                        if ($value === 0 || !empty($value)) {
                            $collection->setValue($key, $value);
                        }
                    } else {
                        if (is_array($fd->getColumnType())) {
                            $multidata = [];
                            foreach ($fd->getColumnType() as $fkey => $fvalue) {
                                $multidata[$key . '__' . $fkey] = $result[$key . '__' . $fkey];
                            }
                            $collection->setValue($key, $fd->getDataFromResource($multidata));
                        } else {
                            $collection->setValue($key, $fd->getDataFromResource($result[$key]));
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
     */
    public function delete(DataObject\Concrete $object)
    {
        // empty or create all relevant tables
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname(), ['suppressEnrichment' => true]);

        foreach ($fieldDef->getAllowedTypes() as $type) {
            try {
                /** @var $definition Definition */
                $definition = DataObject\Fieldcollection\Definition::getByKey($type);
            } catch (\Exception $e) {
                continue;
            }

            $tableName = $definition->getTableName($object->getClass());

            try {
                $this->db->delete($tableName, [
                    'o_id' => $object->getId(),
                    'fieldname' => $this->model->getFieldname()
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
                        'fieldname' => $this->model->getFieldname()
                    ]);
                } catch (\Exception $e) {
                    \Logger::error($e);
                }
            }

            $childDefinitions = $definition->getFielddefinitions(['suppressEnrichment' => true]);

            if (is_array($childDefinitions)) {
                foreach ($childDefinitions as $fd) {
                    if (method_exists($fd, 'delete')) {
                        $fd->delete(
                            $object,
                            [
                                'context' => [
                                    'containerType' => 'fieldcollection',
                                    'containerKey' => $type,
                                    'fieldname' => $this->model->getFieldname()
                                ]
                            ]
                        );
                    }
                }
            }
        }

        // empty relation table
        $this->db->deleteWhere(
            'object_relations_' . $object->getClassId(),
            "(ownertype = 'fieldcollection' AND " . $this->db->quoteInto('ownername = ?', $this->model->getFieldname()) . ' AND ' . $this->db->quoteInto('src_id = ?', $object->getId()) . ')'
            . " OR (ownertype = 'localizedfield' AND " . $this->db->quoteInto('ownername LIKE ?', '/fieldcollection~' . $this->model->getFieldname() . '/%') . ' AND ' . $this->db->quoteInto('src_id = ?', $object->getId()). ')'
        );
    }
}
