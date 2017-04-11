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
 * @category   Pimcore
 * @package    Webservice
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Webservice;

use Pimcore\Model\Webservice;
use Pimcore\Model\Element;
use Pimcore\Model;

abstract class Data
{

    /**
     * @param $object
     * @param null $options
     * @throws \Exception
     */
    public function map($object, $options = null)
    {
        $keys = get_object_vars($this);
        $blockedKeys = ["childs","fieldDefinitions"];
        foreach ($keys as $key => $value) {
            $method = "get" . $key;
            if (method_exists($object, $method) && !in_array($key, $blockedKeys)) {
                if ($object->$method()) {
                    $this->$key = $object->$method();

                    // check for a pimcore data type
                    if ($this->$key instanceof Element\ElementInterface) {
                        $this->$key = $this->$key->getId();
                    }

                    // if the value is an object or array call the mapper again for the value
                    if (is_object($this->$key) || is_array($this->$key)) {
                        $type = "out";
                        if (strpos(get_class($this), "_In")!==false) {
                            $type = "in";
                        }
                        $className = Webservice\Data\Mapper::findWebserviceClass($this->$key, "out");
                        $this->$key = Webservice\Data\Mapper::map($this->$key, $className, $type);
                    }
                }
            }
        }
    }

    /**
     * @param $value
     * @return array
     */
    private function mapProperties($value)
    {
        if (is_array($value)) {
            $result = [];

            foreach ($value as $property) {
                if ($property instanceof \stdClass) {
                    $newProperty = new Model\Property();
                    $vars = get_object_vars($property);
                    foreach ($vars as $varName => $varValue) {
                        $newProperty->$varName = $property->$varName;
                    }
                    $result[] = $newProperty;
                } else {
                    $result[] = $property;
                }
            }
            $value = $result;
        }

        return $value;
    }

    /**
     * @param $object
     * @param bool $disableMappingExceptions
     * @param null $idMapper
     * @throws \Exception
     */
    public function reverseMap($object, $disableMappingExceptions = false, $idMapper = null)
    {
        $keys = get_object_vars($this);
        foreach ($keys as $key => $value) {
            $method = "set" . $key;
            if (method_exists($object, $method)) {
                if ($object instanceof Element\ElementInterface && $key == "properties") {
                    $value = $this->mapProperties($value);
                }
                $object->$method($value);
            }
        }

        if ($object instanceof Element\ElementInterface) {
            // Classes do not have properties
            $object->setProperties(null);
        }

        if (is_array($this->properties)) {
            foreach ($this->properties as $propertyWs) {
                $propertyWs = (array) $propertyWs;

                $dat = $propertyWs["data"];
                $type = $propertyWs["type"];
                if (in_array($type, ["object", "document", "asset"])) {
                    $id = $propertyWs["data"];
                    $type = $propertyWs["type"];
                    $dat = null;
                    if ($idMapper) {
                        $id = $idMapper->getMappedId($type, $id);
                    }

                    if ($id) {
                        $dat = Element\Service::getElementById($type, $id);
                    }

                    if (is_numeric($propertyWs["data"]) and !$dat) {
                        if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                            throw new \Exception("cannot import property [ " . $type . " ] because it references unknown " . $propertyWs["data"]);
                        } else {
                            $idMapper->recordMappingFailure("object", $object->getId(), $type, $propertyWs["data"]);
                        }
                    }
                } elseif ($type == "date") {
                    $dat = new \Pimcore\Date(strtotime($propertyWs["data"]));
                } else {
                    $dat = $propertyWs["data"];
                }

                $object->setProperty($propertyWs["name"], $propertyWs["type"], $dat, $propertyWs["inherited"], $propertyWs["inheritable"]);
            }
        }
    }
}
