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
 
class Admin_ElementController extends Pimcore_Controller_Action_Admin {
    
    public function lockElementAction()
    {
        Element_Editlock::lock($this->_getParam("id"), $this->_getParam("type"));
        exit;
    }

    public function unlockElementAction()
    {
        Element_Editlock::unlock($this->_getParam("id"), $this->_getParam("type"));
        exit;
    }


    public function noteListAction () {

        $list = new Element_Note_List();

        $list->setLimit($this->_getParam("limit"));
        $list->setOffset($this->_getParam("start"));

        if($this->_getParam("sort")) {
            $list->setOrderKey($this->_getParam("sort"));
            $list->setOrder($this->_getParam("dir"));
        } else {
            $list->setOrderKey("date");
            $list->setOrder("DESC");
        }

        $conditions = array();
        if($this->_getParam("filter")) {
            $conditions[] = "(`title` LIKE " . $list->quote("%".$this->_getParam("filter")."%") . " OR `description` LIKE " . $list->quote("%".$this->_getParam("filter")."%") . " OR `type` LIKE " . $list->quote("%".$this->_getParam("filter")."%") . ")";
        }

        if($this->_getParam("cid") && $this->_getParam("ctype")) {
            $conditions[] = "(cid = " . $list->quote($this->_getParam("cid")) . " AND ctype = " . $list->quote($this->_getParam("ctype")) . ")";
        }

        if(!empty($conditions)) {
            $list->setCondition(implode(" AND ", $conditions));
        }

        $list->load();

        $notes = array();

        foreach ($list->getNotes() as $note) {

            $cpath = "";
            if($note->getCid() && $note->getCtype()) {
                if($element = Element_Service::getElementById($note->getCtype(), $note->getCid())) {
                    $cpath = $element->getFullpath();
                }
            }

            $e = array(
                "id" => $note->getId(),
                "type" => $note->getType(),
                "cid" => $note->getCid(),
                "ctype" => $note->getCtype(),
                "cpath" => $cpath,
                "date" => $note->getDate(),
                "title" => $note->getTitle(),
                "description" => $note->getDescription()
            );

            // prepare key-values
            $keyValues = array();
            if(is_array($note->getData())) {
                foreach ($note->getData() as $name => $d) {

                    $type = $d["type"];
                    $data = $d["data"];

                    if($type == "document" || $type == "object" || $type == "asset") {
                        if($d["data"] instanceof Element_Interface) {
                            $data = array(
                                "id" => $d["data"]->getId(),
                                "path" => $d["data"]->getFullpath(),
                                "type" => $d["data"]->getType()
                            );
                        }
                    } else if ($type == "date") {
                        if($d["data"] instanceof Zend_Date) {
                            $data = $d["data"]->getTimestamp();
                        }
                    }

                    $keyValue = array(
                        "type" => $type,
                        "name" => $name,
                        "data" => $data
                    );

                    $keyValues[] = $keyValue;
                }
            }

            $e["data"] = $keyValues;


            // prepare user data
            if($note->getUser()) {
                $user = User::getById($note->getUser());
                if($user) {
                    $e["user"] = array(
                        "id" => $user->getId(),
                        "name" => $user->getName()
                    );
                } else {
                    $e["user"] = "";
                }
            }

            $notes[] = $e;
        }

        $this->_helper->json(array(
            "data" => $notes,
            "success" => true,
            "total" => $list->getTotalCount()
        ));
    }

    public function noteAddAction() {

        $note = new Element_Note();
        $note->setCid((int) $this->_getParam("cid"));
        $note->setCtype($this->_getParam("ctype"));
        $note->setDate(time());
        $note->setTitle($this->_getParam("title"));
        $note->setDescription($this->_getParam("description"));
        $note->setType($this->_getParam("type"));
        $note->save();

        $this->_helper->json(array(
            "success" => true
        ));
    }


}
