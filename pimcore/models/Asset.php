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
 * @package    Asset
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Asset extends Pimcore_Model_Abstract implements Element_Interface {

    public static $chmod = 0766;

    /**
     * possible types of an asset
     * @var array
     */
    public static $types = array("folder", "image", "text", "audio", "video", "document", "archive", "unknown");


    /**
     * Unique ID
     *
     * @var integer
     */
    public $id;

    /**
     * ID of the parent asset
     *
     * @var integer
     */
    public $parentId;

    /**
     * @var Asset
     */
    public $parent;

    /**
     * Type
     *
     * @var string
     */
    public $type;

    /**
     * Name of the file
     *
     * @var string
     */
    public $filename;

    /**
     * Path of the file, without the filename, only the full path of the parent asset
     *
     * @var string
     */
    public $path;

    /**
     * @var string old path before update, later needed to update children
     */
    protected $_oldPath;

    /**
     * Mime-Type of the file
     *
     * @var string
     */
    public $mimetype;

    /**
     * Timestamp of creation
     *
     * @var integer
     */
    public $creationDate;

    /**
     * Timestamp of modification
     *
     * @var integer
     */
    public $modificationDate;

    /**
     * Contains the whole data of the asset (raw)
     *
     * @var mixed
     */
    public $data;

    /**
     * ID of the owner user
     *
     * @var integer
     */
    public $userOwner;

    /**
     * ID of the user who make the latest changes
     *
     * @var integer
     */
    public $userModification;

    /**
     * List of properties
     *
     * @var array
     */
    public $properties = null;

    /**
     * List of versions
     *
     * @var array
     */
    public $versions = null;


    /**
     * enum('self','propagate') nullable
     * @var string
     */
    public $locked;

    /**
     * List of some custom settings  [key] => value
     * Here there can be stored some data, eg. the video thumbnail files, ...  of the asset, ...
     *
     * @var array
     */
    public $customSettings = array();

    /**
     * Dependencies of this asset
     *
     * @var Dependency
     */
    public $dependencies;

    /**
     * Contains the child elements
     *
     * @var array
     */
    public $childs;

    /**
     * Indicator if there are childs
     *
     * @var boolean
     */
    public $hasChilds;

    /**
     * Contains all scheduled tasks
     *
     * @var array
     */
    public $scheduledTasks = null;

    /**
     * Indicator if data has changed
     * @var bool
     */
    protected $_dataChanged = false;

    /**
     *
     * @return array
     */
    public static function getTypes() {
        return self::$types;
    }

    /**
     * Static helper to get an asset by the passed path (returned is not the concrete asset like Asset_Folder!)
     *
     * @param string $path
     * @return Asset
     */

    public static function getByPath($path) {

        $path = Element_Service::correctPath($path);

        try {
            $asset = new Asset();

            if (Pimcore_Tool::isValidPath($path)) {
                $asset->getResource()->getByPath($path);
                return self::getById($asset->getId());
            }
        }
        catch (Exception $e) {
            Logger::warning($e);
        }

        return null;
    }

    /**
     * Static helper to get an asset by the passed id (returned is not the concrete asset like Asset_Folder!)
     *
     * @param integer $id
     * @return Asset
     */
    public static function getById($id) {

        $id = intval($id);

        if ($id < 1) {
            return null;
        }

        $cacheKey = "asset_" . $id;

        try {
            $asset = Zend_Registry::get($cacheKey);
            if(!$asset){
                throw new Exception("Asset in registry is null");
            }
        }
        catch (Exception $e) {
            try {
                if (!$asset = Pimcore_Model_Cache::load($cacheKey)) {
                    $asset = new Asset();
                    $asset->getResource()->getById($id);

                    $typeClass = "Asset_" . ucfirst($asset->getType());
                    $typeClass = Pimcore_Tool::getModelClassMapping($typeClass);

                    if (Pimcore_Tool::classExists($typeClass)) {
                        $asset = new $typeClass();
                        Zend_Registry::set($cacheKey, $asset);
                        $asset->getResource()->getById($id);

                        Pimcore_Model_Cache::save($asset, $cacheKey);
                    }
                }
                else {
                    Zend_Registry::set($cacheKey, $asset);
                }
            }
            catch (Exception $e) {
                Logger::warning($e);
                return null;
            }
        }
        
        if(!$asset) {
            return null;
        }

        return $asset;
    }

    /**
     *
     *
     * @param Asset|integer $id
     * @return Asset
     */
    public static function getConcreteById($id) {
        return self::getById($id);
    }

    /**
     * Helper to quickly create a new asset
     *
     * @param integer $parentId
     * @param array $data
     * @return Asset
     */
    public static function create($parentId, $data = array()) {

        $asset = new self();
        $asset->setParentId($parentId);
        foreach ($data as $key => $value) {
            $asset->setValue($key, $value);
        }
        $asset->save();

        // get concrete type of asset
        Zend_Registry::set("asset_" . $asset->getId(), null);
        $asset = self::getById($asset->getId());
        Zend_Registry::set("asset_" . $asset->getId(), $asset);

        return $asset;
    }


    /**
     * @param array $config
     * @return Asset_List
     */
    public static function getList($config = array()) {

        if (is_array($config)) {
            $listClass = "Asset_List";
            $listClass = Pimcore_Tool::getModelClassMapping($listClass);
            $list = new $listClass();

            $list->setValues($config);
            $list->load();

            return $list;
        }
    }


    /**
     * get the cache tag for the current asset
     *
     * @return Dependency
     */
    public function getCacheTag() {
        return "asset_" . $this->getId();
    }

    /**
     * Get the cache tags for the asset, resolve all dependencies to tag the cache entries
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
     * Get full path to the asset on the filesystem
     *
     * @return string
     */
    public function getFileSystemPath() {
        return PIMCORE_ASSET_DIRECTORY . $this->getFullPath();
    }

    /**
     * Load the binary data into the object
     *
     * @return void
     */
    public function loadData() {
        if ($this->getType() != "folder" && file_exists($this->getFileSystemPath())) {
            $this->setData(file_get_contents($this->getFileSystemPath()));
            $this->_dataChanged = false;
        }
    }

    /**
     * @return void
     */
    public function save() {

        if($this->getId()) {
            // do not lock when creating a new asset, this will cause a dead-lock because the cache-tag is used as key
            // and the cache tag is different when releasing the lock later, because the asset has then an id
            Tool_Lock::acquire($this->getCacheTag());
        }

        $this->beginTransaction();

        try {
            if (!Pimcore_Tool::isValidKey($this->getKey())) {
                throw new Exception("invalid filname '".$this->getKey()."' for asset with id [ " . $this->getId() . " ]");
            }

            $this->correctPath();

            if ($this->getId()) {
                $this->update();
            }
            else {
                Pimcore_API_Plugin_Broker::getInstance()->preAddAsset($this);
                $this->getResource()->create();
                Pimcore_API_Plugin_Broker::getInstance()->postAddAsset($this);
                $this->update();
            }

            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();

            throw $e;
        }

        $this->clearDependedCache();

        Tool_Lock::release($this->getCacheTag());
    }

    public function correctPath() {
        // set path
        if ($this->getId() != 1) { // not for the root node
            $parent = Asset::getById($this->getParentId());
            if($parent) {
                $this->setPath(str_replace("//", "/", $parent->getFullPath() . "/"));
            } else {
                // parent document doesn't exist anymore, so delete this document
                //$this->delete();

                // parent document doesn't exist anymore, set the parent to to root
                $this->setParentId(1);
                $this->setPath("/");
            }

        }

        if(Asset_Service::pathExists($this->getFullPath())) {
            $duplicate = Asset::getByPath($this->getFullPath());
            if ($duplicate instanceof Asset  and $duplicate->getId() != $this->getId()) {
                throw new Exception("Duplicate full path [ " . $this->getFullPath() . " ] - cannot create asset");
            }
        }

    }

    /**
     * @return void
     */
    protected function update() {

        Pimcore_API_Plugin_Broker::getInstance()->preUpdateAsset($this);


        if (!$this->getFilename() && $this->getId() != 1) {
            $this->setFilename("---no-valid-filename---" . $this->getId());
            throw new Exception("Asset requires filename, generated filename automatically");
        }

        // set date
        $this->setModificationDate(time());

        // create foldertree
        $destinationPath = $this->getFileSystemPath();
        if (!is_dir(dirname($destinationPath))) {
            mkdir(dirname($destinationPath), self::$chmod, true);
        }

        if ($this->_oldPath) {
            rename(PIMCORE_ASSET_DIRECTORY . $this->_oldPath, $this->getFileSystemPath());
        }

        if ($this->getType() != "folder") {

            // get data
            $this->getData();

            // remove if exists
            if (is_file($destinationPath)) {
                unlink($destinationPath);
            }

            file_put_contents($destinationPath, $this->getData());
            chmod($destinationPath, self::$chmod);

            // check file exists
            if (!is_file($destinationPath)) {
                throw new Exception("couldn't create new asset");
            }

            // set mime type

            $mimetype = MIME_Type::autoDetect($this->getFileSystemPath());
            $this->setMimetype($mimetype);

            // set type
            $this->setTypeFromMapping();

            // update scheduled tasks
            $this->saveScheduledTasks();

            // create version
            $this->getData(); // load data from filesystem to put it into the version

            // only create a new version if there is at least 1 allowed
            if(Pimcore_Config::getSystemConfig()->assets->versions) {
                $version = new Version();
                $version->setCid($this->getId());
                $version->setCtype("asset");
                $version->setDate($this->getModificationDate());
                $version->setUserId($this->getUserModification());
                $version->setData($this);
                $version->save();
            }
        }


        // save properties
        $this->getProperties();
        $this->getResource()->deleteAllProperties();
        if (is_array($this->getProperties()) and count($this->getProperties()) > 0) {
            foreach ($this->getProperties() as $property) {
                if (!$property->getInherited()) {
                    $property->setResource(null);
                    $property->setCid($this->getId());
                    $property->setCpath($this->getPath() . $this->getKey());
                    $property->save();
                }
            }
        }

        // save dependencies
        $d = $this->getDependencies();
        $d->clean();

        foreach ($this->resolveDependencies() as $requirement) {
            if ($requirement["id"] == $this->getId() && $requirement["type"] == "asset") {
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

        //set object to registry
        Zend_Registry::set("asset_" . $this->getId(), $this);

        Pimcore_API_Plugin_Broker::getInstance()->postUpdateAsset($this);
    }


    /**
     * detects the pimcore internal asset type based on the mime-type and file extension
     *
     * @return void
     */
    public function setTypeFromMapping () {

        $found = false;

        $mappings = array(
            "image" => array("/image/", "/\.eps$/", "/\.ai$/", "/\.svgz$/", "/\.pcx$/", "/\.iff$/", "/\.pct$/", "/\.wmf$/"),
            "text" => array("/text/"),
            "audio" => array("/audio/"),
            "video" => array("/video/"),
            "document" => array("/msword/","/pdf/","/powerpoint/","/office/","/excel/","/opendocument/"),
            "archive" => array("/zip/","/tar/")
        );

        foreach ($mappings as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if(preg_match($pattern,$this->getMimetype() . " .". Pimcore_File::getFileExtension($this->getFilename()))) {
                    $this->setType($type);
                    $found = true;
                    break;
                }
            }

            // break at first match
            if($found) {
                break;
            }
        }

        // default is unknown
        if(!$found) {
            $this->setType("unknown");    
        }
    }

    /**
     * Returns the full path of the document including the filename
     *
     * @return string
     */
    public function getFullPath() {
        $path = $this->getPath() . $this->getFilename();

        return $path;
    }

    
    /**
     * @return array
     */
    public function getChilds() {

        if ($this->childs === null) {
            $list = new Asset_List();
            $list->setCondition("parentId = ?", $this->getId());
            $list->setOrderKey("filename");
            $list->setOrder("asc");

            $this->childs = $list->load();
        }

        return $this->childs;
    }

    /**
     * @return boolean
     */
    public function hasChilds() {
        if ($this->getType() == "folder") {
            if (is_bool($this->hasChilds)) {
                if (($this->hasChilds and empty($this->childs)) or (!$this->hasChilds and !empty($this->childs))) {
                    return $this->getResource()->hasChilds();
                } else {
                    return $this->hasChilds;
                }
            }
            return $this->getResource()->hasChilds();
        }
        return false;
    }

    /**
     * @return boolean
     */
    public function hasNoChilds() {
        if ($this->hasChilds()) {
            return false;
        }
        return true;
    }

    /**
     * Returns true if the element is locked
     * @return string
     */
    public function getLocked(){
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
     * @return void
     */
    public function delete() {

        if ($this->getId() == 1) {
            throw new Exception("root-node cannot be deleted");
        }

        Pimcore_API_Plugin_Broker::getInstance()->preDeleteAsset($this);

        // remove childs
        if ($this->hasChilds()) {
            foreach ($this->getChilds() as $child) {
                $child->delete();
            }
        }

        // remove file on filesystem
        $fsPath = PIMCORE_ASSET_DIRECTORY . $this->getPath() . $this->getFilename();

        if ($this->getType() != "folder") {
            if (is_file($fsPath) && is_writable($fsPath)) {
                unlink($fsPath);
            }
        }
        else {
            if (is_dir($fsPath) && is_writable($fsPath)) {
                recursiveDelete($fsPath, true);
            }
        }

        $versions = $this->getVersions();
        foreach ($versions as $version) {
            $version->delete();
        }


        // remove permissions
        $this->getResource()->deleteAllPermissions();

        // remove all properties
        $this->getResource()->deleteAllProperties();

        // remove all tasks
        $this->getResource()->deleteAllTasks();

        // remove dependencies
        $d = $this->getDependencies();
        $d->cleanAllForElement($this);

        // remove from resource
        $this->getResource()->delete();

        // empty object cache
        $this->clearDependedCache();

        //set object to registry
        Zend_Registry::set("asset_" . $this->getId(), null);

        Pimcore_API_Plugin_Broker::getInstance()->postDeleteAsset($this);
    }

    public function clearDependedCache() {
        try {
            Pimcore_Model_Cache::clearTag("asset_" . $this->getId());
        }
        catch (Exception $e) {
        }
        try {
            Pimcore_Model_Cache::clearTag("properties");
        }
        catch (Exception $e) {
        }
        try {
            Pimcore_Model_Cache::clearTag("output");
        }
        catch (Exception $e) {
        }
    }


    /**
     * @return Dependency
     */
    public function getDependencies() {
        if (!$this->dependencies) {
            $this->dependencies = Dependency::getBySourceId($this->getId(), "asset");
        }
        return $this->dependencies;
    }

    /**
     * @return array
     */
    public function resolveDependencies() {

        $dependencies = array();

        // check for properties
        if (method_exists($this, "getProperties")) {
            $properties = $this->getProperties();
            foreach ($properties as $property) {
                $dependencies = array_merge($dependencies, $property->resolveDependencies());
            }
        }

        return $dependencies;
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
    public function getFilename() {
        return $this->filename;
    }

    /**
     * Alias for getFilename()
     *
     * @return string
     */
    public function getKey() {
        return $this->getFilename();
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
        return $this->path;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param integer $creationDate
     * @return void
     */
    public function setCreationDate($creationDate) {
        $this->creationDate = (int) $creationDate;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
    }

    /**
     * @param string $filename
     * @return void
     */
    public function setFilename($filename) {

        //set old path so that child paths are updated after this asset was saved
        if ($this->filename != null and $filename != null and $filename != $this->filename) {
            $this->_oldPath = $this->getResource()->getCurrentFullPath();
        }
        $this->filename = $filename;
    }

    /**
     * @param integer $modificationDate
     * @return void
     */
    public function setModificationDate($modificationDate) {
        $this->modificationDate = (int) $modificationDate;
    }

    /**
     * @param integer $parentId
     * @return void
     */
    public function setParentId($parentId) {
        if ($this->parentId != null and $parentId != null and $this->parentId != $parentId) {
            $this->_oldPath = $this->getResource()->getCurrentFullPath();
        }
        $this->parentId = (int) $parentId;
    }

    /**
     * @param string $path
     * @return void
     */
    public function setPath($path) {
        $this->path = $path;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getData() {
        if (!$this->data) {
            $this->loadData();
        }
        return $this->data;
    }

    /**
     * @param mixed $data
     * @return void
     */
    public function setData($data) {
        $this->data = $data;
        $this->_dataChanged = true;
    }

    /**
     * @return Property[]
     */
    public function getProperties() {
        if ($this->properties === null) {
            // try to get from cache
            $cacheKey = "asset_properties_" . $this->getId();
            ;
            if (!$properties = Pimcore_Model_Cache::load($cacheKey)) {
                $properties = $this->getResource()->getProperties();
                Pimcore_Model_Cache::save($properties, $cacheKey, array("asset_properties", "properties"));
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
     * set a property
     *
     * @param string $name
     * @param string $type
     * @param mixed $data
     * @param boolean $inherited
     */
    public function setProperty($name, $type, $data, $inherited = false) {

        $this->getProperties();

        $property = new Property();
        $property->setType($type);
        $property->setCid($this->getId());
        $property->setName($name);
        $property->setCtype("asset");
        $property->setData($data);
        $property->setInherited($inherited);

        $this->properties[$name] = $property;
    }

    /**
     * @return integer
     */
    public function getUserOwner() {
        return $this->userOwner;
    }

    /**
     * @return integer
     */
    public function getUserModification() {
        return $this->userModification;
    }

    /**
     * @param integer $userOwner
     * @return void
     */
    public function setUserOwner($userOwner) {
        $this->userOwner = $userOwner;
    }

    /**
     * @param integer $userModification
     * @return void
     */
    public function setUserModification($userModification) {
        $this->userModification = $userModification;
    }

    /**
     * @return array
     */
    public function getVersions() {
        if ($this->versions === null) {
            $this->setVersions($this->getResource()->getVersions());
        }
        return $this->versions;
    }

    /**
     * @param array $versions
     * @return void
     */
    public function setVersions($versions) {
        $this->versions = $versions;
    }

    /**
     * returns the path to a temp file
     *
     * @return string
     */
    public function getTemporaryFile() {

        $conf = Pimcore_Config::getSystemConfig();
        $destinationPath = PIMCORE_TEMPORARY_DIRECTORY . "/asset_" . $this->getId() . "_" . md5(microtime());

        file_put_contents($destinationPath, $this->getData());
        chmod($destinationPath, self::$chmod);

        return str_replace(PIMCORE_DOCUMENT_ROOT, "", $destinationPath);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setCustomSetting($key, $value) {
        $this->customSettings[$key] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getCustomSetting($key) {
        return $this->customSettings[$key];
    }

    /**
     * @param string $name
     */
    public function removeCustomSetting($key) {
        unset($this->customSettings[$key]);
    }

    /**
     * @return array
     */
    public function getCustomSettings() {
        return $this->customSettings;
    }

    /**
     * @param array $customSettings
     * @return void
     */
    public function setCustomSettings($customSettings) {
        if (is_string($customSettings)) {
            $customSettings = Pimcore_Tool_Serialize::unserialize($customSettings);
        }

        $this->customSettings = $customSettings;
    }

    /**
     * @return string
     */
    public function getMimetype() {
        return $this->mimetype;
    }

    /**
     * @param string $mimetype
     * @return void
     */
    public function setMimetype($mimetype) {
        $this->mimetype = $mimetype;
    }

    /**
     * This is used for user-permissions, pass a permission type (eg. list, view, save) an you know if the current user is allowed to perform the requested action
     *
     * @param string $type
     * @return integer
     */
    public function isAllowed($type) {

        $currentUser = Pimcore_Tool_Admin::getCurrentUser();
        //everything is allowed for admin
        if ($currentUser->isAdmin()) {
            return true;
        }

        return $this->getResource()->isAllowed($type, $currentUser);
    }

    /**
     * @return array
     */
    public function getUserPermissions () {

        $vars = get_class_vars("User_Workspace_Asset");
        $ignored = array("userId","cid","cpath","resource");
        $permissions = array();

        foreach ($vars as $name => $defaultValue) {
            if(!in_array($name, $ignored)) {
                $permissions[$name] = $this->isAllowed($name);
            }
        }

        return $permissions;
    }


    /**
     * @return array
     */
    public function getScheduledTasks() {
        if ($this->scheduledTasks === null) {
            $taskList = new Schedule_Task_List();
            $taskList->setCondition("cid = ? AND ctype='asset'", $this->getId());
            $this->setScheduledTasks($taskList->load());
        }
        return $this->scheduledTasks;
    }

    /**
     * @param array $scheduledTasks
     */
    public function setScheduledTasks($scheduledTasks) {
        $this->scheduledTasks = $scheduledTasks;
    }

    /**
     */
    public function saveScheduledTasks() {
        $this->getScheduledTasks();
        $this->getResource()->deleteAllTasks();

        if (is_array($this->getScheduledTasks()) && count($this->getScheduledTasks()) > 0) {
            foreach ($this->getScheduledTasks() as $task) {
                $task->setId(null);
                $task->setResource(null);
                $task->setCid($this->getId());
                $task->setCtype("asset");
                $task->save();
            }
        }
    }

    /**
     * Get filesize
     *
     * @param string $format ('GB','MB','KB','B')
     * @return string
     */
    public function getFileSize($format = 'b', $precision = 2) {

        $bytes = filesize($this->getFileSystemPath());
        switch (strtolower($format))
        {
            case 'gb':
                $size = (($bytes / 1024) / 1024) / 1024;
                break;

            case 'mb':
                $size = (($bytes / 1024) / 1024);
                break;

            case 'kb':
                $size = ($bytes / 1024);
                break;

            case 'b':
            default:
                $size = $bytes;
                $precision = 0;
                break;
        }

        return round($size, $precision) . ' ' . $format;
    }

    /**
     * @return Asset
     */
    public function getParent() {

        if($this->parent === null) {
            $this->setParent(Asset::getById($this->getParentId()));
        }

        return $this->parent;
    }

    /**
     * @param Asset $parent
     * @return void
     */
    public function setParent ($parent) {
        $this->parent = $parent;
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
            $blockedVars = array("scheduledTasks", "dependencies", "userPermissions", "hasChilds", "_oldPath", "versions", "parent");
            $finalVars[] = "_fulldump";
            $this->removeInheritedProperties();
        } else {
            // this is if we want to cache the object
            $blockedVars = array("scheduledTasks", "dependencies", "userPermissions", "hasChilds", "_oldPath", "versions", "childs", "properties", "data", "parent");
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
            $originalElement = Asset::getById($this->getId());
            if($originalElement) {
                $this->setFilename($originalElement->getFilename());
                $this->setPath($originalElement->getPath());
            }
        }
    }
    
    public function removeInheritedProperties () {
        
        $myProperties = $this->getProperties();
        
        if($myProperties) {
            foreach ($this->getProperties() as $name => $property) {
                if($property->getInherited()) {
                    unset($myProperties[$name]);
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
