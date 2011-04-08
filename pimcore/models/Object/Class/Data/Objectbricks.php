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
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Data_Objectbricks extends Object_Class_Data
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "objectbricks";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "";

    /**
     * @var string
     */
    public $allowedTypes = array();

    public function getPhpdocType() {
        return "hugo";


    }


    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param string $data
     * @return string
     */
    public function getDataForEditmode($data)
    {
        $editmodeData = array();

        if ($data instanceof Object_Fieldcollection) {
            foreach ($data as $item) {

                if (!$item instanceof Object_Fieldcollection_Data_Abstract) {
                    continue;
                }

                try {
                    $collectionDef = Object_Fieldcollection_Definition::getByKey($item->getType());
                } catch (Exception $e) {
                    continue;
                }

                $collectionData = array();

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $collectionData[$fd->getName()] = $fd->getDataForEditmode($item->{$fd->getName()});
                }

                $editmodeData[] = array(
                    "data" => $collectionData,
                    "type" => $item->getType()
                );
            }
        }

        return $editmodeData;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param string $data
     * @return string
     */
    public function getDataFromEditmode($data)
    {

        $className = $this->getObject()->getClass()->getName();

        $containerClass = "Object_" . ucfirst($className) . "_" . ucfirst($this->getName());
        $container = new $containerClass($this->getObject(), $this->getName());

//        p_r($this);
//        p_r($this->getName());
//
//
//        p_r($data);

//        $values = array();
//        $count = 0;

        if (is_array($data)) {
            foreach ($data as $collectionRaw) {

                $collectionData = array();
                $collectionDef = Object_Objectbrick_Definition::getByKey($collectionRaw["type"]);

                $collectionClass = "Object_Objectbrick_Data_" . ucfirst($collectionRaw["type"]);
                $collection = new $collectionClass;

                if($collectionRaw["data"] == "deleted") {
                    $collection->setDoDelete(true); 
                } else {
                    foreach ($collectionDef->getFieldDefinitions() as $fd) {
                        if ($collectionRaw["data"][$fd->getName()]) {
                            $collectionData[$fd->getName()] = $fd->getDataFromEditmode($collectionRaw["data"][$fd->getName()]);
                        }
                    }
                    $collection->setValues($collectionData);
                    $collection->setFieldname($this->getName());
                }

                $setter = "set" . ucfirst($collectionRaw["type"]);
                $container->$setter($collection);
            }
        }
//        $container = new Object_Fieldcollection($values, $this->getName());
        return $container;
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data)
    {
        return "BRICKS";
    }

    /**
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object)
    {
        return "NOT SUPPORTED";
    }

    /**
     * @param string $importValue
     * @return null
     */
    public function getFromCsvImport($importValue)
    {
        return;
    }


    public function save($object)
    {

        $getter = "get" . ucfirst($this->getName());
        $container = $object->$getter();

        if ($container instanceof Object_Objectbrick) {
            $container->save($object);
        }
    }

    public function load($object)
    {
        $container = new Object_Objectbrick($object, $this->getName());
        $container->load($object);

        return $container;
    }

    public function delete($object)
    {
        $container = new Object_Objectbrick(null, $this->getName());
        $container->delete($object);
    }

    public function getAllowedTypes()
    {
        return $this->allowedTypes;
    }

    public function setAllowedTypes($allowedTypes)
    {
        if (is_string($allowedTypes)) {
            $allowedTypes = explode(",", $allowedTypes);
        }

        if (is_array($allowedTypes)) {
            for ($i = 0; $i < count($allowedTypes); $i++) {
                try {
                    Object_Objectbrick_Definition::getByKey($allowedTypes[$i]);
                } catch (Exception $e) {

                    Logger::warn(get_class($this) . ": Removed unknown allowed type [ $allowedTypes[$i] ] from allowed types of field collection");
                    unset($allowedTypes[$i]);
                }
            }
        }

        $this->allowedTypes = (array)$allowedTypes;
    }

    /**
     * @param Object_Abstract $object
     * @return mixed
     */
    public function getForWebserviceExport($object)
    {
        $getter = "get" . ucfirst($this->getName());
        $data = $object->$getter();
        $wsData = array();

        if ($data instanceof Object_Fieldcollection) {
            foreach ($data as $item) {


                if (!$item instanceof Object_Fieldcollection_Data_Abstract) {
                    continue;
                }

                $wsDataItem = new Webservice_Data_Object_Element();
                $wsDataItem->value = array();
                $wsDataItem->type = $item->getType();

                try {
                    $collectionDef = Object_Fieldcollection_Definition::getByKey($item->getType());
                } catch (Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $el = new Webservice_Data_Object_Element();
                    $el->name = $fd->getName();
                    $el->type = $fd->getFieldType();
                    $el->value = $fd->getForWebserviceExport($item);

                    $wsDataItem->value[] = $el;
                    //$wsData[$item->getType()."_".$fd->getName()]=$el;
                    //$wsData[]=$el;

                }


            }
            $wsData[] = $wsDataItem;

        }

        return $wsData;


    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($data)
    {


        $values = array();
        $count = 0;

        if (is_array($data)) {
            foreach ($data as $collectionRaw) {
                if (!$collectionRaw instanceof Webservice_Data_Object_Element) {

                    throw new Exception("invalid data in fieldcollections [" . $this->getName() . "]");
                }

                $fieldcollection = $collectionRaw->type;
                $collectionData = array();
                $collectionDef = Object_Fieldcollection_Definition::getByKey($fieldcollection);

                if (!$collectionDef) {
                    throw new Exception("Unknown fieldcollection in webservice import [" . $fieldcollection . "]");
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    foreach ($collectionRaw->value as $field) {
                        if (!$field instanceof Webservice_Data_Object_Element) {
                            throw new Exception("invalid data in fieldcollections [" . $this->getName() . "]");
                        } else if ($field->name == $fd->getName()) {

                            if ($field->type != $fd->getFieldType()) {
                                throw new Exception("Type mismatch for fieldcollection field [" . $field->name . "]. Should be [" . $fd->getFieldType() . "] but is [" . $field->type . "]");
                            }
                            $collectionData[$fd->getName()] = $fd->getFromWebserviceImport($field->value);
                            break;
                        }


                    }

                }

                $collectionClass = "Object_Fieldcollection_Data_" . ucfirst($fieldcollection);
                $collection = new $collectionClass;
                $collection->setValues($collectionData);
                $collection->setIndex($count);
                $collection->setFieldname($this->getName());

                $values[] = $collection;

                $count++;
            }
        }

        $container = new Object_Fieldcollection($values, $this->getName());
        return $container;


    }


    public function preSetData($object, $value)
    {
        if ($value instanceof Object_Fieldcollection) {
            $value->setFieldname($this->getName());
        }
        return $value;
    }


    /**
     * @param mixed $data
     */
    public function resolveDependencies($data)
    {
        $dependencies = array();

        if ($data instanceof Object_Fieldcollection) {
            foreach ($data as $item) {

                if (!$item instanceof Object_Fieldcollection_Data_Abstract) {
                    continue;
                }

                try {
                    $collectionDef = Object_Fieldcollection_Definition::getByKey($item->getType());
                } catch (Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $key = $fd->getName();
                    $getter = "get" . ucfirst($key);
                    $dependencies = array_merge($dependencies, $fd->resolveDependencies($item->$getter()));
                }
            }
        }

        return $dependencies;
    }

    /**
     * @param mixed $data
     * @param Object_Concrete $ownerObject
     * @param array $blockedTags
     */
    public function getCacheTags($data, $ownerObject, $blockedTags = array())
    {
        $tags = array();


        if ($data instanceof Object_Fieldcollection) {
            foreach ($data as $item) {

                if (!$item instanceof Object_Fieldcollection_Data_Abstract) {
                    continue;
                }

                try {
                    $collectionDef = Object_Fieldcollection_Definition::getByKey($item->getType());
                } catch (Exception $e) {
                    continue;
                }

                foreach ($collectionDef->getFieldDefinitions() as $fd) {
                    $key = $fd->getName();
                    $getter = "get" . ucfirst($key);
                    $tags = array_merge($tags, $fd->getCacheTags($item->$getter(), $item, $blockedTags));
                    $blockedTags = array_merge($tags, $blockedTags);
                }
            }
        }

        return $tags;
    }


    //TODO: validity and sanity check
}
