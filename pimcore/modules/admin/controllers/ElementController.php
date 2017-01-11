<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\Model\Element;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Pimcore\Model\Version;
use Pimcore\Model;
use Pimcore\Logger;

class Admin_ElementController extends \Pimcore\Controller\Action\Admin
{
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

    public function getIdPathAction()
    {
        $id = (int) $this->getParam("id");
        $type = $this->getParam("type");

        $response = ["success" => true];

        if ($element = Element\Service::getElementById($type, $id)) {
            $response["idPath"] = Element\Service::getIdPath($element);
        }

        $this->_helper->json($response);
    }

    /**
     * Returns the element data denoted by the given type and ID or path.
     */
    public function getSubtypeAction()
    {
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

        if ($el) {
            if ($el instanceof Asset || $el instanceof Document) {
                $subtype = $el->getType();
            } elseif ($el instanceof Object\Concrete) {
                $subtype = $el->getClassName();
            } elseif ($el instanceof Object\Folder) {
                $subtype = "folder";
            }

            $this->_helper->json([
                "subtype" => $subtype,
                "id" => $el->getId(),
                "type" => $type,
                "success" => true
            ]);
        } else {
            $this->_helper->json([
                "success" => false
            ]);
        }
    }

    public function noteListAction()
    {
        $this->checkPermission("notes_events");

        $list = new Element\Note\Listing();

        $list->setLimit($this->getParam("limit"));
        $list->setOffset($this->getParam("start"));

        $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
        if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
            $list->setOrderKey($sortingSettings['orderKey']);
            $list->setOrder($sortingSettings['order']);
        } else {
            $list->setOrderKey(["date", "id"]);
            $list->setOrder(["DESC", "DESC"]);
        }

        $conditions = [];
        if ($this->getParam("filter")) {
            $conditions[] = "(`title` LIKE " . $list->quote("%".$this->getParam("filter")."%") . " OR `description` LIKE " . $list->quote("%".$this->getParam("filter")."%") . " OR `type` LIKE " . $list->quote("%".$this->getParam("filter")."%") . ")";
        }

        if ($this->getParam("cid") && $this->getParam("ctype")) {
            $conditions[] = "(cid = " . $list->quote($this->getParam("cid")) . " AND ctype = " . $list->quote($this->getParam("ctype")) . ")";
        }

        if (!empty($conditions)) {
            $list->setCondition(implode(" AND ", $conditions));
        }

        $list->load();

        $notes = [];

        foreach ($list->getNotes() as $note) {
            $cpath = "";
            if ($note->getCid() && $note->getCtype()) {
                if ($element = Element\Service::getElementById($note->getCtype(), $note->getCid())) {
                    $cpath = $element->getRealFullPath();
                }
            }

            $e = [
                "id" => $note->getId(),
                "type" => $note->getType(),
                "cid" => $note->getCid(),
                "ctype" => $note->getCtype(),
                "cpath" => $cpath,
                "date" => $note->getDate(),
                "title" => $note->getTitle(),
                "description" => $note->getDescription()
            ];

            // prepare key-values
            $keyValues = [];
            if (is_array($note->getData())) {
                foreach ($note->getData() as $name => $d) {
                    $type = $d["type"];
                    $data = $d["data"];

                    if ($type == "document" || $type == "object" || $type == "asset") {
                        if ($d["data"] instanceof Element\ElementInterface) {
                            $data = [
                                "id" => $d["data"]->getId(),
                                "path" => $d["data"]->getRealFullPath(),
                                "type" => $d["data"]->getType()
                            ];
                        }
                    } elseif ($type == "date") {
                        if (is_object($d["data"])) {
                            $data = $d["data"]->getTimestamp();
                        }
                    }

                    $keyValue = [
                        "type" => $type,
                        "name" => $name,
                        "data" => $data
                    ];

                    $keyValues[] = $keyValue;
                }
            }

            $e["data"] = $keyValues;


            // prepare user data
            if ($note->getUser()) {
                $user = Model\User::getById($note->getUser());
                if ($user) {
                    $e["user"] = [
                        "id" => $user->getId(),
                        "name" => $user->getName()
                    ];
                } else {
                    $e["user"] = "";
                }
            }

            $notes[] = $e;
        }

