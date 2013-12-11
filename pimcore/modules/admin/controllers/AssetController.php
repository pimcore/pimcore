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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Admin_AssetController extends Pimcore_Controller_Action_Admin {

    /**
     * @var Asset_Service
     */
    protected $_assetService;

    public function init() {
        parent::init();

        // check permissions
        $notRestrictedActions = array("get-image-thumbnail", "get-video-thumbnail", "get-document-thumbnail");
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("assets");
        }

        $this->_assetService = new Asset_Service($this->getUser());
    }

    public function getDataByIdAction() {

        // check for lock
        if (Element_Editlock::isLocked($this->getParam("id"), "asset")) {
            $this->_helper->json(array(
                "editlock" => Element_Editlock::getByElement($this->getParam("id"), "asset")
            ));
        }
        Element_Editlock::lock($this->getParam("id"), "asset");

        $asset = Asset::getById(intval($this->getParam("id")));

        if(!$asset instanceof Asset) {
            $this->_helper->json(array("success" => false, "message" => "asset doesn't exist"));
        }

        $asset->setProperties(Element_Service::minimizePropertiesForEditmode($asset->getProperties()));
        $asset->getVersions();
        $asset->getScheduledTasks();
        $asset->idPath = Element_Service::getIdPath($asset);
        $asset->userPermissions = $asset->getUserPermissions();
        $asset->setLocked($asset->isLocked());

        if ($asset instanceof Asset_Text) {
            $asset->data = $asset->getData();
        }

        if ($asset instanceof Asset_Image) {
            $imageInfo = array();

            if($asset->getWidth() && $asset->getHeight()) {
                $imageInfo["dimensions"] = array();
                $imageInfo["dimensions"]["width"] = $asset->getWidth();
                $imageInfo["dimensions"]["height"] = $asset->getHeight();
            }

            if(function_exists("exif_read_data") && is_file($asset->getFileSystemPath())) {
                $supportedTypes = array(IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM);

                if(in_array(exif_imagetype($asset->getFileSystemPath()),$supportedTypes)) {
                    $exif = @exif_read_data($asset->getFileSystemPath());
                    if(is_array($exif)) {
                        $imageInfo["exif"] = array();
                        foreach($exif as $name => $value) {
                            if((is_string($value) && strlen($value) < 50) || is_numeric($value)) {
                                // this is to ensure that the data can be converted to json (must be utf8)
                                if(mb_check_encoding($value, "UTF-8")) {
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


    public function getRequiresDependenciesAction() {
        $id = $this->getParam("id");
        $asset = Asset::getById($id);
        if ($asset instanceof Asset) {
            $dependencies = Element_Service::getRequiresDependenciesForFrontend($asset->getDependencies());
            $this->_helper->json($dependencies);
        }
        $this->_helper->json(false);
    }

    public function getRequiredByDependenciesAction() {
        $id = $this->getParam("id");
        $asset = Asset::getById($id);
        if ($asset instanceof Asset) {
            $dependencies = Element_Service::getRequiredByDependenciesForFrontend($asset->getDependencies());
            $this->_helper->json($dependencies);
        }
        $this->_helper->json(false);
    }

    public function treeGetRootAction() {

        $id = 1;
        if ($this->getParam("id")) {
            $id = intval($this->getParam("id"));
        }

        $root = Asset::getById($id);
        if ($root->isAllowed("list")) {
            $this->_helper->json($this->getTreeNodeConfig($root));
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }

    public function treeGetChildsByIdAction() {

        $assets = array();
        $asset = Asset::getById($this->getParam("node"));

        if ($asset->hasChilds()) {

            $limit = intval($this->getParam("limit"));
            if (!$this->getParam("limit")) {
                $limit = 100000000;
            }
            $offset = intval($this->getParam("start"));


            // get assets
            $childsList = new Asset_List();
            $childsList->setCondition("parentId = ?", $asset->getId());
            $childsList->setLimit($limit);
            $childsList->setOffset($offset);
            $childsList->setOrderKey("filename");
            $childsList->setOrder("asc");

            $childs = $childsList->load();

            foreach ($childs as $childAsset) {
                if ($childAsset->isAllowed("list")) {
                    $assets[] = $this->getTreeNodeConfig($childAsset);
                }
            }
        }


        if ($this->getParam("limit")) {
            $this->_helper->json(array(
                "total" => $asset->getChildAmount(),
                "nodes" => $assets
            ));
        }
        else {
            $this->_helper->json($assets);
        }

        $this->_helper->json(false);
    }

    public function getPredefinedPropertiesAction() {

        $list = new Property_Predefined_List();
        $list->setCondition("ctype = 'asset'");
        $list->load();

        $properties = array();
        foreach ($list->getProperties() as $type) {
            $properties[] = $type;
        }

        $this->_helper->json(array("properties" => $properties));
    }

    public function addAssetAction() {
        $res = $this->addAsset();
        $this->_helper->json(array("success" => $res["success"], "msg" => "Success"));
    }

    public function addAssetCompatibilityAction() {
        // this is a special action for the compatibility mode upload (without flash)
        $res = $this->addAsset();

        // here we have to use this method and not the JSON action helper ($this->_helper->json()) because this will add
        // Content-Type: application/json which fires a download window in most browsers, because this is a normal POST
        // request and not XHR where the content-type doesn't matter
        $this->disableViewAutoRender();
        echo Zend_Json::encode(array(
            "success" => $res["success"],
            "msg" => "Success",
            "id" => $res["asset"] ? $res["asset"]->getId() : null,
            "fullpath" => $res["asset"] ? $res["asset"]->getFullPath() : null,
            "type" => $res["asset"] ? $res["asset"]->getType() : null
        ));
    }

    protected function addAsset () {
        $success = false;

        if(array_key_exists("Filedata", $_FILES)) {
            $filename = $_FILES["Filedata"]["name"];
            $sourcePath = $_FILES["Filedata"]["tmp_name"];
        } else if($this->getParam("type") == "base64") {
            $filename = $this->getParam("filename");
            $sourcePath = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/upload-base64" . uniqid() . ".tmp";
            $data = preg_replace("@^data:[^,]+;base64,@", "", $this->getParam("data"));
            Pimcore_File::put($sourcePath, base64_decode($data));
        }

        if(!$this->getParam("parentId") && $this->getParam("parentPath")) {
            $parent = Asset::getByPath($this->getParam("parentPath"));
            if($parent instanceof Asset_Folder) {
                $this->setParam("parentId", $parent->getId());
            } else {
                $this->setParam("parentId", 1);
            }
        } else if (!$this->getParam("parentId")) {
            // set the parent to the root folder
            $this->setParam("parentId", 1);
        }

        $filename = Pimcore_File::getValidFilename($filename);
        if(empty($filename)) {
            throw new Exception("The filename of the asset is empty");
        }

        $parentAsset = Asset::getById(intval($this->getParam("parentId")));

        // check for duplicate filename
        $filename = $this->getSafeFilename($parentAsset->getFullPath(), $filename);

        if ($parentAsset->isAllowed("create")) {

            $asset = Asset::create($this->getParam("parentId"), array(
                "filename" => $filename,
                "sourcePath" => $sourcePath,
                "userOwner" => $this->user->getId(),
                "userModification" => $this->user->getId()
            ));
            $success = true;
        }
        else {
            Logger::debug("prevented creating asset because of missing permissions");
        }

        return array(
            "success" => $success,
            "asset" => $asset
        );
    }

    protected function getSafeFilename($targetPath, $filename) {

        $originalFilename = $filename;
        $count = 1;

        if ($targetPath == "/") {
            $targetPath = "";
        }

        while (true) {
            if (Asset_Service::pathExists($targetPath . "/" . $filename)) {
                $filename = str_replace("." . Pimcore_File::getFileExtension($originalFilename), "_" . $count . "." . Pimcore_File::getFileExtension($originalFilename), $originalFilename);
                $count++;
            }
            else {
                return $filename;
            }
        }
    }

    public function replaceAssetAction() {
        $asset = Asset::getById($this->getParam("id"));

        $stream = fopen($_FILES["Filedata"]["tmp_name"], "r+");
        $asset->setStream($stream);
        $asset->setCustomSetting("thumbnails",null);
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
            throw new Exception("missing permission");
        }
    }

    public function addFolderAction() {

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
        }
        else {
            Logger::debug("prevented creating asset because of missing permissions");
        }

        $this->_helper->json(array("success" => $success));
    }

    public function deleteAction()
    {
        if ($this->getParam("type") == "childs") {

            $parentAsset = Asset::getById($this->getParam("id"));

            $list = new Asset_List();
            $list->setCondition("path LIKE '" . $parentAsset->getFullPath() . "/%'");
            $list->setLimit(intval($this->getParam("amount")));
            $list->setOrderKey("LENGTH(path)", false);
            $list->setOrder("DESC");

            $assets = $list->load();

            $deletedItems = array();
            foreach ($assets as $asset) {
                $deletedItems[] = $asset->getFullPath();
                $asset->delete();
            }

            $this->_helper->json(array("success" => true, "deleted" => $deletedItems));

        } else if($this->getParam("id")) {
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

        try {
            $asset = Asset::getById($this->getParam("id"));
            $hasDependency = $asset->getDependencies()->isRequired();
        }
        catch (Exception $e) {
            Logger::err("failed to access asset with id: " . $this->getParam("id"));
        }

        $deleteJobs = array();

        // check for childs
        if($asset instanceof Asset) {

            $deleteJobs[] = array(array(
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
            if($hasChilds) {
                // get amount of childs
                $list = new Asset_List();
                $list->setCondition("path LIKE '" . $asset->getFullPath() . "/%'");
                $childs = $list->getTotalCount();

                if($childs > 0) {
                    $deleteObjectsPerRequest = 5;
                    for($i=0; $i<ceil($childs/$deleteObjectsPerRequest); $i++) {
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

            // the object itself is the last one
            $deleteJobs[] = array(array(
                "url" => "/admin/asset/delete",
                "params" => array(
                    "id" => $asset->getId()
                )
            ));
        }

        $this->_helper->json(array(
            "hasDependencies" => $hasDependency,
            "childs" => $childs,
            "deletejobs" => $deleteJobs
        ));
    }

    /**
     * @param Asset $asset
     * @return array|string
     */
    protected function getTreeNodeConfig($asset) {
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
            foreach($asset->getChilds() as $child) {
                if($thumbnailUrl = $this->getThumbnailUrl($child)) {
                    $folderThumbs[] = $thumbnailUrl;
                }
            }

            if(!empty($folderThumbs)) {
                if(count($folderThumbs) > 35) {
                    $folderThumbs = array_splice($folderThumbs, 0, 35);
                }
                $tmpAsset["thumbnails"] = $folderThumbs;
            }
        }
        else {
            $tmpAsset["leaf"] = true;
            $tmpAsset["iconCls"] = "pimcore_icon_" . Pimcore_File::getFileExtension($asset->getFilename());
        }

        $tmpAsset["qtipCfg"] = array(
            "title" => "ID: " . $asset->getId()
        );

        if ($asset->getType() == "image") {
            try {
                $tmpAsset["thumbnail"] = $this->getThumbnailUrl($asset);

                // this is for backward-compatibility, to calculate the dimensions if they are not there
                if(!$asset->getCustomSetting("imageDimensionsCalculated")) {
                    $asset->save();
                }

                // we need the dimensions for the wysiwyg editors, so that they can resize the image immediately
                if($asset->getCustomSetting("imageWidth") && $asset->getCustomSetting("imageHeight")) {
                    $tmpAsset["imageWidth"] = $asset->getCustomSetting("imageWidth");
                    $tmpAsset["imageHeight"] = $asset->getCustomSetting("imageHeight");
                }

            } catch (Exception $e) {
                Logger::debug("Cannot get dimensions of image, seems to be broken.");
            }
        } else if ($asset->getType() == "video") {
            try {
                if(Pimcore_Video::isAvailable()) {
                    $tmpAsset["thumbnail"] = $this->getThumbnailUrl($asset);
                }
            } catch (Exception $e) {
                Logger::debug("Cannot get dimensions of video, seems to be broken.");
            }
        } else if ($asset->getType() == "document") {
            try {
                // add the PDF check here, otherwise the preview layer in admin is shown without content
                if(Pimcore_Document::isAvailable() && Pimcore_Document::isFileTypeSupported($asset->getFilename())) {
                    $tmpAsset["thumbnail"] = $this->getThumbnailUrl($asset);
                }
            } catch (Exception $e) {
                Logger::debug("Cannot get dimensions of video, seems to be broken.");
            }
        }

        $tmpAsset["cls"] = "";
        if($asset->isLocked()) {
            $tmpAsset["cls"] .= "pimcore_treenode_locked ";
        }
        if($asset->getLocked()) {
            $tmpAsset["cls"] .= "pimcore_treenode_lockOwner ";
        }

        return $tmpAsset;
    }

    protected function getThumbnailUrl($asset) {
        if($asset instanceof Asset_Image) {
            return "/admin/asset/get-image-thumbnail/id/" . $asset->getId() . "/treepreview/true";
        } else if ($asset instanceof Asset_Video && Pimcore_Video::isAvailable()) {
            return "/admin/asset/get-video-thumbnail/id/" . $asset->getId() . "/treepreview/true";
        } else if ($asset instanceof Asset_Document && Pimcore_Document::isAvailable()) {
            return "/admin/asset/get-document-thumbnail/id/" . $asset->getId() . "/treepreview/true";
        }
        return null;
    }

    public function updateAction() {

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

                    if(!$parentAsset->isAllowed("create")){
                        throw new Exception("Prevented moving asset - no create permission on new parent ");
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
                }
            }

            if ($allowUpdate) {
                if ($this->getParam("filename") || $this->getParam("parentId")) {
                    $asset->getData();
                }

                if($this->getParam("filename") != $asset->getFilename() and !$asset->isAllowed("rename")){
                    unset($updateData["filename"]);
                    Logger::debug("prevented renaming asset because of missing permissions ");
                }

                $asset->setValues($updateData);


                try {
                    $asset->save();
                    $success = true;
                } catch (Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }


            }
            else {
                Logger::debug("prevented moving asset, asset with same path+key already exists at target location ");
                $this->_helper->json(array("success" => $success, "message" => "the_filename_is_already_in_use"));
            }
        } else if ($asset->isAllowed("rename") &&  $this->getParam("filename")  ) {
            //just rename
            try {
                $asset->setFilename($this->getParam("filename"));
                $asset->save();
                $success = true;
            } catch (Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
            }
        } else {
            Logger::debug("prevented update asset because of missing permissions ");
        }

        $this->_helper->json(array("success" => $success));
    }


    public function webdavAction() {

        $homeDir = Asset::getById(1);

        try {
            $publicDir = new Asset_WebDAV_Folder($homeDir);
            $objectTree = new Asset_WebDAV_Tree($publicDir);
            $server = new Sabre_DAV_Server($objectTree);

            $lockBackend = new Sabre_DAV_Locks_Backend_File(PIMCORE_WEBDAV_TEMP . '/locks.dat');
            $lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);
            $server->addPlugin($lockPlugin);

            //$plugin = new Sabre_DAV_Browser_Plugin();
            //$server->addPlugin($plugin);

            $server->exec();
        } catch (Exception $e) {
            Logger::error($e);
        }

        exit;
    }


    public function saveAction() {
        $success = false;
        if ($this->getParam("id")) {
            $asset = Asset::getById($this->getParam("id"));
            if ($asset->isAllowed("publish")) {


                // properties
                if ($this->getParam("properties")) {
                    $properties = array();
                    $propertiesData = Zend_Json::decode($this->getParam("properties"));

                    if (is_array($propertiesData)) {
                        foreach ($propertiesData as $propertyName => $propertyData) {

                            $value = $propertyData["data"];

                            try {
                                $property = new Property();
                                $property->setType($propertyData["type"]);
                                $property->setName($propertyName);
                                $property->setCtype("asset");
                                $property->setDataFromEditmode($value);
                                $property->setInheritable($propertyData["inheritable"]);

                                $properties[$propertyName] = $property;
                            }
                            catch (Exception $e) {
                                Logger::err("Can't add " . $propertyName . " to asset " . $asset->getFullPath());
                            }
                        }

                        $asset->setProperties($properties);
                    }
                }

                // scheduled tasks
                if ($this->getParam("scheduler")) {
                    $tasks = array();
                    $tasksData = Zend_Json::decode($this->getParam("scheduler"));

                    if (!empty($tasksData)) {
                        foreach ($tasksData as $taskData) {
                            $taskData["date"] = strtotime($taskData["date"] . " " . $taskData["time"]);

                            $task = new Schedule_Task($taskData);
                            $tasks[] = $task;
                        }
                    }

                    $asset->setScheduledTasks($tasks);
                }

                if ($this->getParam("data")) {
                    $asset->setData($this->getParam("data"));
                }

                $asset->setUserModification($this->getUser()->getId());


                try {
                    $asset->save();
                    $asset->getData();
                    $success = true;
                } catch (Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }
            }
            else {
                Logger::debug("prevented save asset because of missing permissions ");
            }

            $this->_helper->json(array("success" => $success));
        }

        $this->_helper->json(false);
    }

    public function deleteVersionAction() {
        $version = Version::getById($this->getParam("id"));
        $version->delete();

        $this->_helper->json(array("success" => true));
    }

    public function publishVersionAction() {

        $version = Version::getById($this->getParam("id"));
        $asset = $version->loadData();

        $currentAsset = Asset::getById($asset->getId());
        if ($currentAsset->isAllowed("publish")) {
            try {
                $asset->setUserModification($this->getUser()->getId());
                $asset->save();
                $this->_helper->json(array("success" => true));
            } catch (Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
            }


        }

        $this->_helper->json(false);
    }

    public function showVersionAction() {

        $version = Version::getById($this->getParam("id"));
        $asset = $version->loadData();

        $this->view->asset = $asset;

        $this->render("show-version-" . $asset->getType());
    }

    public function getVersionsAction() {
        if ($this->getParam("id")) {
            $asset = Asset::getById($this->getParam("id"));
            $versions = $asset->getVersions();

            $this->_helper->json(array("versions" => $versions));
        }
    }

    public function downloadAction() {
        $asset = Asset::getById($this->getParam("id"));

        if ($asset->isAllowed("view")) {
            header("Content-Type: " . $asset->getMimetype(), true);
            header('Content-Disposition: attachment; filename="' . $asset->getFilename() . '"');
            header("Content-Length: " . filesize($asset->getFileSystemPath()), true);

            while(@ob_end_flush());
            flush();

            readfile($asset->getFileSystemPath());
            exit;
        }

        $this->removeViewRenderer();
    }

    public function getImageThumbnailAction() {

        $image = Asset_Image::getById(intval($this->getParam("id")));
        $thumbnail = null;

        if ($this->getParam("thumbnail")) {
            $thumbnail = $image->getThumbnailConfig($this->getParam("thumbnail"));
        }
        if (!$thumbnail) {
            if($this->getParam("config")) {
                $thumbnail = $image->getThumbnailConfig(Zend_Json::decode($this->getParam("config")));
            } else {
                $thumbnail = $image->getThumbnailConfig($this->getAllParams());
            }
        }

        $format = strtolower($thumbnail->getFormat());
        if ($format == "source" || $format == "print") {
            $thumbnail->setFormat("PNG");
            $format = "png";
        }

        if($this->getParam("treepreview")) {
            $thumbnail = Asset_Image_Thumbnail_Config::getPreviewConfig();
        }

        if ($this->getParam("cropPercent")) {
            $thumbnail->addItemAt(0,"cropPercent", array(
                "width" => $this->getParam("cropWidth"),
                "height" => $this->getParam("cropHeight"),
                "y" => $this->getParam("cropTop"),
                "x" => $this->getParam("cropLeft")
            ));

            $hash = md5(Pimcore_Tool_Serialize::serialize($this->getAllParams()));
            $thumbnail->setName("auto_" . $hash);
        }

        if($this->getParam("download")) {
            header('Content-Disposition: attachment; filename="' . $image->getFilename() . '"');
        }

        $thumbnailFile = PIMCORE_DOCUMENT_ROOT . $image->getThumbnail($thumbnail);
        $fileExtension = Pimcore_File::getFileExtension($thumbnailFile);
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

    public function getVideoThumbnailAction() {

        if($this->getParam("id")) {
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

        if($this->getParam("treepreview")) {
            $thumbnail = Asset_Image_Thumbnail_Config::getPreviewConfig();
        }

        $time = null;
        if($this->getParam("time")) {
            $time = intval($this->getParam("time"));
        }

        if($this->getParam("settime")) {
            $video->removeCustomSetting("image_thumbnail_asset");
            $video->setCustomSetting("image_thumbnail_time", $time);
            $video->save();
        }

        $image = null;
        if($this->getParam("image")) {
            $image = Asset::getById(intval($this->getParam("image")));
        }

        if($this->getParam("setimage") && $image) {
            $video->removeCustomSetting("image_thumbnail_time");
            $video->setCustomSetting("image_thumbnail_asset", $image->getId());
            $video->save();
        }

        $thumbnailFile = PIMCORE_DOCUMENT_ROOT . $video->getImageThumbnail($thumbnail, $time, $image);

        header("Content-type: image/" . $format, true);
        header("Content-Length: " . filesize($thumbnailFile), true);
        $this->sendThumbnailCacheHeaders();

        while(@ob_end_flush());
        flush();

        readfile($thumbnailFile);
        exit;
    }

    public function getDocumentThumbnailAction() {

        $document = Asset::getById(intval($this->getParam("id")));
        $thumbnail = Asset_Image_Thumbnail_Config::getByAutoDetect($this->getAllParams());

        $format = strtolower($thumbnail->getFormat());
        if ($format == "source") {
            $thumbnail->setFormat("jpeg"); // default format for documents is JPEG not PNG (=too big)
        }

        if($this->getParam("treepreview")) {
            $thumbnail = Asset_Image_Thumbnail_Config::getPreviewConfig();
        }

        $page = 1;
        if(is_numeric($this->getParam("page"))) {
            $page = (int) $this->getParam("page");
        }


        $thumbnailFile = PIMCORE_DOCUMENT_ROOT . $document->getImageThumbnail($thumbnail, $page);

        $format = "png";
        header("Content-type: image/" . $format, true);
        header("Content-Length: " . filesize($thumbnailFile), true);
        $this->sendThumbnailCacheHeaders();

        while(@ob_end_flush());
        flush();

        readfile($thumbnailFile);
        exit;
    }

    protected function sendThumbnailCacheHeaders() {
        $this->getResponse()->clearAllHeaders();

        $lifetime = 300;
        header("Cache-Control: public, max-age=" . $lifetime, true);
        header("Expires: " . Zend_Date::now()->add($lifetime)->get(Zend_Date::RFC_1123), true);
        header("Pragma: ");
    }

    public function getPreviewDocumentAction() {
        $asset = Asset::getById($this->getParam("id"));
        $this->view->asset = $asset;
    }


    public function getPreviewVideoAction() {

        $asset = Asset::getById($this->getParam("id"));

        $this->view->asset = $asset;

        $config = Asset_Video_Thumbnail_Config::getPreviewConfig();

        $thumbnail = $asset->getThumbnail($config, array("mp4"));

        if ($thumbnail) {
            $this->view->asset = $asset;
            $this->view->thumbnail = $thumbnail;

            if ($thumbnail["status"] == "finished") {
                $this->render("get-preview-video-display");
            } else {
                $this->render("get-preview-video-error");
            }
        }
        else {
            $this->render("get-preview-video-error");
        }
    }


    public function saveImagePixlrAction() {

        $asset = Asset::getById($this->getParam("id"));
        $asset->setData(Pimcore_Tool::getHttpData($this->getParam("image")));
        $asset->setUserModification($this->getUser()->getId());
        $asset->save();

        $this->view->asset = $asset;
    }

    public function getFolderContentPreviewAction() {

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
            if ($asset instanceof Asset_Image) {
                $thumbnailMethod = "getThumbnail";
            } else if ($asset instanceof Asset_Video && Pimcore_Video::isAvailable()) {
                $thumbnailMethod = "getImageThumbnail";
            } else if ($asset instanceof Asset_Document && Pimcore_Document::isAvailable()) {
                $thumbnailMethod = "getImageThumbnail";
            }

            if (!empty($thumbnailMethod)) {
                $assets[] = array(
                    "id" => $asset->getId(),
                    "type" => $asset->getType(),
                    "filename" => $asset->getFilename(),
                    "url" => "/admin/asset/get-" . $asset->getType() . "-thumbnail/id/" . $asset->getId() . "/treepreview/true"
                );
            }
        }


        $this->_helper->json(array(
            "assets" => $assets,
            "success" => true,
            "total" => $list->getTotalCount()
        ));
    }

    public function copyInfoAction() {

        $transactionId = time();
        $pasteJobs = array();

        Pimcore_Tool_Session::useSession(function ($session) use ($transactionId) {
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

            if($asset->hasChilds()) {
                // get amount of childs
                $list = new Asset_List();
                $list->setCondition("path LIKE '" . $asset->getFullPath() . "/%'");
                $list->setOrderKey("LENGTH(path)", false);
                $list->setOrder("ASC");
                $childIds = $list->loadIdList();

                if(count($childIds) > 0) {
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
        }
        else if ($this->getParam("type") == "child" || $this->getParam("type") == "replace") {
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


    public function copyAction() {
        $success = false;
        $sourceId = intval($this->getParam("sourceId"));
        $source = Asset::getById($sourceId);
        $session = Pimcore_Tool_Session::get("pimcore_copy");

        $targetId = intval($this->getParam("targetId"));
        if($this->getParam("targetParentId")) {
            $sourceParent = Asset::getById($this->getParam("sourceParentId"));

            // this is because the key can get the prefix "_copy" if the target does already exists
            if($session->{$this->getParam("transactionId")}["parentId"]) {
                $targetParent = Asset::getById($session->{$this->getParam("transactionId")}["parentId"]);
            } else {
                $targetParent = Asset::getById($this->getParam("targetParentId"));
            }

            $targetPath = preg_replace("@^".$sourceParent->getFullPath()."@", $targetParent."/", $source->getPath());
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
                    if($this->getParam("saveParentId")) {
                        $session->{$this->getParam("transactionId")}["parentId"] = $newAsset->getId();
                    }
                }
                else if ($this->getParam("type") == "replace") {
                    $this->_assetService->copyContents($target, $source);
                }

                $success = true;
            }
            else {
                Logger::debug("prevended copy/paste because asset with same path+key already exists in this location");
            }
        } else {
            Logger::error("could not execute copy/paste because of missing permissions on target [ ".$targetId." ]");
            $this->_helper->json(array("error" => false, "message" => "missing_permission"));
        }

        Pimcore_Tool_Session::writeClose();

        $this->_helper->json(array("success" => $success));
    }


    public function downloadAsZipJobsAction() {

        $jobId = uniqid();
        $filesPerJob = 5;
        $jobs = array();
        $asset = Asset::getById($this->getParam("id"));

        if ($asset->isAllowed("view")) {
            $parentPath = $asset->getFullPath();
            if($asset->getId() == 1) {
                $parentPath = "";
            }

            $assetList = new Asset_List();
            $assetList->setCondition("path LIKE ? AND type != ?", array($parentPath . "/%", "folder"));
            $assetList->setOrderKey("LENGTH(path)", false);
            $assetList->setOrder("ASC");

            for($i=0; $i<ceil($assetList->getTotalCount()/$filesPerJob); $i++) {
                $jobs[] = array(array(
                    "url" => "/admin/asset/download-as-zip-add-files",
                    "params" => array(
                        "id" => $asset->getId(),
                        "offset" => $i*$filesPerJob,
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


    public function downloadAsZipAddFilesAction() {

        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/download-zip-" . $this->getParam("jobId") . ".zip";
        $asset = Asset::getById($this->getParam("id"));
        $success = false;

        if ($asset->isAllowed("view")) {

            $zip = new ZipArchive();
            if(!is_file($zipFile)) {
                $zipState = $zip->open($zipFile, ZipArchive::CREATE);
            } else {
                $zipState = $zip->open($zipFile);
            }

            if ($zipState === TRUE) {

                $parentPath = $asset->getFullPath();
                if($asset->getId() == 1) {
                    $parentPath = "";
                }

                $assetList = new Asset_List();
                $assetList->setCondition("path LIKE ?", $parentPath . "/%");
                $assetList->setOrderKey("LENGTH(path)", false);
                $assetList->setOrder("ASC");
                $assetList->setOffset((int) $this->getParam("offset"));
                $assetList->setLimit((int) $this->getParam("limit"));

                foreach ($assetList->load() as $a) {
                    if($a->isAllowed("view")) {
                        if (!$a instanceof Asset_Folder) {
                            // add the file with the relative path to the parent directory
                            $zip->addFile($a->getFileSystemPath(), preg_replace("@^" . preg_quote($asset->getPath(),"@") . "@i","",$a->getFullPath()));
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
    public function downloadAsZipAction () {

        $asset = Asset::getById($this->getParam("id"));
        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/download-zip-" . $this->getParam("jobId") . ".zip";
        $suggestedFilename = $asset->getFilename();
        if (empty($suggestedFilename)) {
            $suggestedFilename = "assets";
        }

        header("Content-Type: application/zip");
        header("Content-Length: " . filesize($zipFile));
        header('Content-Disposition: attachment; filename="' . $suggestedFilename . '.zip"');

        while(@ob_end_flush());
        flush();

        readfile($zipFile);
        unlink($zipFile);

        exit;
    }

    public function importZipAction() {

        $jobId = uniqid();
        $filesPerJob = 5;
        $jobs = array();
        $asset = Asset::getById($this->getParam("parentId"));
        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $jobId . ".zip";

        copy($_FILES["Filedata"]["tmp_name"], $zipFile);

        $zip = new ZipArchive;
        if ($zip->open($zipFile) === true) {
            $jobAmount = ceil($zip->numFiles/$filesPerJob);
            for($i = 0; $i < $jobAmount; $i++) {
                $jobs[] = array(array(
                    "url" => "/admin/asset/import-zip-files",
                    "params" => array(
                        "parentId" => $asset->getId(),
                        "offset" => $i*$filesPerJob,
                        "limit" => $filesPerJob,
                        "jobId" => $jobId,
                        "last" => (($i+1) >= $jobAmount) ? "true" : ""
                    )
                ));
            }
            $zip->close();
        }

        // here we have to use this method and not the JSON action helper ($this->_helper->json()) because this will add
        // Content-Type: application/json which fires a download window in most browsers, because this is a normal POST
        // request and not XHR where the content-type doesn't matter
        $this->disableViewAutoRender();
        echo Zend_Json::encode(array(
            "success" => true,
            "jobs" => $jobs,
            "jobId" => $jobId
        ));
    }

    public function importZipFilesAction() {
        $jobId = $this->getParam("jobId");
        $limit = (int) $this->getParam("limit");
        $offset = (int) $this->getParam("offset");
        $importAsset = Asset::getById($this->getParam("parentId"));
        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $jobId . ".zip";
        $tmpDir = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/zip-import";

        if(!is_dir($tmpDir)) {
            Pimcore_File::mkdir($tmpDir, 0777, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipFile) === true) {
            for($i = $offset; $i < ($offset+$limit); $i++) {
                $path = $zip->getNameIndex($i);

                if($path !== false) {
                    if($zip->extractTo($tmpDir . "/", $path)) {

                        $tmpFile = $tmpDir . "/" . preg_replace("@^/@", "", $path);

                        $filename = Pimcore_File::getValidFilename(basename($path));

                        $relativePath = "";
                        if(dirname($path) != ".") {
                            $relativePath = dirname($path);
                        }

                        $parentPath = $importAsset->getFullPath() . "/" . preg_replace("@^/@", "", $relativePath);
                        $parent = Asset_Service::createFolderByPath($parentPath);

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
                        }
                        else {
                            Logger::debug("prevented creating asset because of missing permissions");
                        }
                    }
                }
            }
            $zip->close();
        }

        if($this->getParam("last")) {
            unlink($zipFile);
        }

        $this->_helper->json(array(
            "success" => true
        ));
    }

    public function importServerAction() {

        $success = true;
        $filesPerJob = 5;
        $jobs = array();
        $importDirectory = str_replace("/fileexplorer",PIMCORE_DOCUMENT_ROOT, $this->getParam("serverPath"));
        if(is_dir($importDirectory)) {

            $files = rscandir($importDirectory ."/");
            $count = count($files);
            $jobFiles = array();

            for($i = 0; $i < $count; $i++) {

                if(is_dir($files[$i])) continue;

                $jobFiles[] = preg_replace("@^" . preg_quote($importDirectory, "@") . "@", "", $files[$i]);

                if(count($jobFiles) >= $filesPerJob || $i >= ($count-1)) {
                    $jobs[] = array(array(
                        "url" => "/admin/asset/import-server-files",
                        "params" => array(
                            "parentId" => $this->getParam("parentId"),
                            "serverPath" => $importDirectory,
                            "files" => implode("::",$jobFiles)
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

    public function importServerFilesAction() {
        $assetFolder = Asset::getById($this->getParam("parentId"));
        $serverPath = $this->getParam("serverPath");
        $files = explode("::", $this->getParam("files"));

        foreach ($files as $file) {
            $absolutePath = $serverPath . $file;
            if(is_file($absolutePath)) {
                $relFolderPath = str_replace('\\', '/', dirname($file));
                $folder = Asset_Service::createFolderByPath($assetFolder->getFullPath() . $relFolderPath);
                $filename = basename($file);

                // check for duplicate filename
                $filename = Pimcore_File::getValidFilename($filename);
                $filename = $this->getSafeFilename($folder->getFullPath(), $filename);

                if ($assetFolder->isAllowed("create")) {
                    $asset = Asset::create($folder->getId(), array(
                        "filename" => $filename,
                        "sourcePath" => $absolutePath,
                        "userOwner" => $this->getUser()->getId(),
                        "userModification" => $this->getUser()->getId()
                    ));
                }
                else {
                    Logger::debug("prevented creating asset because of missing permissions ");
                }
            }
        }

        $this->_helper->json(array(
            "success" => true
        ));
    }

    public function importUrlAction() {
        $success = true;

        $data = Pimcore_Tool::getHttpData($this->getParam("url"));
        $filename = basename($this->getParam("url"));
        $parentId = $this->getParam("id");
        $parentAsset = Asset::getById(intval($parentId));

        $filename = Pimcore_File::getValidFilename($filename);
        $filename = $this->getSafeFilename($parentAsset->getFullPath(), $filename);

        if(empty($filename)) {
            throw new Exception("The filename of the asset is empty");
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
            Logger::debug("prevented creating asset because of missing permissions");
        }

        $this->_helper->json(array("success" => $success));
    }

    public function clearThumbnailAction () {

        $success = false;

        if($asset = Asset::getById($this->getParam("id"))) {
            if(method_exists($asset, "clearThumbnails")) {
                $asset->clearThumbnails(true); // force clear
                $asset->save();

                $success = true;
            }
        }

        $this->_helper->json(array("success" => $success));
    }
}
