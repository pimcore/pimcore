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
 * @package    Document
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 *
 */

class Document extends Pimcore_Model_Abstract implements Document_Interface {

    /**
     * possible types of a document
     * @var array
     */
    public static $types = array("folder", "page", "snippet", "link");

    
    private static $hidePublished = false;
    public static function setHideUnpublished($hidePublished) {
        self::$hidePublished = $hidePublished;
    }
    public static function doHideUnpublished() {
        return self::$hidePublished;
    }


    /**
     * ID of the document
     *
     * @var integer
     */
    public $id;

    /**
     * ID of the parent document, on root document this is null
     *
     * @var integer
     */
    public $parentId;

    /**
     * @var Document
     */
    public $parent;

    /**
     * Type of the document as string (enum)
     * Possible values: page,snippet,link,folder
     *
     * @var string
     */
    public $type;

    /**
     * Filename/Key of the document
     *
     * @var string
     */
    public $key;

    /**
     * Path to the document, not conaining the key (the full path of the parent document)
     *
     * @var string
     */
    public $path;

    /**
     * @var string old path before update, later needed to update children
     */
    protected $_oldPath;

    /**
     * Sorter index in the tree, can also be used for generating a navigation and so on
     *
     * @var integer
     */
    public $index;

    /**
     * published or not
     *
     * @var bool
     */
    public $published = true;

    /**
     * timestamp of creationdate
     *
     * @var integer
     */
    public $creationDate;

    /**
     * timestamp of modificationdate
     *
     * @var integer
     */
    public $modificationDate;

    /**
     * User-ID of the owner
     *
     * @var integer
     */
    public $userOwner;

    /**
     * User-ID of the user last modified the document
     *
     * @var integer
     */
    public $userModification;

    /**
     * Permissions set to this document (user-independend)
     * Contains a list of Document_Permissions
     *
     * @var array
     */
    public $permissions = null;

    /**
     * Permissions for the user which requested this document in editmode
     *
     * @var Document_Permissions
     */
    public $userPermissions;

    /**
     * Dependencies for this document
     *
     * @var Dependency
     */
    public $dependencies;

    /**
     * List of Property, concerning the folder
     *
     * @var array
     */
    public $properties = null;

    /**
     * Contains a list of child-documents
     *
     * @var array
     */
    public $childs;

    /**
     * Indicator of document has childs or not.
     *
     * @var boolean
     */
    public $hasChilds;

    /**
     * @var string
     */
    public $locked = null;

    /**
     * get possible types
     * @return string
     */
    public static function getTypes() {
        return self::$types;
    }

    /**
     * Static helper to get a Document by it's path, only type Document is returned, not Document_Page, ... (see getConcreteByPath() )
     *
     * @param string $path
     * @return Document
     */
    public static function getByPath($path) {

        // remove trailing slash
        if($path != "/") {
            $path = rtrim($path,"/ ");
        }

        // correct wrong path (root-node problem)
        $path = str_replace("//", "/", $path);

        try {
            $document = new Document();
            // validate path
            if (Pimcore_Tool::isValidPath($path)) {
                $document->getResource()->getByPath($path);
            }

            return self::getById($document->getId());
        }
        catch (Exception $e) {
            Logger::warning($e);
        }

        return null;
    }

    /**
     * Static helper to get a Document by it's id, only type Document is returned, not Document_Page, ... (see getConcreteById() )
     *
     * @param integer $id
     * @return Document
     */
    public static function getById($id) {

        $id = intval($id);

        if ($id < 1) {
            return null;
        }

        $cacheKey = "document_" . $id;

        try {
            $document = Zend_Registry::get($cacheKey);
            if(!$document){
                throw new Exception("Document in registry is null");   
            }
        }
        catch (Exception $e) {
            try {
                if (!$document = Pimcore_Model_Cache::load($cacheKey)) {
                    $document = new Document();
                    $document->getResource()->getById($id);

                    $typeClass = "Document_" . ucfirst($document->getType());
                    $typeClass = Pimcore_Tool::getModelClassMapping($typeClass);

                    if (class_exists($typeClass)) {
                        $document = new $typeClass();
                        Zend_Registry::set($cacheKey, $document);
                        $document->getResource()->getById($id);

                        Pimcore_Model_Cache::save($document, $cacheKey);
                    }
                }
                else {
                    Zend_Registry::set($cacheKey, $document);
                }
            }
            catch (Exception $e) {
                Logger::warning($e);
                return null;
            }
        }
        
        if(!$document) {
            return null;
        }
        
        return $document;
    }

