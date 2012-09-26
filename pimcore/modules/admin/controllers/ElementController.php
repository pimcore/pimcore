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
        Element_Editlock::lock($this->getParam("id"), $this->getParam("type"));
        exit;
    }

    public function unlockElementAction()
    {
        Element_Editlock::unlock($this->getParam("id"), $this->getParam("type"));
        exit;
    }

    public function getSubtypeAction () {

        $id = (int) $this->getParam("id");
        $type = $this->getParam("type");
        $el = Element_Service::getElementById($type, $id);

        if($el) {
            if($el instanceof Asset || $el instanceof Document) {
                $subtype = $el->getType();
            } else if($el instanceof Object_Concrete) {
                $subtype = $el->geto_className();
            } else if ($el instanceof Object_Folder) {
                $subtype = "folder";
            }

            $this->_helper->json(array(
                "subtype" => $subtype,
                "id" => $id,
                "type" => $type,
                "success" => true
            ));
        } else {
            $this->_helper->json(array(
                "success" => false
            ));
        }
    }

    public function noteListAction () {

        $list = new Element_Note_List();

        $list->setLimit($this->getParam("limit"));
        $list->setOffset($this->getParam("start"));

        if($this->getParam("sort")) {
            $list->setOrderKey($this->getParam("sort"));
            $list->setOrder($this->getParam("dir"));
        } else {
            $list->setOrderKey("date");
            $list->setOrder("DESC");
        }

        $conditions = array();
        if($this->getParam("filter")) {
            $conditions[] = "(`title` LIKE " . $list->quote("%".$this->getParam("filter")."%") . " OR `description` LIKE " . $list->quote("%".$this->getParam("filter")."%") . " OR `type` LIKE " . $list->quote("%".$this->getParam("filter")."%") . ")";
        }

        if($this->getParam("cid") && $this->getParam("ctype")) {
            $conditions[] = "(cid = " . $list->quote($this->getParam("cid")) . " AND ctype = " . $list->quote($this->getParam("ctype")) . ")";
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
        $note->setCid((int) $this->getParam("cid"));
        $note->setCtype($this->getParam("ctype"));
        $note->setDate(time());
        $note->setTitle($this->getParam("title"));
        $note->setDescription($this->getParam("description"));
        $note->setType($this->getParam("type"));
        $note->save();

        $this->_helper->json(array(
            "success" => true
        ));
    }


}
