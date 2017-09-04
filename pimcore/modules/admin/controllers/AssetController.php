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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

use Pimcore\File;
use Pimcore\Tool;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Model;
use Pimcore\Logger;

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
        $notRestrictedActions = ["get-image-thumbnail", "get-video-thumbnail", "get-document-thumbnail"];
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("assets");
        }

        $this->_assetService = new Asset\Service($this->getUser());
    }

    public function getDataByIdAction()
    {

        // check for lock
        if (Element\Editlock::isLocked($this->getParam("id"), "asset")) {
            $this->_helper->json([
                "editlock" => Element\Editlock::getByElement($this->getParam("id"), "asset")
            ]);
        }
        Element\Editlock::lock($this->getParam("id"), "asset");

        $asset = Asset::getById(intval($this->getParam("id")));
        $asset = clone $asset;

        if (!$asset instanceof Asset) {
            $this->_helper->json(["success" => false, "message" => "asset doesn't exist"]);
        }

        $asset->setMetadata(Asset\Service::expandMetadataForEditmode($asset->getMetadata()));
        $asset->setProperties(Element\Service::minimizePropertiesForEditmode($asset->getProperties()));
        //$asset->getVersions();
        $asset->getScheduledTasks();
        $asset->idPath = Element\Service::getIdPath($asset);
        $asset->userPermissions = $asset->getUserPermissions();
        $asset->setLocked($asset->isLocked());
        $asset->setParent(null);

        if ($asset instanceof Asset\Text) {
            if ($asset->getFileSize() < 2000000) {
                // it doesn't make sense to show a preview for files bigger than 2MB
                $asset->data =  \ForceUTF8\Encoding::toUTF8($asset->getData());
            } else {
                $asset->data = false;
            }
        }

        if ($asset instanceof Asset\Image) {
            $imageInfo = [];

            if ($asset->getWidth() && $asset->getHeight()) {
                $imageInfo["dimensions"] = [];
                $imageInfo["dimensions"]["width"] = $asset->getWidth();
                $imageInfo["dimensions"]["height"] = $asset->getHeight();
            }

            $exifData = $asset->getEXIFData();
            if (!empty($exifData)) {
                $imageInfo["exif"] = $exifData;
            }

            $iptcData = $asset->getIPTCData();
            if (!empty($iptcData)) {
                // flatten data, to be displayed in grid
                foreach($iptcData as &$value) {
                    if (is_array($value)) {
                        $value = implode(", ", $value);
                    }
                }

                $imageInfo['iptc'] = $iptcData;
            }

            $imageInfo["exiftoolAvailable"] = (bool) \Pimcore\Tool\Console::getExecutable("exiftool");

            $asset->imageInfo = $imageInfo;
        }

        $asset->setStream(null);

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $returnValueContainer = new Model\Tool\Admin\EventDataContainer(object2array($asset));
        \Pimcore::getEventManager()->trigger("admin.asset.get.preSendData", $this, [
            "asset" => $asset,
            "returnValueContainer" => $returnValueContainer
        ]);


        if ($asset->isAllowed("view")) {
            $this->_helper->json($returnValueContainer->getData());
        }

        $this->_helper->json(["success" => false, "message" => "missing_permission"]);
    }

    public function treeGetChildsByIdAction()
    {
        $assets = [];
        $cv = false;
        $asset = Asset::getById($this->getParam("node"));

        if ($asset->hasChilds()) {
            $limit = intval($this->getParam("limit"));
            if (!$this->getParam("limit")) {
                $limit = 100000000;
            }
            $offset = intval($this->getParam("start"));


            if ($this->getParam("view")) {
                $cv = \Pimcore\Model\Element\Service::getCustomViewById($this->getParam("view"));
            }

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
            $childsList->setOrderKey("FIELD(assets.type, 'folder') DESC, assets.filename ASC", false);

            \Pimcore\Model\Element\Service::addTreeFilterJoins($cv, $childsList);
            $childs = $childsList->load();

            foreach ($childs as $childAsset) {
                if ($childAsset->isAllowed("list")) {
                    $assets[] = $this->getTreeNodeConfig($childAsset);
                }
            }
        }


        if ($this->getParam("limit")) {
            $this->_helper->json([
                "offset" => $offset,
                "limit" => $limit,
                "total" => $asset->getChildAmount($this->getUser()),
                "nodes" => $assets
            ]);
        } else {
            $this->_helper->json($assets);
        }

        $this->_helper->json(false);
    }

    public function addAssetAction()
    {
        $res = $this->addAsset();
        $this->_helper->json(["success" => $res["success"], "msg" => "Success"]);
    }

    public function addAssetCompatibilityAction()
    {
        // this is a special action for the compatibility mode upload (without flash)
        $res = $this->addAsset();

        // here we have to use this method and not the JSON action helper ($this->_helper->json()) because this will add
        // Content-Type: application/json which fires a download window in most browsers, because this is a normal POST
        // request and not XHR where the content-type doesn't matter
        $this->disableViewAutoRender();
        echo \Zend_Json::encode([
            "success" => $res["success"],
            "msg" => $res["success"] ? "Success" : "Error",
            "id" => $res["asset"] ? $res["asset"]->getId() : null,
            "fullpath" => $res["asset"] ? $res["asset"]->getRealFullPath() : null,
            "type" => $res["asset"] ? $res["asset"]->getType() : null
        ]);
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function addAsset()
    {
        $success = false;

        if (array_key_exists("Filedata", $_FILES)) {
            $filename = $_FILES["Filedata"]["name"];
            $sourcePath = $_FILES["Filedata"]["tmp_name"];
        } elseif ($this->getParam("type") == "base64") {
            $filename = $this->getParam("filename");
            $sourcePath = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/upload-base64" . uniqid() . ".tmp";
            $data = preg_replace("@^data:[^,]+;base64,@", "", $this->getParam("data"));
            File::put($sourcePath, base64_decode($data));
        }

        if ($this->getParam("dir") && $this->getParam("parentId")) {
            // this is for uploading folders with Drag&Drop
            // param "dir" contains the relative path of the file
            $parent = Asset::getById($this->getParam("parentId"));
            $newPath = $parent->getRealFullPath() . "/" . trim($this->getParam("dir"), "/ ");

            // check if the path is outside of the asset directory
            $newRealPath = PIMCORE_ASSET_DIRECTORY . $newPath;
            $newRealPath = resolvePath($newRealPath);
            if (strpos($newRealPath, PIMCORE_ASSET_DIRECTORY) !== 0) {
                throw new \Exception("not allowed");
            }

            $maxRetries = 5;
            for ($retries=0; $retries<$maxRetries; $retries++) {
                try {
                    $newParent = Asset\Service::createFolderByPath($newPath);
                    break;
                } catch (\Exception $e) {
                    if ($retries < ($maxRetries-1)) {
                        $waitTime = rand(100000, 900000); // microseconds
                        usleep($waitTime); // wait specified time until we restart the transaction
                    } else {
                        // if the transaction still fail after $maxRetries retries, we throw out the exception
                        throw $e;
                    }
                }
            }

            $this->setParam("parentId", $newParent->getId());
        } elseif (!$this->getParam("parentId") && $this->getParam("parentPath")) {
            $parent = Asset::getByPath($this->getParam("parentPath"));
            if ($parent instanceof Asset\Folder) {
                $this->setParam("parentId", $parent->getId());
            } else {
                $this->setParam("parentId", 1);
            }
        } elseif (!$this->getParam("parentId")) {
            // set the parent to the root folder
            $this->setParam("parentId", 1);
        }

        $filename = Element\Service::getValidKey($filename, "asset");
        if (empty($filename)) {
            throw new \Exception("The filename of the asset is empty");
        }

        $parentAsset = Asset::getById(intval($this->getParam("parentId")));

        // check for duplicate filename
        $filename = $this->getSafeFilename($parentAsset->getRealFullPath(), $filename);

        if ($parentAsset->isAllowed("create")) {
            if (!is_file($sourcePath) || filesize($sourcePath) < 1) {
                throw new \Exception("Something went wrong, please check upload_max_filesize and post_max_size in your php.ini and write permissions of /website/var/");
            }

            $asset = Asset::create($this->getParam("parentId"), [
                "filename" => $filename,
                "sourcePath" => $sourcePath,
                "userOwner" => $this->user->getId(),
                "userModification" => $this->user->getId()
            ]);
            $success = true;

            @unlink($sourcePath);
        } else {
            Logger::debug("prevented creating asset because of missing permissions, parent asset is " . $parentAsset->getRealFullPath());
        }

        return [
            "success" => $success,
            "asset" => $asset
        ];
    }

    /**
     * @param $targetPath
     * @param $filename
     * @return mixed
     */
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

        $newFilename = Element\Service::getValidKey($_FILES["Filedata"]["name"], 'asset');
        $mimetype = Tool\Mime::detect($_FILES["Filedata"]["tmp_name"], $newFilename);
        $newType = Asset::getTypeFromMimeMapping($mimetype, $newFilename);

        if($newType != $asset->getType()) {
            $t = \Zend_Registry::get("Zend_Translate");
            $this->_helper->json([
                'success'=>false,
                'message'=> sprintf($t->translate('asset_type_change_not_allowed'), $asset->getType(), $newType)
            ]);
        }

        $stream = fopen($_FILES["Filedata"]["tmp_name"], "r+");
        $asset->setStream($stream);
        $asset->setCustomSetting("thumbnails", null);
        $asset->setUserModification($this->getUser()->getId());
        $newFilename = Element\Service::getValidKey($_FILES["Filedata"]["name"], 'asset');
        if($newFilename != $asset->getFilename()) {
            $newFilename = Element\Service::getSaveCopyName('asset', $newFilename, $asset->getParent());
        }
        $asset->setFilename($newFilename);

        if ($asset->isAllowed("publish")) {
            $asset->save();

            $this->_helper->json([
                "id" => $asset->getId(),
                "path" => $asset->getRealFullPath(),
                "success" => true
            ], false);

            // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
            // Ext.form.Action.Submit and mark the submission as failed
            $this->getResponse()->setHeader("Content-Type", "text/html", true);
        } else {
            throw new \Exception("missing permission");
        }
    }

    public function addFolderAction()
    {
        $success = false;
        $parentAsset = Asset::getById(intval($this->getParam("parentId")));
        $equalAsset = Asset::getByPath($parentAsset->getRealFullPath() . "/" . $this->getParam("name"));

        if ($parentAsset->isAllowed("create")) {
            if (!$equalAsset) {
                $asset = Asset::create($this->getParam("parentId"), [
                    "filename" => $this->getParam("name"),
                    "type" => "folder",
                    "userOwner" => $this->user->getId(),
                    "userModification" => $this->user->getId()
                ]);
                $success = true;
            }
        } else {
            Logger::debug("prevented creating asset because of missing permissions");
        }

        $this->_helper->json(["success" => $success]);
    }

    public function deleteAction()
    {
        if ($this->getParam("type") == "childs") {
            $parentAsset = Asset::getById($this->getParam("id"));

            $list = new Asset\Listing();
            $list->setCondition("path LIKE '" . $parentAsset->getRealFullPath() . "/%'");
            $list->setLimit(intval($this->getParam("amount")));
            $list->setOrderKey("LENGTH(path)", false);
            $list->setOrder("DESC");

            $assets = $list->load();

            $deletedItems = [];
            foreach ($assets as $asset) {
                $deletedItems[] = $asset->getRealFullPath();
                if ($asset->isAllowed("delete")) {
                    $asset->delete();
                }
            }

            $this->_helper->json(["success" => true, "deleted" => $deletedItems]);
        } elseif ($this->getParam("id")) {
            $asset = Asset::getById($this->getParam("id"));

            if ($asset->isAllowed("delete")) {
                $asset->delete();

                $this->_helper->json(["success" => true]);
            }
        }

        $this->_helper->json(["success" => false, "message" => "missing_permission"]);
    }

    public function deleteInfoAction()
    {
        $hasDependency = false;
        $deleteJobs = [];
        $recycleJobs = [];

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
                Logger::err("failed to access asset with id: " . $id);
                continue;
            }


            // check for childs
            if ($asset instanceof Asset) {
                $recycleJobs[] = [[
                    "url" => "/admin/recyclebin/add",
                    "params" => [
                        "type" => "asset",
                        "id" => $asset->getId()
                    ]
                ]];


                $hasChilds = $asset->hasChilds();
                if (!$hasDependency) {
                    $hasDependency = $hasChilds;
                }

                $childs = 0;
                if ($hasChilds) {
                    // get amount of childs
                    $list = new Asset\Listing();
                    $list->setCondition("path LIKE '" . $asset->getRealFullPath() . "/%'");
                    $childs = $list->getTotalCount();
                    $totalChilds += $childs;

                    if ($childs > 0) {
                        $deleteObjectsPerRequest = 5;
                        for ($i = 0; $i < ceil($childs / $deleteObjectsPerRequest); $i++) {
                            $deleteJobs[] = [[
                                "url" => "/admin/asset/delete",
                                "params" => [
                                    "step" => $i,
                                    "amount" => $deleteObjectsPerRequest,
                                    "type" => "childs",
                                    "id" => $asset->getId()
                                ]
                            ]];
                        }
                    }
                }

                // the asset itself is the last one
                $deleteJobs[] = [[
                    "url" => "/admin/asset/delete",
                    "params" => [
                        "id" => $asset->getId()
                    ]
                ]];
            }
        }

        // get the element key in case of just one
        $elementKey = false;
        if (count($ids) === 1) {
            $elementKey = Asset::getById($id)->getKey();
        }

        $deleteJobs = array_merge($recycleJobs, $deleteJobs);
        $this->_helper->json([
            "hasDependencies" => $hasDependency,
            "childs" => $totalChilds,
            "deletejobs" => $deleteJobs,
            "batchDelete" => count($ids) > 1,
            "elementKey" => $elementKey
        ]);
    }

    /**
     * @param $element
     * @return array
     */
    protected function getTreeNodeConfig($element)
    {
        $asset = $element;

        $tmpAsset = [
            "id" => $asset->getId(),
            "text" => $asset->getFilename(),
            "type" => $asset->getType(),
            "path" => $asset->getRealFullPath(),
            "basePath" => $asset->getRealPath(),
            "locked" => $asset->isLocked(),
            "lockOwner" => $asset->getLocked() ? true : false,
            "elementType" => "asset",
            "permissions" => [
                "remove" => $asset->isAllowed("delete"),
                "settings" => $asset->isAllowed("settings"),
                "rename" => $asset->isAllowed("rename"),
                "publish" => $asset->isAllowed("publish"),
                "view" => $asset->isAllowed("view")
            ]
        ];

        // set type specific settings
        if ($asset->getType() == "folder") {
            $tmpAsset["leaf"] = false;
            $tmpAsset["expanded"] = $asset->hasNoChilds();
            $tmpAsset["loaded"] = $asset->hasNoChilds();
            $tmpAsset["iconCls"] = "pimcore_icon_folder";
            $tmpAsset["permissions"]["create"] = $asset->isAllowed("create");

            $folderThumbs = [];
            $children = new Asset\Listing();
            $children->setCondition("path LIKE ?", [$asset->getRealFullPath() . "/%"]);
            $children->setLimit(35);

            foreach ($children as $child) {
                if ($child->isAllowed("view")) {
                    if ($thumbnailUrl = $this->getThumbnailUrl($child)) {
                        $folderThumbs[] = $thumbnailUrl;
                    }
                }
            }

            if (!empty($folderThumbs)) {
                $tmpAsset["thumbnails"] = $folderThumbs;
            }
        } else {
            $tmpAsset["leaf"] = true;
            $tmpAsset["expandable"] = false;
            $tmpAsset["expanded"] = false;

            $tmpAsset["iconCls"] = "pimcore_icon_asset_default";

            $fileExt = File::getFileExtension($asset->getFilename());
            if ($fileExt) {
                $tmpAsset["iconCls"] .= " pimcore_icon_" . File::getFileExtension($asset->getFilename());
            }
        }

        $tmpAsset["qtipCfg"] = [
            "title" => "ID: " . $asset->getId()
        ];

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
                Logger::debug("Cannot get dimensions of image, seems to be broken.");
            }
        } elseif ($asset->getType() == "video") {
            try {
                if (\Pimcore\Video::isAvailable()) {
                    $tmpAsset["thumbnail"] = $this->getThumbnailUrl($asset);
                }
            } catch (\Exception $e) {
                Logger::debug("Cannot get dimensions of video, seems to be broken.");
            }
        } elseif ($asset->getType() == "document") {
            try {
                // add the PDF check here, otherwise the preview layer in admin is shown without content
                if (\Pimcore\Document::isAvailable() && \Pimcore\Document::isFileTypeSupported($asset->getFilename())) {
                    $tmpAsset["thumbnail"] = $this->getThumbnailUrl($asset);
                }
            } catch (\Exception $e) {
                Logger::debug("Cannot get dimensions of video, seems to be broken.");
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

    /**
     * @param $asset
     * @return null|string
     */
    protected function getThumbnailUrl($asset)
    {
        if ($asset instanceof Asset\Image) {
            return "/admin/asset/get-image-thumbnail/id/" . $asset->getId() . "/treepreview/true";
        } elseif ($asset instanceof Asset\Video && \Pimcore\Video::isAvailable()) {
            return "/admin/asset/get-video-thumbnail/id/" . $asset->getId() . "/treepreview/true";
        } elseif ($asset instanceof Asset\Document && \Pimcore\Document::isAvailable()) {
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

                    $intendedPath = $parentAsset->getRealPath();
                    $pKey = $parentAsset->getKey();
                    if (!empty($pKey)) {
                        $intendedPath .= $parentAsset->getKey() . "/";
                    }

                    $assetWithSamePath = Asset::getByPath($intendedPath . $asset->getKey());

                    if ($assetWithSamePath != null) {
                        $allowUpdate = false;
                    }

                    if ($asset->isLocked()) {
                        $allowUpdate = false;
                    }
                }
            }

            if ($allowUpdate) {
                if ($this->getParam("filename") != $asset->getFilename() and !$asset->isAllowed("rename")) {
                    unset($updateData["filename"]);
                    Logger::debug("prevented renaming asset because of missing permissions ");
                }

                $asset->setValues($updateData);


                try {
                    $asset->save();
                    $success = true;
                } catch (\Exception $e) {
                    $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                }
            } else {
                $msg = "prevented moving asset, asset with same path+key already exists at target location or the asset is locked. ID: " . $asset->getId();
                Logger::debug($msg);
                $this->_helper->json(["success" => $success, "message" => $msg]);
            }
        } elseif ($asset->isAllowed("rename") && $this->getParam("filename")) {
            //just rename
            try {
                $asset->setFilename($this->getParam("filename"));
                $asset->save();
                $success = true;
            } catch (\Exception $e) {
                $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
            }
        } else {
            Logger::debug("prevented update asset because of missing permissions ");
        }

        $this->_helper->json(["success" => $success]);
    }


    public function webdavAction()
    {
        $homeDir = Asset::getById(1);

        try {
            $publicDir = new Asset\WebDAV\Folder($homeDir);
            $objectTree = new Asset\WebDAV\Tree($publicDir);
            $server = new \Sabre\DAV\Server($objectTree);
            $server->setBaseUri("/admin/asset/webdav/");

            // lock plugin
            $lockBackend = new \Sabre\DAV\Locks\Backend\File(PIMCORE_WEBDAV_TEMP . '/locks.dat');
            $lockPlugin = new \Sabre\DAV\Locks\Plugin($lockBackend);
            $server->addPlugin($lockPlugin);

            // sync plugin
            $server->addPlugin(new \Sabre\DAV\Sync\Plugin());

            // browser plugin
            $server->addPlugin(new Sabre\DAV\Browser\Plugin());

            $server->exec();
        } catch (\Exception $e) {
            Logger::error($e);
        }

        exit;
    }


    public function saveAction()
    {
        try {
            $success = false;
            if ($this->getParam("id")) {
                $asset = Asset::getById($this->getParam("id"));
                if ($asset->isAllowed("publish")) {


                    // metadata
                    if ($this->getParam("metadata")) {
                        $metadata = \Zend_Json::decode($this->getParam("metadata"));
                        $metadata = Asset\Service::minimizeMetadata($metadata);
                        $asset->setMetadata($metadata);
                    }

                    // properties
                    if ($this->getParam("properties")) {
                        $properties = [];
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
                                    Logger::err("Can't add " . $propertyName . " to asset " . $asset->getRealFullPath());
                                }
                            }

                            $asset->setProperties($properties);
                        }
                    }

                    // scheduled tasks
                    if ($this->getParam("scheduler")) {
                        $tasks = [];
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
                        if (Tool\Admin::isExtJS6() && $e instanceof Element\ValidationException) {
                            throw $e;
                        }
                        $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                    }
                } else {
                    Logger::debug("prevented save asset because of missing permissions ");
                }

                $this->_helper->json(["success" => $success]);
            }

            $this->_helper->json(false);
        } catch (\Exception $e) {
            Logger::log($e);
            if (Tool\Admin::isExtJS6() && $e instanceof Element\ValidationException) {
                $this->_helper->json(["success" => false, "type" => "ValidationException", "message" => $e->getMessage(), "stack" => $e->getTraceAsString(), "code" => $e->getCode()]);
            }
            throw $e;
        }
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
                $this->_helper->json(["success" => true]);
            } catch (\Exception $e) {
                $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
            }
        }

        $this->_helper->json(false);
    }

    public function showVersionAction()
    {
        $id = intval($this->getParam("id"));
        $version = Model\Version::getById($id);
        $asset = $version->loadData();

        if ($asset->isAllowed("versions")) {
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
            header("Content-Length: " . filesize($asset->getFileSystemPath()), true); while (@ob_end_flush()) ;
            flush();

            readfile($asset->getFileSystemPath());
            exit;
        }

        $this->removeViewRenderer();
    }

    public function downloadImageThumbnailAction()
    {
        $image = Asset\Image::getById($this->getParam("id"));

        if (!$image->isAllowed("view")) {
            throw new \Exception("not allowed to view thumbnail");
        }

        $config = null;

        if ($this->getParam("config")) {
            $config = \Zend_Json::decode($this->getParam("config"));
        } elseif ($this->getParam("type")) {
            $predefined = [
                "web" => [
                    "resize_mode" => "scaleByWidth",
                    "width" => 3500,
                    "dpi" => 72,
                    "format" => "JPEG",
                    "quality" => 85
                ],
                "print" => [
                    "resize_mode" => "scaleByWidth",
                    "width" => 6000,
                    "dpi" => 300,
                    "format" => "JPEG",
                    "quality" => 95
                ],
                "office" => [
                    "resize_mode" => "scaleByWidth",
                    "width" => 1190,
                    "dpi" => 144,
                    "format" => "JPEG",
                    "quality" => 90
                ],
            ];

            $config = $predefined[$this->getParam("type")];
        }

        if ($config) {
            $thumbnailConfig = new Asset\Image\Thumbnail\Config();
            $thumbnailConfig->setName("pimcore-download-" . $image->getId() . "-" . md5($this->getParam("config")));

            if ($config["resize_mode"] == "scaleByWidth") {
                $thumbnailConfig->addItem("scaleByWidth", [
                    "width" => $config["width"]
                ]);
            } elseif ($config["resize_mode"] == "scaleByHeight") {
                $thumbnailConfig->addItem("scaleByHeight", [
                    "height" => $config["height"]
                ]);
            } else {
                $thumbnailConfig->addItem("resize", [
                    "width" => $config["width"],
                    "height" => $config["height"]
                ]);
            }

            $thumbnailConfig->setQuality($config["quality"]);
            $thumbnailConfig->setFormat($config["format"]);


            if ($thumbnailConfig->getFormat() == "JPEG") {
                $thumbnailConfig->setPreserveMetaData(true);

                if (empty($config["quality"])) {
                    $thumbnailConfig->setPreserveColor(true);
                }
            }

            $thumbnail = $image->getThumbnail($thumbnailConfig);
            $thumbnailFile = $thumbnail->getFileSystemPath();

            $exiftool = \Pimcore\Tool\Console::getExecutable("exiftool");
            if ($thumbnailConfig->getFormat() == "JPEG" && $exiftool && isset($config["dpi"]) && $config["dpi"]) {
                \Pimcore\Tool\Console::exec($exiftool . " -overwrite_original -xresolution=" . $config["dpi"] . " -yresolution=" . $config["dpi"] . " -resolutionunit=inches " . escapeshellarg($thumbnailFile));
            }

            $downloadFilename = str_replace("." . File::getFileExtension($image->getFilename()),
                "." . $thumbnail->getFileExtension(), $image->getFilename());
            $downloadFilename = strtolower($downloadFilename);
            header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');

            header("Content-Type: " . $thumbnail->getMimeType(), true);

            // we have to clear the stat cache here, otherwise filesize() would return the wrong value
            // the reason is that exiftool modifies the file's size, but PHP doesn't know anything about that
            clearstatcache();
            header("Content-Length: " . filesize($thumbnailFile), true);
            $this->sendThumbnailCacheHeaders();
            while (@ob_end_flush()) {
                ;
            }
            flush();

            readfile($thumbnailFile);
            @unlink($thumbnailFile);
            exit;
        }
    }

    public function getImageThumbnailAction()
    {
        $fileinfo = $this->getParam("fileinfo");
        $image = Asset\Image::getById(intval($this->getParam("id")));

        if (!$image->isAllowed("view")) {
            throw new \Exception("not allowed to view thumbnail");
        }

        $thumbnail = null;

        if ($this->getParam("thumbnail")) {
            $thumbnail = $image->getThumbnailConfig($this->getParam("thumbnail"));
        }
        if (!$thumbnail) {
            if ($this->getParam("config")) {
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
        }

        if ($this->getParam("treepreview")) {
            $thumbnail = Asset\Image\Thumbnail\Config::getPreviewConfig();
        }

        if ($this->getParam("cropPercent")) {
            $thumbnail->addItemAt(0, "cropPercent", [
                "width" => $this->getParam("cropWidth"),
                "height" => $this->getParam("cropHeight"),
                "y" => $this->getParam("cropTop"),
                "x" => $this->getParam("cropLeft")
            ]);

            $hash = md5(Tool\Serialize::serialize($this->getAllParams()));
            $thumbnail->setName($thumbnail->getName() . "_auto_" . $hash);
        }

        $thumbnail = $image->getThumbnail($thumbnail);

        if ($fileinfo) {
            $this->_helper->json([
                "width" => $thumbnail->getWidth(),
                "height" => $thumbnail->getHeight()]);
        }

        $thumbnailFile = $thumbnail->getFileSystemPath();
        header("Content-Type: " . $thumbnail->getMimeType(), true);
        header("Access-Control-Allow-Origin: *"); // for Aviary.Feather (Adobe Creative SDK)
        header("Content-Length: " . filesize($thumbnailFile), true);
        $this->sendThumbnailCacheHeaders(); while (@ob_end_flush());
        flush();

        readfile($thumbnailFile);
        exit;
    }

    public function getVideoThumbnailAction()
    {
        if ($this->getParam("id")) {
            $video = Asset::getById(intval($this->getParam("id")));
        } elseif ($this->getParam("path")) {
            $video = Asset::getByPath($this->getParam("path"));
        }

        if (!$video->isAllowed("view")) {
            throw new \Exception("not allowed to view thumbnail");
        }

        $thumbnail = $this->getAllParams();

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

        $thumb = $video->getImageThumbnail($thumbnail, $time, $image);
        $thumbnailFile = $thumb->getFileSystemPath();

        header("Content-type: image/" . File::getFileExtension($thumbnailFile), true);
        header("Content-Length: " . filesize($thumbnailFile), true);
        $this->sendThumbnailCacheHeaders(); while (@ob_end_flush()) ;
        flush();

        readfile($thumbnailFile);
        exit;
    }

    public function getDocumentThumbnailAction()
    {
        $document = Asset::getById(intval($this->getParam("id")));

        if (!$document->isAllowed("view")) {
            throw new \Exception("not allowed to view thumbnail");
        }


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


        $thumb = $document->getImageThumbnail($thumbnail, $page);
        $thumbnailFile = $thumb->getFileSystemPath();

        $format = "png";
        header("Content-type: image/" . $format, true);
        header("Content-Length: " . filesize($thumbnailFile), true);
        $this->sendThumbnailCacheHeaders(); while (@ob_end_flush()) ;
        flush();

        readfile($thumbnailFile);
        exit;
    }

    protected function sendThumbnailCacheHeaders()
    {
        $this->getResponse()->clearAllHeaders();

        $lifetime = 300;
        $date = new \DateTime("now");
        $date->add(new \DateInterval("PT" . $lifetime . "S"));

        header("Cache-Control: public, max-age=" . $lifetime, true);
        header("Expires: " . $date->format(\DateTime::RFC1123), true);
        header("Pragma: ");
    }

    public function getPreviewDocumentAction()
    {
        $asset = Asset::getById($this->getParam("id"));

        if (!$asset->isAllowed("view")) {
            throw new \Exception("not allowed to preview");
        }

        $this->view->asset = $asset;
    }


    public function getPreviewVideoAction()
    {
        $asset = Asset::getById($this->getParam("id"));

        if (!$asset->isAllowed("view")) {
            throw new \Exception("not allowed to preview");
        }

        $this->view->asset = $asset;

        $config = Asset\Video\Thumbnail\Config::getPreviewConfig();

        $thumbnail = $asset->getThumbnail($config, ["mp4"]);

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

    public function imageEditorAction()
    {
        $asset = Asset::getById($this->getParam("id"));

        if (!$asset->isAllowed("view")) {
            throw new \Exception("not allowed to preview");
        }

        $this->view->asset = $asset;
    }

    public function imageEditorSaveAction()
    {
        $asset = Asset::getById($this->getParam("id"));

        if (!$asset->isAllowed("publish")) {
            throw new \Exception("not allowed to publish");
        }

        $asset->setData(Tool::getHttpData($this->getParam("url")));
        $asset->setUserModification($this->getUser()->getId());
        $asset->save();

        $this->_helper->json(["success" => true]);
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

        $conditionFilters = [];
        $conditionFilters[] = "path LIKE '" . ($folder->getRealFullPath() == "/" ? "/%'" : $folder->getRealFullPath() . "/%'") ." AND type != 'folder'";

        if (!$this->getUser()->isAdmin()) {
            $userIds = $this->getUser()->getRoles();
            $userIds[] = $this->getUser()->getId();
            $conditionFilters[] .= " (
                                                    (select list from users_workspaces_asset where userId in (" . implode(',', $userIds) . ") and LOCATE(CONCAT(path, filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_asset where userId in (" . implode(',', $userIds) . ") and LOCATE(cpath,CONCAT(path, filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )";
        }

        $condition = implode(" AND ", $conditionFilters);
        $list = Asset::getList([
            "condition" => $condition,
            "limit" => $limit,
            "offset" => $start,
            "orderKey" => "filename",
            "order" => "asc"
        ]);

        $assets = [];

        foreach ($list as $asset) {
            $thumbnailMethod = "";
            if ($asset instanceof Asset\Image) {
                $thumbnailMethod = "getThumbnail";
            } elseif ($asset instanceof Asset\Video && \Pimcore\Video::isAvailable()) {
                $thumbnailMethod = "getImageThumbnail";
            } elseif ($asset instanceof Asset\Document && \Pimcore\Document::isAvailable()) {
                $thumbnailMethod = "getImageThumbnail";
            }

            if (!empty($thumbnailMethod)) {
                $filenameDisplay = $asset->getFilename();
                if (strlen($filenameDisplay) > 32) {
                    $filenameDisplay = substr($filenameDisplay, 0, 25) . "..." . \Pimcore\File::getFileExtension($filenameDisplay);
                }

                $assets[] = [
                    "id" => $asset->getId(),
                    "type" => $asset->getType(),
                    "filename" => $asset->getFilename(),
                    "filenameDisplay" => $filenameDisplay,
                    "url" => "/admin/asset/get-" . $asset->getType() . "-thumbnail/id/" . $asset->getId() . "/treepreview/true",
                    "idPath" => $data["idPath"] = Element\Service::getIdPath($asset)
                ];
            }
        }


        $this->_helper->json([
            "assets" => $assets,
            "success" => true,
            "total" => $list->getTotalCount()
        ]);
    }

    public function copyInfoAction()
    {
        $transactionId = time();
        $pasteJobs = [];

        Tool\Session::useSession(function ($session) use ($transactionId) {
            $session->$transactionId = [];
        }, "pimcore_copy");


        if ($this->getParam("type") == "recursive") {
            $asset = Asset::getById($this->getParam("sourceId"));

            // first of all the new parent
            $pasteJobs[] = [[
                "url" => "/admin/asset/copy",
                "params" => [
                    "sourceId" => $this->getParam("sourceId"),
                    "targetId" => $this->getParam("targetId"),
                    "type" => "child",
                    "transactionId" => $transactionId,
                    "saveParentId" => true
                ]
            ]];

            if ($asset->hasChilds()) {
                // get amount of childs
                $list = new Asset\Listing();
                $list->setCondition("path LIKE '" . $asset->getRealFullPath() . "/%'");
                $list->setOrderKey("LENGTH(path)", false);
                $list->setOrder("ASC");
                $childIds = $list->loadIdList();

                if (count($childIds) > 0) {
                    foreach ($childIds as $id) {
                        $pasteJobs[] = [[
                            "url" => "/admin/asset/copy",
                            "params" => [
                                "sourceId" => $id,
                                "targetParentId" => $this->getParam("targetId"),
                                "sourceParentId" => $this->getParam("sourceId"),
                                "type" => "child",
                                "transactionId" => $transactionId
                            ]
                        ]];
                    }
                }
            }
        } elseif ($this->getParam("type") == "child" || $this->getParam("type") == "replace") {
            // the object itself is the last one
            $pasteJobs[] = [[
                "url" => "/admin/asset/copy",
                "params" => [
                    "sourceId" => $this->getParam("sourceId"),
                    "targetId" => $this->getParam("targetId"),
                    "type" => $this->getParam("type"),
                    "transactionId" => $transactionId
                ]
            ]];
        }


        $this->_helper->json([
            "pastejobs" => $pasteJobs
        ]);
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

            $targetPath = preg_replace("@^" . $sourceParent->getRealFullPath() . "@", $targetParent . "/", $source->getRealPath());
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
                } elseif ($this->getParam("type") == "replace") {
                    $this->_assetService->copyContents($target, $source);
                }

                $success = true;
            } else {
                Logger::debug("prevended copy/paste because asset with same path+key already exists in this location");
            }
        } else {
            Logger::error("could not execute copy/paste because of missing permissions on target [ " . $targetId . " ]");
            $this->_helper->json(["error" => false, "message" => "missing_permission"]);
        }

        Tool\Session::writeClose();

        $this->_helper->json(["success" => $success]);
    }


    public function downloadAsZipJobsAction()
    {
        $jobId = uniqid();
        $filesPerJob = 5;
        $jobs = [];
        $asset = Asset::getById($this->getParam("id"));

        if ($asset->isAllowed("view")) {
            $parentPath = $asset->getRealFullPath();
            if ($asset->getId() == 1) {
                $parentPath = "";
            }

            $db = \Pimcore\Db::get();
            $conditionFilters = [];
            $conditionFilters[] .= "path LIKE " . $db->quote($parentPath . "/%") ." AND type != " . $db->quote("folder");
            if (!$this->getUser()->isAdmin()) {
                $userIds = $this->getUser()->getRoles();
                $userIds[] = $this->getUser()->getId();
                $conditionFilters[] .= " (
                                                    (select list from users_workspaces_asset where userId in (" . implode(',', $userIds) . ") and LOCATE(CONCAT(path, filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_asset where userId in (" . implode(',', $userIds) . ") and LOCATE(cpath,CONCAT(path, filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )";
            }

            $condition = implode(" AND ", $conditionFilters);

            $assetList = new Asset\Listing();
            $assetList->setCondition($condition);
            $assetList->setOrderKey("LENGTH(path)", false);
            $assetList->setOrder("ASC");

            for ($i = 0; $i < ceil($assetList->getTotalCount() / $filesPerJob); $i++) {
                $jobs[] = [[
                    "url" => "/admin/asset/download-as-zip-add-files",
                    "params" => [
                        "id" => $asset->getId(),
                        "offset" => $i * $filesPerJob,
                        "limit" => $filesPerJob,
                        "jobId" => $jobId
                    ]
                ]];
            }
        }

        $this->_helper->json([
            "success" => true,
            "jobs" => $jobs,
            "jobId" => $jobId
        ]);
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

            if ($zipState === true) {
                $parentPath = $asset->getRealFullPath();
                if ($asset->getId() == 1) {
                    $parentPath = "";
                }

                $db = \Pimcore\Db::get();
                $conditionFilters = [];
                $conditionFilters[] .= "type != 'folder' AND path LIKE " . $db->quote($parentPath . "/%");
                if (!$this->getUser()->isAdmin()) {
                    $userIds = $this->getUser()->getRoles();
                    $userIds[] = $this->getUser()->getId();
                    $conditionFilters[] .= " (
                                                    (select list from users_workspaces_asset where userId in (" . implode(',', $userIds) . ") and LOCATE(CONCAT(path, filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_asset where userId in (" . implode(',', $userIds) . ") and LOCATE(cpath,CONCAT(path, filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )";
                }

                $condition = implode(" AND ", $conditionFilters);

                $assetList = new Asset\Listing();
                $assetList->setCondition($condition);
                $assetList->setOrderKey("LENGTH(path) ASC, id ASC", false);
                $assetList->setOffset((int)$this->getParam("offset"));
                $assetList->setLimit((int)$this->getParam("limit"));

                foreach ($assetList->load() as $a) {
                    if ($a->isAllowed("view")) {
                        if (!$a instanceof Asset\Folder) {
                            // add the file with the relative path to the parent directory
                            $zip->addFile($a->getFileSystemPath(), preg_replace("@^" . preg_quote($asset->getRealPath(), "@") . "@i", "", $a->getRealFullPath()));
                        }
                    }
                }

                $zip->close();
                $success = true;
            }
        }

        $this->_helper->json([
            "success" => $success
        ]);
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
        header('Content-Disposition: attachment; filename="' . $suggestedFilename . '.zip"'); while (@ob_end_flush()) ;
        flush();

        readfile($zipFile);
        unlink($zipFile);

        exit;
    }

    public function importZipAction()
    {
        $jobId = uniqid();
        $filesPerJob = 5;
        $jobs = [];
        $asset = Asset::getById($this->getParam("parentId"));

        if (!$asset->isAllowed("create")) {
            throw new \Exception("not allowed to create");
        }

        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $jobId . ".zip";

        copy($_FILES["Filedata"]["tmp_name"], $zipFile);

        $zip = new \ZipArchive;
        if ($zip->open($zipFile) === true) {
            $jobAmount = ceil($zip->numFiles / $filesPerJob);
            for ($i = 0; $i < $jobAmount; $i++) {
                $jobs[] = [[
                    "url" => "/admin/asset/import-zip-files",
                    "params" => [
                        "parentId" => $asset->getId(),
                        "offset" => $i * $filesPerJob,
                        "limit" => $filesPerJob,
                        "jobId" => $jobId,
                        "last" => (($i + 1) >= $jobAmount) ? "true" : ""
                    ]
                ]];
            }
            $zip->close();
        }

        // here we have to use this method and not the JSON action helper ($this->_helper->json()) because this will add
        // Content-Type: application/json which fires a download window in most browsers, because this is a normal POST
        // request and not XHR where the content-type doesn't matter
        $this->disableViewAutoRender();
        echo \Zend_Json::encode([
            "success" => true,
            "jobs" => $jobs,
            "jobId" => $jobId
        ]);
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

                        $filename = Element\Service::getValidKey(basename($path), "asset");

                        $relativePath = "";
                        if (dirname($path) != ".") {
                            $relativePath = dirname($path);
                        }

                        $parentPath = $importAsset->getRealFullPath() . "/" . preg_replace("@^/@", "", $relativePath);
                        $parent = Asset\Service::createFolderByPath($parentPath);

                        // check for duplicate filename
                        $filename = $this->getSafeFilename($parent->getRealFullPath(), $filename);

                        if ($parent->isAllowed("create")) {
                            $asset = Asset::create($parent->getId(), [
                                "filename" => $filename,
                                "sourcePath" => $tmpFile,
                                "userOwner" => $this->user->getId(),
                                "userModification" => $this->user->getId()
                            ]);

                            @unlink($tmpFile);
                        } else {
                            Logger::debug("prevented creating asset because of missing permissions");
                        }
                    }
                }
            }
            $zip->close();
        }

        if ($this->getParam("last")) {
            unlink($zipFile);
        }

        $this->_helper->json([
            "success" => true
        ]);
    }

    public function importServerAction()
    {
        $success = true;
        $filesPerJob = 5;
        $jobs = [];
        $importDirectory = str_replace("/fileexplorer", PIMCORE_DOCUMENT_ROOT, $this->getParam("serverPath"));
        if (is_dir($importDirectory)) {
            $files = rscandir($importDirectory . "/");
            $count = count($files);
            $jobFiles = [];

            for ($i = 0; $i < $count; $i++) {
                if (is_dir($files[$i])) {
                    continue;
                }

                $jobFiles[] = preg_replace("@^" . preg_quote($importDirectory, "@") . "@", "", $files[$i]);

                if (count($jobFiles) >= $filesPerJob || $i >= ($count - 1)) {
                    $jobs[] = [[
                        "url" => "/admin/asset/import-server-files",
                        "params" => [
                            "parentId" => $this->getParam("parentId"),
                            "serverPath" => $importDirectory,
                            "files" => implode("::", $jobFiles)
                        ]
                    ]];
                    $jobFiles = [];
                }
            }
        }

        $this->_helper->json([
            "success" => $success,
            "jobs" => $jobs
        ]);
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
                $folder = Asset\Service::createFolderByPath($assetFolder->getRealFullPath() . $relFolderPath);
                $filename = basename($file);

                // check for duplicate filename
                $filename = Element\Service::getValidKey($filename, "asset");
                $filename = $this->getSafeFilename($folder->getRealFullPath(), $filename);

                if ($assetFolder->isAllowed("create")) {
                    $asset = Asset::create($folder->getId(), [
                        "filename" => $filename,
                        "sourcePath" => $absolutePath,
                        "userOwner" => $this->getUser()->getId(),
                        "userModification" => $this->getUser()->getId()
                    ]);
                } else {
                    Logger::debug("prevented creating asset because of missing permissions ");
                }
            }
        }

        $this->_helper->json([
            "success" => true
        ]);
    }

    public function importUrlAction()
    {
        $success = true;

        $data = Tool::getHttpData($this->getParam("url"));
        $filename = basename($this->getParam("url"));
        $parentId = $this->getParam("id");
        $parentAsset = Asset::getById(intval($parentId));

        $filename = Element\Service::getValidKey($filename, "asset");
        $filename = $this->getSafeFilename($parentAsset->getRealFullPath(), $filename);

        if (empty($filename)) {
            throw new \Exception("The filename of the asset is empty");
        }

        // check for duplicate filename
        $filename = $this->getSafeFilename($parentAsset->getRealFullPath(), $filename);

        if ($parentAsset->isAllowed("create")) {
            $asset = Asset::create($parentId, [
                "filename" => $filename,
                "data" => $data,
                "userOwner" => $this->user->getId(),
                "userModification" => $this->user->getId()
            ]);
            $success = true;
        } else {
            Logger::debug("prevented creating asset because of missing permissions");
        }

        $this->_helper->json(["success" => $success]);
    }

    public function clearThumbnailAction()
    {
        $success = false;

        if ($asset = Asset::getById($this->getParam("id"))) {
            if (method_exists($asset, "clearThumbnails")) {
                if (!$asset->isAllowed("publish")) {
                    throw new \Exception("not allowed to publish");
                }

                $asset->clearThumbnails(true); // force clear
                $asset->save();

                $success = true;
            }
        }

        $this->_helper->json(["success" => $success]);
    }

    public function gridProxyAction()
    {
        if ($this->getParam("data")) {
            if ($this->getParam("xaction") == "update") {
                //TODO probably not needed
            }
        } else {
            $db = \Pimcore\Db::get();
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

            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
            if ($sortingSettings['orderKey']) {
                $orderKey = $sortingSettings['orderKey'];
                if ($orderKey == "fullpath") {
                    $orderKey = ["path", "filename"];
                }

                $order = $sortingSettings['order'];
            }

            $conditionFilters = [];
            if ($this->getParam("only_direct_children") == "true") {
                $conditionFilters[] = "parentId = " . $folder->getId();
            } else {
                $conditionFilters[] = "path LIKE '" . ($folder->getRealFullPath() == "/" ? "/%'" : $folder->getRealFullPath() . "/%'");
            }

            $conditionFilters[] = "type != 'folder'";
            $filterJson = $this->getParam("filter");
            if ($filterJson) {
                $filters = \Zend_Json::decode($filterJson);
                foreach ($filters as $filter) {
                    $operator = "=";

                    if ($filter["type"] == "string") {
                        $operator = "LIKE";
                    } elseif ($filter["type"] == "numeric") {
                        if ($filter["comparison"] == "lt") {
                            $operator = "<";
                        } elseif ($filter["comparison"] == "gt") {
                            $operator = ">";
                        } elseif ($filter["comparison"] == "eq") {
                            $operator = "=";
                        }
                    } elseif ($filter["type"] == "date") {
                        if ($filter["comparison"] == "lt") {
                            $operator = "<";
                        } elseif ($filter["comparison"] == "gt") {
                            $operator = ">";
                        } elseif ($filter["comparison"] == "eq") {
                            $operator = "=";
                        }
                        $filter["value"] = strtotime($filter["value"]);
                    } elseif ($filter["type"] == "list") {
                        $operator = "=";
                    } elseif ($filter["type"] == "boolean") {
                        $operator = "=";
                        $filter["value"] = (int) $filter["value"];
                    }
                    // system field
                    $value = $filter["value"];
                    if ($operator == "LIKE") {
                        $value = "%" . $value . "%";
                    }

                    $field = "`" . $filter["field"] . "` ";
                    if ($filter["field"] == "fullpath") {
                        $field = "CONCAT(path,filename)";
                    }

                    $conditionFilters[] =  $field . $operator . " " . $db->quote($value);
                }
            }

            if (!$this->getUser()->isAdmin()) {
                $userIds = $this->getUser()->getRoles();
                $userIds[] = $this->getUser()->getId();
                $conditionFilters[] .= " (
                                                    (select list from users_workspaces_asset where userId in (" . implode(',', $userIds) . ") and LOCATE(CONCAT(path, filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_asset where userId in (" . implode(',', $userIds) . ") and LOCATE(cpath,CONCAT(path, filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )";
            }

            $list = new Asset\Listing();
            $condition = implode(" AND ", $conditionFilters);
            $list->setCondition($condition);
            $list->setLimit($limit);
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            $list->load();

            $assets = [];
            foreach ($list->getAssets() as $asset) {

                /** @var $asset Asset */
                $filename = PIMCORE_ASSET_DIRECTORY . "/" . $asset->getRealFullPath();
                $size = @filesize($filename);

                $assets[] = [
                    "id" => $asset->getid(),
                    "type" => $asset->getType(),
                    "fullpath" => $asset->getRealFullPath(),
                    "creationDate" => $asset->getCreationDate(),
                    "modificationDate" => $asset->getModificationDate(),
                    "size" => formatBytes($size),
                    "idPath" => $data["idPath"] = Element\Service::getIdPath($asset)
                ];
            }

            $this->_helper->json(["data" => $assets, "success" => true, "total" => $list->getTotalCount()]);
        }
    }

    public function getTextAction()
    {
        $asset = Asset::getById($this->getParam('id'));

        if (!$asset->isAllowed("view")) {
            throw new \Exception("not allowed to view");
        }

        $page = $this->getParam('page');
        if ($asset instanceof Asset\Document) {
            $text = $asset->getText($page);
        }
        $this->_helper->json(['success' => 'true', 'text' => $text]);
    }
}