    /**
     * Static helper to get a concrete implementation of a document by it's id, this method returns the concrete object to retrieve only the basic object use getById()
     *
     * @param Document|integer $id
     * @return Document_Page|Document_Snippet|Document_Folder|Document_Link
     */
    public static function getConcreteById($id) {
        return self::getById($id);
    }

    /**
     * Static helper to get a concrete implementation of a document by it's path, this method returns the concrete object to retrieve only the basic object use getById()
     *
     * @param string|Document $path
     * @return Document_Page|Document_Snippet|Document_Folder|Document_Link
     */
    public static function getConcreteByPath($path) {
        return self::getByPath($path);
    }

    /**
     * Static helper to quickly create a new document
     *
     * @param integer $parentId
     * @param array $data
     * @return Document
     */
    public static function create($parentId, $data = array()) {

        $document = new static();
        $document->setParentId($parentId);

        foreach ($data as $key => $value) {
            $document->setValue($key, $value);
        }
        $document->save();

        return $document;
    }


    /**
     * @param array $config
     * @return Document_List
     */
    public static function getList($config = array()) {

        if (is_array($config)) {

            $listClass = "Document_List";
            $listClass = Pimcore_Tool::getModelClassMapping($listClass);
            $list = new $listClass();

            $list->setValues($config);
            $list->load();

            return $list;
        }
    }


    /**
     * Saves the document
     *
     * @return void
     */
    public function save() {

        if (!Pimcore_Tool::isValidKey($this->getKey())) {
            throw new Exception("invalid key for object with id [ " . $this->getId() . " ]");
        }

        $this->correctPath();
        // set date
        $this->setModificationDate(time());

        if ($this->getId()) {
            $this->update();
        }
        else {
            $this->getResource()->create();
            Pimcore_API_Plugin_Broker::getInstance()->postAddDocument($this);
            $this->update();
        }
    }

    public function correctPath() {
        // set path
        if ($this->getId() != 1) { // not for the root node
            $parent = Document::getById($this->getParentId());
            if($parent) {
                $this->setPath(str_replace("//", "/", $parent->getFullPath() . "/"));
            } else {
                // parent document doesn't exist anymore, so delete this document
                $this->delete();
            }
        }

        $duplicate = Document::getByPath($this->getFullPath());
        if ($duplicate instanceof Document  and $duplicate->getId() != $this->getId()) {
            throw new Exception("Duplicate full path [ " . $this->getFullPath() . " ] - cannot create document");
        }

    }

    /**
     * Updates the document, is mostly called by save().
     * Only saves the changes to the document, but doesn't create it, so it's a good idea to use just save()
     *
     * @return void
     */
    protected function update() {

        if (!$this->getKey() && $this->getId() != 1) {
            $this->delete();
            throw new Exception("Document requires key, document with id " . $this->getId() . " deleted");
        }


        Pimcore_API_Plugin_Broker::getInstance()->preUpdateDocument($this);


        // save properties
        $this->getProperties();
        $this->getResource()->deleteAllProperties();
        if (is_array($this->getProperties()) and count($this->getProperties()) > 0) {
            foreach ($this->getProperties() as $property) {
                if (!$property->getInherited()) {
                    $property->setResource(null);
                    $property->setCid($this->getId());
                    $property->setCpath($this->getFullPath());
                    $property->save();
                }
            }
        }

        // save permissions
        $this->getPermissions();
        // remove all permissions
        $this->getResource()->deleteAllPermissions();

        if (is_array($this->permissions)) {
            foreach ($this->permissions as $permission) {
                $permission->setId(null);
                $permission->setCid($this->getId());
                $permission->setCpath($this->getFullPath());
                $permission->save();
            }
        }

        // save dependencies
        $d = $this->getDependencies();
        $d->clean();

        foreach ($this->resolveDependencies() as $requirement) {
            if ($requirement["id"] == $this->getId() && $requirement["type"] == "document") {
                // dont't add a reference to yourself
                continue;
            }
            else {
                $d->addRequirement($requirement["id"], $requirement["type"]);
            }
        }
        $d->save();

        $this->getResource()->update();

        if ($this->_oldPath) {
            $this->getResource()->updateChildsPaths($this->_oldPath);
        }

        // empty object cache
        $this->clearDependedCache();

        //set object to registry
        Zend_Registry::set("document_" . $this->getId(), $this);

        Pimcore_API_Plugin_Broker::getInstance()->postUpdateDocument($this);

    }

