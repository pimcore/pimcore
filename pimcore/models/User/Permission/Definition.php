<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    User
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\User\Permission;

use Pimcore\Model;

class Definition extends Model\AbstractModel {

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
     * @param $key
     * @return $this
     */
    function setKey($key) {
        $this->key = $key;
        return $this;
    }

    /**
     * @param $permission
     * @return mixed
     * @throws \Exception
     */
    public static function getByKey($permission){
        if(!$permission){
            throw new \Exception("No permisson defined.");
        }
        $list = new Definition\Listing();
        $list->setCondition("`key`=?",array($permission));
        $list->setLimit(1);
        $permissionDefinition = $list->load();
        if($permissionDefinition[0]){
            return $permissionDefinition[0];
        }
    }

    /**
     * @param $permission
     * @return mixed|static
     * @throws \Exception
     */
    public static function create($permission){
        if(!$permission){
            throw new \Exception("No permisson defined.");
        }
        $permissionDefinition = static::getByKey($permission);
        if($permissionDefinition instanceof self){
            \Logger::info("Permission $permission allready exists. Skipping creation.");
            return $permissionDefinition;
        }else{
            $permissionDefinition = new static();
            $permissionDefinition->setKey($permission);
            $permissionDefinition->save();
            return $permissionDefinition;
        }
    }
}
