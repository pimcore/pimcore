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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Db;
use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Tool;

class MultihrefMetadata extends Model\Object\ClassDefinition\Data\Multihref
{

    /**
     * @var
     */
    public $columns;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "multihrefMetadata";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "\\Pimcore\\Model\\Object\\Data\\ElementMetadata[]";


    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            $counter = 1;
            foreach ($data as $metaObject) {
                $element = $metaObject->getElement();
                if ($element instanceof Element\ElementInterface) {
                    $return[] = [
                        "dest_id" => $element->getId(),
                        "type" => Element\Service::getElementType($element),
                        "fieldname" => $this->getName(),
                        "index" => $counter
                    ];
                }
                $counter++;
            }

            return $return;
        } elseif (is_array($data) and count($data)===0) {
            //give empty array if data was not null
            return [];
        } else {
            //return null if data was null - this indicates data was not loaded
            return null;
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return array
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        $list = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                $destination = null;
                $source = Object::getById($element["src_id"]);


                if ($element["type"] == "object") {
                    $destination = Object::getById($element["dest_id"]);
                } elseif ($element["type"] == "asset") {
                    $destination = Asset::getById($element["dest_id"]);
                } elseif ($element["type"] == "document") {
                    $destination = Document::getById($element["dest_id"]);
                }

                if ($destination instanceof Element\ElementInterface) {
                    $metaData = \Pimcore::getDiContainer()->make('Pimcore\Model\Object\Data\ElementMetadata', [
                        "fieldname" => $this->getName(),
                        "columns" => $this->getColumnKeys(),
                        "element" => $destination
                    ]);

                    $ownertype = $element["ownertype"] ? $element["ownertype"] : "";
                    $ownername = $element["ownername"] ? $element["ownername"] : "";
                    $position = $element["position"] ? $element["position"] : "0";
                    $type = $element["type"];


                    $metaData->load($source, $destination, $this->getName(), $ownertype, $ownername, $position, $type);
                    $objects[] = $metaData;

                    $list[] = $metaData;
                }
            }
        }
        //must return array - otherwise this means data is not loaded
        return $list;
    }

    /**
     * @param $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @throws \Exception
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {

        //return null when data is not set
        if (!$data) {
            return null;
        }

        $ids = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $element = $metaObject->getElement();
                if ($element instanceof Element\ElementInterface) {
                    $elementType = Element\Service::getElementType($element);
                    $d[] = $elementType . "|" . $element->getId();
                }
            }

            return "," . implode(",", $ids) . ",";
        } elseif (is_array($data) && count($data) === 0) {
            return "";
        } else {
            throw new \Exception("invalid data passed to getDataForQueryResource - must be array");
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $element = $metaObject->getElement();

                $itemData = null;

                if ($element instanceof Object\Concrete) {
                    $itemData = ["id" => $element->getId(), "path" => $element->getRealFullPath(), "type" => "object", "subtype" => $element->getClassName()];
                } elseif ($element instanceof Object\AbstractObject) {
                    $itemData = ["id" => $element->getId(), "path" => $element->getRealFullPath(), "type" => "object",  "subtype" => "folder"];
                } elseif ($element instanceof Asset) {
                    $itemData = ["id" => $element->getId(), "path" => $element->getRealFullPath(), "type" => "asset",  "subtype" => $element->getType()];
                } elseif ($element instanceof Document) {
                    $itemData= ["id" => $element->getId(), "path" => $element->getRealFullPath(), "type" => "document", "subtype" => $element->getType()];
                }

                if (!$itemData) {
                    continue;
                }


                foreach ($this->getColumns() as $c) {
                    $getter = "get" . ucfirst($c['key']);
                    $itemData[$c['key']] = $metaObject->$getter();
                }
                $return[] = $itemData;
            }
            if (empty($return)) {
                $return = false;
            }

            return $return;
        }
    }


    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return array
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        //if not set, return null
        if ($data === null or $data === false) {
            return null;
        }

        $multihrefMetadata = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                if ($element["type"] == "object") {
                    $e = Object::getById($element["id"]);
                } elseif ($element["type"] == "asset") {
                    $e = Asset::getById($element["id"]);
                } elseif ($element["type"] == "document") {
                    $e = Document::getById($element["id"]);
                }

                if ($e instanceof Element\ElementInterface) {
                    $metaData = \Pimcore::getDiContainer()->make('Pimcore\Model\Object\Data\ElementMetadata', [
                        "fieldname" => $this->getName(),
                        "columns" => $this->getColumnKeys(),
                        "element" => $e
                    ]);

                    foreach ($this->getColumns() as $columnConfig) {
                        $key = $columnConfig["key"];
                        $setter = "set" . ucfirst($key);
                        $value = $element[$key];
                        $metaData->$setter($value);
                    }
                    $multihrefMetadata[] = $metaData;

                    $elements[] = $e;
                }
            }
        }

        //must return array if data shall be set
        return $multihrefMetadata;
    }

    /**
     * @param $data
     * @param null $object
     * @param array $params
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        if (is_array($data)) {
            $pathes = [];
            foreach ($data as $metaObject) {
                $eo = $metaObject->getElement();
                if ($eo instanceof Element\ElementInterface) {
                    $pathes[] = $eo->getRealFullPath();
                }
            }

            return $pathes;
        }
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param array $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $o = $metaObject->getElement();
                $pathes[] = Element\Service::getElementType($o) . " " . $o->getRealFullPath();
            }

            return implode("<br />", $pathes);
        }
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new Element\ValidationException("Empty mandatory field [ ".$this->getName()." ]");
        }

        if (is_array($data)) {
            foreach ($data as $elementMetadata) {
                if (!($elementMetadata instanceof Object\Data\ElementMetadata)) {
                    throw new Element\ValidationException("Expected Object\\Data\\ElementMetadata");
                }

                $d = $elementMetadata->getElement();

                if ($d instanceof Document) {
                    $allow = $this->allowDocumentRelation($d);
                } elseif ($d instanceof Asset) {
                    $allow = $this->allowAssetRelation($d);
                } elseif ($d instanceof Object\AbstractObject) {
                    $allow = $this->allowObjectRelation($d);
                } elseif (empty($d)) {
                    $allow = true;
                } else {
                    $allow = false;
                }
                if (!$allow) {
                    throw new Element\ValidationException("Invalid multihref relation", null, null);
                }
            }
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object\AbstractObject $object
     * @param array $params
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            $paths = [];
            foreach ($data as $metaObject) {
                $eo = $metaObject->getElement();
                if ($eo instanceof Element\ElementInterface) {
                    $paths[] = Element\Service::getType($eo) . ":" . $eo->getRealFullPath();
                }
            }

            return implode(",", $paths);
        } else {
            return null;
        }
    }

    /**
     * @param $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return array|mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $values = explode(",", $importValue);

        $value = [];
        foreach ($values as $element) {
            $tokens = explode(":", $element);

            $type = $tokens[0];
            $path = $tokens[1];
            $el = Element\Service::getElementByPath($type, $path);

            if ($el) {
                $metaObject = \Pimcore::getDiContainer()->make('Pimcore\Model\Object\Data\ElementMetadata', [
                    "fieldname" => $this->getName(),
                    "columns" => $this->getColumnKeys(),
                    "element" => $el
                ]);

                $value[] = $metaObject;
            }
        }

        return $value;
    }


    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     * @return array
     */
    public function getCacheTags($data, $tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

        if ($this->getLazyLoading()) {
            return $tags;
        }

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaObject) {
                $element = $metaObject->getElement();
                if ($element instanceof Element\ElementInterface && !array_key_exists($element->getCacheTag(), $tags)) {
                    $tags = $element->getCacheTags($tags);
                }
            }
        }

        return $tags;
    }


    /**
     * @param Object\AbstractObject $object
     * @param mixed $params
     * @return array|mixed|null
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            $items = [];
            foreach ($data as $metaObject) {
                $eo = $metaObject->getElement();
                if ($eo instanceof Element\ElementInterface) {
                    $item = [];
                    $item["type"] = Element\Service::getType($eo);
                    $item["id"] = $eo->getId();

                    foreach ($this->getColumns() as $c) {
                        $getter = "get" . ucfirst($c['key']);
                        $item[$c['key']] = $metaObject->$getter();
                    }
                    $items[] = $item;
                }
            }

            return $items;
        } else {
            return null;
        }
    }


    /**
     * @param mixed $value
     * @param null $relatedObject
     * @param mixed $params
     * @param null $idMapper
     * @return mixed|void
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $params = [], $idMapper = null)
    {
        if (empty($value)) {
            return null;
        } elseif (is_array($value)) {
            $hrefs = [];
            foreach ($value as $href) {
                // cast is needed to make it work for both SOAP and REST
                $href = (array) $href;
                if (is_array($href) and array_key_exists("id", $href) and array_key_exists("type", $href)) {
                    $type = $href["type"];
                    $id = $href["id"];
                    if ($idMapper) {
                        $id = $idMapper->getMappedId($type, $id);
                    }

                    $e = null;
                    if ($id) {
                        $e = Element\Service::getElementById($type, $id);
                    }

                    if ($e instanceof Element\ElementInterface) {
                        $elMeta = new Object\Data\ElementMetadata($this->getName(), $this->getColumnKeys(), $e);

                        foreach ($this->getColumns() as $c) {
                            $setter = "set" . ucfirst($c['key']);
                            $elMeta->$setter($href[$c['key']]);
                        }


                        $hrefs[] = $elMeta;
                    } else {
                        if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                            throw new \Exception("cannot get values from web service import - unknown element of type [ " . $href["type"] . " ] with id [" . $href["id"] . "] is referenced");
                        } else {
                            $idMapper->recordMappingFailure("object", $relatedObject->getId(), $type, $href["id"]);
                        }
                    }
                }
            }

            return $hrefs;
        } else {
            throw new \Exception("cannot get values from web service import - invalid data");
        }
    }


    /**
     * @param Object\Concrete $object
     * @param array $params
     * @return void
     */
    public function save($object, $params = [])
    {
        $multihrefMetadata = $this->getDataFromObjectParam($object, $params);

        $classId = null;
        $objectId = null;

        if ($object instanceof Object\Concrete) {
            $objectId = $object->getId();
        } elseif ($object instanceof Object\Fieldcollection\Data\AbstractData) {
            $objectId = $object->getObject()->getId();
        } elseif ($object instanceof Object\Localizedfield) {
            $objectId = $object->getObject()->getId();
        } elseif ($object instanceof Object\Objectbrick\Data\AbstractData) {
            $objectId = $object->getObject()->getId();
        }

        if ($object instanceof Object\Localizedfield) {
            $classId = $object->getClass()->getId();
        } elseif ($object instanceof Object\Objectbrick\Data\AbstractData || $object instanceof Object\Fieldcollection\Data\AbstractData) {
            $classId = $object->getObject()->getClassId();
        } else {
            $classId = $object->getClassId();
        }

        $table = "object_metadata_" . $classId;
        $db = Db::get();

        $this->enrichRelation($object, $params, $classId, $relation);

        $position = (isset($relation["position"]) && $relation["position"]) ? $relation["position"] : "0";

        if ($params && $params["context"] && $params["context"]["containerType"] == "fieldcollection" && $params["context"]["subContainerType"] == "localizedfield") {
            $context = $params["context"];
            $index = $context["index"];
            $containerName = $context["fieldname"];

            $sql = $db->quoteInto("o_id = ?", $objectId) . " AND ownertype = 'localizedfield' AND "
                . $db->quoteInto("ownername LIKE ?", "/fieldcollection~" . $containerName . "/" . $index . "/%")
                . " AND " . $db->quoteInto("fieldname = ?", $this->getName())
                . " AND " . $db->quoteInto("position = ?", $position);
        } else {
            $sql = $db->quoteInto("o_id = ?", $objectId) . " AND " . $db->quoteInto("fieldname = ?", $this->getName())
                . " AND " . $db->quoteInto("position = ?", $position);
        }

        $db->delete($table, $sql);

        if (!empty($multihrefMetadata)) {
            if ($object instanceof Object\Localizedfield || $object instanceof Object\Objectbrick\Data\AbstractData
                || $object instanceof Object\Fieldcollection\Data\AbstractData) {
                $objectConcrete = $object->getObject();
            } else {
                $objectConcrete = $object;
            }

            foreach ($multihrefMetadata as $meta) {
                $ownerName = isset($relation["ownername"]) ? $relation["ownername"] : null;
                $ownerType = isset($relation["ownertype"]) ? $relation["ownertype"] : null;
                $meta->save($objectConcrete, $ownerType, $ownerName, $position);
            }
        }

        parent::save($object, $params);
    }

    /**
     * @param $object
     * @param array $params
     * @return array|mixed|null
     */
    public function preGetData($object, $params = [])
    {
        $data = null;
        if ($object instanceof Object\Concrete) {
            $data = $object->{$this->getName()};
            if ($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())) {
                //$data = $this->getDataFromResource($object->getRelationData($this->getName(),true,null));
                $data = $this->load($object, ["force" => true]);

                $setter = "set" . ucfirst($this->getName());
                if (method_exists($object, $setter)) {
                    $object->$setter($data);
                }
            }
        } elseif ($object instanceof Object\Localizedfield) {
            $data = $params["data"];
        } elseif ($object instanceof Object\Fieldcollection\Data\AbstractData) {
            $data = $object->{$this->getName()};
        } elseif ($object instanceof Object\Objectbrick\Data\AbstractData) {
            $data = $object->{$this->getName()};
        }

        if (Object\AbstractObject::doHideUnpublished() and is_array($data)) {
            $publishedList = [];
            /** @var  $listElement Object\Data\ElementMetadata */
            foreach ($data as $listElement) {
                if (Element\Service::isPublished($listElement->getElement())) {
                    $publishedList[] = $listElement;
                }
            }

            return $publishedList;
        }

        return $data;
    }

    /**
     * @param Object\Concrete $object
     * @param array $params
     * @return void
     */
    public function delete($object, $params = [])
    {
        $db = Db::get();

        if ($params && $params["context"] && $params["context"]["containerType"] == "fieldcollection" && $params["context"]["subContainerType"] == "localizedfield") {
            $context = $params["context"];
            $index = $context["index"];
            $containerName = $context["fieldname"];

            $db->delete("object_metadata_" . $object->getClassId(),
                $db->quoteInto("o_id = ?", $object->getId()) . " AND ownertype = 'localizedfield' AND "
                . $db->quoteInto("ownername LIKE ?", "/fieldcollection~" . $containerName . "/" . $index . "/%")
                . " AND " . $db->quoteInto("fieldname = ?", $this->getName())
            );
        } else {
            $db->delete("object_metadata_" . $object->getClassId(), $db->quoteInto("o_id = ?", $object->getId()) . " AND " . $db->quoteInto("fieldname = ?", $this->getName()));
        }
    }

    /**
     * @param $columns
     * @return $this
     */
    public function setColumns($columns)
    {
        if (isset($columns['key'])) {
            $columns = [$columns];
        }
        usort($columns, [$this, 'sort']);

        $this->columns = [];
        $this->columnKeys = [];
        foreach ($columns as $c) {
            $c['key'] = strtolower($c['key']);
            $this->columns[] = $c;
            $this->columnKeys[] = $c['key'];
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getColumnKeys()
    {
        $this->columnKeys = [];
        foreach ($this->columns as $c) {
            $this->columnKeys[] = $c['key'];
        }

        return $this->columnKeys;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    public function sort($a, $b)
    {
        if (is_array($a) && is_array($b)) {
            return $a['position'] - $b['position'];
        }

        return strcmp($a, $b);
    }

    /**
     * @param $class
     * @return void
     */
    public function classSaved($class)
    {
        $temp = \Pimcore::getDiContainer()->make('Pimcore\Model\Object\Data\ElementMetadata', [
            "fieldname" => null
        ]);
        $temp->getDao()->createOrUpdateTable($class);
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     * @param mixed $object
     * @param array $idMapping
     * @param array $params
     * @return Element\ElementInterface
     */
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if (is_array($data)) {
            foreach ($data as &$metaObject) {
                $eo = $metaObject->getObject();
                if ($eo instanceof Element\ElementInterface) {
                    $id = $eo->getId();
                    $type = Element\Service::getElementType($eo);

                    if (array_key_exists($type, $idMapping) && array_key_exists($id, $idMapping[$type])) {
                        $newElement = Element\Service::getElementById($type, $idMapping[$type][$id]);
                        $metaObject->setObject($newElement);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition)
    {
        parent::synchronizeWithMasterDefinition($masterDefinition);
        $this->columns = $masterDefinition->columns;
    }

    /** Override point for Enriching the layout definition before the layout is returned to the admin interface.
     * @param $object Object\Concrete
     * @param array $context additional contextual data
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
        // nothing to do
    }

    /**
     * @param $data
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $metaElement) {
                $e = $metaElement->getElement();
                if ($e instanceof Element\ElementInterface) {
                    $elementType = Element\Service::getElementType($e);
                    $dependencies[$elementType . "_" . $e->getId()] = [
                        "id" => $e->getId(),
                        "type" => $elementType
                    ];
                }
            }
        }

        return $dependencies;
    }

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\Object\AbstractObject $object
     * @param mixed $params
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if (is_array($value)) {
            $result = [];
            /** @var  $elementMetadata Object\Data\ElementMetadata */
            foreach ($value as $elementMetadata) {
                $element = $elementMetadata->getElement();

                $type = Element\Service::getType($element);
                $id = $element->getId();
                $result[] =  [
                    "element" => [
                        "type" => $type,
                        "id" => $id
                    ],
                    "fieldname" => $elementMetadata->getFieldname(),
                    "columns" => $elementMetadata->getColumns(),
                    "data" => $elementMetadata->data];
            }

            return $result;
        }

        return null;
    }

    /** See marshal
     * @param mixed $value
     * @param Model\Object\AbstractObject $object
     * @param mixed $params
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $elementMetadata) {
                $elementData = $elementMetadata["element"];

                $type = $elementData["type"];
                $id = $elementData["id"];
                $element = Element\Service::getElementById($type, $id);
                if ($element) {
                    $columns = $elementMetadata["columns"];
                    $fieldname = $elementMetadata["fieldname"];
                    $data = $elementMetadata["data"];

                    $item = new Object\Data\ElementMetadata($fieldname, $columns, $element);
                    $item->data = $data;
                    $result[] = $item;
                }
            }

            return $result;
        }
    }

    /**
     * @return string
     */
    public function getPhpdocType()
    {
        return $this->phpdocType;
    }
}