    public function clearDependedCache() {
        try {
            Pimcore_Model_Cache::clearTag("document_" . $this->getId());
        }
        catch (Exception $e) {
            Logger::info($e);
        }

        try {
            Pimcore_Model_Cache::clearTag("properties");
        }
        catch (Exception $e) {
            Logger::info($e);
        }

        try {
            Pimcore_Model_Cache::clearTag("output");
        }
        catch (Exception $e) {
            Logger::info($e);
        }
    }

    /**
     * Returns the dependencies of the document
     *
     * @return Dependency
     */
    public function getDependencies() {

        if (!$this->dependencies) {
            $this->dependencies = Dependency::getBySourceId($this->getId(), "document");
        }
        return $this->dependencies;
    }

    /**
     * get the cache tag for the current document
     *
     * @return string
     */
    public function getCacheTag() {
        return "document_" . $this->getId();
    }

    /**
     * Get the cache tags for the documents, resolve all dependencies to tag the cache entries
     * This is necessary to update the cache if there is a change in an depended object
     *
     * @return array
     */
    public function getCacheTags($tags = array()) {

        $tags = is_array($tags) ? $tags : array();
        
        $tags[$this->getCacheTag()] = $this->getCacheTag();
        return $tags;
    }

    /**
     * Resolve the dependencies of the document and returns an array of them - Used by update()
     *
     * @return array
     */
    public function resolveDependencies() {

        $dependencies = array();

        // check for properties
        $properties = $this->getProperties();
        foreach ($properties as $property) {
            $dependencies = array_merge($dependencies, $property->resolveDependencies());
        }

        return $dependencies;
    }

    /**
     * set the children of the document
     *
     * @return array
     */
    public function setChilds($childs) {
        $this->childs=$childs;
        if(is_array($childs) and count($childs>0)){
            $this->hasChilds=true;
        } else {
            $this->hasChilds=false;
        }
    }

    /**
     * Get a list of the Childs (not recursivly)
     *
     * @return array
     */
    public function getChilds() {

        if ($this->childs === null) {
            $list = new Document_List();
            $list->setCondition("parentId = ?", $this->getId());
            $list->setOrderKey("index");
            $list->setOrder("asc");
            $this->childs = $list->load();
        }
        return $this->childs;
    }


    /**
     * Returns true if the document has at least one child
     *
     * @return boolean
     */
    public function hasChilds() {
        if(is_bool($this->hasChilds)){
            if(($this->hasChilds and empty($this->childs)) or (!$this->hasChilds and !empty($this->childs))){
                return $this->getResource()->hasChilds();
            } else {
                return $this->hasChilds;
            }
        }
        return $this->getResource()->hasChilds();
    }


    /**
     * Inverted hasChilds()
     *
     * @return boolean
     */
    public function hasNoChilds() {
        return !$this->hasChilds();
    }

    /**
     * Returns true if the element is locked
     * @return string
     */
    public function getLocked(){
        if(empty($this->locked)) {
            return null;
        }
        return $this->locked;
    }

    /**
     * @param  $locked
     * @return void
     */
    public function setLocked($locked){
        $this->locked = $locked;
    }

    /**
     * Returns true if the element is locked 
     * @return bool
     */
    public function isLocked(){
        if($this->getLocked()) {
            return true;
        }
        
        // check for inherited
        return $this->getResource()->isLocked();
    }

    /**
     * Deletes the document
     *
     * @return void
     */
    public function delete() {

        Pimcore_API_Plugin_Broker::getInstance()->preDeleteDocument($this);

        // remove childs
        if ($this->hasChilds()) {
            foreach ($this->getChilds() as $child) {
                $child->delete();
            }
        }

        // remove all properties
        $this->getResource()->deleteAllProperties();

        // remove permissions
        $this->getResource()->deleteAllPermissions();

        // remove dependencies
        $d = $this->getDependencies();
        $d->cleanAllForElement($this);

        $this->getResource()->delete();

        // clear cache
        $this->clearDependedCache();

        //set object to registry
        Zend_Registry::set("document_" . $this->getId(), null);
    }

    /**
     * Get a list of permissions set to the document (not user-specific)
     * If they aren't already set try to get them from resource
     *
     * @return array
     */
    public function getPermissions() {
        if ($this->permissions === null) {
            $this->setPermissions($this->getResource()->getPermissions());
        }
        return $this->permissions;
    }

