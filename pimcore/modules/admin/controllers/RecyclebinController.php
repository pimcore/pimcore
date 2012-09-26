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
 
class Admin_RecyclebinController extends Pimcore_Controller_Action_Admin {
    
    public function listAction () {
        
        if($this->getParam("xaction") == "destroy") {
            $item = Element_Recyclebin_Item::getById($this->getParam("data"));
            $item->delete();
 
            $this->_helper->json(array("success" => true, "data" => array()));
        }
        else {
            $list = new Element_Recyclebin_Item_List();
            $list->setLimit($this->getParam("limit"));
            $list->setOffset($this->getParam("start"));

            if($this->getParam("sort")) {
                $list->setOrderKey($this->getParam("sort"));
                $list->setOrder($this->getParam("dir"));
            }

            if($this->getParam("filter")) {
                $list->setCondition("path LIKE " . $list->quote("%".$this->getParam("filter")."%"));
            }
            
            $items = $list->load();
            
            $this->_helper->json(array("data" => $items, "success" => true, "total" => $list->getTotalCount()));
        }
    }
    
    public function restoreAction () {
        $item = Element_Recyclebin_Item::getById($this->getParam("id"));
        $item->restore();
 
        $this->_helper->json(array("success" => true));
    }
 
    public function flushAction () {
        $bin = new Element_Recyclebin();
        $bin->flush();
        
        $this->_helper->json(array("success" => true)); 
    }

    public function addAction () {

        $element = Element_Service::getElementById($this->getParam("type"), $this->getParam("id"));

        if($element) {
            Element_Recyclebin_Item::create($element, $this->getUser());

            $this->_helper->json(array("success" => true));
        } else {
            $this->_helper->json(array("success" => false));
        }

    }
}