        $this->_helper->json([
            "data" => $notes,
            "success" => true,
            "total" => $list->getTotalCount()
        ]);
    }

    public function noteAddAction()
    {
        $this->checkPermission("notes_events");

        $note = new Element\Note();
        $note->setCid((int) $this->getParam("cid"));
        $note->setCtype($this->getParam("ctype"));
        $note->setDate(time());
        $note->setTitle($this->getParam("title"));
        $note->setDescription($this->getParam("description"));
        $note->setType($this->getParam("type"));
        $note->save();

        $this->_helper->json([
            "success" => true
        ]);
    }

    public function findUsagesAction()
    {
        if ($this->getParam("id")) {
            $element = Element\Service::getElementById($this->getParam("type"), $this->getParam("id"));
        } elseif ($this->getParam("path")) {
            $element = Element\Service::getElementByPath($this->getParam("type"), $this->getParam("path"));
        }

        $results = [];
        $success = false;

        if ($element) {
            $elements = $element->getDependencies()->getRequiredBy();
            foreach ($elements as $el) {
                $item = Element\Service::getElementById($el["type"], $el["id"]);
                if ($item instanceof Element\ElementInterface) {
                    $el["path"] = $item->getRealFullPath();
                    $results[] = $el;
                }
            }
            $success = true;
        }

        $this->_helper->json([
            "data" => $results,
            "success" => $success
        ]);
    }

    public function replaceAssignmentsAction()
    {
        $success = false;
        $message = "";
        $element = Element\Service::getElementById($this->getParam("type"), $this->getParam("id"));
        $sourceEl = Element\Service::getElementById($this->getParam("sourceType"), $this->getParam("sourceId"));
        $targetEl = Element\Service::getElementById($this->getParam("targetType"), $this->getParam("targetId"));

        if ($element && $sourceEl && $targetEl
            && $this->getParam("sourceType") == $this->getParam("targetType")
            && $sourceEl->getType() == $targetEl->getType()
        ) {
            $rewriteConfig = [
                $this->getParam("sourceType") => [
                    $sourceEl->getId() => $targetEl->getId()
                ]
            ];

            if ($element instanceof Document) {
                $element = Document\Service::rewriteIds($element, $rewriteConfig);
            } elseif ($element instanceof Object\AbstractObject) {
                $element = Object\Service::rewriteIds($element, $rewriteConfig);
            } elseif ($element instanceof Asset) {
                $element = Asset\Service::rewriteIds($element, $rewriteConfig);
            }

            $element->setUserModification($this->getUser()->getId());
            $element->save();

            $success = true;
        } else {
            $message = "source-type and target-type do not match";
        }

        $this->_helper->json([
            "success" => $success,
            "message" => $message
        ]);
    }

    public function unlockPropagateAction()
    {
        $success = false;

        $element = Element\Service::getElementById($this->getParam("type"), $this->getParam("id"));
        if ($element) {
            $element->unlockPropagate();
            $success = true;
        }

        $this->_helper->json([
            "success" => $success
        ]);
    }

    public function typePathAction()
    {
        $id = $this->getParam("id");
        $type = $this->getParam("type");
        $data = [];

        if ($type == "asset") {
            $element = Asset::getById($id);
        } elseif ($type == "document") {
            $element = Document::getById($id);
            $data["index"] = $element->getIndex();
        } else {
            $element = Object::getById($id);
        }
        $typePath = Element\Service::getTypePath($element);

        $data["success"] = true;
        $data["idPath"] = Element\Service::getIdPath($element);
        $data["typePath"] = $typePath;
        $data["fullpath"] = $element->getRealFullPath();


        $this->_helper->json($data);
    }


    public function versionUpdateAction()
    {
        $data = \Zend_Json::decode($this->getParam("data"));

        $version = Version::getById($data["id"]);
        $version->setPublic($data["public"]);
        $version->setNote($data["note"]);
        $version->save();

        $this->_helper->json(["success" => true]);
    }

    public function getNicePathAction()
    {
        $source = \Zend_Json::decode($this->getParam("source"));
        if ($source["type"] != "object") {
            throw new \Exception("currently only objects as source elements are supported");
        }

        $result = [];

        $id = $source["id"];
        $source = Object\Concrete::getById($id);

        if ($this->getParam("context")) {
            $context = \Zend_Json::decode($this->getParam("context"));
        } else {
            $context = [];
        }

        $ownerType = $context["containerType"];
        $fieldname = $context["fieldname"];

        if ($ownerType == "object") {
            $fd = $source->getClass()->getFieldDefinition($fieldname);
        } elseif ($ownerType == "localizedfield") {
            $fd = $source->getClass()->getFieldDefinition("localizedfields")->getFieldDefinition($fieldname);
        } elseif ($ownerType == "objectbrick") {
            $fdBrick = Object\Objectbrick\Definition::getByKey($context["containerKey"]);
            $fd = $fdBrick->getFieldDefinition($fieldname);
        } elseif ($ownerType == "fieldcollection") {
            $containerKey = $context["containerKey"];
            $fdCollection = Object\Fieldcollection\Definition::getByKey($containerKey);
            if ($context["subContainerType"] == "localizedfield") {
                $fdLocalizedFields = $fdCollection->getFieldDefinition("localizedfields");
                $fd = $fdLocalizedFields->getFieldDefinition($fieldname);
            } else {
                $fd = $fdCollection->getFieldDefinition($fieldname);
            }
        }


        if (method_exists($fd, "getPathFormatterClass")) {
            $formatterClass = $fd->getPathFormatterClass();
            if (Pimcore\Tool::classExists($formatterClass)) {
                $targets = \Zend_Json::decode($this->getParam("targets"));

                $result = call_user_func($formatterClass . "::formatPath", $result, $source, $targets,
                    [
                        "fd" => $fd,
                        "context" => $context
                    ]);
            } else {
                Logger::error("Formatter Class does not exist: " . $formatterClass);
            }
        }

        $this->_helper->json(["success" => true, "data" => $result]);
    }
}
