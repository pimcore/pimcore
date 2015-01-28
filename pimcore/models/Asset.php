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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model;

use Pimcore\Tool;
use Pimcore\Tool\Mime;
use Pimcore\File;
use Pimcore\Config;
use Pimcore\Model;
use Pimcore\Model\Element;

class Asset extends Element\AbstractElement {

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
     * @var array
     */
    public $metadata = array();

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
	 * Contains a list of sibling documents
	 *
	 * @var array
	 */
	public $siblings;

	/**
	 * Indicator if document has siblings or not
	 *
	 * @var boolean
	 */
	public $hasSiblings;

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
     * Static helper to get an asset by the passed path (returned is not the concrete asset like Asset\Folder!)
     *
     * @param string $path
     * @return Asset
     */

    public static function getByPath($path) {

        $path = Element\Service::correctPath($path);

        try {
            $asset = new Asset();

            if (Tool::isValidPath($path)) {
                $asset->getResource()->getByPath($path);
                return self::getById($asset->getId());
            }
        }
        catch (\Exception $e) {
            \Logger::warning($e->getMessage());
        }

        return null;
    }

    /**
     * Static helper to get an asset by the passed id (returned is not the concrete asset like Asset\Folder!)
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
            $asset = \Zend_Registry::get($cacheKey);
            if(!$asset){
                throw new \Exception("Asset in registry is null");
            }
        }
        catch (\Exception $e) {
            try {
                if (!$asset = Cache::load($cacheKey)) {
                    $asset = new Asset();
                    $asset->getResource()->getById($id);

                    $mappingClass = "\\Pimcore\\Model\\Asset\\" . ucfirst($asset->getType());
                    $typeClass = Tool::getModelClassMapping($mappingClass);

                    if (Tool::classExists($typeClass)) {
                        $asset = new $typeClass();
                        \Zend_Registry::set($cacheKey, $asset);
                        $asset->getResource()->getById($id);

                        Cache::save($asset, $cacheKey);
                    }
                }
                else {
                    \Zend_Registry::set($cacheKey, $asset);
                }
            }
            catch (\Exception $e) {
                \Logger::warning($e->getMessage());
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
                $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/asset-create-tmp-file-" . uniqid() . "." . File::getFileExtension($data["filename"]);
                if(array_key_exists("data", $data)) {
                    File::put($tmpFile, $data["data"]);
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
                $mimeType = Mime::detect($tmpFile);
                unlink($tmpFile);
            } else {
                $mimeType = Mime::detect($data["sourcePath"], $data["filename"]);
                if (is_file($data["sourcePath"])) {
                    $data["stream"] = fopen($data["sourcePath"], "r+");
                }

                unset($data["sourcePath"]);
            }

            $type = self::getTypeFromMimeMapping($mimeType, $data["filename"]);
            $class = "\\Pimcore\\Model\\Asset\\" . ucfirst($type);
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
     * @return mixed
     * @throws \Exception
     */
    public static function getList($config = array()) {

        if (is_array($config)) {
            $listClass = "\\Pimcore\\Model\\Asset\\Listing";
            $listClass = Tool::getModelClassMapping($listClass);

            if (Tool::classExists($listClass)) {
                $list = new $listClass();

                $list->setValues($config);
                $list->load();

                return $list;
            }
        }

        throw new \Exception("Unable to initiate list class - class not found or invalid configuration");
    }

