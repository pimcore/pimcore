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
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\KeyValue;

use Pimcore\Model;
use Pimcore\Model\Object;

class Helper {

    /** Returns the group/key config as XML.
     * @return mixed
     */
    public static function export() {
        $xml = new \SimpleXMLElement('<xml/>');

        $groupConfigList = new Object\KeyValue\GroupConfig\Listing();
        $groupConfigList->load();
        $groupConfigItems = $groupConfigList->getList();

        $groups = $xml->addChild('groups');

        foreach ($groupConfigItems as $item) {
            $group = $groups->addChild('group');
            $group->addChild("id", $item->getId());
            $group->addChild("name", $item->getName());
            $group->addChild("description", $item->getDescription());
        }

        $keyConfigList = new Object\KeyValue\KeyConfig\Listing();
        $keyConfigList->load();
        $keyConfigItems = $keyConfigList->getList();

        $keys = $xml->addChild('keys');

        foreach ($keyConfigItems as $item) {
            $key = $keys->addChild('key');
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
    public static function import($config) {
        if (is_array($config["groups"])) {
            $groups = $config["groups"]["group"];
            if (!isset($groups[0])) {
                $groups = array($groups);
            }
            $groupIdMapping = array();

            foreach ($groups as $groupConfig) {
                $name = $groupConfig["name"];
                $group = Object\KeyValue\GroupConfig::getByName($name);
                if (!$group) {
                    $group = new Object\KeyValue\GroupConfig();
                    $group->setName($name);
                }
                $group->setDescription($groupConfig["description"]);
                $group->save();
                // mapping of remote id to local id
                $groupIdMapping[$groupConfig["id"]] = $group->getId();
            }
        }

        if (is_array($config["keys"])) {
            $keys = $config["keys"]["key"];
            if (!isset($keys[0])) {
                $keys = array($keys);
            }
            foreach ($keys as $keyConfig) {
                $name = $keyConfig["name"];
                $key = Object\KeyValue\KeyConfig::getByName($name);
                if (!$key) {
                    $key = new Object\KeyValue\KeyConfig();
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

}
