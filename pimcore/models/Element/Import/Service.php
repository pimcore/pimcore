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
 * @package    Element
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Element_Import_Service
{

    /**
     * @var Webservice_Service
     */
    protected $webService;

    /**
     * @var array
     */
    protected $importInfo;

    /**
     * @var User
     */
    protected $user;


    public function __construct($user)
    {
        $this->webService = new Webservice_Service();
        $this->importInfo = array();
        $this->user = $user;
    }


    public function getWebservice()
    {
        return $this->webService;
    }

    /**
     * @return array
     */
    public function getImportInfo()
    {
        return $this->importInfo;
    }

    /**
     * @throws Exception
     * @param  $rootElement
     * @param  $apiKey
     * @param  $path
     * @param  $apiElement
     * @param  bool $overwrite
     * @param  $elementCounter
     * @return Element_Interface
     */
    public function create($rootElement, $apiKey, $path, $apiElement, $overwrite, $elementCounter)
    {


        //correct relative path
        if (strpos($path, "/") !== 0) {
            $path = $rootElement->getFullPath() . "/" . $path;
        }

        $type = $apiElement->type;

        if ($apiElement instanceof Webservice_Data_Asset) {
            $className = "Asset_" . ucfirst($type);
            $parentClassName = "Asset";
            $maintype = "asset";
            $fullPath = $path . $apiElement->filename;
        } else if ($apiElement instanceof Webservice_Data_Object) {
            $maintype = "object";
            if ($type == "object") {
                $className = "Object_" . ucfirst($apiElement->className);
                if (!Pimcore_Tool::classExists($className)) {
                    throw new Exception("Unknown class [ " . $className . " ]");
                }
            } else {
                $className = "Object_" . ucfirst($type);
            }
            $parentClassName = "Object_Abstract";
            $fullPath = $path . $apiElement->key;
        } else if ($apiElement instanceof Webservice_Data_Document) {
            $maintype = "document";
            $className = "Document_" . ucfirst($type);
            $parentClassName = "Document";
            $fullPath = $path . $apiElement->key;
        } else {
            throw new Exception("Unknown import element");
        }

        $existingElement = $className::getByPath($fullPath);
        if ($overwrite && $existingElement) {
            $apiElement->parentId = $existingElement->getParentId();
            return $existingElement;
        }

        $element = new $className();
        $element->setId(null);
        $element->setCreationDate(time());
        if ($element instanceof Asset) {
            $element->setFilename($apiElement->filename);
            $element->setData(base64_decode($apiElement->data));
        } else if ($element instanceof Object_Concrete) {
            $element->setKey($apiElement->key);
            $element->setO_className($apiElement->className);
            $class = Object_Class::getByName($apiElement->className);
            if (!$class instanceof Object_Class) {
                throw new Exception("Unknown object class [ " . $apiElement->className . " ] ");
            }
            $element->setO_classId($class->getId());

        } else {
            $element->setKey($apiElement->key);
        }

        $this->setModificationParams($element, true);
        $key = $element->getKey();
        if (empty($key) and $apiElement->id == 1) {
            if ($element instanceof Asset) {
                $element->setFilename("home_" . uniqid());
            } else {
                $element->setKey("home_" . uniqid());
            }
        } else if (empty($key)) {
            throw new Exception ("Cannot create element without key ");
        }

        $parent = $parentClassName::getByPath($path);

        if (Element_Service::getType($rootElement) == $maintype and $parent) {
            $element->setParentId($parent->getId());
            $apiElement->parentId = $parent->getId();
            $existingElement = $parentClassName::getByPath($parent->getFullPath() . "/" . $element->getKey());
            if ($existingElement) {
                //set dummy key to avoid duplicate paths
                if ($element instanceof Asset) {
                    $element->setFilename(str_replace("/", "_", $apiElement->path) . uniqid() . "_" . $elementCounter . "_" . $element->getFilename());
                } else {
                    $element->setKey(str_replace("/", "_", $apiElement->path) . uniqid() . "_" . $elementCounter . "_" . $element->getKey());
                }
            }
        } else if (Element_Service::getType($rootElement) != $maintype) {
            //this is a related element - try to import it to it's original path or set the parent to home folder
            $potentialParent = $parentClassName::getByPath($path);

            //set dummy key to avoid duplicate paths
            if ($element instanceof Asset) {
                $element->setFilename(str_replace("/", "_", $apiElement->path) . uniqid() . "_" . $elementCounter . "_" . $element->getFilename());
            } else {
                $element->setKey(str_replace("/", "_", $apiElement->path) . uniqid() . "_" . $elementCounter . "_" . $element->getKey());
            }

            if ($potentialParent) {
                $element->setParentId($potentialParent->getId());
                //set actual id and path for second run
                $apiElements[$apiKey]["path"] = $potentialParent->getFullPath();
                $apiElement->parentId = $potentialParent->getId();
            } else {
                $element->setParentId(1);
                //set actual id and path for second run
                $apiElements[$apiKey]["path"] = "/";
                $apiElement->parentId = 1;
            }
        } else {
            $element->setParentId($rootElement->getId());
            //set actual id and path for second run
            $apiElements[$apiKey]["path"] = $rootElement->getFullPath();
            $apiElement->parentId = $rootElement->getId();

            //set dummy key to avoid duplicate paths
            if ($element instanceof Asset) {
                $element->setFilename(str_replace("/", "_", $apiElement->path) . uniqid() . "_" . $elementCounter . "_" . $element->getFilename());
            } else {
                $element->setKey(str_replace("/", "_", $apiElement->path) . uniqid() . "_" . $elementCounter . "_" . $element->getKey());
            }

        }

        //if element exists, make temp key permanent by setting it in apiElement
        if ($parentClassName::getByPath($fullPath)) {

            if ($element instanceof Asset) {
                $apiElement->filename = $element->getFilename();
            } else {
                $apiElement->key = $element->getKey();
            }
        }

        $element->save();

        //todo save type and id for later rollback
        $this->importInfo[Element_Service::getType($element) . "_" . $element->getId()] = array("id" => $element->getId(), "type" => Element_Service::getType($element), "fullpath" => $element->getFullPath());


        return $element;

    }

    /**
     * @param Webservice_Data $apiElement
     * @param string $type
     * @param array $idMapping
     * @return void
     */
    public function correctElementIdRelations($apiElement, $type, $idMapping)
    {

        //correct id
        $apiElement->id = $idMapping[$type][$apiElement->id];

        //correct properties
        if ($apiElement->properties) {
            foreach ($apiElement->properties as $property) {
                if (in_array($property->type, array("asset", "object", "document"))) {
                    $property->data = $idMapping[$property->type][$property->data];
                }
            }
        }

    }

    /**
     * @param  Webservice_Data_Document_PageSnippet $apiElement
     * @param  array $idMapping
     * @return void
     */
    public function correctDocumentRelations($apiElement, $idMapping)
    {
        if ($apiElement->elements) {
            foreach ($apiElement->elements as $el) {

                if ($el->type == "href" and is_object($el->value) and $el->value->id) {
                    $el->value->id = $idMapping[$el->value->type][$el->value->id];
                } else if ($el->type == "image" and is_object($el->value) and $el->value->id) {
                    $el->value->id = $idMapping["asset"][$el->value->id];
                } else if ($el->type == "wysiwyg" and is_object($el->value) and $el->value->text) {
                    $el->value->text = Pimcore_Tool_Text::replaceWysiwygTextRelationIds($idMapping, $el->value->text);
                } else if ($el->type == "link" and is_object($el->value) and is_array($el->value->data) and $el->value->data["internalId"]) {
                    $el->value->data["internalId"] = $idMapping[$el->value->data["internalType"]][$el->value->data["internalId"]];
                } else if ($el->type == "video" and is_object($el->value) and $el->value->type == "asset") {
                    $el->value->id = $idMapping[$el->value->type][$el->value->id];
                } else if ($el->type == "snippet" and is_object($el->value) and $el->value->id) {
                    $el->value->id = $idMapping["document"][$el->value->id];
                } else if ($el->type == "renderlet" and is_object($el->value) and $el->value->id) {
                    $el->value->id = $idMapping[$el->value->type][$el->value->id];
                }
            }
        }
    }

    /**
     * @param  Webservice_Data_Object_Concrete $apiElement
     * @return void
     */
    public function correctObjectRelations($apiElement, $idMapping)
    {
        if ($apiElement->elements) {
            foreach ($apiElement->elements as $el) {
                if ($el->type == "href" and $el->value["id"]) {
                    $el->value["id"] = $idMapping[$el->value["type"]][$el->value["id"]];
                } else if ($el->type == "image" and $el->value) {
                    $el->value = $idMapping["asset"][$el->value];
                } else if ($el->type == "link" and $el->value["internal"]) {
                    $el->value["internal"] = $idMapping[$el->value["internalType"]][$el->value["internal"]];
                } else if ($el->type == "multihref") {
                    if (is_array($el->value)) {
                        for ($i = 0; $i < count($el->value); $i++) {
                            $el->value[$i]["id"] = $idMapping[$el->value[$i]["type"]][$el->value[$i]["id"]];
                        }
                    }

                } else if ($el->type == "objects") {
                    if (is_array($el->value)) {
                        for ($i = 0; $i < count($el->value); $i++) {
                            $el->value[$i]["id"] = $idMapping["object"][$el->value[$i]["id"]];
                        }
                    }

                } else if ($el->type == "wysiwyg") {
                    $el->value = Pimcore_Tool_Text::replaceWysiwygTextRelationIds($idMapping, $el->value);
                } else if ($el->type == "fieldcollections") {
                    if ($el instanceof Webservice_Data_Object_Element and is_array($el->value)) {
                        foreach ($el->value as $fieldCollectionEl) {
                            if (is_array($fieldCollectionEl->value)) {
                                foreach ($fieldCollectionEl->value as $collectionItem) {
                                    if ($collectionItem->type == "image") {
                                        $collectionItem->value = $idMapping["asset"][$collectionItem->value];
                                    } else if ($collectionItem->type == "wysiwyg") {
                                        $collectionItem->value = Pimcore_Tool_Text::replaceWysiwygTextRelationIds($idMapping, $collectionItem->value);
                                    } else if ($collectionItem->type == "link" and $collectionItem->value["internalType"]) {
                                        $collectionItem->value["internal"] = $idMapping[$collectionItem->value["internalType"]][$collectionItem->value["internal"]];
                                    } else if ($collectionItem->type == "href" and $collectionItem->value["id"]){
                                        $collectionItem->value["id"] = $idMapping[$collectionItem->value["type"]][$collectionItem->value["id"]];
                                    } else if (($collectionItem->type == "objects" or $collectionItem->type == "multihref") and is_array($collectionItem->value) and count($collectionItem->value)>0){
                                        for($i=0; $i < count($collectionItem->value);$i++){
                                            if($collectionItem->value[$i]["id"]){
                                                $collectionItem->value[$i]["id"] = $idMapping[$collectionItem->value[$i]["type"]][$collectionItem->value[$i]["id"]];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }


                } else if ($el->type == "localizedfields") {
                    if (is_array($el->value)) {
                        foreach ($el->value as $localizedDataEl) {

                            if ($localizedDataEl->type == "image") {
                                $localizedDataEl->value = $idMapping["asset"][$localizedDataEl->value];
                            } else if ($localizedDataEl->type == "wysiwyg") {
                                $localizedDataEl->value = Pimcore_Tool_Text::replaceWysiwygTextRelationIds($idMapping, $localizedDataEl->value);
                            } else if ($localizedDataEl->type == "link" and $localizedDataEl->value["internalType"]) {
                                $localizedDataEl->value["internal"] = $idMapping[$localizedDataEl->value["internalType"]][$localizedDataEl->value["internal"]];
                            }

                        }
                    }
                }
            }
        }
    }

    /**
     * @param  Element_Interface $element
     * @return void
     */
    public function setModificationParams($element, $creation = false)
    {
        $user = $this->user;
        if (!$user instanceof User) {
            throw new Exception("No user present");
        }
        if ($creation) {
            $element->setUserOwner($user->getId());
        }
        $element->setUserModification($user->getId());
        $element->setModificationDate(time());
    }


}