<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jaichhorn
 * Date: 11.02.13
 * Time: 16:11
 * To change this template use File | Settings | File Templates.
 */
class Object_KeyValue_Helper {


    /** Returns the group/key config as XML.
     * @return mixed
     */
    public function export() {
        $xml = new SimpleXMLElement('<xml/>');

        $list = new Object_KeyValue_GroupConfig_List();
        $list->load();
        $items = $list->getList();

        $groups = $xml->addChild('groups');

        foreach ($items as $item) {
            $group = $groups->addChild('group');
            $group->addChild("id", $item->getId());
            $group->addChild("name", $item->getName());
            $group->addChild("description", $item->getDescription());
        }

        $list = new Object_KeyValue_KeyConfig_List();
        $list->load();
        $items = $list->getList();

        $keys = $xml->addChild('keys');

        foreach ($items as $item) {
            $key= $keys->addChild('key');
            $id = $key->addChild('id', $item->getId());
            $name = $key->addChild('name', $item->getName());
            $description = $key->addChild('description', $item->getDescription());
            $type = $key->addChild('type', $item->getType());
            $unit = $key->addChild('unit', $item->getUnit());
            $group = $key->addChild('group', $item->getGroup());
            $possiblevalues = $key->addChild('possiblevalues', $item->getPossibleValues());
        }

        return $xml->asXML();
    }

    /** Imports the group/key config from XML.
     * @param $config
     */
    public function import($config) {
        $groups = $config["groups"]["group"];
        $groupIdMapping = array();

        foreach ($groups as $groupConfig) {
            $name = $groupConfig["name"];
            $group = Object_KeyValue_GroupConfig::getByName($name);
            if (!$group) {
                $group = new Object_KeyValue_GroupConfig();
                $group->setName($name);
            }
            $group->setDescription($groupConfig["description"]);
            $group->save();
            // mapping of remote id to local id
            $groupIdMapping[$groupConfig["id"]] = $group->getId();
        }

        $keys = $config["keys"]["key"];
        foreach ($keys as $keyConfig) {
            $name = $keyConfig["name"];
            $key = Object_KeyValue_KeyConfig::getByName($name);
            if (!$key) {
                $key = new Object_KeyValue_KeyConfig();
                $key->setName($name);
            }

            $key->setDescription($keyConfig["description"]);
            $key->setType($keyConfig["type"]);
            if (!empty($keyConfig["unit"])) {
                $key->setUnit($keyConfig["unit"]);
            }
            if (!empty($keyConfig["possiblevalues"])) {
                $key->setPossibleValues($keyConfig["possiblevalues"]);
            }

            $originalGroupId = $keyConfig["group"];
            if (!empty($originalGroupId)) {
                $mappedGroupId = $groupIdMapping[$originalGroupId];
                $key->setGroup($mappedGroupId);
            }
            $key->save();
        }
    }
}
