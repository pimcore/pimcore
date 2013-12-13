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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Asset extends Element_Abstract {

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
     * @var resource
     */
    public $stream;

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
            Logger::warning($e->getMessage());
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
                Logger::warning($e->getMessage());
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
    public static function create($parentId, $data = array(), $save = true) {

        // create already the real class for the asset type, this is especially for images, because a system-thumbnail
        // (tree) is generated immediately after creating an image
        $class = "Asset";
        if(array_key_exists("filename", $data) && (array_key_exists("data", $data) || array_key_exists("sourcePath", $data) || array_key_exists("stream", $data))) {
            if(array_key_exists("data", $data) || array_key_exists("stream", $data)) {
                $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/asset-create-tmp-file-" . uniqid() . "." . Pimcore_File::getFileExtension($data["filename"]);
                if(array_key_exists("data", $data)) {
                    Pimcore_File::put($tmpFile, $data["data"]);
                } else {
                    $streamMeta = stream_get_meta_data($data["stream"]);
                    if(file_exists($streamMeta["uri"])) {
                        // stream is a local file, so we don't have to write a tmp file
                        $tmpFile = $streamMeta["uri"];
                    } else {
                        // write a tmp file because the stream isn't a pointer to the local filesystem
                        rewind($data["stream"]);
                        $dest = fopen($tmpFile, "w+");
                        stream_copy_to_stream($data["stream"], $dest);
                        fclose($dest);
                    }
                }
                $mimeType = Pimcore_Tool_Mime::detect($tmpFile);
                unlink($tmpFile);
            } else {
                $mimeType = Pimcore_Tool_Mime::detect($data["sourcePath"], $data["filename"]);
                $data["stream"] = fopen($data["sourcePath"], "r+");
                unset($data["sourcePath"]);
            }

            $type = self::getTypeFromMimeMapping($mimeType, $data["filename"]);
            $class = "Asset_" . ucfirst($type);
            if(array_key_exists("type", $data)) {
                unset($data["type"]);
            }
        }

        $asset = new $class();
        $asset->setParentId($parentId);
        $asset->setValues($data);

        if($save) {
            $asset->save();
        }


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
     * @param array $config
     * @return total count
     */
    public static function getTotalCount($config = array()) {

        if (is_array($config)) {
            $listClass = "Asset_List";
            $listClass = Pimcore_Tool::getModelClassMapping($listClass);
            $list = new $listClass();

            $list->setValues($config);
            $count = $list->getTotalCount();

            return $count;
        }
    }


    /**
     * returns the asset type of a filename and mimetype
     * @param $mimeType
     * @param $filename
     * @return int|string
     */
    public static function getTypeFromMimeMapping ($mimeType, $filename) {

        $type = "unknown";

        $mappings = array(
            "image" => array("/image/", "/\.eps$/", "/\.ai$/", "/\.svgz$/", "/\.pcx$/", "/\.iff$/", "/\.pct$/", "/\.wmf$/"),
            "text" => array("/text/"),
            "audio" => array("/audio/"),
            "video" => array("/video/"),
            "document" => array("/msword/","/pdf/","/powerpoint/","/office/","/excel/","/opendocument/"),
            "archive" => array("/zip/","/tar/")
        );

        foreach ($mappings as $assetType => $patterns) {
            foreach ($patterns as $pattern) {
                if(preg_match($pattern,$mimeType . " .". Pimcore_File::getFileExtension($filename))) {
                    $type = $assetType;
                    break;
                }
            }

            // break at first match
            if($type != "unknown") {
                break;
            }
        }

        return $type;
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
     * @return void
     */
    public function save() {

        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
            Pimcore_API_Plugin_Broker::getInstance()->preUpdateAsset($this);
        } else {
            Pimcore_API_Plugin_Broker::getInstance()->preAddAsset($this);
        }

        // we wrap the save actions in a loop here, so that we can restart the database transactions in the case it fails
        // if a transaction fails it gets restarted $maxRetries times, then the exception is thrown out
        // this is especially useful to avoid problems with deadlocks in multi-threaded environments (forked workers, ...)
        $maxRetries = 5;
        for($retries=0; $retries<$maxRetries; $retries++) {

            $this->beginTransaction();

            try {
                if (!Pimcore_Tool::isValidKey($this->getKey()) && $this->getId() != 1) {
                    throw new Exception("invalid filename '".$this->getKey()."' for asset with id [ " . $this->getId() . " ]");
                }

                $this->correctPath();

                if (!$isUpdate) {
                    $this->getResource()->create();
                }

                // get the old path from the database before the update is done
                $oldPath = null;
                if ($isUpdate) {
                    $oldPath = $this->getResource()->getCurrentFullPath();
                }

                $this->update();

                // if the old path is different from the new path, update all children
                $updatedChildren = array();
                if($oldPath && $oldPath != $this->getFullPath()) {
                    @rename(PIMCORE_ASSET_DIRECTORY . $oldPath, $this->getFileSystemPath());
                    $updatedChildren = $this->getResource()->updateChildsPaths($oldPath);
                }

                $this->commit();

                break; // transaction was successfully completed, so we cancel the loop here -> no restart required
            } catch (Exception $e) {
                $this->rollBack();

                // we try to start the transaction $maxRetries times again (deadlocks, ...)
                if($retries < ($maxRetries-1)) {
                    $run = $retries+1;
                    $waitTime = 100000; // microseconds
                    Logger::warn("Unable to finish transaction (" . $run . ". run) because of the following reason '" . $e->getMessage() . "'. --> Retrying in " . $waitTime . " microseconds ... (" . ($run+1) . " of " . $maxRetries . ")");

                    usleep($waitTime); // wait specified time until we restart the transaction
                } else {
                    // if the transaction still fail after $maxRetries retries, we throw out the exception
                    throw $e;
                }
            }
        }

        if ($isUpdate) {
            Pimcore_API_Plugin_Broker::getInstance()->postUpdateAsset($this);
        } else {
            Pimcore_API_Plugin_Broker::getInstance()->postAddAsset($this);
        }


        $additionalTags = array();
        if(isset($updatedChildren) && is_array($updatedChildren)) {
            foreach ($updatedChildren as $assetId) {
                $tag = "asset_" . $assetId;
                $additionalTags[] = $tag;

                // remove the child also from registry (internal cache) to avoid path inconsistencies during long running scripts, such as CLI
                Zend_Registry::set($tag, null);
            }
        }
        $this->clearDependentCache($additionalTags);

        return $this;
    }

    public function correctPath() {
        // set path
        if ($this->getId() != 1) { // not for the root node

            if($this->getParentId() == $this->getId()) {
                throw new Exception("ParentID and ID is identical, an element can't be the parent of itself.");
            }

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

        // do not allow PHP files
        if(preg_match("@\.ph(p[345]?|t|tml|ps)$@i", $this->getFilename())) {
            $this->setFilename($this->getFilename() . ".txt");
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

        if (!$this->getFilename() && $this->getId() != 1) {
            $this->setFilename("---no-valid-filename---" . $this->getId());
            throw new Exception("Asset requires filename, generated filename automatically");
        }

        // set date
        $this->setModificationDate(time());

        if(!$this->getCreationDate()) {
            $this->setCreationDate(time());
        }

        // create foldertree
        $destinationPath = $this->getFileSystemPath();

        $dirPath = dirname($destinationPath);
        if (!is_dir($dirPath)) {
            Pimcore_File::mkdir($dirPath);
        }

        if ($this->getType() != "folder") {

            if($this->getDataChanged()) {

                $src = $this->getStream();
                $streamMeta = stream_get_meta_data($src);
                if($destinationPath != $streamMeta["uri"]) {
                    $dest = fopen($destinationPath, "w+");
                    stream_copy_to_stream($src, $dest);
                    fclose($dest);
                }

                chmod($destinationPath, Pimcore_File::getDefaultMode());

                // check file exists
                if (!is_file($destinationPath)) {
                    throw new Exception("couldn't create new asset, file " . $destinationPath . " doesn't exist");
                }

                // set mime type

                $mimetype = Pimcore_Tool_Mime::detect($this->getFileSystemPath());
                $this->setMimetype($mimetype);

                // set type
                $this->setTypeFromMapping();
            }

            // update scheduled tasks
            $this->saveScheduledTasks();

            // only create a new version if there is at least 1 allowed
            if(Pimcore_Config::getSystemConfig()->assets->versions->steps
                || Pimcore_Config::getSystemConfig()->assets->versions->days) {
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
                    $property->setCtype("asset");
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

        //set object to registry
        Zend_Registry::set("asset_" . $this->getId(), $this);

        $this->closeStream();
    }

    /**
     * detects the pimcore internal asset type based on the mime-type and file extension
     *
     * @return void
     */
    public function setTypeFromMapping () {
        $this->setType(self::getTypeFromMimeMapping($this->getMimetype(), $this->getFilename()));
        return $this;
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
        return $this;
    }

    /**
     * @return void
     */
    public function delete() {

        if ($this->getId() == 1) {
            throw new Exception("root-node cannot be deleted");
        }

        Pimcore_API_Plugin_Broker::getInstance()->preDeleteAsset($this);

        $this->closeStream();

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
        $this->clearDependentCache();

        //set object to registry
        Zend_Registry::set("asset_" . $this->getId(), null);

        Pimcore_API_Plugin_Broker::getInstance()->postDeleteAsset($this);
    }

    public function clearDependentCache($additionalTags = array()) {

        // get concrete type of asset
        // this is important because at the time of creating an asset it's not clear which type (resp. class) it will have
        // the type (image, document, ...) depends on the mime-type
        Zend_Registry::set("asset_" . $this->getId(), null);
        $asset = self::getById($this->getId());
        Zend_Registry::set("asset_" . $this->getId(), $asset);


        try {
            $tags = array("asset_" . $this->getId(), "properties", "output");
            $tags = array_merge($tags, $additionalTags);

            Pimcore_Model_Cache::clearTags($tags);
        }
        catch (Exception $e) {
            Logger::crit($e);
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
        return (string) $this->filename;
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
        return (int) $this->modificationDate;
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
        return $this;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @param string $filename
     * @return void
     */
    public function setFilename($filename) {
        $this->filename = (string) $filename;
        return $this;
    }

    /**
     * @param integer $modificationDate
     * @return void
     */
    public function setModificationDate($modificationDate) {
        $this->modificationDate = (int) $modificationDate;
        return $this;
    }

    /**
     * @param integer $parentId
     * @return void
     */
    public function setParentId($parentId) {
        $this->parentId = (int) $parentId;
        return $this;
    }

    /**
     * @param string $path
     * @return void
     */
    public function setPath($path) {
        $this->path = $path;
        return $this;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData() {
        $stream = $this->getStream();
        if($stream) {
            return stream_get_contents($stream);
        }

        return "";
    }

    /**
     * @param mixed $data
     * @return void
     */
    public function setData($data) {

        $handle = tmpfile();
        fwrite($handle, $data);
        $this->setStream($handle);

        return $this;
    }


    /**
     * @return resource
     */
    public function getStream() {

        if($this->stream) {
            if(!@rewind($this->stream)) {
                $this->stream = null;
            }
        }

        if(!$this->stream && $this->getType() != "folder") {
            if(file_exists($this->getFileSystemPath())) {
                $this->stream = fopen($this->getFileSystemPath(), "r+");
            } else {
                $this->stream = tmpfile();
            }
        }

        return $this->stream;
    }

    /**
     * @param $stream
     */
    public function setStream($stream) {

        // close existing stream
        $this->closeStream();

        if(is_resource($stream)) {
            $this->setDataChanged(true);
            $this->stream = $stream;
            rewind($this->stream);
        } else if(is_null($stream)) {
            $this->stream = null;
        }

        return $this;
    }

    /**
     *
     */
    protected function closeStream() {
        if(is_resource($this->stream)) {
            @fclose($this->stream);
            $this->stream = null;
        }
    }

    /**
     * @param string $type
     * @return null|string
     */
    public function getChecksum($type = "md5") {
        $file = $this->getFileSystemPath();
        if(is_file($file)) {
            if($type == "md5") {
                return md5_file($file);
            } else if ($type = "sha1") {
                return sha1_file($file);
            } else {
                throw new \Exception("hashing algorithm '" . $type . "' isn't supported");
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function getDataChanged() {
        return $this->_dataChanged;
    }

    /**
     * @param bool $changed
     * @return $this
     */
    public function setDataChanged ($changed = true) {
        $this->_dataChanged = $changed;
        return $this;
    }


    /**
     * @return Property[]
     */
    public function getProperties() {
        if ($this->properties === null) {
            // try to get from cache
            $cacheKey = "asset_properties_" . $this->getId();
            if (!$properties = Pimcore_Model_Cache::load($cacheKey)) {
                $properties = $this->getResource()->getProperties();
                $elementCacheTag = $this->getCacheTag();
                $cacheTags = array("properties" => "properties", $elementCacheTag => $elementCacheTag);
                Pimcore_Model_Cache::save($properties, $cacheKey, $cacheTags);
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
        return $this;
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
        return $this;
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
        return $this;
    }

    /**
     * @param integer $userModification
     * @return void
     */
    public function setUserModification($userModification) {
        $this->userModification = $userModification;
        return $this;
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
        return $this;
    }

    /**
     * returns the path to a temp file
     *
     * @return string
     */
    public function getTemporaryFile($fullPath = false) {
        $destinationPath = PIMCORE_TEMPORARY_DIRECTORY . "/asset-temporary/asset_" . $this->getId() . "_" . md5(microtime()) . "__" . $this->getFilename();
        if(!is_dir(dirname($destinationPath))) {
            Pimcore_File::mkdir(dirname($destinationPath));
        }

        $src = $this->getStream();
        $dest = fopen($destinationPath, "w+");
        stream_copy_to_stream($src, $dest);
        fclose($dest);

        chmod($destinationPath, Pimcore_File::getDefaultMode());

        if($fullPath) {
            return $destinationPath;
        }

        return str_replace(PIMCORE_DOCUMENT_ROOT, "", $destinationPath);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setCustomSetting($key, $value) {
        $this->customSettings[$key] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getCustomSetting($key) {
        if(is_array($this->customSettings) && array_key_exists($key, $this->customSettings)) {
            return $this->customSettings[$key];
        }
        return null;
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

        if ($customSettings instanceof stdClass) {
            $customSettings = (array) $customSettings;
        }

        $this->customSettings = $customSettings;
        return $this;
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
        return $this;
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
        return $this;
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
    public function getFileSize($format = 'noformatting', $precision = 2) {

        $format = strtolower($format);
        $bytes = 0;
        if(is_file($this->getFileSystemPath())) {
            $bytes = filesize($this->getFileSystemPath());
        }

        switch ($format)
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

        if($format == "noformatting") {
            return $size;
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
        if($parent instanceof Asset) {
            $this->parentId = $parent->getId();
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getImageThumbnailSavePath() {
        $path = PIMCORE_TEMPORARY_DIRECTORY . "/image-thumbnails/" . $this->getId();
        return $path;
    }

    /**
     * @return string
     */
    public function getVideoThumbnailSavePath() {
        $path = PIMCORE_TEMPORARY_DIRECTORY . "/video-thumbnails/" . $this->getId();
        return $path;
    }


    /**
     *
     */
    public function __sleep() {

        $finalVars = array();
        $parentVars = parent::__sleep();

        if(isset($this->_fulldump)) {
            // this is if we want to make a full dump of the object (eg. for a new version), including childs for recyclebin
            $blockedVars = array("scheduledTasks", "dependencies", "userPermissions", "hasChilds", "versions", "parent", "stream");
            $finalVars[] = "_fulldump";
            $this->removeInheritedProperties();
        } else {
            // this is if we want to cache the object
            $blockedVars = array("scheduledTasks", "dependencies", "userPermissions", "hasChilds", "versions", "childs", "properties", "stream", "parent");
        }


        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }
    
    public function __wakeup() {
        if(isset($this->_fulldump)) {
            // set current key and path this is necessary because the serialized data can have a different path than the original element (element was renamed or moved)
            $originalElement = Asset::getById($this->getId());
            if($originalElement) {
                $this->setFilename($originalElement->getFilename());
                $this->setPath($originalElement->getPath());
            }

            unset($this->_fulldump);
        }

        if(isset($this->_fulldump) && $this->properties !== null) {
            $this->renewInheritedProperties();
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

        // add to registry to avoid infinite regresses in the following $this->getResource()->getProperties()
        $cacheKey = "asset_" . $this->getId();
        if(!Zend_Registry::isRegistered($cacheKey)) {
            Zend_Registry::set($cacheKey, $this);
        }

        $myProperties = $this->getProperties();
        $inheritedProperties = $this->getResource()->getProperties(true);
        $this->setProperties(array_merge($inheritedProperties, $myProperties));
    }

    public function __destruct() {

        // close open streams
        $this->closeStream();
    }
}
