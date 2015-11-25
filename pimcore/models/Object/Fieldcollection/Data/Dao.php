<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object\Fieldcollection
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\Fieldcollection\Data;

use Pimcore\Model;

class Dao extends Model\Dao\AbstractDao {

    /**
     * @param Model\Object\Concrete $object
     * @throws \Exception
     */
    public function save (Model\Object\Concrete $object) {
        
        $tableName = $this->model->getDefinition()->getTableName($object->getClass());
        $data = array(
            "o_id" => $object->getId(),
            "index" => $this->model->getIndex(),
            "fieldname" => $this->model->getFieldname()
        );
        
        try {
            
            foreach ($this->model->getDefinition()->getFieldDefinitions() as $fd) {
                $getter = "get" . ucfirst($fd->getName());

                if (method_exists($fd, "save")) {
                    // for fieldtypes which have their own save algorithm eg. objects, multihref, ...
                    $fd->save($this->model);
                    
                } else if ($fd->getColumnType()) {
                    if (is_array($fd->getColumnType())) {
                        $insertDataArray = $fd->getDataForResource($this->model->$getter(), $object);
                        $data = array_merge($data, $insertDataArray);
                    } else {
                        $data[$fd->getName()] = $fd->getDataForResource($this->model->$getter(), $object);
                    }
                }
            }
            
            $this->db->insert($tableName, $data);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