    /**
     * @param array $config
     * @return total count
     */
    public static function getTotalCount($config = array()) {

        if (is_array($config)) {
            $listClass = "\\Pimcore\\Model\\Asset\\Listing";
            $listClass = Tool::getModelClassMapping($listClass);
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

        if ($mimeType == "directory") {
            return "folder";
        }

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
                if(preg_match($pattern,$mimeType . " .". File::getFileExtension($filename))) {
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
     * @return $this
     * @throws \Exception
     */
    public function save() {

        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("asset.preUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("asset.preAdd", $this);
        }

        // we wrap the save actions in a loop here, so that we can restart the database transactions in the case it fails
        // if a transaction fails it gets restarted $maxRetries times, then the exception is thrown out
        // this is especially useful to avoid problems with deadlocks in multi-threaded environments (forked workers, ...)
        $maxRetries = 5;
        for($retries=0; $retries<$maxRetries; $retries++) {

            $this->beginTransaction();

            try {
                if (!Tool::isValidKey($this->getKey()) && $this->getId() != 1) {
                    throw new \Exception("invalid filename '".$this->getKey()."' for asset with id [ " . $this->getId() . " ]");
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
                    $oldFullPath = PIMCORE_ASSET_DIRECTORY . $oldPath;
                    if(is_file($oldFullPath) || is_dir($oldFullPath)) {
                        if(!@rename(PIMCORE_ASSET_DIRECTORY . $oldPath, $this->getFileSystemPath())) {
                            throw new \Exception("Unable to rename asset " . $this->getId() . " on the filesystem: " . $oldFullPath);
                        }
                        $this->getResource()->updateWorkspaces();
                        $updatedChildren = $this->getResource()->updateChildsPaths($oldPath);
                    }
                }

                $this->commit();

                break; // transaction was successfully completed, so we cancel the loop here -> no restart required
            } catch (\Exception $e) {
                try {
                    $this->rollBack();
                } catch (\Exception $er) {
                    // PDO adapter throws exceptions if rollback fails
                    \Logger::error($er);
                }

                // we try to start the transaction $maxRetries times again (deadlocks, ...)
                if($retries < ($maxRetries-1)) {
                    $run = $retries+1;
                    $waitTime = 100000; // microseconds
                    \Logger::warn("Unable to finish transaction (" . $run . ". run) because of the following reason '" . $e->getMessage() . "'. --> Retrying in " . $waitTime . " microseconds ... (" . ($run+1) . " of " . $maxRetries . ")");

                    usleep($waitTime); // wait specified time until we restart the transaction
                } else {
                    // if the transaction still fail after $maxRetries retries, we throw out the exception
                    throw $e;
                }
            }
        }

        $additionalTags = array();
        if(isset($updatedChildren) && is_array($updatedChildren)) {
            foreach ($updatedChildren as $assetId) {
                $tag = "asset_" . $assetId;
                $additionalTags[] = $tag;

                // remove the child also from registry (internal cache) to avoid path inconsistencies during long running scripts, such as CLI
                \Zend_Registry::set($tag, null);
            }
        }
        $this->clearDependentCache($additionalTags);
        $this->setDataChanged(false);

        if ($isUpdate) {
            \Pimcore::getEventManager()->trigger("asset.postUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("asset.postAdd", $this);
        }

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function correctPath() {
        // set path
        if ($this->getId() != 1) { // not for the root node

            if($this->getParentId() == $this->getId()) {
                throw new \Exception("ParentID and ID is identical, an element can't be the parent of itself.");
            }

            $parent = Asset::getById($this->getParentId());
            if($parent) {
                // use the parent's path from the database here (getCurrentFullPath), to ensure the path really exists and does not rely on the path
                // that is currently in the parent object (in memory), because this might have changed but wasn't not saved
                $this->setPath(str_replace("//", "/", $parent->getCurrentFullPath() . "/"));
            } else {
                // parent document doesn't exist anymore, so delete this document
                //$this->delete();

                // parent document doesn't exist anymore, set the parent to to root
                $this->setParentId(1);
                $this->setPath("/");
            }

        }

        // do not allow PHP and .htaccess files
        if(preg_match("@\.ph(p[345]?|t|tml|ps)$@i", $this->getFilename()) || $this->getFilename() == ".htaccess") {
            $this->setFilename($this->getFilename() . ".txt");
        }

        if(Asset\Service::pathExists($this->getFullPath())) {
            $duplicate = Asset::getByPath($this->getFullPath());
            if ($duplicate instanceof Asset  and $duplicate->getId() != $this->getId()) {
                throw new \Exception("Duplicate full path [ " . $this->getFullPath() . " ] - cannot save asset");
            }
        }

        if(strlen($this->getFullPath()) > 765) {
            throw new \Exception("Full path is limited to 765 characters, reduce the length of your parent's path");
        }
    }

    /**
     * @throws \Exception
     */
    protected function update() {

        if (!$this->getFilename() && $this->getId() != 1) {
            $this->setFilename("---no-valid-filename---" . $this->getId());
            throw new \Exception("Asset requires filename, generated filename automatically");
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
            if(!File::mkdir($dirPath)) {
                throw new \Exception("Unable to create directory: ". $dirPath . " for asset :" . $this->getId());
            }
        }

        $typeChanged = false;

        if ($this->getType() != "folder") {
            if($this->getDataChanged()) {
                $src = $this->getStream();
                $streamMeta = stream_get_meta_data($src);
                if($destinationPath != $streamMeta["uri"]) {
                    $dest = fopen($destinationPath, "w+");
                    if($dest) {
                        stream_copy_to_stream($src, $dest);
                        if(!fclose($dest)) {
                            throw new \Exception("Unable to close file handle " . $destinationPath . " for asset " . $this->getId());
                        }
                    } else {
                        throw new \Exception("Unable to open file: " . $destinationPath . " for asset " . $this->getId());
                    }
                }

                @chmod($destinationPath, File::getDefaultMode());

                // check file exists
                if (!is_file($destinationPath)) {
                    throw new \Exception("couldn't create new asset, file " . $destinationPath . " doesn't exist");
                }

                // set mime type

                $mimetype = Mime::detect($this->getFileSystemPath());
                $this->setMimetype($mimetype);

                // set type
                $type = self::getTypeFromMimeMapping($mimetype, $this->getFilename());
                if($type != $this->getType()) {
                    $this->setType($type);
                    $typeChanged = true;
                }
            }

            // scheduled tasks are saved in $this->saveVersion();
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
        \Zend_Registry::set("asset_" . $this->getId(), $this);
        if(get_class($this) == "Asset" || $typeChanged) {
            // get concrete type of asset
            // this is important because at the time of creating an asset it's not clear which type (resp. class) it will have
            // the type (image, document, ...) depends on the mime-type
            \Zend_Registry::set("asset_" . $this->getId(), null);
            $asset = self::getById($this->getId());
            \Zend_Registry::set("asset_" . $this->getId(), $asset);
        }

        // lastly create a new version if necessary
        // this has to be after the registry update and the DB update, otherwise this would cause problem in the
        // $this->__wakeUp() method which is called by $version->save(); (path correction for version restore)
        if($this->getType() != "folder") {
            $this->saveVersion(false, false);
        }

        $this->closeStream();
    }

    /**
     * @param bool $setModificationDate
     * @param bool $callPluginHook
     * @return null|Version
     * @throws \Exception
     */
    public function saveVersion($setModificationDate = true, $callPluginHook = true) {

        // hook should be also called if "save only new version" is selected
        if($callPluginHook) {
            \Pimcore::getEventManager()->trigger("asset.preUpdate", $this);
        }

        // set date
        if ($setModificationDate) {
            $this->setModificationDate(time());
        }

        // scheduled tasks are saved always, they are not versioned!
        $this->saveScheduledTasks();

        // create version
        $version = null;

        // only create a new version if there is at least 1 allowed
        if(Config::getSystemConfig()->assets->versions->steps
            || Config::getSystemConfig()->assets->versions->days) {
            $version = new Version();
            $version->setCid($this->getId());
            $version->setCtype("asset");
            $version->setDate($this->getModificationDate());
            $version->setUserId($this->getUserModification());
            $version->setData($this);
            $version->save();
        }

        // hook should be also called if "save only new version" is selected
        if($callPluginHook) {
            \Pimcore::getEventManager()->trigger("asset.postUpdate", $this);
        }

        return $version;
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
            $list = new Asset\Listing();
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
	 * Get a list of the sibling assets
	 *
	 * @return array
	 */
	public function getSiblings() {
		if ($this->siblings === null) {
			$list = new Asset\Listing();
			// string conversion because parentId could be 0
			$list->addConditionParam("parentId = ?", (string)$this->getParentId());
			$list->addConditionParam("id != ?", $this->getId());
			$list->setOrderKey("filename");
			$list->setOrder("asc");
			$this->siblings = $list->load();
		}
		return $this->siblings;
	}

	/**
	 * Returns true if the asset has at least one sibling
	 *
	 * @return bool
	 */
	public function hasSiblings() {
		if(is_bool($this->hasSiblings)){
			if(($this->hasSiblings and empty($this->siblings)) or (!$this->hasSiblings and !empty($this->siblings))){
				return $this->getResource()->hasSiblings();
			} else {
				return $this->hasSiblings;
			}
		}
		return $this->getResource()->hasSiblings();
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
     * @throws \Exception
     */
    public function delete() {

        if ($this->getId() == 1) {
            throw new \Exception("root-node cannot be deleted");
        }

        \Pimcore::getEventManager()->trigger("asset.preDelete", $this);

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

        // remove all metadata
        $this->getResource()->deleteAllMetadata();


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
        \Zend_Registry::set("asset_" . $this->getId(), null);

        \Pimcore::getEventManager()->trigger("asset.postDelete", $this);
    }

    /**
     * @param array $additionalTags
     */
    public function clearDependentCache($additionalTags = array()) {

        try {
            $tags = array("asset_" . $this->getId(), "asset_properties", "output");
            $tags = array_merge($tags, $additionalTags);

            Cache::clearTags($tags);
        }
        catch (\Exception $e) {
            \Logger::crit($e);
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
        $this->parent = Asset::getById($parentId);
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
     * @return $this
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
     * @throws \Exception
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
            $properties = Cache::load($cacheKey);
            if (!is_array($properties)) {
                $properties = $this->getResource()->getProperties();
                $elementCacheTag = $this->getCacheTag();
                $cacheTags = array("asset_properties" => "asset_properties", $elementCacheTag => $elementCacheTag);
                Cache::save($properties, $cacheKey, $cacheTags);
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
     * @param $name
     * @param $type
     * @param $data
     * @param bool $inherited
     * @return $this
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
            File::mkdir(dirname($destinationPath));
        }

        $src = $this->getStream();
        $dest = fopen($destinationPath, "w+");
        stream_copy_to_stream($src, $dest);
        fclose($dest);

        @chmod($destinationPath, File::getDefaultMode());

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
     * @param $key
     * @return null
     */
    public function getCustomSetting($key) {
        if(is_array($this->customSettings) && array_key_exists($key, $this->customSettings)) {
            return $this->customSettings[$key];
        }
        return null;
    }

    /**
     * @param $key
     */
    public function removeCustomSetting($key) {
        if(is_array($this->customSettings) && array_key_exists($key, $this->customSettings)) {
            unset($this->customSettings[$key]);
        }
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
            $customSettings = \Pimcore\Tool\Serialize::unserialize($customSettings);
        }

        if ($customSettings instanceof \stdClass) {
            $customSettings = (array) $customSettings;
        }

        if(!is_array($customSettings)) {
            $customSettings = array();
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
     * @param array $metadata
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }


    /**
     * @param $name
     * @param $type can be "folder", "image", "text", "audio", "video", "document", "archive" or "unknown"
     * @param null $data
     * @param null $language
     */
    public function addMetadata($name, $type, $data = null, $language = null) {
        if ($name && $type) {
            $tmp = array();
            if (!is_array($this->metadata)) {
                $this->metadata = array();
            }

            foreach ($this->metadata as $item) {
                if ($item["name"] != $name || $language != $item["language"]) {
                    $tmp[] = $item;
                }
            }
            $tmp[] = array(
                "name" => $name,
                "type" => $type,
                "data" => $data,
                "language" => $language
            );
            $this->metadata = $tmp;
        }
    }

    /**
     * @return array
     */
    public function getMetadata($name = null, $language = null)
    {
        $convert = function ($metaData) {
            if(in_array($metaData["type"], ["asset","document","object"]) && is_numeric($metaData["data"])) {
                return Element\Service::getElementById($metaData["type"], $metaData["data"]);
            }
            return $metaData["data"];
        };


        if($name) {
            if($language === null) {
                if(\Zend_Registry::isRegistered("Zend_Locale")) {
                    $language = (string) \Zend_Registry::get("Zend_Locale");
                }
            }

            $data = null;
            foreach ($this->metadata as $md) {
                if($md["name"] == $name) {
                    if($language == $md["language"]) {
                        return $convert($md);
                    }
                    if(empty($md["language"])) {
                        $data = $md;
                    }
                }
            }

            if($data) {
                return $convert($data);
            }
            return null;
        }

        $metaData = $this->metadata;
        foreach($metaData as &$md) {
            $md["data"] = $convert($md);
        }

        return $metaData;
    }

    /**
     * @return array
     */
    public function getScheduledTasks() {
        if ($this->scheduledTasks === null) {
            $taskList = new Schedule\Task\Listing();
            $taskList->setCondition("cid = ? AND ctype='asset'", $this->getId());
            $this->setScheduledTasks($taskList->load());
        }
        return $this->scheduledTasks;
    }

    /**
     * @param $scheduledTasks
     * @return $this
     */
    public function setScheduledTasks($scheduledTasks) {
        $this->scheduledTasks = $scheduledTasks;
        return $this;
    }

    /**
     *
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
        // group the thumbnails because of limitations of some filesystems (eg. ext3 allows only 32k subfolders)
        $group = floor($this->getId() / 10000) * 10000;
        $path = PIMCORE_TEMPORARY_DIRECTORY . "/image-thumbnails/" . $group . "/" . $this->getId();
        return $path;
    }

    /**
     * @return string
     */
    public function getVideoThumbnailSavePath() {
        // group the thumbnails because of limitations of some filesystems (eg. ext3 allows only 32k subfolders)
        $group = floor($this->getId() / 10000) * 10000;
        $path = PIMCORE_TEMPORARY_DIRECTORY . "/video-thumbnails/" . $group . "/" . $this->getId();
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

    /**
     *
     */
    public function __wakeup() {
        if(isset($this->_fulldump)) {
            // set current key and path this is necessary because the serialized data can have a different path than the original element (element was renamed or moved)
            $originalElement = Asset::getById($this->getId());
            if($originalElement) {
                $this->setFilename($originalElement->getFilename());
                $this->setPath($originalElement->getPath());
            }
        }

        if(isset($this->_fulldump) && $this->properties !== null) {
            $this->renewInheritedProperties();
        }

        if(isset($this->_fulldump)) {
            unset($this->_fulldump);
        }
    }

    /**
     *
     */
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

    /**
     *
     */
    public function renewInheritedProperties () {
        $this->removeInheritedProperties();

        // add to registry to avoid infinite regresses in the following $this->getResource()->getProperties()
        $cacheKey = "asset_" . $this->getId();
        if(!\Zend_Registry::isRegistered($cacheKey)) {
            \Zend_Registry::set($cacheKey, $this);
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
