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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
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
        $notRestrictedActions = array("get-image-thumbnail");
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            if (!$this->getUser()->isAllowed("assets")) {

                $this->redirect("/admin/login");
                die();
            }
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
        $asset->idPath = Pimcore_Tool::getIdPathForElement($asset);
        $asset->userPermissions = $asset->getUserPermissions();
        $asset->setLocked($asset->isLocked());

        if ($asset instanceof Asset_Text) {
            $asset->getData();
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

        $filename = Pimcore_File::getValidFilename($_FILES["Filedata"]["name"]);
        if(empty($filename)) {
            throw new Exception("The filename of the asset is empty");
        }

        $parentAsset = Asset::getById(intval($this->getParam("parentId")));

        // check for dublicate filename
        $filename = $this->getSafeFilename($parentAsset->getFullPath(), $filename);

        if ($parentAsset->isAllowed("create")) {

            $asset = Asset::create($this->getParam("parentId"), array(
                "filename" => $filename,
                "data" => file_get_contents($_FILES["Filedata"]["tmp_name"]),
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
        $asset->setData(file_get_contents($_FILES["Filedata"]["tmp_name"]));
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
                $tmpAsset["qtipCfg"] = array(
                    "title" => "ID: " . $asset->getId(),
                    "text" => '<img src="/admin/asset/get-image-thumbnail/id/' . $asset->getId() . '/width/130/aspectratio/true" width="130" />',
                    "width" => 140
                );

                // this is for backward-compatibilty, to calculate the dimensions if they are not there
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
                    $tmpAsset["qtipCfg"] = array(
                        "title" => "ID: " . $asset->getId(),
                        "text" => '<img src="/admin/asset/get-video-thumbnail/id/' . $asset->getId() . '/width/130/aspectratio/true" width="130" />',
                        "width" => 140
                    );
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

            $plugin = new Sabre_DAV_Browser_Plugin();
            $server->addPlugin($plugin);

            //$server->addPlugin(new Sabre_DAV_Mount_Plugin());

            //$tffp = new Sabre_DAV_TemporaryFileFilterPlugin(PIMCORE_WEBDAV_TEMP);
            //$tffp->temporaryFilePatterns[] = "'/^~.(.*)tmp$/'"; // photoshop
            //$server->addPlugin($tffp);

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
            $this->getResponse()->setHeader("Content-Type", $asset->getMimetype(), true);
            $this->getResponse()->setHeader("Content-Disposition", 'attachment; filename="' . $asset->getFilename() . '"');

            echo $asset->getData();
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
        if ($format == "source") {
            $thumbnail->setFormat("PNG");
            $format = "png";
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


        header("Content-Type: image/" . $format, true);

        if($this->getParam("download")) {
            header('Content-Disposition: attachment; filename="' . $image->getFilename() . '"');
        }

        $thumbnailFile = PIMCORE_DOCUMENT_ROOT . $image->getThumbnail($thumbnail);
        $imageContent = file_get_contents($thumbnailFile);

        $fileExtension = Pimcore_File::getFileExtension($thumbnailFile);
        if(in_array($fileExtension, array("gif","jpeg","jpeg","png"))) {
            header("Content-Type: image/".$fileExtension);
        } else {
            header("Content-Type: " . $image->getMimetype());
        }

        header("Content-Length: " . filesize($thumbnailFile));
        echo $imageContent;
        exit;
    }

    public function getVideoThumbnailAction() {

        $video = Asset::getById(intval($this->getParam("id")));
        $thumbnail = $video->getImageThumbnailConfig($this->getAllParams());

        $format = strtolower($thumbnail->getFormat());
        if ($format == "source") {
            $thumbnail->setFormat("PNG");
            $format = "png";
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

        $this->getResponse()->setHeader("Content-Type", "image/png", true);
        readfile(PIMCORE_DOCUMENT_ROOT . $video->getImageThumbnail($thumbnail, $time, $image));
        $this->removeViewRenderer();
    }

    public function getPreviewDocumentAction() {
        $asset = Asset::getById($this->getParam("id"));
        $this->view->asset = $asset;
    }


    public function getPreviewVideoAction() {

        $asset = Asset::getById($this->getParam("id"));

        $this->view->asset = $asset;

        $config = new Asset_Video_Thumbnail_Config();
        $config->setName("pimcore_video_preview_" . $asset->getId());
        $config->setAudioBitrate(128);
        $config->setVideoBitrate(700);

        $config->setItems(array(
            array(
                "method" => "scaleByWidth",
                "arguments" =>
                    array(
                        "width" => 500
                    )
            )
        ));

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
            "condition" => "path LIKE '" . $folder->getFullPath() . "%' AND type != 'folder'",
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
            }

            if (!empty($thumbnailMethod)) {
                $assets[] = array(
                    "id" => $asset->getId(),
                    "type" => $asset->getType(),
                    "filename" => $asset->getFilename(),
                    "url" => $asset->$thumbnailMethod(array(
                        "contain" => true,
                        "width" => 250,
                        "height" => 250,
                        "format" => "JPEG",
                        "interlace" => true,
                        "quality" => 80
                    ))
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
        $session = new Zend_Session_Namespace("pimcore_copy");
        $session->$transactionId = array();

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
        $session = new Zend_Session_Namespace("pimcore_copy");

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

        $this->_helper->json(array("success" => $success));
    }


    public function downloadAsZipAction () {
        
        $asset = Asset::getById($this->getParam("id"));

        if ($asset->isAllowed("view")) {
            $archive_name = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/download.zip";

            if(is_file($archive_name)) {
                unlink($archive_name);
            }

            $archive_folder = $asset->getFileSystemPath();

            $zip = new ZipArchive();
            if ($zip -> open($archive_name, ZipArchive::CREATE) === TRUE) {

                $assetList = new Asset_List();
                $assetList->setCondition("path LIKE ?", $asset->getFullPath() . "/%");
                $assetList->setOrderKey("LENGTH(path)", false);
                $assetList->setOrder("ASC");

                foreach ($assetList->load() as $a) {
                    if($a->isAllowed("view")) {
                        if (!$a instanceof Asset_Folder) {
                            $zip->addFile($a->getFileSystemPath(), substr($a->getFullPath(), 1));
                        }
                    }
                }

                $zip->close();
            }

            $this->getResponse()->setHeader("Content-Type", "application/zip", true);
            $this->getResponse()->setHeader("Content-Disposition", 'attachment; filename="' . $asset->getFilename() . '.zip"');

            echo file_get_contents($archive_name);



            unlink($archive_name);
        }

        $this->removeViewRenderer();
    }

    public function importZipAction() {
        $success = true;

        $importId = uniqid();
        $tmpDirectory = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/asset-zip-import-".$importId;

        $zip = new ZipArchive;
        if ($zip->open($_FILES["Filedata"]["tmp_name"]) === TRUE) {
            $zip->extractTo($tmpDirectory);
            $zip->close();
        }

        $this->importFromFileSystem($tmpDirectory, $this->getParam("parentId"));

        // cleanup
        recursiveDelete($tmpDirectory);

        $this->_helper->json(array("success" => $success));
    }

    public function importServerAction() {
        $success = true;

        $importDirectory = str_replace("/fileexplorer",PIMCORE_DOCUMENT_ROOT, $this->getParam("serverPath"));
        if(is_dir($importDirectory)) {
            $this->importFromFileSystem($importDirectory, $this->getParam("parentId"));
        }

        $this->_helper->json(array("success" => $success));
    }

    public function importUrlAction() {
        $success = true;

        $data = Pimcore_Tool::getHttpData($this->getParam("url"));
        $filename = basename($this->getParam("url"));
        $parentId = $this->getParam("id");
        $parentAsset = Asset::getById(intval($parentId));

        $filename = Pimcore_File::getValidFilename($filename);
        if(empty($filename)) {
            throw new Exception("The filename of the asset is empty");
        }

        // check for dublicate filename
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

    protected function importFromFileSystem ($path, $parentId) {

        $assetFolder = Asset::getById($parentId);
        $files = rscandir($path."/");

        foreach ($files as $file) {
            if(is_file($file)) {
                $relativePath =  $assetFolder->getFullPath() . str_replace($path, "", $file);
                $folder = $this->getOrCreateAssetFolderByPath(dirname($relativePath));
                $filename = basename($file);

                // check for dublicate filename
                $filename = Pimcore_File::getValidFilename($filename);
                $filename = $this->getSafeFilename($folder->getFullPath(), $filename);

                if ($assetFolder->isAllowed("create")) {
                    $asset = Asset::create($folder->getId(), array(
                        "filename" => $filename,
                        "data" => file_get_contents($file),
                        "userOwner" => $this->getUser()->getId(),
                        "userModification" => $this->getUser()->getId()
                    ));
                }
                else {
                    Logger::debug("prevented creating asset because of missing permissions ");
                }
            }
        }
    }

    protected function getOrCreateAssetFolderByPath($path)
    {
        $folder = Asset_Folder::getByPath($path);

        if (!($folder instanceof Asset_Folder)) {
            str_replace("\\", "/", $path);
            $parts = explode("/", $path);
            if (empty($parts[count($parts) - 1])) {
                $parts = array_slice($parts, 0, -1);
            }
            $parts = array_slice($parts, 1);
            $parentPath = "/";
            foreach ($parts as $part) {
                if($part == ''){
                    continue;
                }

                $parent = Asset_Folder::getByPath($parentPath);
                if ($parent instanceof Asset_Folder) {

                    $part = Pimcore_File::getValidFilename($part);
                    $folder = Asset_Folder::getByPath($parentPath . $part);

                    if (!($folder instanceof Asset_Folder)) {
                        $folder = Asset::create($parent->getId(), array(
                           "filename" => $part,
                           "type" => "folder",
                           "userOwner" => $this->getUser()->getId(),
                           "userModification" => $this->getUser()->getId()
                        ));
                    }

                    $parentPath .= $part . "/";
                } else {
                    Logger::error("parent not found!");
                    return null;
                }
            }
        }

        return $folder;
    }
}
