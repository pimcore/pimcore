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

use Pimcore\Db;
use Pimcore\Model\Object;
use Pimcore\Model\Object\KeyValue;
use Pimcore\Logger;

class Admin_KeyValueController extends \Pimcore\Controller\Action\Admin
{
    public function deletegroupAction()
    {
        $id = $this->getParam("id");

        $config = KeyValue\GroupConfig::getById($id);
        $config->delete();

        $this->_helper->json(["success" => true]);
    }

    public function addgroupAction()
    {
        $name = $this->getParam("name");
        $alreadyExist = false;
        $config = KeyValue\GroupConfig::getByName($name);


        if (!$config) {
            $config = new KeyValue\GroupConfig();
            $config->setName($name);
            $config->save();
        }

        $this->_helper->json(["success" => !$alreadyExist, "id" => $config->getName()]);
    }

    public function getgroupAction()
    {
        $id = $this->getParam("id");
        $config = KeyValue\GroupConfig::getByName($id);

        $data = [
            "id" => $id,
            "name" => $config->getName(),
            "description" => $config->getDescription()
        ];

        $this->_helper->json($data);
    }

    public function groupsAction()
    {
        if ($this->getParam("data")) {
            $dataParam = $this->getParam("data");
            $data = \Zend_Json::decode($dataParam);

            $id = $data["id"];
            $config = KeyValue\GroupConfig::getById($id);

            foreach ($data as $key => $value) {
                if ($key != "id") {
                    $setter = "set" . $key;
                    $config->$setter($value);
                }
            }

            $config->save();

            $this->_helper->json(["success" => true, "data" => $config]);
        } else {
            $start = 0;
            $limit = 15;
            $orderKey = "name";
            $order = "ASC";

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey']) {
                $orderKey = $sortingSettings['orderKey'];
            }
            if ($sortingSettings['order']) {
                $order  = $sortingSettings['order'];
            }

            if ($this->getParam("limit")) {
                $limit = $this->getParam("limit");
            }
            if ($this->getParam("start")) {
                $start = $this->getParam("start");
            }

            if ($this->getParam("overrideSort") == "true") {
                $orderKey = "id";
                $order = "DESC";
            }

            $list = new KeyValue\GroupConfig\Listing();

            $list->setLimit($limit);
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);


            if ($this->getParam("filter")) {
                $condition = "";
                $filterString = $this->getParam("filter");
                $filters = json_decode($filterString);

                $db = Db::get();
                $count = 0;

                foreach ($filters as $f) {
                    if ($count > 0) {
                        $condition .= " OR ";
                    }
                    $count++;

                    if (\Pimcore\Tool\Admin::isExtJS6()) {
                        $condition .= $db->getQuoteIdentifierSymbol() . $f->property . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                    } else {
                        $condition .= $db->getQuoteIdentifierSymbol() . $f->field . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                    }
                }
                $list->setCondition($condition);
            }

            $list->load();
            $configList = $list->getList();

            $rootElement = [];

            $data = [];
            foreach ($configList as $config) {
                $name = $config->getName();
                if (!$name) {
                    $name = "EMPTY";
                }
                $item = [
                    "id" => $config->getId(),
                    "name" => $name,
                    "description" => $config->getDescription()
                ];
                if ($config->getCreationDate()) {
                    $item["creationDate"] = $config->getCreationDate();
                }

                if ($config->getModificationDate()) {
                    $item["modificationDate"] = $config->getModificationDate();
                }


                $data[] = $item;
            }
            $rootElement["data"] = $data;
            $rootElement["success"] = true;
            $rootElement["total"] = $list->getTotalCount();

