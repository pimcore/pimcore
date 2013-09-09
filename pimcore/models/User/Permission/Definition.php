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
 * @package    User
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class User_Permission_Definition extends Pimcore_Model_Abstract {

    public $key;

    /**
     * @param array
     */
    public function __construct($data = array()) {
        if (is_array($data) && !empty($data)) {
            $this->setValues($data);
        }
    }

    /**
     * @return string
     */
    function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    function setKey($key) {
        $this->key = $key;
        return $this;
    }

    /**
     * @param $permisson permission key
     * @return mixed
     * @throws Exception
     */
    public static function getByKey($permission){
        if(!$permission){
            throw new Exception("No permisson defined.");
        }
        $list = new User_Permission_Definition_List();
        $list->setCondition("`key`=?",array($permission));
        $list->setLimit(1);
        $permissionDefinition = $list->load();
        if($permissionDefinition[0]){
            return $permissionDefinition[0];
        }
    }

    /**
     * @param $permission permission key
     * @return mixed
     * @throws Exception
     */
    public static function create($permission){
        if(!$permission){
            throw new Exception("No permisson defined.");
        }
        $permissionDefinition = static::getByKey($permission);
        if($permissionDefinition instanceof User_Permission_Definition){
            Logger::info("Permission $permission allready exists. Skipping creation.");
            return $permissionDefinition;
        }else{
            $permissionDefinition = new static();
            $permissionDefinition->setKey($permission);
            $permissionDefinition->save();
            return $permissionDefinition;
        }
    }
}