    /**
     * Set a list of Permissions
     *
     * @param array $permissions
     * @return void
     */
    public function setPermissions($permissions) {
        $this->permissions = $permissions;
    }

    /**
     * Returns the full path of the document including the key (path+key)
     *
     * @return string
     */
    public function getFullPath() {
        $path = $this->getPath() . $this->getKey();
        return $path;
    }

    /**
     * @return integer
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * @return integer
     */
    public function getId() {
        return (int) $this->id;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @return integer
     */
    public function getModificationDate() {
        return $this->modificationDate;
    }

    /**
     * @return integer
     */
    public function getParentId() {
        return $this->parentId;
    }

    /**
     * @return string
     */
    public function getPath() {

        // check for site
        try {

            $site = Zend_Registry::get("pimcore_site");
            if ($site instanceof Site) {
                if ($site->getRootDocument() instanceof Document_Page && $site->getRootDocument() !== $this) {
                    $rootPath = $site->getRootPath();
                    $rootPath = addcslashes($rootPath, "/");

                    return preg_replace("/^" . $rootPath . "/", "", $this->path);
                }
            }
        }
        catch (Exception $e) {
        }

        return $this->path;
    }

    /**
     * @return string
     */
    public function getRealPath() {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getRealFullPath() {
        $path = $this->getRealPath() . $this->getKey();
        return $path;
    }

    /**
     * @param integer $creationDate
     * @return void
     */
    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        //TODO: why can't I set a document ID null through setter?
        if ($id) {
            $this->id = $id;
        }
    }

    /**
     * @param integer $key
     * @return void
     */
    public function setKey($key) {
        //set old path so that child paths are updated after this document was saved
        if ($this->key != null and $key != null and $key != $this->key) {
            $this->_oldPath = $this->getResource()->getCurrentFullPath();
        }
        $this->key = $key;

    }


    /**
     * @param integer $modificationDate
     * @return void
     */
    public function setModificationDate($modificationDate) {
        $this->modificationDate = $modificationDate;
    }


    /**
     * @param integer $parentId
     * @return void
     */
    public function setParentId($parentId) {

        if ($this->parentId != null and $parentId != null and $this->parentId != $parentId) {
            $this->_oldPath = $this->getResource()->getCurrentFullPath();
        }
        $this->parentId = $parentId;
    }

    /**
     * @param integer $path
     * @return void
     */
    public function setPath($path) {
        $this->path = $path;
    }

    /**
     * @return integer
     */
    public function getIndex() {
        return $this->index;
    }

    /**
     * @param integer $index
     * @return void
     */
    public function setIndex($index) {
        $this->index = $index;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param integer $type
     * @return void
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return integer
     */
    public function getUserModification() {
        return $this->userModification;
    }

    /**
     * @return integer
     */
    public function getUserOwner() {
        return $this->userOwner;
    }

    /**
     * @param integer $userModification
     * @return void
     */
    public function setUserModification($userModification) {
        $this->userModification = $userModification;
    }

    /**
     * @param integer $userOwner
     * @return void
     */
    public function setUserOwner($userOwner) {
        $this->userOwner = $userOwner;
    }

    /**
     * @return boolean
     */
    public function isPublished() {
        return $this->getPublished();
    }

    /**
     * @return boolean
     */
    public function getPublished() {
        return (bool) $this->published;
    }

    /**
     * @param integer $published
     * @return void
     */
    public function setPublished($published) {
        $this->published = (bool) $published;
    }

    /**
     * @param Document_Permissions $permissions
     * @return void
     */
    public function setUserPermissions(Document_Permissions $permissions = null) {
        $this->userPermissions = $permissions;
    }

    /**
     * @return Document_Permissions
     */
    public function getUserPermissions(User $user = null) {
        
        // get global user if no user is specified and permissions are undefined
        if(!$user && !$this->userPermissions) {
            $user = Zend_Registry::get("pimcore_admin_user");
        }
        
        if ($user) {
            $this->userPermissions = $this->getPermissionsForUser($user);
        }
        return $this->userPermissions;
    }


    /**
     * Get a list of properties (including the inherited)
     *
     * @return Property[]
     */
    public function getProperties() {
        if ($this->properties === null) {

            // try to get from cache
            $cacheKey = "document_properties_" . $this->getId();

            if (!$properties = Pimcore_Model_Cache::load($cacheKey)) {
                $properties = $this->getResource()->getProperties();
                Pimcore_Model_Cache::save($properties, $cacheKey, array("document_properties", "properties"));
            }
            $this->setProperties($properties);
        }
        return $this->properties;
    }

    /**
     * @param array $properties
     * @return void
     */
    public function setProperties($properties) {
        $this->properties = $properties;
    }

    /**
     * Get specific property data or the property object itself ($asContainer=true) by it's name, if the property doesn't exists return null
     * @param string $name
     * @param bool $asContainer
     * @return mixed
     */
    public function getProperty($name, $asContainer = false) {
        $properties = $this->getProperties();
        if ($this->hasProperty($name)) {
            if($asContainer) {
                return $properties[$name];
            } else {
                return $properties[$name]->getData();
            }
        }
        return null;
    }

    /**
     * @param  $name
     * @return bool
     */
    public function hasProperty ($name) {
        $properties = $this->getProperties();
        return array_key_exists($name, $properties);
    }


    /**
     * @param string $name
     * @param string $type
     * @param mixed $data
     * @param bool $inherited
     * @param bool $inheritable
     * @return void
     */
    public function setProperty($name, $type, $data, $inherited = false, $inheritable = true) {

        $this->getProperties();

        $property = new Property();
        $property->setType($type);
        $property->setCid($this->getId());
        $property->setName($name);
        $property->setCtype("document");
        $property->setData($data);
        $property->setInherited($inherited);
        $property->setInheritable($inheritable);

        $this->properties[$name] = $property;
    }

    /**
     * @return Document
     */
    public function getParent() {

        if($this->parent === null) {
            $this->setParent(Document::getById($this->getParentId()));
        }

        return $this->parent;
    }

    /**
     * @param Document $parent
     * @return void
     */
    public function setParent ($parent) {
        $this->parent = $parent;
    }

    /**
     * This is used for user-permissions, pass a permission type (eg. list, view, save) an you know if the current user is allowed to perform the requested action
     *
     * @param string $type
     * @return boolean
     */
    public function isAllowed($type) {

        if (!$this->getUserPermissions() instanceof Document_Permissions) {
            return false;
        }

        $currentUser = $this->getUserPermissions()->getUser();

        //everything is allowed for admin
        if ($currentUser->isAdmin()) {
            return true;
        }

        //check general permission on documents
        if (!$currentUser->isAllowed("documents")) {
            return false;
        }


        if ($this->getUserPermissions() instanceof Document_Permissions) {

            $method = "get" . $type;
            if (method_exists($this->getUserPermissions(), $method)) {
                return $this->getUserPermissions()->$method();
            }

        }

        return false;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->getFullPath();
    }


    /**
     *
     */
    public function __sleep() {

        $finalVars = array();
        $parentVars = parent::__sleep();
        
        if(isset($this->_fulldump)) {
            // this is if we want to make a full dump of the object (eg. for a new version), including childs for recyclebin
            $blockedVars = array("dependencies", "userPermissions", "permissions", "hasChilds", "_oldPath", "versions", "scheduledTasks", "parent");
            $finalVars[] = "_fulldump";
            $this->removeInheritedProperties();
        } else {
            // this is if we want to cache the object
            $blockedVars = array("dependencies", "userPermissions", "permissions", "childs", "hasChilds", "_oldPath", "versions", "scheduledTasks", "properties", "parent");
        }
        

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }
        
        return $finalVars;
    }
    
    public function __wakeup() {
        if(isset($this->_fulldump) && $this->properties !== null) {
            unset($this->_fulldump);
            $this->renewInheritedProperties();
        }

        if(isset($this->_fulldump)) {
            // set current key and path this is necessary because the serialized data can have a different path than the original element (element was renamed or moved)
            $originalElement = Document::getById($this->getId());
            if($originalElement) {
                $this->setKey($originalElement->getKey());
                $this->setPath($originalElement->getPath());
            }
        }
    }
    
    public function removeInheritedProperties () {
        $myProperties = array();
        if($this->properties !== null) {
            foreach ($this->properties as $name => $property) {
                if(!$property->getInherited()) {
                    $myProperties[$name] = $property;
                }
            }
        }

        $this->setProperties($myProperties);
    }
    
    public function renewInheritedProperties () {
        $this->removeInheritedProperties();
        
        $myProperties = $this->getProperties();
        $inheritedProperties = $this->getResource()->getProperties(true);
        $this->setProperties(array_merge($inheritedProperties, $myProperties));
    }
}
