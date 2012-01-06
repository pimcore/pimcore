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
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Webservice_Service
{


    public function getUser()
    {
        try {
            $user = Zend_Registry::get("pimcore_user");
            if (!$user instanceof User) {
                Logger::critical("Webservice instantiated, but no user present");
            }
            return $user;
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }


    /**
     * @param int $id
     * @return Webservice_Data_Document_Folder_Out
     */
    public function getDocumentFolderById($id)
    {
        try {
            $folder = Document::getById($id);
            if ($folder instanceof Document_Folder) {
                $className = Webservice_Data_Mapper::findWebserviceClass($folder, "out");
                $apiFolder = Webservice_Data_Mapper::map($folder, $className, "out");
                return $apiFolder;
            }

            throw new Exception("Document Folder with given ID (" . $id . ") does not exist.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param int $id
     * @return Webservice_Data_Document_Link_Out
     */
    public function getDocumentLinkById($id)
    {
        try {
            $link = Document::getById($id);
            if ($link instanceof Document_Link) {
                $className = Webservice_Data_Mapper::findWebserviceClass($link, "out");
                $apiLink = Webservice_Data_Mapper::map($link, $className, "out");
                return $apiLink;
            }

            throw new Exception("Document Link with given ID (" . $id . ") does not exist.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param int $id
     * @return Webservice_Data_Document_Page_Out
     */
    public function getDocumentPageById($id)
    {
        try {
            $page = Document::getById($id);
            if ($page instanceof Document_Page) {
                // load all data (eg. href, snippet, ... which are lazy loaded)
                Document_Service::loadAllDocumentFields($page);
                $className = Webservice_Data_Mapper::findWebserviceClass($page, "out");
                $apiPage = Webservice_Data_Mapper::map($page, $className, "out");
                return $apiPage;
            }

            throw new Exception("Document Page with given ID (" . $id . ") does not exist.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param int $id
     * @return Webservice_Data_Document_Snippet_Out
     */
    public function getDocumentSnippetById($id)
    {
        try {
            $snippet = Document::getById($id);
            if ($snippet instanceof Document_Snippet) {
                // load all data (eg. href, snippet, ... which are lazy loaded)
                Document_Service::loadAllDocumentFields($snippet);
                $className = Webservice_Data_Mapper::findWebserviceClass($snippet, "out");
                $apiSnippet = Webservice_Data_Mapper::map($snippet, $className, "out");

                return $apiSnippet;
            }

            throw new Exception("Document Snippet with given ID (" . $id . ") does not exist.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param string $condition
     * @param string $order
     * @param string $orderKey
     * @param string $offset
     * @param string $limit
     * @param string $groupBy
     * @return Webservice_Data_Document_List_Item[]
     */
    public function getDocumentList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null)
    {
        try {
            $list = Document::getList(array(
                                           "condition" => $condition,
                                           "order" => $order,
                                           "orderKey" => $orderKey,
                                           "offset" => $offset,
                                           "limit" => $limit,
                                           "groupBy" => $groupBy
                                      ));

            $items = array();
            foreach ($list as $doc) {
                $item = new Webservice_Data_Document_List_Item();
                $item->id = $doc->getId();
                $item->type = $doc->getType();

                $items[] = $item;
            }

            return $items;
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteDocument($id)
    {
        try {
            $doc = Document::getById($id);
            if ($doc instanceof Document) {
                $doc->delete();
                return true;
            }

            throw new Exception("Document with given ID (" . $id . ") does not exist.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Document_Page_In $wsDocument
     * @return bool
     */
    public function updateDocumentPage($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Document_Page_In) {
                return $this->updateDocument($wsDocument);
            } else {
                throw new Exception("Unable to update Document Page. Inappropriate Data given");
            }
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Document_Folder_In $wsDocument
     * @return bool
     */
    public function updateDocumentFolder($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Document_Folder_In) {
                return $this->updateDocument($wsDocument);
            } else {
                throw new Exception("Unable to update Document Folder. Inappropriate Data given");
            }
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Document_Snippet_In $wsDocument
     * @return bool
     */
    public function updateDocumentSnippet($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Document_Snippet_In) {
                return $this->updateDocument($wsDocument);
            } else {
                throw new Exception("Unable to update Document Snippet. Inappropriate Data given");
            }
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Document_Link_In $wsDocument
     * @return bool
     */
    public function updateDocumentLink($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Document_Link_In) {
                return $this->updateDocument($wsDocument);
            } else {
                throw new Exception("Unable to update Document Link. Inappropriate Data given");
            }
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Object_Folder_In $wsDocument
     * @return bool
     */
    public function updateObjectFolder($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Object_Folder_In) {
                return $this->updateObject($wsDocument);
            } else {
                throw new Exception("Unable to update Object Folder. Inappropriate Data given");
            }
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Object_Concrete_In $wsDocument
     * @return bool
     */
    public function updateObjectConcrete($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Object_Concrete_In) {
                return $this->updateObject($wsDocument);
            } else {
                throw new Exception("Unable to update Object Concrete. Inappropriate Data given");
            }
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Asset_Folder_In $wsDocument
     * @return bool
     */
    public function updateAssetFolder($wsDocument)
    {

        try {
            if ($wsDocument instanceof Webservice_Data_Asset_Folder_In) {
                return $this->updateAsset($wsDocument);
            } else {
                throw new Exception("Unable to update Asset Folder. Inappropriate Data given");
            }
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Asset_File_In $wsDocument
     * @return bool
     */
    public function updateAssetFile($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Asset_File_In) {
                return $this->updateAsset($wsDocument);
            } else {
                throw new Exception("Unable to update Asset Folder. Inappropriate Data given");
            }
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Document_Page_In $document
     * @return int
     */
    public function createDocumentPage($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Document_Page_In) {
                $wsDocument->type = "page";
                $document = new Document_Page();
                return $this->create($wsDocument, $document);
            }
            throw new Exception("Unable to create new Document Page.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Document_Snippet_In $document
     * @return int
     */
    public function createDocumentSnippet($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Document_Snippet_In) {
                $wsDocument->type = "snippet";
                $document = new Document_Snippet();
                return $this->create($wsDocument, $document);
            }

            throw new Exception("Unable to create new Document Snippet.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Document_Folder_In $document
     * @return int
     */
    public function createDocumentFolder($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Document_Folder_In) {
                $wsDocument->type = "folder";
                $document = new Document_Folder();
                return $this->create($wsDocument, $document);
            }
            throw new Exception("Unable to create new Document Folder.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Document_Link_In $document
     * @return int
     */
    public function createDocumentLink($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Document_Link_In) {
                $wsDocument->type = "link";
                $document = new Document_Link();
                return $this->create($wsDocument, $document);
            }
            throw new Exception("Unable to create new Document Link.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Asset_Folder_In $object
     * @return int
     */
    public function createAssetFolder($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Asset_Folder_In) {
                $wsDocument->type = "folder";
                $asset = new Asset_Folder();
                return $this->create($wsDocument, $asset);
            }
            throw new Exception("Unable to create new Asset Folder.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Asset_File_In $object
     * @return int
     */
    public function createAssetFile($wsDocument)
    {

        try {
            if ($wsDocument instanceof Webservice_Data_Asset_File_In) {

                $type = $wsDocument->type;
                if (!empty($type)) {
                    $type = "Asset_" . ucfirst($type);
                    $asset = new $type();
                    //TODO: maybe introduce an Asset_Abstract from which all Asset_Files should be derived
                    if ($asset instanceof Asset and !$asset instanceof Asset_Folder) {
                        return $this->create($wsDocument, $asset);
                    } else {
                        throw new Exception("Unable to create new Asset File, could not instantiate Asset with given type[ $type ]");
                    }
                } else {
                    throw new Exception("Unable to create new Asset File, no type  provided");
                }
            }

            throw new Exception("Unable to create new Asset File.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Object_Folder_In $object
     * @return int
     */
    public function createObjectFolder($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice_Data_Object_Folder_In) {
                $wsDocument->type = "folder";
                $object = new Object_Folder();
                return $this->create($wsDocument, $object);
            }

            throw new Exception("Unable to create new Object Folder.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param Webservice_Data_Object_Concrete_In $object
     * @return int
     */
    public function createObjectConcrete($wsDocument)
    {
//        p_r($wsDocument); die();
        try {
            if ($wsDocument instanceof Webservice_Data_Object_Concrete_In) {
                $wsDocument->type = "object";
                $classname = "Object_" . ucfirst($wsDocument->className);
                if (Pimcore_Tool::classExists($classname)) {
                    $object = new $classname();

                    if ($object instanceof Object_Concrete) {
                        return $this->create($wsDocument, $object);
                    } else {
                        throw new Exception("Unable to create new Object Concrete, could not instantiate Object with given class name [ $classname ]");
                    }
                } else {
                    throw new Exception("Unable to create new Object Concrete, no class name provided");
                }
            }

            throw new Exception("Unable to create new Object Concrete.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }


    /**
     * @param int $id
     * @return Webservice_Data_Asset_Folder_Out
     */
    public function getAssetFolderById($id)
    {
        try {
            $asset = Asset::getById($id);
            if ($asset instanceof Asset_Folder) {
                $className = Webservice_Data_Mapper::findWebserviceClass($asset, "out");
                $apiAsset = Webservice_Data_Mapper::map($asset, $className, "out");
                return $apiAsset;
            }

            throw new Exception("Asset Folder with given ID (" . $id . ") does not exist.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param int $id
     * @return Webservice_Data_Asset_File_Out
     */
    public function getAssetFileById($id)
    {
        try {
            $asset = Asset::getById($id);
            if ($asset instanceof Asset) {
                $apiAsset = Webservice_Data_Mapper::map($asset, "Webservice_Data_Asset_File_Out", "out");
                return $apiAsset;
            }

            throw new Exception("Asset File with given ID (" . $id . ") does not exist.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param string $condition
     * @param string $order
     * @param string $orderKey
     * @param string $offset
     * @param string $limit
     * @param string $groupBy
     * @return Webservice_Data_Asset_List_Item[]
     */
    public function getAssetList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null)
    {
        try {
            $params = array();

            if (!empty($condition)) $params["condition"] = $condition;
            if (!empty($order)) $params["order"] = $order;
            if (!empty($orderKey)) $params["orderKey"] = $orderKey;
            if (!empty($offset)) $params["offset"] = $offset;
            if (!empty($limit)) $params["limit"] = $limit;
            if (!empty($groupBy)) $params["groupBy"] = $groupBy;


            $list = Asset::getList($params);

            $items = array();
            foreach ($list as $asset) {
                $item = new Webservice_Data_Asset_List_Item();
                $item->id = $asset->getId();
                $item->type = $asset->getType();

                $items[] = $item;
            }

            return $items;
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteAsset($id)
    {

        try {
            $asset = Asset::getById($id);
            if ($asset instanceof Asset) {
                $asset->delete();
                return true;
            }

            throw new Exception("Asset with given ID (" . $id . ") does not exist.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param int $id
     * @return Webservice_Data_Object_Folder_Out
     */
    public function getObjectFolderById($id)
    {
        try {
            $folder = Object_Abstract::getById($id);
            if ($folder instanceof Object_Folder) {
                $apiFolder = Webservice_Data_Mapper::map($folder, "Webservice_Data_Object_Folder_Out", "out");
                return $apiFolder;
            }

            throw new Exception("Object Folder with given ID (" . $id . ") does not exist.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param int $id
     * @return Webservice_Data_Object_Concrete_Out
     */
    public function getObjectConcreteById($id)
    {
        try {
            $object = Object_Concrete::getById($id);

            if ($object instanceof Object_Concrete) {
                // load all data (eg. lazy loaded fields like multihref, object, ...)
                Object_Service::loadAllObjectFields($object);
                $apiObject = Webservice_Data_Mapper::map($object, "Webservice_Data_Object_Concrete_Out", "out");
                return $apiObject;
            }

            throw new Exception("Object with given ID (" . $id . ") does not exist.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param string $condition
     * @param string $order
     * @param string $orderKey
     * @param string $offset
     * @param string $limit
     * @param string $groupBy
     * @param string $objectClass
     * @return Webservice_Data_Object_List_Item[]
     */
    public function getObjectList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $objectClass = null)
    {
        try {
            $params = array();

            if (!empty($condition)) $params["condition"] = $condition;
            if (!empty($order)) $params["order"] = $order;
            if (!empty($orderKey)) $params["orderKey"] = $orderKey;
            if (!empty($offset)) $params["offset"] = $offset;
            if (!empty($limit)) $params["limit"] = $limit;
            if (!empty($groupBy)) $params["groupBy"] = $groupBy;

            $listClassName = "Object_Abstract";
            if(!empty($objectClass)) {
                $listClassName = "Object_" . ucfirst($objectClass);
                if(!Pimcore_Tool::classExists($listClassName)) {
                    $listClassName = "Object_Abstract";
                }
            }

            $list = $listClassName::getList($params);

            $items = array();
            foreach ($list as $object) {
                $item = new Webservice_Data_Object_List_Item();
                $item->id = $object->getId();
                $item->type = $object->getType();

                $items[] = $item;
            }

            return $items;
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteObject($id)
    {
        try {
            $object = Object_Abstract::getById($id);
            if ($object instanceof Object_Abstract) {
                $object->delete();
                return true;
            }

            throw new Exception("Object with given ID (" . $id . ") does not exist.");
        } catch (Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param  Webservice_Data $wsDocument
     * @param  Element_Interface $element
     * @return int
     */
    protected function create($wsDocument, $element)
    {

        $wsDocument->reverseMap($element);
        $element->setId(null);
        $element->setCreationDate(time());
        $this->setModificationParams($element, true);
        $key = $element->getKey();
        if (empty($key)) {
            throw new Exception ("Cannot create element without key");
        }

        $element->save();

        return $element->getId();
    }

    /**
     * Returns a uniqe key for the document in the $target-Path (recursive)
     * @param Element_Interface $element
     */
    protected function getSaveCopyName($element, $key, $path)
    {


        if ($element instanceof Object_Abstract) {
            $equal = Object_Abstract::getByPath($path . "/" . $key);
        } else  if ($element instanceof Document) {
            $equal = Document::getByPath($path . "/" . $key);
        } else if ($element instanceof Asset) {
            $equal = Asset::getByPath($path . "/" . $key);
        }

        if ($equal) {
            $key .= "_WScopy";
            return $this->getSaveCopyName($element, $key, $path);
        }
        return $key;

    }


    /**
     * @param Webservice_Data_Document $wsDocument
     * @return bool
     */
    protected function updateDocument($wsDocument)
    {
        $document = Document::getById($wsDocument->id);
        $this->setModificationParams($document, false);


        if ($document instanceof Document and strtolower($wsDocument->type) == $document->getType()) {
            $wsDocument->reverseMap($document);
            $document->save();
            return true;
        } else if ($document instanceof Document) {
            throw new Exception("Type mismatch for given document with ID [" . $wsDocument->id . "] and existing document with id [" . $document->getId() . "]");
        } else {
            throw new Exception("Document with given ID (" . $wsDocument->id . ") does not exist.");
        }
    }


    /**
     * @param Webservice_Data_Object $wsDocument
     * @return bool
     */
    protected function updateObject($wsDocument)
    {
        $object = Object_Abstract::getById($wsDocument->id);

        $this->setModificationParams($object, false);
        if ($object instanceof Object_Concrete and $object->getO_className() == $wsDocument->className) {

            $wsDocument->reverseMap($object);
            $object->save();
            return true;
        } else if ($object instanceof Object_Folder and $object->getType() == strtolower($wsDocument->type)) {
            $wsDocument->reverseMap($object);
            $object->save();
            return true;
        } else if ($object instanceof Object_Abstract) {
            throw new Exception("Type/Class mismatch for given object with ID [" . $wsDocument->id . "] and existing object with id [" . $object->getId() . "]");
        } else {
            throw new Exception("Object with given ID (" . $wsDocument->id . ") does not exist.");
        }
    }


    /**
     * @param Webservice_Data_Asset $wsDocument
     * @return bool
     */
    protected function updateAsset($wsDocument)
    {

        $asset = Asset::getById($wsDocument->id);
        $this->setModificationParams($asset, false);
        if ($asset instanceof Asset and $asset->getType() == strtolower($wsDocument->type)) {
            $wsDocument->reverseMap($asset);
            $asset->save();
            return true;
        } else if ($asset instanceof Asset) {
            throw new Exception("Type mismatch for given asset with ID [" . $wsDocument->id . "] and existing asset with id [" . $asset->getId() . "]");
        } else {
            throw new Exception("Asset with given ID (" . $wsDocument->id . ") does not exist.");
        }

    }


    /**
     * @param  Element_Interface $element
     * @return void
     */
    protected function setModificationParams($element, $creation = false)
    {
        $user = $this->getUser();
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
