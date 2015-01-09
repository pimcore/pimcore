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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Tool\Serialize;

class Link extends Model\Object\ClassDefinition\Data {

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
    public $phpdocType = "\\Pimcore\\Model\\Object\\Data\\Link";

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param Object\Data\Link $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForResource($data, $object = null) {
        if($data instanceof Object\Data\Link and isset($data->object)){
            unset($data->object);
        }

        if($data) {
            try {
                $this->checkValidity($data, true);
            } catch (\Exception $e) {
                $data->setInternalType(null);
                $data->setInternal(null);
            }
        }

        return Serialize::serialize($data);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param string $data
     * @return Object\Data\Link
     */
    public function getDataFromResource($data) {
        $link = Serialize::unserialize($data);

        if ($link instanceof Object\Data\Link) {
            if ($link->isEmpty()) {
                return false;
            }

            try {
                $this->checkValidity($data, true);
            } catch (\Exception $e) {
                $data->setInternalType(null);
                $data->setInternal(null);
            }
        }

        return $link;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {
        return Serialize::serialize($data);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null) {
        if (!$data instanceof Object\Data\Link) {
            return false;
        }
        $data->path = $data->getPath();
        return $data;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null) {

        $link = new Object\Data\Link();
        $link->setValues($data);

        if ($link->isEmpty()) {
            return false;
        }
        return $link;
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
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
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){
        if ($data) {
            if ($data instanceof Object\Data\Link) {
                if (intval($data->getInternal()) > 0) {
                    if ($data->getInternalType() == "document") {
                        $doc = Document::getById($data->getInternal());
                        if (!$doc instanceof Document) {
                            throw new \Exception("invalid internal link, referenced document with id [" . $data->getInternal() . "] does not exist");
                        }
                    }
                    else if ($data->getInternalType() == "asset") {
                        $asset = Asset::getById($data->getInternal());
                        if (!$asset instanceof Asset) {
                            throw new \Exception("invalid internal link, referenced asset with id [" . $data->getInternal() . "] does not exist");
                        }
                    }
                } 
            }
        }
    }

    /**
     * @param $data
     * @return array
     */
    public function resolveDependencies($data) {
        $dependencies = array();

        if ($data instanceof Object\Data\Link and $data->getInternal()) {

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
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     * @return array
     */
    public function getCacheTags($data, $tags = array()) {

        $tags = is_array($tags) ? $tags : array();

        if ($data instanceof Object\Data\Link and $data->getInternal()) {

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
     * @param Model\Object\AbstractObject $object
     * @return string
     */
    public function getForCsvExport($object) {
        $data = $this->getDataFromObjectParam($object);
        if ($data instanceof Object\Data\Link) {
            return base64_encode(Serialize::serialize($data));
        } else return null;
    }

    /**
     * fills object field data values from CSV Import String
     * @param string $importValue
     * @return Object\ClassDefinition\Data\Link
     */
    public function getFromCsvImport($importValue) {
        $value = Serialize::unserialize(base64_decode($importValue));
        if ($value instanceof Object\Data\Link) {
            return $value;
        } else return null;

    }

    /**
     * converts data to be exposed via webservices
     * @param string $object
     * @return mixed
     */
    public function getForWebserviceExport($object) {
        $data = $this->getDataFromObjectParam($object);
        if ($data instanceof Object\Data\Link) {

            $keys = get_object_vars($data);
            foreach ($keys as $key => $value) {
                $method = "get" . ucfirst($key);
                if (!method_exists($data, $method) or $key=="object") {
                    unset($keys[$key]);
                }
            }
            return $keys;
        } else return null;
    }

    /**
     * @param mixed $value
     * @param null $relatedObject
     * @param null $idMapper
     * @return mixed|void
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $idMapper = null) {
        if ($value instanceof \stdclass) {
            $value = (array) $value;
        }

        if (empty($value)) {
            return null;
        } else if (is_array($value) and !empty($value['text']) and !empty($value['direct'])) {
            $link = new Object\Data\Link();
            foreach ($value as $key => $value) {
                $method = "set" . ucfirst($key);
                if (method_exists($link, $method)) {
                    $link->$method($value);
                } else {
                    throw new \Exception("cannot get values from web service import - invalid data. Unknown Object\\Data\\Link setter [ " . $method . " ]");
                }
            }
            return $link;

        } else if (is_array($value) and !empty($value['text']) and !empty($value['internalType']) and !empty($value['internal'])) {
            $id = $value['internal'];

            if ($idMapper) {
                $id = $idMapper->getMappedId($value['internalType'], $id);
            }


            $element = Element\Service::getElementById($value['internalType'],$id);
            if(!$element){
                if ($idMapper && $idMapper->ignoreMappingFailures()) {
                    $idMapper->recordMappingFailure("object", $relatedObject->getId(),$value['internalType'], $value['internal']);
                    return null;
                } else {
                    throw new \Exception("cannot get values from web service import - referencing unknown internal element with type [ ".$value['internalType']." ] and id [ ".$value['internal']." ]");
                }
            }

            $link = new Object\Data\Link();
            foreach ($value as $key => $value) {
                $method = "set" . ucfirst($key);
                if (method_exists($link, $method)) {
                    $link->$method($value);
                } else {
                    throw new \Exception("cannot get values from web service import - invalid data. Unknown Object\\Data\\Link setter [ " . $method . " ]");
                }
            }
            return $link;

        } else {
            throw new \Exception("cannot get values from web service import - invalid data");
        }

    }

    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     * @param $data
     * @param null $object
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null) {
        if ($data) {
           if ($data->text) {
               return $data->text;
           } else if ($data->direct) {
               return $data->direct;
           }
        }
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
    public function rewriteIds($object, $idMapping, $params = array()) {
        $data = $this->getDataFromObjectParam($object, $params);
        if($data instanceof Object\Data\Link && $data->getLinktype() == "internal") {
            $id = $data->getInternal();
            $type = $data->getInternalType();

            if(array_key_exists($type, $idMapping) and array_key_exists($id, $idMapping[$type])) {
                $data->setInternal($idMapping[$type][$id]);
            }
        }
        return $data;
    }
}
