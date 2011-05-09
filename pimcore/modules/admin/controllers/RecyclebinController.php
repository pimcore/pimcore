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
        
        if($this->_getParam("xaction") == "destroy") {
            $item = Element_Recyclebin_Item::getById($this->_getParam("data"));
            $item->delete();
 
            $this->_helper->json(array("success" => true, "data" => array()));
        }
        else {
            $list = new Element_Recyclebin_Item_List();
            $list->setLimit($this->_getParam("limit"));
            $list->setOffset($this->_getParam("start"));
            $list->setOrderKey("path");
            $list->setOrder("ASC");

            if($this->_getParam("filter")) {
                $list->setCondition("path LIKE " . $list->quote("%".$this->_getParam("filter")."%"));
            }
            
            $items = $list->load();
            
            $this->_helper->json(array("data" => $items, "success" => true, "total" => $list->getTotalCount()));
        }
    }
    
    public function restoreAction () {
        $item = Element_Recyclebin_Item::getById($this->_getParam("id"));
        $item->restore();
 
        $this->_helper->json(array("success" => true));
    }
 
    public function flushAction () {
        $bin = new Element_Recyclebin();
        $bin->flush();
        
        $this->_helper->json(array("success" => true)); 
    }
}
