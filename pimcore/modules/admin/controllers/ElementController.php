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


    public function eventsListAction () {

        $list = new Element_Event_List();

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

        $events = array();

        foreach ($list->getEvents() as $event) {

            $cpath = "";
            if($event->getCid() && $event->getCtype()) {
                if($element = Element_Service::getElementById($event->getCtype(), $event->getCid())) {
                    $cpath = $element->getFullpath();
                }
            }

            $e = array(
                "id" => $event->getId(),
                "type" => $event->getType(),
                "cid" => $event->getCid(),
                "ctype" => $event->getCtype(),
                "cpath" => $cpath,
                "date" => $event->getDate(),
                "title" => $event->getTitle(),
                "description" => $event->getDescription()
            );

            // prepare key-values
            $keyValues = array();
            if(is_array($event->getData())) {
                foreach ($event->getData() as $name => $d) {

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
            if($event->getUser()) {
                $user = User::getById($event->getUser());
                if($user) {
                    $e["user"] = array(
                        "id" => $user->getId(),
                        "name" => $user->getName()
                    );
                } else {
                    $e["user"] = "";
                }
            }

            $events[] = $e;
        }

        $this->_helper->json(array(
            "data" => $events,
            "success" => true,
            "total" => $list->getTotalCount()
        ));
    }

    public function eventsAddAction() {

        $event = new Element_Event();
        $event->setCid((int) $this->_getParam("cid"));
        $event->setCtype($this->_getParam("ctype"));
        $event->setDate(time());
        $event->setTitle($this->_getParam("title"));
        $event->setDescription($this->_getParam("description"));
        $event->setType($this->_getParam("type"));
        $event->save();

        $this->_helper->json(array(
            "success" => true
        ));
    }


}
