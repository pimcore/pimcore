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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Controller_Action_Webservice extends Pimcore_Controller_Action {

    public function init() {

        if(!$this->getParam("apikey")){
            throw new Exception("API key missing");
        }

        $userList = new User_List();
        $userList->setCondition("password = ? AND type = ?", array($this->getParam("apikey"), "user"));
        $users = $userList->load();

        if(!is_array($users) or count($users)!==1){
            throw new Exception("API key error");
        }
        $user = $users[0];
        Zend_Registry::set("pimcore_user", $user);

        parent::init();
    }
}
