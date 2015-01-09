<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license dsf sdaf asdf asdf
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

use Pimcore\File;
use Pimcore\Tool;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Model;

class Admin_AssetController extends \Pimcore\Controller\Action\Admin\Element
{

    /**
     * @var Asset\Service
     */
    protected $_assetService;

    public function init()
    {
        parent::init();

        // check permissions
        $notRestrictedActions = array("get-image-thumbnail", "get-video-thumbnail", "get-document-thumbnail");
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("assets");
        }

        $this->_assetService = new Asset\Service($this->getUser());
    }

    public function getDataByIdAction()
    {

        // check for lock
        if (Element\Editlock::isLocked($this->getParam("id"), "asset")) {
            $this->_helper->json(array(
                "editlock" => Element\Editlock::getByElement($this->getParam("id"), "asset")
            ));
        }
        Element\Editlock::lock($this->getParam("id"), "asset");

        $asset = Asset::getById(intval($this->getParam("id")));

        if (!$asset instanceof Asset) {
            $this->_helper->json(array("success" => false, "message" => "asset doesn't exist"));
        }

        $asset->setMetadata(Asset\Service::expandMetadata($asset->getMetadata()));
        $asset->setProperties(Element\Service::minimizePropertiesForEditmode($asset->getProperties()));
        //$asset->getVersions();
        $asset->getScheduledTasks();
        $asset->idPath = Element\Service::getIdPath($asset);
        $asset->userPermissions = $asset->getUserPermissions();
        $asset->setLocked($asset->isLocked());
        $asset->setParent(null);

        if ($asset instanceof Asset\Text) {
            $asset->data = $asset->getData();
        }

        if ($asset instanceof Asset\Image) {
            $imageInfo = array();

            if ($asset->getWidth() && $asset->getHeight()) {
                $imageInfo["dimensions"] = array();
                $imageInfo["dimensions"]["width"] = $asset->getWidth();
                $imageInfo["dimensions"]["height"] = $asset->getHeight();
            }

            if (function_exists("exif_read_data") && is_file($asset->getFileSystemPath())) {
                $supportedTypes = array(IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM);

                if (in_array(exif_imagetype($asset->getFileSystemPath()), $supportedTypes)) {
                    $exif = @exif_read_data($asset->getFileSystemPath());
                    if (is_array($exif)) {
                        $imageInfo["exif"] = array();
                        foreach ($exif as $name => $value) {
                            if ((is_string($value) && strlen($value) < 50) || is_numeric($value)) {
                                // this is to ensure that the data can be converted to json (must be utf8)
                                if (mb_check_encoding($value, "UTF-8")) {
                                    $imageInfo["exif"][$name] = $value;
                                }
                            }
                        }
                    }
                }
            }

            $asset->imageInfo = $imageInfo;
        }

        $asset->setStream(null);
        if ($asset->isAllowed("view")) {
            $this->_helper->json($asset);
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }

    public function treeGetChildsByIdAction()
    {

        $assets = array();
        $asset = Asset::getById($this->getParam("node"));

        if ($asset->hasChilds()) {

            $limit = intval($this->getParam("limit"));
            if (!$this->getParam("limit")) {
                $limit = 100000000;
            }
            $offset = intval($this->getParam("start"));


            // get assets
            $childsList = new Asset\Listing();
            if ($this->getUser()->isAdmin()) {
                $childsList->setCondition("parentId = ? ", $asset->getId());
            } else {
                $userIds = $this->getUser()->getRoles();
                $userIds[] = $this->getUser()->getId();
                $childsList->setCondition("parentId = ? and
                                                    (
                                                    (select list from users_workspaces_asset where userId in (" . implode(',', $userIds) . ") and LOCATE(CONCAT(path,filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    or
                                                    (select list from users_workspaces_asset where userId in (" . implode(',', $userIds) . ") and LOCATE(cpath,CONCAT(path,filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    )", $asset->getId());
            }
            $childsList->setLimit($limit);
            $childsList->setOffset($offset);
            $childsList->setOrderKey("FIELD(type, 'folder') DESC, filename ASC", false);

            $childs = $childsList->load();

            foreach ($childs as $childAsset) {
                if ($childAsset->isAllowed("list")) {
                    $assets[] = $this->getTreeNodeConfig($childAsset);
                }
            }
        }


        if ($this->getParam("limit")) {
            $this->_helper->json(array(
                "total" => $asset->getChildAmount($this->getUser()),
                "nodes" => $assets
            ));
        } else {
            $this->_helper->json($assets);
        }

        $this->_helper->json(false);
    }

    public function addAssetAction()
    {
        $res = $this->addAsset();
        $this->_helper->json(array("success" => $res["success"], "msg" => "Success"));
    }

    public function addAssetCompatibilityAction()
    {
        // this is a special action for the compatibility mode upload (without flash)
        $res = $this->addAsset();

        // here we have to use this method and not the JSON action helper ($this->_helper->json()) because this will add
        // Content-Type: application/json which fires a download window in most browsers, because this is a normal POST
        // request and not XHR where the content-type doesn't matter
        $this->disableViewAutoRender();
        echo \Zend_Json::encode(array(
            "success" => $res["success"],
            "msg" => $res["success"] ? "Success" : "Error",
            "id" => $res["asset"] ? $res["asset"]->getId() : null,
            "fullpath" => $res["asset"] ? $res["asset"]->getFullPath() : null,
            "type" => $res["asset"] ? $res["asset"]->getType() : null
        ));
    }

    protected function addAsset()
    {
        $success = false;

        if (array_key_exists("Filedata", $_FILES)) {
            $filename = $_FILES["Filedata"]["name"];
            $sourcePath = $_FILES["Filedata"]["tmp_name"];
        } else if ($this->getParam("type") == "base64") {
            $filename = $this->getParam("filename");
            $sourcePath = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/upload-base64" . uniqid() . ".tmp";
            $data = preg_replace("@^data:[^,]+;base64,@", "", $this->getParam("data"));
            File::put($sourcePath, base64_decode($data));
        }

        if($this->getParam("dir") && $this->getParam("parentId")) {
            // this is for uploading folders with Drag&Drop
            // param "dir" contains the relative path of the file
            $parent = Asset::getById($this->getParam("parentId"));
            $newPath = $parent->getFullPath() . "/" . trim($this->getParam("dir"), "/ ");

            $newParent = Asset\Service::createFolderByPath($newPath);
            $this->setParam("parentId", $newParent->getId());
        } else if (!$this->getParam("parentId") && $this->getParam("parentPath")) {
            $parent = Asset::getByPath($this->getParam("parentPath"));
            if ($parent instanceof Asset\Folder) {
                $this->setParam("parentId", $parent->getId());
            } else {
                $this->setParam("parentId", 1);
            }
        } else if (!$this->getParam("parentId")) {
            // set the parent to the root folder
            $this->setParam("parentId", 1);
        }

        $filename = File::getValidFilename($filename);
        if (empty($filename)) {
            throw new \Exception("The filename of the asset is empty");
        }

        $parentAsset = Asset::getById(intval($this->getParam("parentId")));

        // check for duplicate filename
        $filename = $this->getSafeFilename($parentAsset->getFullPath(), $filename);

        if ($parentAsset->isAllowed("create")) {

            if(!is_file($sourcePath) || filesize($sourcePath) < 1) {
                throw new \Exception("Something went wrong, please check upload_max_filesize and post_max_size in your php.ini and write permissions of /website/var/");
            }

            $asset = Asset::create($this->getParam("parentId"), array(
                "filename" => $filename,
                "sourcePath" => $sourcePath,
                "userOwner" => $this->user->getId(),
                "userModification" => $this->user->getId()
            ));
            $success = true;

            @unlink($sourcePath);
        } else {
            \Logger::debug("prevented creating asset because of missing permissions, parent asset is " . $parentAsset->getFullPath());
        }

        return array(
            "success" => $success,
            "asset" => $asset
        );
    }

    protected function getSafeFilename($targetPath, $filename)
    {

        $originalFilename = $filename;
        $count = 1;

        if ($targetPath == "/") {
            $targetPath = "";
        }

        while (true) {
            if (Asset\Service::pathExists($targetPath . "/" . $filename)) {
                $filename = str_replace("." . File::getFileExtension($originalFilename), "_" . $count . "." . File::getFileExtension($originalFilename), $originalFilename);
                $count++;
            } else {
                return $filename;
            }
        }
    }

    public function replaceAssetAction()
    {
        $asset = Asset::getById($this->getParam("id"));

        $stream = fopen($_FILES["Filedata"]["tmp_name"], "r+");
        $asset->setStream($stream);
        $asset->setCustomSetting("thumbnails", null);
        $asset->setUserModification($this->getUser()->getId());

        if ($asset->isAllowed("publish")) {
            $asset->save();

            $this->_helper->json(array(
                "id" => $asset->getId(),
                "path" => $asset->getPath() . $asset->getFilename(),
                "success" => true
            ), false);

            // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
            // Ext.form.Action.Submit and mark the submission as failed
            $this->getResponse()->setHeader("Content-Type", "text/html");

        } else {
            throw new \Exception("missing permission");
        }
    }

    public function addFolderAction()
    {

        $success = false;
        $parentAsset = Asset::getById(intval($this->getParam("parentId")));
        $equalAsset = Asset::getByPath($parentAsset->getFullPath() . "/" . $this->getParam("name"));

        if ($parentAsset->isAllowed("create")) {

            if (!$equalAsset) {
                $asset = Asset::create($this->getParam("parentId"), array(
                    "filename" => $this->getParam("name"),
                    "type" => "folder",
                    "userOwner" => $this->user->getId(),
                    "userModification" => $this->user->getId()
                ));
                $success = true;
            }
        } else {
            \Logger::debug("prevented creating asset because of missing permissions");
        }

        $this->_helper->json(array("success" => $success));
    }

    public function deleteAction()
    {
        if ($this->getParam("type") == "childs") {

            $parentAsset = Asset::getById($this->getParam("id"));

            $list = new Asset\Listing();
            $list->setCondition("path LIKE '" . $parentAsset->getFullPath() . "/%'");
            $list->setLimit(intval($this->getParam("amount")));
            $list->setOrderKey("LENGTH(path)", false);
            $list->setOrder("DESC");

            $assets = $list->load();

            $deletedItems = array();
            foreach ($assets as $asset) {
                $deletedItems[] = $asset->getFullPath();
                if ($asset->isAllowed("delete")) {
                    $asset->delete();
                }
            }

            $this->_helper->json(array("success" => true, "deleted" => $deletedItems));

        } else if ($this->getParam("id")) {
            $asset = Asset::getById($this->getParam("id"));

            if ($asset->isAllowed("delete")) {
                $asset->delete();

                $this->_helper->json(array("success" => true));
            }
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }

    public function deleteInfoAction()
    {
        $hasDependency = false;
        $deleteJobs = array();
        $recycleJobs = array();

        $totalChilds = 0;

        $ids = $this->getParam("id");
        $ids = explode(',', $ids);

        foreach ($ids as $id) {
            try {
                $asset = Asset::getById($id);
                if (!$asset) {
                    continue;
                }
                $hasDependency = $asset->getDependencies()->isRequired();
            } catch (\Exception $e) {
                \Logger::err("failed to access asset with id: " . $id);
                continue;
            }


            // check for childs
            if ($asset instanceof Asset) {

                $recycleJobs[] = array(array(
                    "url" => "/admin/recyclebin/add",
                    "params" => array(
                        "type" => "asset",
                        "id" => $asset->getId()
                    )
                ));


                $hasChilds = $asset->hasChilds();
                if (!$hasDependency) {
                    $hasDependency = $hasChilds;
                }

                $childs = 0;
                if ($hasChilds) {
                    // get amount of childs
                    $list = new Asset\Listing();
                    $list->setCondition("path LIKE '" . $asset->getFullPath() . "/%'");
                    $childs = $list->getTotalCount();
                    $totalChilds += $childs;

                    if ($childs > 0) {
                        $deleteObjectsPerRequest = 5;
                        for ($i = 0; $i < ceil($childs / $deleteObjectsPerRequest); $i++) {
                            $deleteJobs[] = array(array(
                                "url" => "/admin/asset/delete",
                                "params" => array(
                                    "step" => $i,
                                    "amount" => $deleteObjectsPerRequest,
                                    "type" => "childs",
                                    "id" => $asset->getId()
                                )
                            ));
                        }
                    }
                }

                // the asset itself is the last one
                $deleteJobs[] = array(array(
                    "url" => "/admin/asset/delete",
                    "params" => array(
                        "id" => $asset->getId()
                    )
                ));
            }
        }

        $deleteJobs = array_merge($recycleJobs, $deleteJobs);
        $this->_helper->json(array(
            "hasDependencies" => $hasDependency,
            "childs" => $totalChilds,
            "deletejobs" => $deleteJobs,
            "batchDelete" => count($ids) > 1
        ));
    }

    /**
     * @param Asset $asset
     * @return array|string
     */
    protected function getTreeNodeConfig($asset)
    {
        $tmpAsset = array(
            "id" => $asset->getId(),
            "text" => $asset->getFilename(),
            "type" => $asset->getType(),
            "path" => $asset->getFullPath(),
            "basePath" => $asset->getPath(),
            "locked" => $asset->isLocked(),
            "lockOwner" => $asset->getLocked() ? true : false,
            "elementType" => "asset",
            "permissions" => array(
                "remove" => $asset->isAllowed("delete"),
                "settings" => $asset->isAllowed("settings"),
                "rename" => $asset->isAllowed("rename"),
                "publish" => $asset->isAllowed("publish"),
                "view" => $asset->isAllowed("view")
            )
        );

        // set type specific settings
        if ($asset->getType() == "folder") {
            $tmpAsset["leaf"] = false;
            $tmpAsset["expanded"] = $asset->hasNoChilds();
            $tmpAsset["iconCls"] = "pimcore_icon_folder";
            $tmpAsset["permissions"]["create"] = $asset->isAllowed("create");

            $folderThumbs = array();
            $children = new Asset\Listing();
            $children->setCondition("path LIKE ?", [$asset->getFullPath() . "/%"]);
            $children->setLimit(35);

            foreach ($children as $child) {
                if ($thumbnailUrl = $this->getThumbnailUrl($child)) {
                    $folderThumbs[] = $thumbnailUrl;
                }
            }

            if (!empty($folderThumbs)) {
                $tmpAsset["thumbnails"] = $folderThumbs;
            }
        } else {
            $tmpAsset["leaf"] = true;
            $tmpAsset["iconCls"] = "pimcore_icon_" . File::getFileExtension($asset->getFilename());
        }

        $tmpAsset["qtipCfg"] = array(
            "title" => "ID: " . $asset->getId()
        );

        if ($asset->getType() == "image") {
            try {
                $tmpAsset["thumbnail"] = $this->getThumbnailUrl($asset);

                // this is for backward-compatibility, to calculate the dimensions if they are not there
                if (!$asset->getCustomSetting("imageDimensionsCalculated")) {
                    $asset->save();
                }

                // we need the dimensions for the wysiwyg editors, so that they can resize the image immediately
                if ($asset->getCustomSetting("imageWidth") && $asset->getCustomSetting("imageHeight")) {
                    $tmpAsset["imageWidth"] = $asset->getCustomSetting("imageWidth");
                    $tmpAsset["imageHeight"] = $asset->getCustomSetting("imageHeight");
                }

            } catch (\Exception $e) {
                \Logger::debug("Cannot get dimensions of image, seems to be broken.");
            }
        } else if ($asset->getType() == "video") {
            try {
                if (\Pimcore\Video::isAvailable()) {
                    $tmpAsset["thumbnail"] = $this->getThumbnailUrl($asset);
                }
            } catch (\Exception $e) {
                \Logger::debug("Cannot get dimensions of video, seems to be broken.");
            }
        } else if ($asset->getType() == "document") {
            try {
                // add the PDF check here, otherwise the preview layer in admin is shown without content
                if (\Pimcore\Document::isAvailable() && \Pimcore\Document::isFileTypeSupported($asset->getFilename())) {
                    $tmpAsset["thumbnail"] = $this->getThumbnailUrl($asset);
                }
            } catch (\Exception $e) {
                \Logger::debug("Cannot get dimensions of video, seems to be broken.");
            }
        }

        $tmpAsset["cls"] = "";
        if ($asset->isLocked()) {
            $tmpAsset["cls"] .= "pimcore_treenode_locked ";
        }
        if ($asset->getLocked()) {
            $tmpAsset["cls"] .= "pimcore_treenode_lockOwner ";
        }

        return $tmpAsset;
    }

    protected function getThumbnailUrl($asset)
    {
        if ($asset instanceof Asset\Image) {
            return "/admin/asset/get-image-thumbnail/id/" . $asset->getId() . "/treepreview/true";
        } else if ($asset instanceof Asset\Video && \Pimcore\Video::isAvailable()) {
            return "/admin/asset/get-video-thumbnail/id/" . $asset->getId() . "/treepreview/true";
        } else if ($asset instanceof Asset\Document && \Pimcore\Document::isAvailable()) {
            return "/admin/asset/get-document-thumbnail/id/" . $asset->getId() . "/treepreview/true";
        }
        return null;
    }

    public function updateAction()
    {

        $success = false;
        $allowUpdate = true;

        $updateData = $this->getAllParams();

        $asset = Asset::getById($this->getParam("id"));
        if ($asset->isAllowed("settings")) {

            $asset->setUserModification($this->getUser()->getId());

            // if the position is changed the path must be changed || also from the childs
            if ($this->getParam("parentId")) {
                $parentAsset = Asset::getById($this->getParam("parentId"));

                //check if parent is changed i.e. asset is moved
                if ($asset->getParentId() != $parentAsset->getId()) {

                    if (!$parentAsset->isAllowed("create")) {
                        throw new \Exception("Prevented moving asset - no create permission on new parent ");
                    }

                    $intendedPath = $parentAsset->getPath();
                    $pKey = $parentAsset->getKey();
                    if (!empty($pKey)) {
                        $intendedPath .= $parentAsset->getKey() . "/";
                    }

                    $assetWithSamePath = Asset::getByPath($intendedPath . $asset->getKey());

                    if ($assetWithSamePath != null) {
                        $allowUpdate = false;
                    }

                    if($asset->isLocked()) {
                        $allowUpdate = false;
                    }
                }
            }

            if ($allowUpdate) {
                if ($this->getParam("filename") || $this->getParam("parentId")) {
                    $asset->getData();
                }

                if ($this->getParam("filename") != $asset->getFilename() and !$asset->isAllowed("rename")) {
                    unset($updateData["filename"]);
                    \Logger::debug("prevented renaming asset because of missing permissions ");
                }

                $asset->setValues($updateData);


                try {
                    $asset->save();
                    $success = true;
                } catch (\Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }


            } else {
                $msg = "prevented moving asset, asset with same path+key already exists at target location or the asset is locked. ID: " . $asset->getId();
                \Logger::debug($msg);
                $this->_helper->json(array("success" => $success, "message" => $msg));
            }
        } else if ($asset->isAllowed("rename") && $this->getParam("filename")) {
            //just rename
            try {
                $asset->setFilename($this->getParam("filename"));
                $asset->save();
                $success = true;
            } catch (\Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
            }
        } else {
            \Logger::debug("prevented update asset because of missing permissions ");
        }

        $this->_helper->json(array("success" => $success));
    }


    public function webdavAction()
    {
        $homeDir = Asset::getById(1);

        try {
            $publicDir = new Asset\WebDAV\Folder($homeDir);
            $objectTree = new Asset\WebDAV\Tree($publicDir);
            $server = new \Sabre\DAV\Server($objectTree);

            $lockBackend = new \Sabre\DAV\Locks\Backend\File(PIMCORE_WEBDAV_TEMP . '/locks.dat');
            $lockPlugin = new \Sabre\DAV\Locks\Plugin($lockBackend);
            $server->addPlugin($lockPlugin);

            $server->exec();
        } catch (\Exception $e) {
            \Logger::error($e);
        }

        exit;
    }


    public function saveAction()
    {
        $success = false;
        if ($this->getParam("id")) {
            $asset = Asset::getById($this->getParam("id"));
            if ($asset->isAllowed("publish")) {


                // metadata
                if($this->getParam("metadata")) {
                    $metadata = \Zend_Json::decode($this->getParam("metadata"));
                    $metadata = Asset\Service::minimizeMetadata($metadata);
                    $asset->setMetadata($metadata);
                }

                // properties
                if ($this->getParam("properties")) {
                    $properties = array();
                    $propertiesData = \Zend_Json::decode($this->getParam("properties"));

                    if (is_array($propertiesData)) {
                        foreach ($propertiesData as $propertyName => $propertyData) {

                            $value = $propertyData["data"];

                            try {
                                $property = new Model\Property();
                                $property->setType($propertyData["type"]);
                                $property->setName($propertyName);
                                $property->setCtype("asset");
                                $property->setDataFromEditmode($value);
                                $property->setInheritable($propertyData["inheritable"]);

                                $properties[$propertyName] = $property;
                            } catch (\Exception $e) {
                                \Logger::err("Can't add " . $propertyName . " to asset " . $asset->getFullPath());
                            }
                        }

                        $asset->setProperties($properties);
                    }
                }

                // scheduled tasks
                if ($this->getParam("scheduler")) {
                    $tasks = array();
                    $tasksData = \Zend_Json::decode($this->getParam("scheduler"));

                    if (!empty($tasksData)) {
                        foreach ($tasksData as $taskData) {
                            $taskData["date"] = strtotime($taskData["date"] . " " . $taskData["time"]);

                            $task = new Model\Schedule\Task($taskData);
                            $tasks[] = $task;
                        }
                    }

                    $asset->setScheduledTasks($tasks);
                }

                if ($this->hasParam("data")) {
                    $asset->setData($this->getParam("data"));
                }

                $asset->setUserModification($this->getUser()->getId());


                try {
                    $asset->save();
                    $asset->getData();
                    $success = true;
                } catch (\Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }
            } else {
                \Logger::debug("prevented save asset because of missing permissions ");
            }

            $this->_helper->json(array("success" => $success));
        }

        $this->_helper->json(false);
    }

    public function publishVersionAction()
    {

        $version = Model\Version::getById($this->getParam("id"));
        $asset = $version->loadData();

        $currentAsset = Asset::getById($asset->getId());
        if ($currentAsset->isAllowed("publish")) {
            try {
                $asset->setUserModification($this->getUser()->getId());
                $asset->save();
                $this->_helper->json(array("success" => true));
            } catch (\Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
            }


        }

        $this->_helper->json(false);
    }

    public function showVersionAction()
    {
        $id = intval($this->getParam("id"));
        $version = Model\Version::getById($id);
        $asset = $version->loadData();

        if($asset->isAllowed("versions")) {
            $this->view->asset = $asset;
            $this->render("show-version-" . $asset->getType());
        } else {
            throw new \Exception("Permission denied, version id [" . $id . "]");
        }
    }

    public function downloadAction()
    {
        $asset = Asset::getById($this->getParam("id"));

        if ($asset->isAllowed("view")) {
            header("Content-Type: " . $asset->getMimetype(), true);
            header('Content-Disposition: attachment; filename="' . $asset->getFilename() . '"');
            header("Content-Length: " . filesize($asset->getFileSystemPath()), true);

            while (@ob_end_flush()) ;
            flush();

            readfile($asset->getFileSystemPath());
            exit;
        }

        $this->removeViewRenderer();
    }

    public function getImageThumbnailAction() {

        $fileinfo = $this->getParam("fileinfo");
        $image = Asset\Image::getById(intval($this->getParam("id")));
        $thumbnail = null;

        if ($this->getParam("thumbnail")) {
            $thumbnail = $image->getThumbnailConfig($this->getParam("thumbnail"));
        }
        if (!$thumbnail) {
            if($this->getParam("config")) {
                $thumbnail = $image->getThumbnailConfig(\Zend_Json::decode($this->getParam("config")));
            } else {
                $thumbnail = $image->getThumbnailConfig($this->getAllParams());
            }
        } else {
            // no high-res images in admin mode (editmode)
            // this is mostly because of the document's image editable, which doesn't know anything about the thumbnail
            // configuration, so the dimensions would be incorrect (double the size)
            $thumbnail->setHighResolution(1);
        }

        $format = strtolower($thumbnail->getFormat());
        if ($format == "source" || $format == "print") {
            $thumbnail->setFormat("PNG");
            $format = "png";
        }

        if($this->getParam("treepreview")) {
            $thumbnail = Asset\Image\Thumbnail\Config::getPreviewConfig();
        }

        if ($this->getParam("cropPercent")) {
            $thumbnail->addItemAt(0,"cropPercent", array(
                "width" => $this->getParam("cropWidth"),
                "height" => $this->getParam("cropHeight"),
                "y" => $this->getParam("cropTop"),
                "x" => $this->getParam("cropLeft")
            ));

            $hash = md5(Tool\Serialize::serialize($this->getAllParams()));
            $thumbnail->setName($thumbnail->getName() . "_auto_" . $hash);
        }

        if($this->getParam("download")) {
            $downloadFilename = str_replace("." . File::getFileExtension($image->getFilename()), "." . $thumbnail->getFormat(), $image->getFilename());
            $downloadFilename = strtolower($downloadFilename);
            header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
        }

        $thumbnail = $image->getThumbnail($thumbnail);

        if ($fileinfo) {
            $this->_helper->json(array(
                "width" => $thumbnail->getWidth(),
                "height" => $thumbnail->getHeight()));
        }

        $thumbnailFile = PIMCORE_DOCUMENT_ROOT . $thumbnail;
        $fileExtension = File::getFileExtension($thumbnailFile);
        if(in_array($fileExtension, array("gif","jpeg","jpeg","png","pjpeg"))) {
            header("Content-Type: image/".$fileExtension, true);
        } else {
            header("Content-Type: " . $image->getMimetype(), true);
        }

        header("Content-Length: " . filesize($thumbnailFile), true);
        $this->sendThumbnailCacheHeaders();

        while(@ob_end_flush());
        flush();

        readfile($thumbnailFile);
        exit;
    }

    public function getVideoThumbnailAction()
    {

        if ($this->getParam("id")) {
            $video = Asset::getById(intval($this->getParam("id")));
        } else if ($this->getParam("path")) {
            $video = Asset::getByPath($this->getParam("path"));
        }
        $thumbnail = $video->getImageThumbnailConfig($this->getAllParams());

        $format = strtolower($thumbnail->getFormat());
        if ($format == "source") {
            $thumbnail->setFormat("PNG");
            $format = "png";
        }

        if ($this->getParam("treepreview")) {
            $thumbnail = Asset\Image\Thumbnail\Config::getPreviewConfig();
        }

        $time = null;
        if ($this->getParam("time")) {
            $time = intval($this->getParam("time"));
        }

        if ($this->getParam("settime")) {
            $video->removeCustomSetting("image_thumbnail_asset");
            $video->setCustomSetting("image_thumbnail_time", $time);
            $video->save();
        }

        $image = null;
        if ($this->getParam("image")) {
            $image = Asset::getById(intval($this->getParam("image")));
        }

        if ($this->getParam("setimage") && $image) {
            $video->removeCustomSetting("image_thumbnail_time");
            $video->setCustomSetting("image_thumbnail_asset", $image->getId());
            $video->save();
        }

        $thumbnailFile = PIMCORE_DOCUMENT_ROOT . $video->getImageThumbnail($thumbnail, $time, $image);

        header("Content-type: image/" . $format, true);
        header("Content-Length: " . filesize($thumbnailFile), true);
        $this->sendThumbnailCacheHeaders();

        while (@ob_end_flush()) ;
        flush();

        readfile($thumbnailFile);
        exit;
    }

    public function getDocumentThumbnailAction()
    {

        $document = Asset::getById(intval($this->getParam("id")));
        $thumbnail = Asset\Image\Thumbnail\Config::getByAutoDetect($this->getAllParams());

        $format = strtolower($thumbnail->getFormat());
        if ($format == "source") {
            $thumbnail->setFormat("jpeg"); // default format for documents is JPEG not PNG (=too big)
        }

        if ($this->getParam("treepreview")) {
            $thumbnail = Asset\Image\Thumbnail\Config::getPreviewConfig();
        }

        $page = 1;
        if (is_numeric($this->getParam("page"))) {
            $page = (int)$this->getParam("page");
        }


        $thumbnailFile = PIMCORE_DOCUMENT_ROOT . $document->getImageThumbnail($thumbnail, $page);

        $format = "png";
        header("Content-type: image/" . $format, true);
        header("Content-Length: " . filesize($thumbnailFile), true);
        $this->sendThumbnailCacheHeaders();

        while (@ob_end_flush()) ;
        flush();

        readfile($thumbnailFile);
        exit;
    }

    protected function sendThumbnailCacheHeaders()
    {
        $this->getResponse()->clearAllHeaders();

        $lifetime = 300;
        header("Cache-Control: public, max-age=" . $lifetime, true);
        header("Expires: " . \Zend_Date::now()->add($lifetime)->get(\Zend_Date::RFC_1123), true);
        header("Pragma: ");
    }

    public function getPreviewDocumentAction()
    {
        $asset = Asset::getById($this->getParam("id"));
        $this->view->asset = $asset;
    }


    public function getPreviewVideoAction()
    {

        $asset = Asset::getById($this->getParam("id"));

        $this->view->asset = $asset;

        $config = Asset\Video\Thumbnail\Config::getPreviewConfig();

        $thumbnail = $asset->getThumbnail($config, array("mp4"));

        if ($thumbnail) {
            $this->view->asset = $asset;
            $this->view->thumbnail = $thumbnail;

            if ($thumbnail["status"] == "finished") {
                $this->render("get-preview-video-display");
            } else {
                $this->render("get-preview-video-error");
            }
        } else {
            $this->render("get-preview-video-error");
        }
    }


    public function saveImagePixlrAction()
    {

        $asset = Asset::getById($this->getParam("id"));
        $asset->setData(Tool::getHttpData($this->getParam("image")));
        $asset->setUserModification($this->getUser()->getId());
        $asset->save();

        $this->view->asset = $asset;
    }

    public function getFolderContentPreviewAction()
    {

        $folder = Asset::getById($this->getParam("id"));

        $start = 0;
        $limit = 10;

        if ($this->getParam("limit")) {
            $limit = $this->getParam("limit");
        }
        if ($this->getParam("start")) {
            $start = $this->getParam("start");
        }

        $list = Asset::getList(array(
            "condition" => "path LIKE '" . $folder->getFullPath() . "/%' AND type != 'folder'",
            "limit" => $limit,
            "offset" => $start,
            "orderKey" => "filename",
            "order" => "asc"
        ));

        $assets = array();

        foreach ($list as $asset) {

            $thumbnailMethod = "";
            if ($asset instanceof Asset\Image) {
                $thumbnailMethod = "getThumbnail";
            } else if ($asset instanceof Asset\Video && \Pimcore\Video::isAvailable()) {
                $thumbnailMethod = "getImageThumbnail";
            } else if ($asset instanceof Asset\Document && \Pimcore\Document::isAvailable()) {
                $thumbnailMethod = "getImageThumbnail";
            }

            if (!empty($thumbnailMethod)) {
                $assets[] = array(
                    "id" => $asset->getId(),
                    "type" => $asset->getType(),
                    "filename" => $asset->getFilename(),
                    "url" => "/admin/asset/get-" . $asset->getType() . "-thumbnail/id/" . $asset->getId() . "/treepreview/true",
                    "idPath" => $data["idPath"] = Element\Service::getIdPath($asset)
                );
            }
        }


        $this->_helper->json(array(
            "assets" => $assets,
            "success" => true,
            "total" => $list->getTotalCount()
        ));
    }

    public function copyInfoAction()
    {

        $transactionId = time();
        $pasteJobs = array();

        Tool\Session::useSession(function ($session) use ($transactionId) {
            $session->$transactionId = array();
        }, "pimcore_copy");


        if ($this->getParam("type") == "recursive") {

            $asset = Asset::getById($this->getParam("sourceId"));

            // first of all the new parent
            $pasteJobs[] = array(array(
                "url" => "/admin/asset/copy",
                "params" => array(
                    "sourceId" => $this->getParam("sourceId"),
                    "targetId" => $this->getParam("targetId"),
                    "type" => "child",
                    "transactionId" => $transactionId,
                    "saveParentId" => true
                )
            ));

            if ($asset->hasChilds()) {
                // get amount of childs
                $list = new Asset\Listing();
                $list->setCondition("path LIKE '" . $asset->getFullPath() . "/%'");
                $list->setOrderKey("LENGTH(path)", false);
                $list->setOrder("ASC");
                $childIds = $list->loadIdList();

                if (count($childIds) > 0) {
                    foreach ($childIds as $id) {
                        $pasteJobs[] = array(array(
                            "url" => "/admin/asset/copy",
                            "params" => array(
                                "sourceId" => $id,
                                "targetParentId" => $this->getParam("targetId"),
                                "sourceParentId" => $this->getParam("sourceId"),
                                "type" => "child",
                                "transactionId" => $transactionId
                            )
                        ));
                    }
                }
            }
        } else if ($this->getParam("type") == "child" || $this->getParam("type") == "replace") {
            // the object itself is the last one
            $pasteJobs[] = array(array(
                "url" => "/admin/asset/copy",
                "params" => array(
                    "sourceId" => $this->getParam("sourceId"),
                    "targetId" => $this->getParam("targetId"),
                    "type" => $this->getParam("type"),
                    "transactionId" => $transactionId
                )
            ));
        }


        $this->_helper->json(array(
            "pastejobs" => $pasteJobs
        ));
    }


    public function copyAction()
    {
        $success = false;
        $sourceId = intval($this->getParam("sourceId"));
        $source = Asset::getById($sourceId);
        $session = Tool\Session::get("pimcore_copy");

        $targetId = intval($this->getParam("targetId"));
        if ($this->getParam("targetParentId")) {
            $sourceParent = Asset::getById($this->getParam("sourceParentId"));

            // this is because the key can get the prefix "_copy" if the target does already exists
            if ($session->{$this->getParam("transactionId")}["parentId"]) {
                $targetParent = Asset::getById($session->{$this->getParam("transactionId")}["parentId"]);
            } else {
                $targetParent = Asset::getById($this->getParam("targetParentId"));
            }

            $targetPath = preg_replace("@^" . $sourceParent->getFullPath() . "@", $targetParent . "/", $source->getPath());
            $target = Asset::getByPath($targetPath);
        } else {
            $target = Asset::getById($targetId);
        }

        if ($target->isAllowed("create")) {
            $source = Asset::getById($sourceId);
            if ($source != null) {
                if ($this->getParam("type") == "child") {
                    $newAsset = $this->_assetService->copyAsChild($target, $source);

                    // this is because the key can get the prefix "_copy" if the target does already exists
                    if ($this->getParam("saveParentId")) {
                        $session->{$this->getParam("transactionId")}["parentId"] = $newAsset->getId();
                    }
                } else if ($this->getParam("type") == "replace") {
                    $this->_assetService->copyContents($target, $source);
                }

                $success = true;
            } else {
                \Logger::debug("prevended copy/paste because asset with same path+key already exists in this location");
            }
        } else {
            \Logger::error("could not execute copy/paste because of missing permissions on target [ " . $targetId . " ]");
            $this->_helper->json(array("error" => false, "message" => "missing_permission"));
        }

        Tool\Session::writeClose();

        $this->_helper->json(array("success" => $success));
    }


    public function downloadAsZipJobsAction()
    {

        $jobId = uniqid();
        $filesPerJob = 5;
        $jobs = array();
        $asset = Asset::getById($this->getParam("id"));

        if ($asset->isAllowed("view")) {
            $parentPath = $asset->getFullPath();
            if ($asset->getId() == 1) {
                $parentPath = "";
            }

            $assetList = new Asset\Listing();
            $assetList->setCondition("path LIKE ? AND type != ?", array($parentPath . "/%", "folder"));
            $assetList->setOrderKey("LENGTH(path)", false);
            $assetList->setOrder("ASC");

            for ($i = 0; $i < ceil($assetList->getTotalCount() / $filesPerJob); $i++) {
                $jobs[] = array(array(
                    "url" => "/admin/asset/download-as-zip-add-files",
                    "params" => array(
                        "id" => $asset->getId(),
                        "offset" => $i * $filesPerJob,
                        "limit" => $filesPerJob,
                        "jobId" => $jobId
                    )
                ));
            }
        }

        $this->_helper->json(array(
            "success" => true,
            "jobs" => $jobs,
            "jobId" => $jobId
        ));
    }


    public function downloadAsZipAddFilesAction()
    {

        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/download-zip-" . $this->getParam("jobId") . ".zip";
        $asset = Asset::getById($this->getParam("id"));
        $success = false;

        if ($asset->isAllowed("view")) {

            $zip = new \ZipArchive();
            if (!is_file($zipFile)) {
                $zipState = $zip->open($zipFile, \ZipArchive::CREATE);
            } else {
                $zipState = $zip->open($zipFile);
            }

            if ($zipState === TRUE) {

                $parentPath = $asset->getFullPath();
                if ($asset->getId() == 1) {
                    $parentPath = "";
                }

                $assetList = new Asset\Listing();
                $assetList->setCondition("path LIKE ?", $parentPath . "/%");
                $assetList->setOrderKey("LENGTH(path)", false);
                $assetList->setOrder("ASC");
                $assetList->setOffset((int)$this->getParam("offset"));
                $assetList->setLimit((int)$this->getParam("limit"));

                foreach ($assetList->load() as $a) {
                    if ($a->isAllowed("view")) {
                        if (!$a instanceof Asset\Folder) {
                            // add the file with the relative path to the parent directory
                            $zip->addFile($a->getFileSystemPath(), preg_replace("@^" . preg_quote($asset->getPath(), "@") . "@i", "", $a->getFullPath()));
                        }
                    }
                }

                $zip->close();
                $success = true;
            }

        }

        $this->_helper->json(array(
            "success" => $success
        ));
    }

    /**
     * Download all assets contained in the folder with parameter id as ZIP file.
     * The suggested filename is either [folder name].zip or assets.zip for the root folder.
     */
    public function downloadAsZipAction()
    {

        $asset = Asset::getById($this->getParam("id"));
        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/download-zip-" . $this->getParam("jobId") . ".zip";
        $suggestedFilename = $asset->getFilename();
        if (empty($suggestedFilename)) {
            $suggestedFilename = "assets";
        }

        header("Content-Type: application/zip");
        header("Content-Length: " . filesize($zipFile));
        header('Content-Disposition: attachment; filename="' . $suggestedFilename . '.zip"');

        while (@ob_end_flush()) ;
        flush();

        readfile($zipFile);
        unlink($zipFile);

        exit;
    }

    public function importZipAction()
    {

        $jobId = uniqid();
        $filesPerJob = 5;
        $jobs = array();
        $asset = Asset::getById($this->getParam("parentId"));
        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $jobId . ".zip";

        copy($_FILES["Filedata"]["tmp_name"], $zipFile);

        $zip = new \ZipArchive;
        if ($zip->open($zipFile) === true) {
            $jobAmount = ceil($zip->numFiles / $filesPerJob);
            for ($i = 0; $i < $jobAmount; $i++) {
                $jobs[] = array(array(
                    "url" => "/admin/asset/import-zip-files",
                    "params" => array(
                        "parentId" => $asset->getId(),
                        "offset" => $i * $filesPerJob,
                        "limit" => $filesPerJob,
                        "jobId" => $jobId,
                        "last" => (($i + 1) >= $jobAmount) ? "true" : ""
                    )
                ));
            }
            $zip->close();
        }

        // here we have to use this method and not the JSON action helper ($this->_helper->json()) because this will add
        // Content-Type: application/json which fires a download window in most browsers, because this is a normal POST
        // request and not XHR where the content-type doesn't matter
        $this->disableViewAutoRender();
        echo \Zend_Json::encode(array(
            "success" => true,
            "jobs" => $jobs,
            "jobId" => $jobId
        ));
    }

    public function importZipFilesAction()
    {
        $jobId = $this->getParam("jobId");
        $limit = (int)$this->getParam("limit");
        $offset = (int)$this->getParam("offset");
        $importAsset = Asset::getById($this->getParam("parentId"));
        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $jobId . ".zip";
        $tmpDir = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/zip-import";

        if (!is_dir($tmpDir)) {
            File::mkdir($tmpDir, 0777, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipFile) === true) {
            for ($i = $offset; $i < ($offset + $limit); $i++) {
                $path = $zip->getNameIndex($i);

                if ($path !== false) {
                    if ($zip->extractTo($tmpDir . "/", $path)) {

                        $tmpFile = $tmpDir . "/" . preg_replace("@^/@", "", $path);

                        $filename = File::getValidFilename(basename($path));

                        $relativePath = "";
                        if (dirname($path) != ".") {
                            $relativePath = dirname($path);
                        }

                        $parentPath = $importAsset->getFullPath() . "/" . preg_replace("@^/@", "", $relativePath);
                        $parent = Asset\Service::createFolderByPath($parentPath);

                        // check for duplicate filename
                        $filename = $this->getSafeFilename($parent->getFullPath(), $filename);

                        if ($parent->isAllowed("create")) {

                            $asset = Asset::create($parent->getId(), array(
                                "filename" => $filename,
                                "sourcePath" => $tmpFile,
                                "userOwner" => $this->user->getId(),
                                "userModification" => $this->user->getId()
                            ));

                            @unlink($tmpFile);
                        } else {
                            \Logger::debug("prevented creating asset because of missing permissions");
                        }
                    }
                }
            }
            $zip->close();
        }

        if ($this->getParam("last")) {
            unlink($zipFile);
        }

        $this->_helper->json(array(
            "success" => true
        ));
    }

    public function importServerAction()
    {

        $success = true;
        $filesPerJob = 5;
        $jobs = array();
        $importDirectory = str_replace("/fileexplorer", PIMCORE_DOCUMENT_ROOT, $this->getParam("serverPath"));
        if (is_dir($importDirectory)) {

            $files = rscandir($importDirectory . "/");
            $count = count($files);
            $jobFiles = array();

            for ($i = 0; $i < $count; $i++) {

                if (is_dir($files[$i])) continue;

                $jobFiles[] = preg_replace("@^" . preg_quote($importDirectory, "@") . "@", "", $files[$i]);

                if (count($jobFiles) >= $filesPerJob || $i >= ($count - 1)) {
                    $jobs[] = array(array(
                        "url" => "/admin/asset/import-server-files",
                        "params" => array(
                            "parentId" => $this->getParam("parentId"),
                            "serverPath" => $importDirectory,
                            "files" => implode("::", $jobFiles)
                        )
                    ));
                    $jobFiles = array();
                }
            }
        }

        $this->_helper->json(array(
            "success" => $success,
            "jobs" => $jobs
        ));
    }

    public function importServerFilesAction()
    {
        $assetFolder = Asset::getById($this->getParam("parentId"));
        $serverPath = $this->getParam("serverPath");
        $files = explode("::", $this->getParam("files"));

        foreach ($files as $file) {
            $absolutePath = $serverPath . $file;
            if (is_file($absolutePath)) {
                $relFolderPath = str_replace('\\', '/', dirname($file));
                $folder = Asset\Service::createFolderByPath($assetFolder->getFullPath() . $relFolderPath);
                $filename = basename($file);

                // check for duplicate filename
                $filename = File::getValidFilename($filename);
                $filename = $this->getSafeFilename($folder->getFullPath(), $filename);

                if ($assetFolder->isAllowed("create")) {
                    $asset = Asset::create($folder->getId(), array(
                        "filename" => $filename,
                        "sourcePath" => $absolutePath,
                        "userOwner" => $this->getUser()->getId(),
                        "userModification" => $this->getUser()->getId()
                    ));
                } else {
                    \Logger::debug("prevented creating asset because of missing permissions ");
                }
            }
        }

        $this->_helper->json(array(
            "success" => true
        ));
    }

    public function importUrlAction()
    {
        $success = true;

        $data = Tool::getHttpData($this->getParam("url"));
        $filename = basename($this->getParam("url"));
        $parentId = $this->getParam("id");
        $parentAsset = Asset::getById(intval($parentId));

        $filename = File::getValidFilename($filename);
        $filename = $this->getSafeFilename($parentAsset->getFullPath(), $filename);

        if (empty($filename)) {
            throw new \Exception("The filename of the asset is empty");
        }

        // check for duplicate filename
        $filename = $this->getSafeFilename($parentAsset->getFullPath(), $filename);

        if ($parentAsset->isAllowed("create")) {
            $asset = Asset::create($parentId, array(
                "filename" => $filename,
                "data" => $data,
                "userOwner" => $this->user->getId(),
                "userModification" => $this->user->getId()
            ));
            $success = true;
        } else {
            \Logger::debug("prevented creating asset because of missing permissions");
        }

        $this->_helper->json(array("success" => $success));
    }

    public function clearThumbnailAction()
    {

        $success = false;

        if ($asset = Asset::getById($this->getParam("id"))) {
            if (method_exists($asset, "clearThumbnails")) {
                $asset->clearThumbnails(true); // force clear
                $asset->save();

                $success = true;
            }
        }

        $this->_helper->json(array("success" => $success));
    }

    public function gridProxyAction() {

        if ($this->getParam("data")) {
            if ($this->getParam("xaction") == "update") {
                //TODO probably not needed
            }
        } else {
            // get list of objects
            $folder = Asset::getById($this->getParam("folderId"));


            $start = 0;
            $limit = 20;
            $orderKey = "id";
            $order = "ASC";


            if ($this->getParam("limit")) {
                $limit = $this->getParam("limit");
            }
            if ($this->getParam("start")) {
                $start = $this->getParam("start");
            }

            if ($this->getParam("dir")) {
                $order = $this->getParam("dir");
            }

            if ($this->getParam("sort")) {
                $orderKey = $this->getParam("sort");
                if ($orderKey == "fullpath") {
                    $orderKey = array("path" , "filename");
                }
            }

            $conditionFilters = array();
            if($this->getParam("only_direct_children") == "true") {
                $conditionFilters[] = "parentId = " . $folder->getId();
            } else {
                $conditionFilters[] = "path LIKE '" . $folder->getFullPath() . "/%'";
            }

            $conditionFilters[] = "type != 'folder'";
            $filterJson = $this->getParam("filter");
            if ($filterJson) {


                $filters = \Zend_Json::decode($filterJson);
                foreach ($filters as $filter) {

                    $operator = "=";

                    if($filter["type"] == "string") {
                        $operator = "LIKE";
                    } else if ($filter["type"] == "numeric") {
                        if($filter["comparison"] == "lt") {
                            $operator = "<";
                        } else if($filter["comparison"] == "gt") {
                            $operator = ">";
                        } else if($filter["comparison"] == "eq") {
                            $operator = "=";
                        }
                    } else if ($filter["type"] == "date") {
                        if($filter["comparison"] == "lt") {
                            $operator = "<";
                        } else if($filter["comparison"] == "gt") {
                            $operator = ">";
                        } else if($filter["comparison"] == "eq") {
                            $operator = "=";
                        }
                        $filter["value"] = strtotime($filter["value"]);
                    } else if ($filter["type"] == "list") {
                        $operator = "=";
                    } else if ($filter["type"] == "boolean") {
                        $operator = "=";
                        $filter["value"] = (int) $filter["value"];
                    }
                    // system field
                    $value = $filter["value"];
                    if ($operator == "LIKE") {
                        $value = "%" . $value . "%";
                    }

                    $field = "`" . $filter["field"] . "` ";
                    if($filter["field"] == "fullpath") {
                        $field = "CONCAT(path,filename)";
                    }

                    $conditionFilters[] =  $field . $operator . " '" . $value . "' ";
                }
            }

            $list = new Asset\Listing();
            $condition = implode(" AND ", $conditionFilters);
            $list->setCondition($condition);
            $list->setLimit($limit);
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            $list->load();

            $assets = array();
            foreach ($list->getAssets() as $asset) {

                /** @var $asset Asset */
                $filename = PIMCORE_ASSET_DIRECTORY . "/" . $asset->getFullPath();
                $size = filesize($filename);

                $assets[] = array(
                    "id" => $asset->getid(),
                    "type" => $asset->getType(),
                    "fullpath" => $asset->getFullPath(),
                    "creationDate" => $asset->getCreationDate(),
                    "modificationDate" => $asset->getModificationDate(),
                    "size" => formatBytes($size),
                    "idPath" => $data["idPath"] = Element\Service::getIdPath($asset)
                );
            }

            $this->_helper->json(array("data" => $assets, "success" => true, "total" => $list->getTotalCount()));
        }
    }

    public function getTextAction(){
        $asset = Asset::getById($this->getParam('id'));
        $page = $this->getParam('page');
        if($asset instanceof Asset\Document){
            $text = $asset->getText($page);
        }
        $this->_helper->json(array('success' => 'true','text' => $text));
    }
}