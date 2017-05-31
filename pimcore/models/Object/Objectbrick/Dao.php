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
 * @package    Object\Objectbrick
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Objectbrick;

use Pimcore\Model;
use Pimcore\Model\Object;

/**
 * @property \Pimcore\Model\Object\Objectbrick $model
 */
class Dao extends Model\Object\Fieldcollection\Dao
{
    /**
     * @param Object\Concrete $object
     *
     * @return array
     */
    public function load(Object\Concrete $object)
    {
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname());
        $values = [];

        foreach ($fieldDef->getAllowedTypes() as $type) {
            try {
                $definition = Object\Objectbrick\Definition::getByKey($type);
            } catch (\Exception $e) {
                continue;
            }

            $tableName = $definition->getTableName($object->getClass(), false);

            try {
                $results = $this->db->fetchAll('SELECT * FROM '.$tableName.' WHERE o_id = ? AND fieldname = ?', [$object->getId(), $this->model->getFieldname()]);
            } catch (\Exception $e) {
                $results = [];
            }

            $fieldDefinitions = $definition->getFieldDefinitions(['object' => $object, 'suppressEnrichment' => true]);
            $brickClass = '\\Pimcore\\Model\\Object\\Objectbrick\\Data\\' . ucfirst($type);

            foreach ($results as $result) {
                $brick = new $brickClass($object);
                $brick->setFieldname($result['fieldname']);
                $brick->setObject($object);

                foreach ($fieldDefinitions as $key => $fd) {
                    if (method_exists($fd, 'load')) {
                        // datafield has it's own loader
                        $value = $fd->load($brick);
                        if ($value === 0 || !empty($value)) {
                            $brick->setValue($key, $value);
                        }
                    } else {
                        if (is_array($fd->getColumnType())) {
                            $multidata = [];
                            foreach ($fd->getColumnType() as $fkey => $fvalue) {
                                $multidata[$key . '__' . $fkey] = $result[$key . '__' . $fkey];
                            }
                            $brick->setValue(
                                $key,
                                $fd->getDataFromResource($multidata));
                        } else {
                            $brick->setValue(
                                $key,
                                $fd->getDataFromResource($result[$key]));
                        }
                    }
                }

                $setter = 'set' . ucfirst($type);
                $this->model->$setter($brick);

                $values[] = $brick;
            }
        }

        return $values;
    }

    /**
     * @throws \Exception
     *
     * @param Object\Concrete $object
     */
    public function delete(Object\Concrete $object)
    {
        // this is to clean up also the inherited values
        $fieldDef = $object->getClass()->getFieldDefinition($this->model->getFieldname());
        foreach ($fieldDef->getAllowedTypes() as $type) {
            try {
                $definition = Object\Objectbrick\Definition::getByKey($type);
            } catch (\Exception $e) {
                continue;
            }

            $tableName = $definition->getTableName($object->getClass(), true);
            $this->db->delete($tableName, ['o_id' => $object->getId()]);
        }
    }
}
