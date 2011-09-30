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

class Admin_ObjectHelperController extends Pimcore_Controller_Action_Admin {

    public function loadObjectDataAction() {
        $object = Object_Abstract::getById($this->_getParam("id"));
        $result = array();
        if($object) {
            $result['success'] = true;
            $fields = $this->_getParam("fields");
            foreach($fields as $f) {
                $result['fields']['id'] = $object->getId();
                $getter = "get" . ucfirst($f);
                if(method_exists($object, $getter)) {
                    $result['fields'][$f] = (string) $object->$getter();
                }
            }
            
        } else {
            $result['success'] = false;
        }
        $this->_helper->json($result);
    }


}