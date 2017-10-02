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

/**
 * @property \Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param Model\DataObject\Concrete $object
     *
     * @throws \Exception
     */
    public function save(Model\DataObject\Concrete $object)
    {
        $tableName = $this->model->getDefinition()->getTableName($object->getClass());
        $data = [
            'o_id' => $object->getId(),
            'index' => $this->model->getIndex(),
            'fieldname' => $this->model->getFieldname()
        ];

        try {
            foreach ($this->model->getDefinition()->getFieldDefinitions() as $fd) {
                $getter = 'get' . ucfirst($fd->getName());

                if (method_exists($fd, 'save')) {
                    // for fieldtypes which have their own save algorithm eg. objects, multihref, ...
                    $index = $this->model->getIndex();
                    $fd->save(
                        $this->model,
                        [
                            'context' => [
                                'containerType' => 'fieldcollection',
                                'containerKey' => $this->model->getType(),
                                'fieldname' => $this->model->getFieldname(),
                                'index' => $index
                            ]
                        ]
                    );
                } elseif ($fd->getColumnType()) {
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
