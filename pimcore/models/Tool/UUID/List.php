<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 08.06.13
 * Time: 22:23
 */

class Tool_UUID_List extends Pimcore_Model_List_Abstract {

    public function isValidOrderKey($key){
        $resource = new Tool_UUID_Resource();
        $cols = $resource->getValidTableColumns(Tool_UUID_Resource::TABLE_NAME);
        return in_array($key,$cols);
    }

}