<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object\Fieldcollection
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Fieldcollection\Data;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

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
