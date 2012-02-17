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

class Object_Class_Data_Link extends Object_Class_Data {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "link";

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "text";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "text";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "Object_Data_Link";

    /**
     * @see Object_Class_Data::getDataForResource
     * @param Object_Data_Link $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForResource($data, $object = null) {
        if($data instanceof Object_Data_Link and isset($data->object)){
            unset($data->object);
        }

        if($data) {
            try {
                $this->checkValidity($data, true);
            } catch (Exception $e) {
                $data->setInternalType(null);
                $data->setInternal(null);
            }
        }

        return Pimcore_Tool_Serialize::serialize($data);
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param string $data
     * @return Object_Data_Link
     */
    public function getDataFromResource($data) {
        $link = Pimcore_Tool_Serialize::unserialize($data);

        if ($link instanceof Object_Data_Link) {
            if ($link->isEmpty()) {
                return false;
            }

            try {
                $this->checkValidity($data, true);
            } catch (Exception $e) {
                $data->setInternalType(null);
                $data->setInternal(null);
            }
        }

        return $link;
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {
        return Pimcore_Tool_Serialize::serialize($data);
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null) {
        if (!$data instanceof Object_Data_Link) {
            return false;
        }
        $data->path = $data->getPath();
        return $data;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null) {

        $link = new Object_Data_Link();
        $link->setValues($data);

        if ($link->isEmpty()) {
            return false;
        }
        return $link;
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data) {
        return $data;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){
        if ($data) {
            if ($data instanceof Object_Data_Link) {
                if (intval($data->getInternal()) > 0) {
                    if ($data->getInternalType() == "document") {
                        $doc = Document::getById($data->getInternal());
                        if (!$doc instanceof Document) {
                            throw new Exception("invalid internal link, referenced document with id [" . $data->getInternal() . "] does not exist");
                        }
                    }
                    else if ($data->getInternalType() == "asset") {
                        $asset = Asset::getById($data->getInternal());
                        if (!$asset instanceof Asset) {
                            throw new Exception("invalid internal link, referenced asset with id [" . $data->getInternal() . "] does not exist");
                        }
                    }
                } 
            }
        }
    }

    /**
     * @param mixed $data
     */
    public function resolveDependencies($data) {
        $dependencies = array();

        if ($data instanceof Object_Data_Link and $data->getInternal()) {

            if (intval($data->getInternal()) > 0) {
                if ($data->getInternalType() == "document") {

                    if ($doc = Document::getById($data->getInternal())) {

                        $key = "document_" . $doc->getId();
                        $dependencies[$key] = array(
                            "id" => $doc->getId(),
                            "type" => "document"
                        );
                    }
                }
                else if ($data->getInternalType() == "asset") {
                    if ($asset = Asset::getById($data->getInternal())) {
                        $key = "asset_" . $asset->getId();

                        $dependencies[$key] = array(
                            "id" => $asset->getId(),
                            "type" => "asset"
                        );
                    }
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
    public function getCacheTags($data, $ownerObject, $tags = array()) {

        $tags = is_array($tags) ? $tags : array();

        if ($data instanceof Object_Data_Link and $data->getInternal()) {

            if (intval($data->getInternal()) > 0) {
                if ($data->getInternalType() == "document") {

                    if ($doc = Document::getById($data->getInternal())) {
                        if (!array_key_exists($doc->getCacheTag(), $tags)) {
                            $tags = $doc->getCacheTags($tags);
                        }
                    }
                }
                else if ($data->getInternalType() == "asset") {
                    if ($asset = Asset::getById($data->getInternal())) {
                        if (!array_key_exists($asset->getCacheTag(), $tags)) {
                            $tags = $asset->getCacheTags($tags);
                        }
                    }
                }
            }
        }

        return $tags;
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object) {
        $key = $this->getName();
        $getter = "get".ucfirst($key);
        if ($object->$getter() instanceof Object_Data_Link) {
            return base64_encode(Pimcore_Tool_Serialize::serialize($object->$getter()));
        } else return null;
    }

    /**
     * fills object field data values from CSV Import String
     * @param string $importValue
     * @return Object_Class_Data_Link
     */
    public function getFromCsvImport($importValue) {
        $value = Pimcore_Tool_Serialize::unserialize(base64_decode($importValue));
        if ($value instanceof Object_Data_Link) {
            return $value;
        } else return null;

    }

    /**
     * converts data to be exposed via webservices
     * @param string $object
     * @return mixed
     */
    public function getForWebserviceExport($object) {
        $k = $this->getName();
        $getter = "get".ucfirst($k);
        if ($object->$getter() instanceof Object_Data_Link) {

            $keys = get_object_vars($object->$getter());
            foreach ($keys as $key => $value) {
                $method = "get" . ucfirst($key);
                if (!method_exists($object->$getter(), $method) or $key=="object") {
                    unset($keys[$key]);
                }
            }
            return $keys;
        } else return null;
    }

    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($value) {
        if (empty($value)) {
            return null;
        } else if (is_array($value) and !empty($value['text']) and !empty($value['direct'])) {
            $link = new Object_Data_Link();
            foreach ($value as $key => $value) {
                $method = "set" . ucfirst($key);
                if (method_exists($link, $method)) {
                    $link->$method($value);
                } else {
                    throw new Exception("cannot get values from web service import - invalid data. Unknown Object_Data_Link setter [ " . $method . " ]");
                }
            }
            return $link;

        } else if (is_array($value) and !empty($value['text']) and !empty($value['internalType']) and !empty($value['internal'])) {
            $element = Element_Service::getElementById($value['internalType'],$value['internal']);
            if(!$element){
                throw new Exception("cannot get values from web service import - referencing unknown internal element with type [ ".$value['internalType']." ] and id [ ".$value['internal']." ]");
            }
            $link = new Object_Data_Link();
            foreach ($value as $key => $value) {
                $method = "set" . ucfirst($key);
                if (method_exists($link, $method)) {
                    $link->$method($value);
                } else {
                    throw new Exception("cannot get values from web service import - invalid data. Unknown Object_Data_Link setter [ " . $method . " ]");
                }
            }
            return $link;

        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }

    }

}
