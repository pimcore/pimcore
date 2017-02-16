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

use Pimcore\Model;
use Pimcore\Model\Element;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Object;

class Multihref extends Model\Object\ClassDefinition\Data\Relations\AbstractRelations
{
    use Model\Object\ClassDefinition\Data\Extension\Relation;

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
     * @return $this
     */
    public function setObjectsAllowed($objectsAllowed)
    {
        $this->objectsAllowed = $objectsAllowed;

        return $this;
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
     * @return $this
     */
    public function setDocumentsAllowed($documentsAllowed)
    {
        $this->documentsAllowed = $documentsAllowed;

        return $this;
    }


    /**
     * @return array
     */
    public function getDocumentTypes()
    {
        return $this->documentTypes;
    }

    /**
     * @param array $documentTypes
     * @return $this
     */
    public function setDocumentTypes($documentTypes)
    {
        $this->documentTypes = Element\Service::fixAllowedTypes($documentTypes, "documentTypes");

        return $this;
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
     * @return $this
     */
    public function setAssetsAllowed($assetsAllowed)
    {
        $this->assetsAllowed = $assetsAllowed;

        return $this;
    }

    /**
     * @return array
     */
    public function getAssetTypes()
    {
        return $this->assetTypes;
    }

    /**
     * @param array $assetTypes
     * @return $this
     */
    public function setAssetTypes($assetTypes)
    {
        $this->assetTypes = Element\Service::fixAllowedTypes($assetTypes, "assetTypes");

        return $this;
    }


    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @param mixed $params
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $return = [];

        if (is_array($data) && count($data) > 0) {
            $counter = 1;
            foreach ($data as $object) {
                if ($object instanceof Element\ElementInterface) {
                    $return[] = [
                        "dest_id" => $object->getId(),
                        "type" => Element\Service::getElementType($object),
                        "fieldname" => $this->getName(),
                        "index" => $counter
                    ];
                }
                $counter++;
            }

            return $return;
        } elseif (is_array($data) and count($data) === 0) {
            //give empty array if data was not null
            return [];
        } else {
            //return null if data was null  - this indicates data was not loaded
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
        $elements = [];
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                if ($element["type"] == "object") {
                    $e = Object::getById($element["dest_id"]);
                } elseif ($element["type"] == "asset") {
                    $e = Asset::getById($element["dest_id"]);
                } elseif ($element["type"] == "document") {
                    $e = Document::getById($element["dest_id"]);
                }

                if ($e instanceof Element\ElementInterface) {
                    $elements[] = $e;
                }
            }
        }
        //must return array - otherwise this means data is not loaded
        return $elements;
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

        $d = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {
                if ($element instanceof Element\ElementInterface) {
                    $elementType = Element\Service::getElementType($element);
                    $d[] = $elementType . "|" . $element->getId();
                }
            }

            return "," . implode(",", $d) . ",";
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
            foreach ($data as $element) {
                if ($element instanceof Object\Concrete) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), "object", $element->getClassName()];
                } elseif ($element instanceof Object\AbstractObject) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), "object", "folder"];
                } elseif ($element instanceof Asset) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), "asset", $element->getType()];
                } elseif ($element instanceof Document) {
                    $return[] = [$element->getId(), $element->getRealFullPath(), "document", $element->getType()];
                }
            }
            if (empty($return)) {
                $return = false;
            }

            return $return;
        }

        return false;
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

        $elements = [];
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
                    $elements[] = $e;
                }
            }
        }
        //must return array if data shall be set
        return $elements;
    }

    /**
     * @param $data
     * @param null $object
     * @param array $params
     * @return array
     *
     * @todo: $pathes is undefined
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        if (is_array($data)) {

            foreach ($data as $eo) {
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
            $pathes = [];

            foreach ($data as $e) {
                if ($e instanceof Element\ElementInterface) {
                    $pathes[] = get_class($e) . $e->getRealFullPath();
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
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
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
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $this->getAsIntegerCast($height);

        return $this;
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
            throw new Element\ValidationException("Empty mandatory field [ " . $this->getName() . " ]");
        }

        $allow = true;
        if (is_array($data)) {
            foreach ($data as $d) {
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
            foreach ($data as $eo) {
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
     * fills object field data values from CSV Import String
     * @abstract
     * @param string $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return Object\ClassDefinition\Data
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $values = explode(",", $importValue);

        $value = [];
        foreach ($values as $element) {
            $tokens = explode(":", $element);
            if (count($tokens) == 2) {
                $type = $tokens[0];
                $path = $tokens[1];
                $value[] = Element\Service::getElementByPath($type, $path);
            } else {
                //fallback for old export files
                if ($el = Asset::getByPath($element)) {
                    $value[] = $el;
                } elseif ($el = Document::getByPath($element)) {
                    $value[] = $el;
                } elseif ($el = Object::getByPath($element)) {
                    $value[] = $el;
                }
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
            foreach ($data as $element) {
                if ($element instanceof Element\ElementInterface && !array_key_exists($element->getCacheTag(), $tags)) {
                    $tags = $element->getCacheTags($tags);
                }
            }
        }

        return $tags;
    }

    /**
     * @param $data
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $e) {
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

    /**
     * converts data to be exposed via webservices
     * @param string $object
     * @param mixed $params
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            $items = [];
            foreach ($data as $eo) {
                if ($eo instanceof Element\ElementInterface) {
                    $items[] = [
                        "type" => Element\Service::getType($eo),
                        "subtype" => $eo->getType(),
                        "id" => $eo->getId()
                    ];
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
                        $hrefs[] = $e;
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
                //$data = $this->getDataFromResource($object->getRelationData($this->getName(), true, null));
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

        if (Object::doHideUnpublished() and is_array($data)) {
            $publishedList = [];
            foreach ($data as $listElement) {
                if (Element\Service::isPublished($listElement)) {
                    $publishedList[] = $listElement;
                }
            }

            return $publishedList;
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param $object
     * @param $data
     * @param array $params
     * @return array|null
     */
    public function preSetData($object, $data, $params = [])
    {
        if ($data === null) {
            $data = [];
        }

        if ($object instanceof Object\Concrete) {
            if ($this->getLazyLoading() and !in_array($this->getName(), $object->getO__loadedLazyFields())) {
                $object->addO__loadedLazyField($this->getName());
            }
        }

        return $data;
    }

    /**
     * @param $maxItems
     * @return $this
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = $this->getAsIntegerCast($maxItems);

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxItems()
    {
        return $this->maxItems;
    }

    /**
     * @param $assetUploadPath
     * @return $this
     */
    public function setAssetUploadPath($assetUploadPath)
    {
        $this->assetUploadPath = $assetUploadPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getAssetUploadPath()
    {
        return $this->assetUploadPath;
    }


    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     * @param $data
     * @param null $object
     * @param mixed $params
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        $value = [];
        $value["type"] = "html";
        $value["html"] = "";

        if ($data) {
            $html = $this->getVersionPreview($data, $object, $params);
            $value["html"] = $html;
        }

        return $value;
    }

    /** See parent class.
     * @param $data
     * @param null $object
     * @param mixed $params
     * @return null|Pimcore_Date
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data) {
            $tabledata = $data[0]["data"];

            $result = [];
            if ($tabledata) {
                foreach ($tabledata as $in) {
                    $out = [];
                    $out["id"] = $in[0];
                    $out["path"] = $in[1];
                    $out["type"] = $in[2];
                    $out["subtype"] = $in[3];
                    $result[] = $out;
                }
            }

            return $this->getDataFromEditmode($result, $object, $params);
        }

        return;
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
        $data = $this->rewriteIdsService($data, $idMapping);

        return $data;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition)
    {
        $this->maxItems = $masterDefinition->maxItems;
        $this->assetUploadPath = $masterDefinition->assetUploadPath;
        $this->relationType = $masterDefinition->relationType;
        $this->objectsAllowed = $masterDefinition->objectsAllowed;
        $this->assetsAllowed = $masterDefinition->assetsAllowed;
        $this->assetTypes = $masterDefinition->assetTypes;
        $this->documentsAllowed = $masterDefinition->documentsAllowed;
        $this->documentTypes = $masterDefinition->documentTypes;
    }


    /**
     * @return string
     */
    public function getPhpdocType()
    {
        return implode(' | ', $this->getPhpDocClassString(true));
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
            foreach ($value as $element) {
                $type = Element\Service::getType($element);
                $id = $element->getId();
                $result[] =  [
                    "type" => $type,
                    "id" => $id
                ];
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
            foreach ($value as $elementData) {
                $type = $elementData["type"];
                $id = $elementData["id"];
                $element = Element\Service::getElementById($type, $id);
                if ($element) {
                    $result[] = $element;
                }
            }

            return $result;
        }
    }
}