            return $this->_helper->json($rootElement);
        }
    }


    public function propertiesAction()
    {
        if ($this->getParam("data")) {
            $dataParam = $this->getParam("data");
            $data = \Zend_Json::decode($dataParam);

            $id = $data["id"];
            $config = KeyValue\KeyConfig::getById($id);

            foreach ($data as $key => $value) {
                if ($key != "id") {
                    $setter = "set" . $key;
                    if (method_exists($config, $setter)) {
                        $config->$setter($value);
                    }
                }
            }

            $config->save();
            $item = $this->getConfigItem($config);

            $this->_helper->json(["success" => true, "data" => $item]);
        } else {
            $start = 0;
            $limit = 15;
            $orderKey = "name";
            $order = "ASC";

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey']) {
                $orderKey = $sortingSettings['orderKey'];
            }
            if ($sortingSettings['order']) {
                $order  = $sortingSettings['order'];
            }

            if ($this->getParam("overrideSort") == "true") {
                $orderKey = "id";
                $order = "DESC";
            }

            if ($this->getParam("limit")) {
                $limit = $this->getParam("limit");
            }
            if ($this->getParam("start")) {
                $start = $this->getParam("start");
            }

            $list = new KeyValue\KeyConfig\Listing();

            if ($limit > 0) {
                $list->setLimit($limit);
            }
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            if ($this->getParam("filter")) {
                $db = Db::get();
                $condition = "";
                $filterString = $this->getParam("filter");
                $filters = json_decode($filterString);

                $count = 0;

                foreach ($filters as $f) {
                    if ($count > 0) {
                        $condition .= " OR ";
                    }
                    $count++;
                    if (\Pimcore\Tool\Admin::isExtJS6()) {
                        $condition .= $db->getQuoteIdentifierSymbol() . $f->property . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                    } else {
                        $condition .= $db->getQuoteIdentifierSymbol() . $f->field . $db->getQuoteIdentifierSymbol() . " LIKE " . $db->quote("%" . $f->value . "%");
                    }
                }


                $list->setCondition($condition);
            }

            if ($this->getParam("groupIds") || $this->getParam("keyIds")) {
                $db = Db::get();

                if ($this->getParam("groupIds")) {
                    $ids = \Zend_Json::decode($this->getParam("groupIds"));
                    $col = "group";
                } else {
                    $ids = \Zend_Json::decode($this->getParam("keyIds"));
                    $col = "id";
                }

                $condition = $db->getQuoteIdentifierSymbol() . $col . $db->getQuoteIdentifierSymbol() . " IN (";
                $count = 0;
                foreach ($ids as $theId) {
                    if ($count > 0) {
                        $condition .= ",";
                    }
                    $condition .= $theId;
                    $count++;
                }

                $condition .= ")";
                $list->setCondition($condition);
            }

            $list->load();
            $configList = $list->getList();

            $rootElement = [];

            $data = [];
            foreach ($configList as $config) {
                $item = $this->getConfigItem($config);
                $data[] = $item;
            }
            $rootElement["data"] = $data;
            $rootElement["success"] = true;
            $rootElement["total"] = $list->getTotalCount();

            return $this->_helper->json($rootElement);
        }
    }

    /**
     * @param $config
     * @return array
     */
    protected function getConfigItem($config)
    {
        $name = $config->getName();
        if (!$name) {
            $name = "EMPTY";
        }

        $groupDescription = null;
        if ($config->getGroup()) {
            try {
                $group = KeyValue\GroupConfig::getById($config->getGroup());
                $groupDescription = $group->getDescription();
                $groupName = $group->getName();
            } catch (\Exception $e) {
            }

            if (empty($groupDescription)) {
                $groupDescription = $group->getName();
            }
        }

        $item = [
            "id" => $config->getId(),
            "name" => $name,
            "description" => $config->getDescription(),
            "type" => $config->getType(),
            "unit" => $config->getUnit(),
            "possiblevalues" => $config->getPossibleValues(),
            "group" => $config->getGroup(),
            "groupdescription" => $groupDescription,
            "groupName" => $groupName,
            "translator" => $config->getTranslator(),
            "mandatory" => $config->getMandatory()
        ];

        if ($config->getCreationDate()) {
            $item["creationDate"] = $config->getCreationDate();
        }

        if ($config->getModificationDate()) {
            $item["modificationDate"] = $config->getModificationDate();
        }

        return $item;
    }

    public function addpropertyAction()
    {
        $name = $this->getParam("name");
        $alreadyExist = false;

        if (!$alreadyExist) {
            $config = new KeyValue\KeyConfig();
            $config->setName($name);
            $config->setType("text");
            $config->save();
        }

        $this->_helper->json(["success" => !$alreadyExist, "id" => $config->getName()]);
    }

    public function deletepropertyAction()
    {
        $id = $this->getParam("id");

        $config = KeyValue\KeyConfig::getById($id);
        $config->delete();

        $this->_helper->json(["success" => true]);
    }

    /**
     * Imports group and key config from XML format.
     */
    public function importAction()
    {
        $tmpFile = file_get_contents($_FILES["Filedata"]["tmp_name"]);
        $conf = new \Zend_Config_Xml($tmpFile);
        $importData = $conf->toArray();
        Object\KeyValue\Helper::import($importData);

        $this->_helper->json(["success" => true], false);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $this->getResponse()->setHeader("Content-Type", "text/html", true);
    }

    /**
     * Exports group and key config into XML format.
     */
    public function exportAction()
    {
        $this->removeViewRenderer();

        $data = KeyValue\Helper::export();
        header("Content-type: application/xml");
        header("Content-Disposition: attachment; filename=\"keyvalue_export.xml\"");
        echo $data;
    }


    public function testmagicAction()
    {
        $obj = Object\Concrete::getById(61071);
        $pairs = $obj->getKeyValuePairs();

        $value = $pairs->getab123();
        Logger::debug("value=" . $value);

        $pairs->setab123("new valuexyz");
        $pairs->setdddd("dvalue");
        $obj->save();
    }

    public function getTranslatorConfigsAction()
    {
        $list = new KeyValue\TranslatorConfig\Listing();
        $list->load();
        $items = $list->getList();
        $result = [];
        foreach ($items as $item) {
            $result[] = [
                "id" => $item->getId(),
                "name" => $item->getName(),
                "translator" => $item->getTranslator()
            ];
        }

        $this->_helper->json(["configurations" => $result]);
    }

    public function translateAction()
    {
        $success = false;
        $keyId = $this->getParam("keyId");
        $objectId = $this->getParam("objectId");
        $recordId = $this->getParam("recordId");
        $text = $this->getParam("text");
        $translatedValue = $text;

        try {
            $keyConfig = KeyValue\KeyConfig::getById($keyId);
            $translatorID = $keyConfig->getTranslator();
            $translatorConfig = KeyValue\TranslatorConfig::getById($translatorID);
            $className = $translatorConfig->getTranslator();
            if (\Pimcore\Tool::classExists($className)) {
                $translator = new $className();
                $translatedValue = $translator->translate($text);
                if (!$translatedValue) {
                    $translatedValue = $text;
                }
            }

            $this->_helper->json(["success" => true,
                "keyId" => $this->getParam("keyId"),
                "text" => $text,
                "translated" => $translatedValue,
                "recordId" => $recordId
            ]);
        } catch (\Exception $e) {
        }

        $this->_helper->json(["success" => $success]);
    }
}
