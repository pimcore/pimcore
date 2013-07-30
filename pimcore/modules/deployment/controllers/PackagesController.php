<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian Kogler
 * Date: 30.07.13
 * Time: 13:19
 */

class Deployment_PackagesController extends Pimcore_Controller_Action_Admin {

    public function listAction(){
        $list = new Deployment_Package_List();
        $list->setOrder('DESC');
        $list->setOrderKey('id');

        $objects = $list->load();
        $this->_helper->json(array('data' => $objects));
    }
}