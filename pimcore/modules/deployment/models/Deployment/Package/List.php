<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 30.05.13
 * Time: 15:09
 */

class Deployment_Package_List extends Pimcore_Model_List_Abstract{

    public function isValidOrderKey($key){
        return true;
    }


}