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

class Object_Class_Data_Multihref extends Object_Class_Data_Relations_Abstract
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "multihref";

    /**
     * @var integer
     */
    public $width;

    /**
     * Type for the column to query
     *
     * @var integer
     */
    public $height;

    /**
     * @var int
     */
    public $maxItems;

    /**
     * @var string
     */
    public $assetUploadPath;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "text";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "array";

    /**
     * @var bool
     */
    public $relationType = true;

    /**
     *
     * @var boolean
     */
    public $objectsAllowed;

    /**
     *
     * @var boolean
     */
    public $assetsAllowed;

    /**
     * Allowed asset types
     *
     * @var array
     */
    public $assetTypes;

    /**
     *
     * @var boolean
     */
    public $documentsAllowed;

    /**
     * Allowed document types
     *
     * @var array
     */
    public $documentTypes;


    /**
     * @return boolean
     */
    public function getObjectsAllowed()
    {
        return $this->objectsAllowed;
    }

    /**
     * @param boolean $objectsAllowed
     * @return void
     */
    public function setObjectsAllowed($objectsAllowed)
    {
        $this->objectsAllowed = $objectsAllowed;
    }

    /**
     * @return boolean
     */
    public function getDocumentsAllowed()
    {
        return $this->documentsAllowed;
    }

    /**
     * @param boolean $documentsAllowed
     * @return void
     */
    public function setDocumentsAllowed($documentsAllowed)
    {
        $this->documentsAllowed = $documentsAllowed;
    }


    /**
     * @return array
     */
    public function getDocumentTypes()
    {
        return $this->documentTypes;
    }

    /**
     * @param array
     * @return void $documentTypes
     */
    public function setDocumentTypes($documentTypes)
    {
        // this is the new method with Ext.form.MultiSelect
        if(is_string($documentTypes) && !empty($documentTypes)) {
            $parts = explode(",", $documentTypes);
            $documentTypes = array();
            foreach ($parts as $type) {
                $documentTypes[] = array("documentTypes" => $type);
            }
        }

        $this->documentTypes = $documentTypes;
    }

    /**
     *
     * @return boolean
     */
    public function getAssetsAllowed()
    {
        return $this->assetsAllowed;
    }

    /**
     *
     * @param boolean $assetsAllowed
     * @return void
     */
    public function setAssetsAllowed($assetsAllowed)
    {
        $this->assetsAllowed = $assetsAllowed;
    }

    /**
     * @return array
     */
    public function getAssetTypes()
    {
        return $this->assetTypes;
    }

    /**
     * @param array
     * @return void $assetTypes
     */
    public function setAssetTypes($assetTypes)
    {
        // this is the new method with Ext.form.MultiSelect
        if(is_string($assetTypes) && !empty($assetTypes)) {
            $parts = explode(",", $assetTypes);
            $assetTypes = array();
            foreach ($parts as $type) {
                $assetTypes[] = array("assetTypes" => $type);
            }
        }

        $this->assetTypes = $assetTypes;
    }


    /**
     * @see Object_Class_Data::getDataForResource
     * @param array $data
     * @param null|Object_Abstract $object
     * @return array
     */
    public function getDataForResource($data, $object = null)
    {

        $return = array();

        if (is_array($data) && count($data) > 0) {

            $counter = 1;
            foreach ($data as $object) {
                if ($object instanceof Element_Interface) {
                    $return[] = array(
                        "dest_id" => $object->getId(),
                        "type" => Element_Service::getElementType($object),
                        "fieldname" => $this->getName(),
                        "index" => $counter
                    );
                }
                $counter++;
            }
            return $return;
        } else if (is_array($data) and count($data) === 0) {
            //give empty array if data was not null
            return array();
        } else {
            //return null if data was null  - this indicates data was not loaded
            return null;
        }


    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param array $data
     * @return array
     */
    public function getDataFromResource($data)
    {

        $elements = array();
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {

                if ($element["type"] == "object") {
                    $e = Object_Abstract::getById($element["dest_id"]);
                }
                else if ($element["type"] == "asset") {
                    $e = Asset::getById($element["dest_id"]);
                }
                else if ($element["type"] == "document") {
                    $e = Document::getById($element["dest_id"]);
                }

                if ($e instanceof Element_Interface) {
                    $elements[] = $e;
                }
            }
        }
        //must return array - otherwise this means data is not loaded
        return $elements;
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param array $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null)
    {

        //return null when data is not set
        if (!$data) return null;

        $ids = array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                if ($element instanceof Object_Abstract) {
                    $d[] = "object|" . $element->geto_id();
                }
                else if ($element instanceof Asset) {
                    $d[] = "asset|" . $element->getId();
                }
                else if ($element instanceof Document) {
                    $d[] = "document|" . $element->getId();
                }
            }
            return "," . implode(",", $d) . ",";
        } else if (is_array($data) && count($data) === 0) {
            return "";
        } else {
            throw new Exception("invalid data passed to getDataForQueryResource - must be array");
        }
    }


    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param array $data
     * @param null|Object_Abstract $object
     * @return array
     */
    public function getDataForEditmode($data, $object = null)
    {
        $return = array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                if ($element instanceof Object_Concrete) {
                    $return[] = array($element->geto_id(), $element->getFullPath(), "object", $element->geto_className());
                }
                else if ($element instanceof Object_Abstract) {
                    $return[] = array($element->geto_id(), $element->getFullPath(), "object", "folder");
                }
                else if ($element instanceof Asset) {
                    $return[] = array($element->getId(), $element->getFullPath(), "asset", $element->getType());
                }
                else if ($element instanceof Document) {
                    $return[] = array($element->getId(), $element->getFullPath(), "document", $element->getType());
                }
            }
            if (empty ($return)) {
                $return = false;
            }
            return $return;
        }

        return false;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param array $data
     * @param null|Object_Abstract $object
     * @return array
     */
    public function getDataFromEditmode($data, $object = null)
    {

        //if not set, return null
        if ($data === null or $data === FALSE) {
            return null;
        }

        $elements = array();
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {

                if ($element["type"] == "object") {
                    $e = Object_Abstract::getById($element["id"]);
                }
                else if ($element["type"] == "asset") {
                    $e = Asset::getById($element["id"]);
                }
                else if ($element["type"] == "document") {
                    $e = Document::getById($element["id"]);
                }

                if ($e instanceof Element_Interface) {
                    $elements[] = $e;
                }
            }

        }
        //must return array if data shall be set
        return $elements;
    }

    public function getDataForGrid($data, $object = null) {
        if (is_array($data)) {
            $pathes = array();
            foreach ($data as $eo) {
                if ($eo instanceof Element_Interface) {
                    $pathes[] = $eo->getFullPath();
                }
            }
            return $pathes;
        }
    }    

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param array $data
     * @return string
     */
    public function getVersionPreview($data)
    {

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $e) {
                if($e instanceof Element_Interface) {
                    $pathes[] = get_class($e) . $e->getFullPath();
                }
            }
            return implode("<br />", $pathes);
        }
    }

    /**
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param integer $width
     * @return void
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);
    }

    /**
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param integer $height
     * @return void
     */
    public function setHeight($height)
    {
        $this->height = $this->getAsIntegerCast($height);
    }


    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {

        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new Exception("Empty mandatory field [ " . $this->getName() . " ]");
        }

        $allow = true;
        if (is_array($data)) {
            foreach ($data as $d) {
                if ($d instanceof Document) {
                    $allow = $this->allowDocumentRelation($d);
                } else if ($d instanceof Asset) {
                    $allow = $this->allowAssetRelation($d);
                } else if ($d instanceof Object_Abstract) {
                    $allow = $this->allowObjectRelation($d);
                } else if (empty($d)) {
                    $allow = true;
                } else {
                    $allow = false;
                }
                if (!$allow) {
                    throw new Exception("Invalid multihref relation", null, null);
                }
            }
        }


    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object)
    {
        $key = $this->getName();
        $getter = "get" . ucfirst($key);
        $data = $object->$getter();
        if (is_array($data)) {
            $paths = array();
            foreach ($data as $eo) {
                if ($eo instanceof Element_Interface) {
                    $paths[] = Element_Service::getType($eo) . ":" . $eo->getFullPath();
                }
            }
            return implode(",", $paths);
        } else return null;
    }

    /**
     * fills object field data values from CSV Import String
     * @abstract
     * @param string $importValue
     * @param Object_Abstract $abstract
     * @return Object_Class_Data
     */
    public function getFromCsvImport($importValue)
    {
        $values = explode(",", $importValue);

        $value = array();
        foreach ($values as $element) {

            $tokens = explode(":", $element);
            if (count($tokens) == 2) {
                $type = $tokens[0];
                $path = $tokens[1];
                $value[] = Element_Service::getElementByPath($type, $path);
            } else {
                //fallback for old export files
                if ($el = Asset::getByPath($element)) {
                    $value[] = $el;
                }
                else if ($el = Document::getByPath($element)) {
                    $value[] = $el;
                }
                else if ($el = Object_Abstract::getByPath($element)) {
                    $value[] = $el;
                }
            }
        }
        return $value;
    }


    /**
     * @param mixed $data
     * @param Object_Concrete $ownerObject
     * @param array $blockedTags
     */
    public function getCacheTags($data, $ownerObject, $tags = array())
    {
        $tags = is_array($tags) ? $tags : array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                if (!array_key_exists($element->getCacheTag(), $tags)) {
                    if(!$ownerObject instanceof Element_Interface || $element->getId() != $ownerObject->getId()) {
                        $tags = $element->getCacheTags($tags);
                    }
                }
            }
        }
        return $tags;
    }

    /**
     * @param mixed $data
     */
    public function resolveDependencies($data)
    {

        $dependencies = array();

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $e) {
                if ($e instanceof Object_Abstract) {
                    $dependencies["object_" . $e->getO_Id()] = array(
                        "id" => $e->getO_Id(),
                        "type" => "object"
                    );
                }
                else if ($e instanceof Asset) {
                    $dependencies["asset_" . $e->getId()] = array(
                        "id" => $e->getId(),
                        "type" => "asset"
                    );
                }
                else if ($e instanceof Document) {
                    $dependencies["document_" . $e->getId()] = array(
                        "id" => $e->getId(),
                        "type" => "document"
                    );
                }
            }
        }
        return $dependencies;
    }

    /**
     * converts data to be exposed via webservices
     * @param string $object
     * @return mixed
     */
    public function getForWebserviceExport($object)
    {

        $key = $this->getName();
        $getter = "get" . ucfirst($key);
        $data = $object->$getter();
        if (is_array($data)) {
            $items = array();
            foreach ($data as $eo) {
                if ($eo instanceof Element_Interface) {
                    $items[] = array(
                        "type" => Element_Service::getType($eo),
                        "subtype" => $eo->getType(),
                        "id" => $eo->getId()
                    );
                }
            }
            return $items;
        } else return null;
    }

    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($value)
    {

        if (empty($value)) {
            return null;
        } else if (is_array($value)) {
            $hrefs = array();
            foreach ($value as $href) {
                if (is_array($href) and key_exists("id", $href) and key_exists("type", $href)) {

                    $e = Element_Service::getElementById($href["type"], $href["id"]);

                    if ($e instanceof Element_Interface) {
                        $hrefs[] = $e;
                    } else {
                        throw new Exception("cannot get values from web service import - unknown element of type [ " . $href["type"] . " ] with id [" . $href["id"] . "] is referenced");
                    }
                }
            }
            return $hrefs; 
        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }
    }

    public function preGetData($object)  
    {
        $data = $object->{$this->getName()};

        if ($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())) {
            //$data = $this->getDataFromResource($object->getRelationData($this->getName(), true, null));
            $data = $this->load($object, array("force" => true));

            $setter = "set" . ucfirst($this->getName());
            if (method_exists($object, $setter)) {
                $object->$setter($data);
            }
        }

        if (Object_Abstract::doHideUnpublished() and is_array($data)) {
            $publishedList = array();
            foreach ($data as $listElement) {
                if (Element_Service::isPublished($listElement)) {
                    $publishedList[] = $listElement;
                }
            }
            return $publishedList;
        }

        return is_array($data) ? $data : array();
    }

    public function preSetData($object, $data)
    {

        if ($data === null) $data = array();

        if ($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())) {
            $object->addO__loadedLazyField($this->getName());
        }

        return $data;
    }

    /**
     * @param int $maxItems
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = $this->getAsIntegerCast($maxItems);
    }

    /**
     * @return int
     */
    public function getMaxItems()
    {
        return $this->maxItems;
    }

    /**
     * @param string $assetUploadPath
     */
    public function setAssetUploadPath($assetUploadPath)
    {
        $this->assetUploadPath = $assetUploadPath;
    }

    /**
     * @return string
     */
    public function getAssetUploadPath()
    {
        return $this->assetUploadPath;
    }
}
