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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

use Pimcore\Model\Element;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Pimcore\Model;

class Admin_ElementController extends \Pimcore\Controller\Action\Admin {
    
    public function lockElementAction()
    {
        Element\Editlock::lock($this->getParam("id"), $this->getParam("type"));
        exit;
    }

    public function unlockElementAction()
    {
        Element\Editlock::unlock($this->getParam("id"), $this->getParam("type"));
        exit;
    }

    public function getIdPathAction() {
        $id = (int) $this->getParam("id");
        $type = $this->getParam("type");

        $response = array("success" => true);

        if($element = Element\Service::getElementById($type, $id)) {
            $response["idPath"] = Element\Service::getIdPath($element);
        }

        $this->_helper->json($response);
    }

    /**
     * Returns the element data denoted by the given type and ID or path.
     */
    public function getSubtypeAction () {

        $idOrPath = trim($this->getParam("id"));
        $type = $this->getParam("type");
        if (is_numeric($idOrPath)) {
            $el = Element\Service::getElementById($type, (int) $idOrPath);
        } else {
            if ($type == "document") {
                $el = Document\Service::getByUrl($idOrPath);
            } else {
                $el = Element\Service::getElementByPath($type, $idOrPath);
            }
        }

        if($el) {
            if($el instanceof Asset || $el instanceof Document) {
                $subtype = $el->getType();
            } else if($el instanceof Object\Concrete) {
                $subtype = $el->getClassName();
            } else if ($el instanceof Object\Folder) {
                $subtype = "folder";
            }

            $this->_helper->json(array(
                "subtype" => $subtype,
                "id" => $el->getId(),
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

        $this->checkPermission("notes_events");

        $list = new Element\Note\Listing();

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
                if($element = Element\Service::getElementById($note->getCtype(), $note->getCid())) {
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
                        if($d["data"] instanceof Element\ElementInterface) {
                            $data = array(
                                "id" => $d["data"]->getId(),
                                "path" => $d["data"]->getFullpath(),
                                "type" => $d["data"]->getType()
                            );
                        }
                    } else if ($type == "date") {
                        if($d["data"] instanceof \Zend_Date) {
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
                $user = Model\User::getById($note->getUser());
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

        $this->checkPermission("notes_events");

        $note = new Element\Note();
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

    public function findUsagesAction() {

        if($this->getParam("id")) {
            $element = Element\Service::getElementById($this->getParam("type"), $this->getParam("id"));
        } else if ($this->getParam("path")) {
            $element = Element\Service::getElementByPath($this->getParam("type"), $this->getParam("path"));
        }

        $results = array();
        $success = false;

        if($element) {
            $elements = $element->getDependencies()->getRequiredBy();
            foreach ($elements as $el) {
                $item = Element\Service::getElementById($el["type"], $el["id"]);
                if($item instanceof Element\ElementInterface) {
                    $el["path"] = $item->getFullpath();
                    $results[] = $el;
                }
            }
            $success = true;
        }

        $this->_helper->json(array(
            "data" => $results,
            "success" => $success
        ));
    }

    public function replaceAssignmentsAction() {

        $success = false;
        $message = "";
        $element = Element\Service::getElementById($this->getParam("type"), $this->getParam("id"));
        $sourceEl = Element\Service::getElementById($this->getParam("sourceType"), $this->getParam("sourceId"));
        $targetEl = Element\Service::getElementById($this->getParam("targetType"), $this->getParam("targetId"));

        if($element && $sourceEl && $targetEl
            && $this->getParam("sourceType") == $this->getParam("targetType")
            && $sourceEl->getType() == $targetEl->getType()
        ) {

            $rewriteConfig = array(
                $this->getParam("sourceType") => array(
                    $sourceEl->getId() => $targetEl->getId()
                )
            );

            if($element instanceof Document) {
                $element = Document\Service::rewriteIds($element, $rewriteConfig);
            } else if ($element instanceof Object\AbstractObject) {
                $element = Object\Service::rewriteIds($element, $rewriteConfig);
            } else if ($element instanceof Asset) {
                $element = Asset\Service::rewriteIds($element, $rewriteConfig);
            }

            $element->setUserModification($this->getUser()->getId());
            $element->save();

            $success = true;
        } else {
            $message = "source-type and target-type do not match";
        }

        $this->_helper->json(array(
            "success" => $success,
            "message" => $message
        ));
    }

    public function unlockPropagateAction() {

        $success = false;

        $element = Element\Service::getElementById($this->getParam("type"), $this->getParam("id"));
        if($element) {
            $element->unlockPropagate();
            $success = true;
        }

        $this->_helper->json(array(
            "success" => $success
        ));
    }
}
