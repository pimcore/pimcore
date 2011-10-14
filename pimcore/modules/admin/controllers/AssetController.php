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
        if (!in_array($this->_getParam("action"), $notRestrictedActions)) {
            if (!$this->getUser()->isAllowed("assets")) {

                $this->_redirect("/admin/login");
                die();
            }
        }

        $this->_assetService = new Asset_Service($this->getUser());
    }

    public function getUserPermissionsAction() {

        $asset = Asset::getById($this->_getParam("asset"));

        $list = new User_List();
        $list->load();
        $users = $list->getUsers();
        if (!empty($users)) {
            foreach ($users as $user) {
                $permission = $asset->getUserPermissions($user);
                $permission->setUser($user);
                $permission->setUserId($user->getId());
                $permission->setUsername($user->getUsername());
                $permissions[] = $permission;
            }
        }

        $asset->getPermissionsForUser($this->getUser());
        if ($asset->isAllowed("view")) {
            $this->_helper->json(array("permissions" => $permissions));
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }


    public function getDataByIdAction() {

        // check for lock
        if (Element_Editlock::isLocked($this->_getParam("id"), "asset")) {
            $this->_helper->json(array(
                "editlock" => Element_Editlock::getByElement($this->_getParam("id"), "asset")
            ));
        }
        Element_Editlock::lock($this->_getParam("id"), "asset");

        $asset = Asset::getById(intval($this->_getParam("id")));

        $asset->getPermissionsForUser($this->getUser());
        $asset->setProperties(Element_Service::minimizePropertiesForEditmode($asset->getProperties()));
        $asset->getVersions();
        $asset->getScheduledTasks();
        $asset->idPath = Pimcore_Tool::getIdPathForElement($asset);

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

            if(function_exists("exif_read_data")) {
                $supportedTypes = array(IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM);

                if(in_array(exif_imagetype($asset->getFileSystemPath()),$supportedTypes)) {
                    $exif = @exif_read_data($asset->getFileSystemPath());
                    if(is_array($exif)) {
                        $imageInfo["exif"] = array();
                        foreach($exif as $name => $value) {
                            if((is_string($value) && strlen($value) < 50) || is_numeric($value)) {
                                $imageInfo["exif"][$name] = $value;
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
        $id = $this->_getParam("id");
        $asset = Asset::getById($id);
        if ($asset instanceof Asset) {
            $dependencies = Element_Service::getRequiresDependenciesForFrontend($asset->getDependencies());
            $this->_helper->json($dependencies);
        }
        $this->_helper->json(false);
    }

    public function getRequiredByDependenciesAction() {
        $id = $this->_getParam("id");
        $asset = Asset::getById($id);
        if ($asset instanceof Asset) {
            $dependencies = Element_Service::getRequiredByDependenciesForFrontend($asset->getDependencies());
            $this->_helper->json($dependencies);
        }
        $this->_helper->json(false);
    }

    public function treeGetRootAction() {

        $id = 1;
        if ($this->_getParam("id")) {
            $id = intval($this->_getParam("id"));
        }

        $root = Asset::getById($id);
        $root->getPermissionsForUser($this->getUser());
        if ($root->isAllowed("list")) {
            $this->_helper->json($this->getTreeNodeConfig($root));
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }

    public function treeGetChildsByIdAction() {

        $assets = array();
        $asset = Asset::getById($this->_getParam("node"));

        if ($asset->hasChilds()) {

            $limit = intval($this->_getParam("limit"));
            if (!$this->_getParam("limit")) {
                $limit = 100000000;
            }
            $offset = intval($this->_getParam("start"));


            // get assets
            $childsList = new Asset_List();
            $childsList->setCondition("parentId = ?", $asset->getId());
            $childsList->setLimit($limit);
            $childsList->setOffset($offset);
            $childsList->setOrderKey("filename");
            $childsList->setOrder("asc");

            $childs = $childsList->load();

            foreach ($childs as $childAsset) {

                $childAsset->getPermissionsForUser($this->getUser());

                if ($childAsset->isAllowed("list")) {
                    $assets[] = $this->getTreeNodeConfig($childAsset);
                }
            }
        }


        if ($this->_getParam("limit")) {
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

    public function getTreePermissionsAction() {

        $this->removeViewRenderer();
        $user = User::getById($this->_getParam("user"));

        if ($this->_getParam("xaction") == "update") {
            $data = json_decode($this->_getParam("data"));
            if (!empty($data->id)) {
                $nodes[] = $data;
            } else {
                $nodes = $data;
            }
            //loop through store nodes  = assets to edit
            if (is_array($nodes)) {

                foreach ($nodes as $node) {
                    $asset = Asset::getById($node->id);
                    $parent = Asset::getById($asset->getParentId());
                    $assetPermission = $asset->getPermissionsForUser($user);
                    if ($assetPermission instanceof Asset_Permissions) {
                        $found = true;
                        if (!$node->permissionSet) {
                            //reset permission by deleting it
                            if($assetPermission->getCid() == $asset->getId()){
                                $assetPermission->delete();
                                $permissions = $asset->getPermissions();
                            }
                            break;

                        } else {

                            if ($assetPermission->getCid() != $asset->getId() or $assetPermission->getUser()->getId() != $user->getId()) {
                                //we got a parent's permission create new permission
                                //or we got a usergroup permission, create a new permission for specific user
                                $assetPermission = new Asset_Permissions();
                                $assetPermission->setUser($user);
                                $assetPermission->setUserId($user->getId());
                                $assetPermission->setUsername($user->getUsername());
                                $assetPermission->setCid($asset->getId());
                                $assetPermission->setCpath($asset->getFullPath());
                            }

                            //update asset_permissions
                            $doSave = true;
                            $permissionNames = $assetPermission->getValidPermissionKeys();
                            foreach ($permissionNames as $name) {
                                //check if parent allows list
                                if ($parent) {
                                    $parent->getPermissionsForUser($user);
                                    $parentList = $parent->isAllowed("list");
                                } else {
                                    $parentList = true;
                                }
                                $setterName = "set" . ucfirst($name);

                                if (isset($node->$name) and $node->$name and $parentList) {
                                    $assetPermission->$setterName(true);
                                } else if (isset($node->$name)) {
                                    $assetPermission->$setterName(false);
                                    //if no list permission set all to false
                                    if ($name == "list") {

                                        foreach ($permissionNames as $n) {
                                            $setterName = "set" . ucfirst($n);
                                            $assetPermission->$setterName(false);
                                        }
                                        break;
                                    }
                                }
                            }

                            $assetPermission->save();

                            if ($node->evictChildrenPermissions) {

                                $successorList = new Asset_List();
                                $successorList->setOrderKey("filename");
                                $successorList->setOrder("asc");
                                if ($asset->getParentId() < 1) {
                                    $successorList->setCondition("parentId > 0");

                                } else {
                                    $successorList->setCondition("path like '" . $asset->getFullPath() . "/%'");
                                }
                                logger::debug($successorList->getCondition());
                                $successors = $successorList->load();
                                foreach ($successors as $successor) {

                                    $permission = $successor->getPermissionsForUser($user);
                                    if ($permission->getId() > 0 and $permission->getCid() == $successor->getId()) {
                                        $permission->delete();

                                    }
                                }
                            }
                        }
                    }
                }
                $this->_helper->json(array(
                    "success" => true
                ));
            }
        } else if ($this->_getParam("xaction") == "destroy") {
            //ignore    
        } else {
            //read
            if ($user instanceof User) {
                $userPermissionsNamespace = new Zend_Session_Namespace('assetUserPermissions');
                if (!isset($userPermissionsNamespace->expandedNodes) or $userPermissionsNamespace->currentUser != $user->getId()) {
                    $userPermissionsNamespace->currentUser = $user->getId();
                    $userPermissionsNamespace->expandedNodes = array();
                }
                if (is_numeric($this->_getParam("anode")) and $this->_getParam("anode") > 0) {
                    $node = $this->_getParam("anode");
                    $asset = Asset::getById($node);

                    if ($user instanceof User and $asset->hasChilds()) {

                        $list = new Asset_List();
                        $list->setCondition("parentId = ?", $asset->getId());
                        $list->setOrderKey("filename");
                        $list->setOrder("asc");

                        $childsList = $list->load();
                        $requestedNodes = array();
                        foreach ($childsList as $child) {
                            $requestedNodes[] = $child->getId();
                        }
                        $userPermissionsNamespace->expandedNodes = array_merge($userPermissionsNamespace->expandedNodes, $requestedNodes);
                    }
                } else {
                    $userPermissionsNamespace->expandedNodes = array_merge($userPermissionsNamespace->expandedNodes, array(1));
                }

                //load all nodes which are open in client
                $assetList = new Asset_List();
                $assetList->setOrderKey("filename");
                $assetList->setOrder("asc");
                $queryIds = "'" . implode("','", $userPermissionsNamespace->expandedNodes) . "'";
                $assetList->setCondition("id in (" . $queryIds . ")");
                $o = $assetList->load();
                $total = count($o);
                $assets = array();
                foreach ($o as $asset) {
                    if ($asset->getParentId() > 0) {
                        $parent = Asset::getById($asset->getParentId());
                    } else $parent = null;

                    // get current user permissions
                    $asset->getPermissionsForUser($this->getUser());
                    // only display asset if listing is allowed for the current user
                    if ($asset->isAllowed("list") and $asset->isAllowed("permissions")) {
                        $treeNodePermissionConfig = $this->getTreeNodePermissionConfig($user, $asset, $parent, true);
                        $assets[] = $treeNodePermissionConfig;
                        $tmpAssets[$asset->getId()] = $treeNodePermissionConfig;
                    }
                }

                //only visible nodes and in the order how they should be displayed ... doesn't make sense but seems to fix bug of duplicate nodes
                $assetsForFrontend = array();
                $visible = $this->_getParam("visible");
                if ($visible) {
                    $visibleNodes = explode(",", $visible);
                    foreach ($visibleNodes as $nodeId) {
                        $assetsForFrontend[] = $tmpAssets[$nodeId];
                        if ($nodeId == $this->_getParam("anode") and is_array($requestedNodes)) {
                            foreach ($requestedNodes as $nId) {
                                $assetsForFrontend[] = $tmpAssets[$nId];
                            }
                        }
                    }
                    $assets = $assetsForFrontend;
                }

            }
            $this->_helper->json(array(
                "total" => $total,
                "data" => $assets,
                "success" => true
            ));


        }
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
        $success = $this->addAsset();
        $this->_helper->json(array("success" => $success, "msg" => "Success"));
    }

    protected function addAsset () {
        $success = false;

        $filename = Pimcore_File::getValidFilename($_FILES["Filedata"]["name"]);

        $parentAsset = Asset::getById(intval($this->_getParam("parentId")));
        $parentAsset->getPermissionsForUser($this->getUser());

        // check for dublicate filename
        $filename = $this->getSafeFilename($parentAsset->getFullPath(), $filename);

        if ($parentAsset->isAllowed("create")) {

            $asset = Asset::create($this->_getParam("parentId"), array(
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

        return $success;
    }

    protected function getSafeFilename($targetPath, $filename) {

        $originalFilename = $filename;
        $count = 1;

        if ($targetPath == "/") {
            $targetPath = "";
        }

        while ($found == false) {
            if (Asset::getByPath($targetPath . "/" . $filename)) {
                $filename = str_replace("." . Pimcore_File::getFileExtension($originalFilename), "_" . $count . "." . Pimcore_File::getFileExtension($originalFilename), $originalFilename);
                $count++;
            }
            else {
                return $filename;
            }
        }
    }

    public function replaceAssetAction() {
        $asset = Asset::getById($this->_getParam("id"));
        $asset->setData(file_get_contents($_FILES["Filedata"]["tmp_name"]));
        $asset->setCustomSetting("youtube", null);
        $asset->getPermissionsForUser($this->getUser());

        if ($asset->isAllowed("publish")) {

            try {
                $asset->save();

                $this->_helper->json(array(
                    "id" => $asset->getId(),
                    "path" => $asset->getPath() . $asset->getFilename(),
                    "success" => true
                ));
            } catch (Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
            }


        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }

    public function addFolderAction() {

        $success = false;
        $parentAsset = Asset::getById(intval($this->_getParam("parentId")));
        $parentAsset->getPermissionsForUser($this->getUser());

        $equalAsset = Asset::getByPath($parentAsset->getFullPath() . "/" . $this->_getParam("name"));

        if ($parentAsset->isAllowed("create")) {

            if (!$equalAsset) {
                $asset = Asset::create($this->_getParam("parentId"), array(
                    "filename" => $this->_getParam("name"),
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
        if ($this->_getParam("type") == "childs") {

            $parentAsset = Asset::getById($this->_getParam("id"));

            $list = new Asset_List();
            $list->setCondition("path LIKE '" . $parentAsset->getFullPath() . "/%'");
            $list->setLimit(intval($this->_getParam("amount")));
            $list->setOrderKey("LENGTH(path)", false);
            $list->setOrder("DESC");

            $assets = $list->load();

            $deletedItems = array();
            foreach ($assets as $asset) {
                $deletedItems[] = $asset->getFullPath();
                $asset->delete();
            }

            $this->_helper->json(array("success" => true, "deleted" => $deletedItems));

        } else if($this->_getParam("id")) {
            $asset = Asset::getById($this->_getParam("id"));
            $asset->getPermissionsForUser($this->getUser());

            if ($asset->isAllowed("delete")) {
                Element_Recyclebin_Item::create($asset, $this->getUser());
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
            $asset = Asset::getById($this->_getParam("id"));
            $hasDependency = $asset->getDependencies()->isRequired();
        }
        catch (Exception $e) {
            logger::err("failed to access asset with id: " . $this->_getParam("id"));
        }

        $deleteJobs = array();

        // check for childs
        if($asset instanceof Asset) {
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
                "publish" => $asset->isAllowed("publish")
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
            } catch (Exception $e) {
                Logger::debug("Cannot get dimensions of image, seems to be broken.");
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

    /**
     * @param  User $user
     * @param  Asset $asset
     * @param  Asset $parent
     * @param boolean $expanded
     * @return
     */
    protected function getTreeNodePermissionConfig($user, $child, $parent, $expanded) {

        $userGroup = $user->getParent();
        if ($userGroup instanceof User) {
            $child->getPermissionsForUser($userGroup);

            $lock_list = $child->isAllowed("list");
            $lock_view = $child->isAllowed("view");
            $lock_publish = $child->isAllowed("publish");
            $lock_delete = $child->isAllowed("delete");
            $lock_rename = $child->isAllowed("rename");
            $lock_create = $child->isAllowed("create");
            $lock_permissions = $child->isAllowed("permissions");
            $lock_settings = $child->isAllowed("settings");
            $lock_versions = $child->isAllowed("versions");
            $lock_properties = $child->isAllowed("properties");
        }

        if ($parent instanceof Asset) {
            $parent->getPermissionsForUser($user);
        }
        $assetPermission = $child->getPermissionsForUser($user);

        $generallyAllowed = $user->isAllowed("assets");

        $parentId = (int) $child->getParentId();
        $parentAllowedList = true;
        if ($parent instanceof Asset) {
            $parentAllowedList = $parent->isAllowed("list") and $generallyAllowed;
        }

        $tmpAsset = array(

            "_parent" => $parentId > 0 ? $parentId : null,
            "_id" => (int) $child->getId(),
            "text" => $child->getFilename(),
            "type" => $child->getType(),
            "path" => $child->getFullPath(),
            "basePath" => $child->getPath(),
            "elementType" => "asset",
            "permissionSet" => $assetPermission->getId() > 0 and $assetPermission->getCid() === $child->getId(),
            "list" => $child->isAllowed("list"),
            "list_editable" => $parentAllowedList  and $generallyAllowed and !$lock_list and !$user->isAdmin(),
            "view" => $child->isAllowed("view"),
            "view_editable" => $child->isAllowed("list") and $generallyAllowed and !$lock_view and !$user->isAdmin(),
            "publish" => $child->isAllowed("publish"),
            "publish_editable" => $child->isAllowed("list") and $generallyAllowed and !$lock_publish and !$user->isAdmin(),
            "delete" => $child->isAllowed("delete"),
            "delete_editable" => $child->isAllowed("list") and $generallyAllowed and !$lock_delete and !$user->isAdmin(),
            "rename" => $child->isAllowed("rename"),
            "rename_editable" => $child->isAllowed("list") and $generallyAllowed and !$lock_rename and !$user->isAdmin(),
            "create" => $child->isAllowed("create"),
            "create_editable" => $child->isAllowed("list") and $generallyAllowed and !$lock_create and !$user->isAdmin(),
            "permissions" => $child->isAllowed("permissions"),
            "permissions_editable" => $child->isAllowed("list") and $generallyAllowed and !$lock_permissions and !$user->isAdmin(),
            "settings" => $child->isAllowed("settings"),
            "settings_editable" => $child->isAllowed("list") and $generallyAllowed and !$lock_settings and !$user->isAdmin(),
            "versions" => $child->isAllowed("versions"),
            "versions_editable" => $child->isAllowed("list") and $generallyAllowed and !$lock_versions and !$user->isAdmin(),
            "properties" => $child->isAllowed("properties"),
            "properties_editable" => $child->isAllowed("list") and $generallyAllowed and !$lock_properties and !$user->isAdmin()

        );
        $tmpAsset["expanded"] = $expanded;
        $tmpAsset["_is_leaf"] = $child->hasNoChilds();
        // set type specific settings
        if ($child->getType() == "folder") {
            $tmpAsset["iconCls"] = "pimcore_icon_folder";
        }
        else {
            $tmpAsset["iconCls"] = "pimcore_icon_" . Pimcore_File::getFileExtension($child->getFilename());
        }


        return $tmpAsset;
    }

    public function updateAction() {

        $success = false;
        $allowUpdate = true;

        $updateData = $this->_getAllParams();

        $asset = Asset::getById($this->_getParam("id"));
        $asset->getPermissionsForUser($this->getUser());

        if ($asset->isAllowed("settings")) {

            // if the position is changed the path must be changed || also from the childs
            if ($this->_getParam("parentId")) {
                $parentAsset = Asset::getById($this->_getParam("parentId"));

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
                if ($this->_getParam("filename") || $this->_getParam("parentId")) {
                    $asset->getData();
                }

                if($this->_getParam("filename") != $asset->getFilename() and !$asset->isAllowed("rename")){
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
        } else if ($asset->isAllowed("rename") &&  $this->_getParam("filename")  ) {
            //just rename
            try {
                    $asset->setFilename($this->_getParam("filename"));
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

            $server->exec();
        } catch (Exception $e) {
            Logger::error($e);
        }

        exit;
    }


    public function saveAction() {
        $success = false;
        if ($this->_getParam("id")) {
            $asset = Asset::getById($this->_getParam("id"));

            $asset->getPermissionsForUser($this->getUser());
            if ($asset->isAllowed("publish")) {


                // properties
                if ($this->_getParam("properties")) {
                    $properties = array();
                    $propertiesData = Zend_Json::decode($this->_getParam("properties"));

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
                                logger::err("Can't add " . $propertyName . " to asset " . $asset->getFullPath());
                            }
                        }

                        $asset->setProperties($properties);
                    }
                }

                // permissions
                if ($this->_getParam("permissions")) {
                    $permissions = array();
                    $permissionsData = Zend_Json::decode($this->_getParam("permissions"));

                    if (is_array($permissionsData)) {
                        foreach ($permissionsData as $permissionData) {

                            $permission = new Asset_Permissions();
                            $permission->setValues($permissionData);

                            $permissions[] = $permission;
                        }
                    }

                    $asset->setPermissions($permissions);
                }

                // scheduled tasks
                if ($this->_getParam("scheduler")) {
                    $tasks = array();
                    $tasksData = Zend_Json::decode($this->_getParam("scheduler"));

                    if (!empty($tasksData)) {
                        foreach ($tasksData as $taskData) {
                            $taskData["date"] = strtotime($taskData["date"] . " " . $taskData["time"]);

                            $task = new Schedule_Task($taskData);
                            $tasks[] = $task;
                        }
                    }

                    $asset->setScheduledTasks($tasks);
                }

                if ($this->_getParam("data")) {
                    $asset->setData($this->_getParam("data"));
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
        $version = Version::getById($this->_getParam("id"));
        $version->delete();

        $this->_helper->json(array("success" => true));
    }

    public function publishVersionAction() {

        $version = Version::getById($this->_getParam("id"));
        $asset = $version->loadData();

        $currentAsset = Asset::getById($asset->getId());
        $currentAsset->getPermissionsForUser($this->getUser());
        if ($currentAsset->isAllowed("publish")) {
            try {
                $asset->save();
                $this->_helper->json(array("success" => true));
            } catch (Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
            }


        }

        $this->_helper->json(false);
    }

    public function showVersionAction() {

        $version = Version::getById($this->_getParam("id"));
        $asset = $version->loadData();

        $this->view->asset = $asset;

        $this->render("show-version-" . $asset->getType());
    }

    public function getVersionsAction() {
        if ($this->_getParam("id")) {
            $asset = Asset::getById($this->_getParam("id"));
            $versions = $asset->getVersions();

            $this->_helper->json(array("versions" => $versions));
        }
    }

    public function downloadAction() {
        $asset = Asset::getById($this->_getParam("id"));

        $this->getResponse()->setHeader("Content-Type", $asset->getMimetype(), true);
        $this->getResponse()->setHeader("Content-Disposition", 'attachment; filename="' . $asset->getFilename() . '"');

        echo $asset->getData();

        $this->removeViewRenderer();
    }

    public function getImageThumbnailAction() {

        $image = Asset_Image::getById(intval($this->_getParam("id")));
        $thumbnail = null;

        if ($this->_getParam("thumbnail")) {
            $thumbnail = $image->getThumbnailConfig($this->_getParam("thumbnail"));
        }
        if (!$thumbnail) {
            if($this->_getParam("config")) {
                $thumbnail = $image->getThumbnailConfig(Zend_Json::decode($this->_getParam("config")));
            } else {
                $thumbnail = $image->getThumbnailConfig($this->_getAllParams());
            }
        }
        
        $format = strtolower($thumbnail->getFormat());
        if ($format == "source") {
            $thumbnail->setFormat("PNG");
            $format = "png";
        }

        if ($this->_getParam("cropPercent")) {
            $thumbnail->addItemAt(0,"cropPercent", array(
                "width" => $this->_getParam("cropWidth"),
                "height" => $this->_getParam("cropHeight"),
                "y" => $this->_getParam("cropTop"),
                "x" => $this->_getParam("cropLeft")
            ));

            $hash = md5(serialize($this->_getAllParams()));
            $thumbnail->setName("auto_" . $hash);
        }


        $this->getResponse()->setHeader("Content-Type", "image/" . $format, true);

        if($this->_getParam("download")) {
            $this->getResponse()->setHeader("Content-Disposition", 'attachment; filename="' . $image->getFilename() . '"');
        }
        
        readfile(PIMCORE_DOCUMENT_ROOT . $image->getThumbnail($thumbnail));

        $this->removeViewRenderer();
    }

    public function getPreviewDocumentAction() {
        $asset = Asset::getById($this->_getParam("id"));
        $this->view->asset = $asset;
    }


    public function getPreviewVideoAction() {

        $asset = Asset::getById($this->_getParam("id"));

        $this->view->asset = $asset;

        $youtubeSettings = $asset->getCustomSetting("youtube");

        if (!Asset_Video_Youtube::getYoutubeCredentials()) {
            $this->view->configError = true;
            $this->render("get-preview-video-error");
        } else {

            if (!is_array($youtubeSettings)) {

                $this->view->asset = $asset;

                if (Asset_Video_Youtube::upload($asset)) {
                    $this->render("get-preview-video-display");
                } else {
                    $this->render("get-preview-video-error");
                }

            }
            else if ($youtubeSettings["failed"]) {
                $this->render("get-preview-video-error");
            }
            else {
                $this->render("get-preview-video-display");
            }
        }
    }


    public function saveImagePixlrAction() {

        $asset = Asset::getById($this->_getParam("id"));
        $asset->setData(Pimcore_Tool::getHttpData($this->_getParam("image")));
        $asset->save();

        $this->view->asset = $asset;
    }

    public function getFolderContentPreviewAction() {

        $folder = Asset::getById($this->_getParam("id"));

        $start = 0;
        $limit = 10;

        if ($this->_getParam("limit")) {
            $limit = $this->_getParam("limit");
        }
        if ($this->_getParam("start")) {
            $start = $this->_getParam("start");
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
            if (method_exists($asset, "getThumbnail")) {
                $assets[] = array(
                    "id" => $asset->getId(),
                    "type" => $asset->getType(),
                    "filename" => $asset->getFilename(),
                    "url" => $asset->getThumbnail(array(
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

        if ($this->_getParam("type") == "recursive") {

            $asset = Asset::getById($this->_getParam("sourceId"));

            // first of all the new parent
            $pasteJobs[] = array(array(
                "url" => "/admin/asset/copy",
                "params" => array(
                    "sourceId" => $this->_getParam("sourceId"),
                    "targetId" => $this->_getParam("targetId"),
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
                                "targetParentId" => $this->_getParam("targetId"),
                                "sourceParentId" => $this->_getParam("sourceId"),
                                "type" => "child",
                                "transactionId" => $transactionId
                            )
                        ));
                    }
                }
            }
        }
        else if ($this->_getParam("type") == "child" || $this->_getParam("type") == "replace") {
            // the object itself is the last one
            $pasteJobs[] = array(array(
                "url" => "/admin/asset/copy",
                "params" => array(
                    "sourceId" => $this->_getParam("sourceId"),
                    "targetId" => $this->_getParam("targetId"),
                    "type" => $this->_getParam("type"),
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
        $sourceId = intval($this->_getParam("sourceId"));
        $source = Asset::getById($sourceId);
        $session = new Zend_Session_Namespace("pimcore_copy");

        $targetId = intval($this->_getParam("targetId"));
        if($this->_getParam("targetParentId")) {
            $sourceParent = Asset::getById($this->_getParam("sourceParentId"));

            // this is because the key can get the prefix "_copy" if the target does already exists
            if($session->{$this->_getParam("transactionId")}["parentId"]) {
                $targetParent = Asset::getById($session->{$this->_getParam("transactionId")}["parentId"]);
            } else {
                $targetParent = Asset::getById($this->_getParam("targetParentId"));
            }

            $targetPath = preg_replace("@^".$sourceParent->getFullPath()."@", $targetParent."/", $source->getPath());
            $target = Asset::getByPath($targetPath);
        } else {
            $target = Asset::getById($targetId);
        }

        $target->getPermissionsForUser($this->getUser());
        if ($target->isAllowed("create")) {
            $source = Asset::getById($sourceId);
            if ($source != null) {
                if ($this->_getParam("type") == "child") {
                    $newAsset = $this->_assetService->copyAsChild($target, $source);

                    // this is because the key can get the prefix "_copy" if the target does already exists
                    if($this->_getParam("saveParentId")) {
                        $session->{$this->_getParam("transactionId")}["parentId"] = $newAsset->getId();
                    }
                }
                else if ($this->_getParam("type") == "replace") {
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
        
        $asset = Asset::getById($this->_getParam("id"));

        $archive_name = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/download.zip";
        
        if(is_file($archive_name)) {
            unlink($archive_name);
        }
        
        $archive_folder = $asset->getFileSystemPath();
        
        $zip = new ZipArchive(); 
        if ($zip -> open($archive_name, ZipArchive::CREATE) === TRUE) { 
            $dir = preg_replace('/[\/]{2,}/', '/', $archive_folder."/"); 
            
            $dirs = array($dir); 
            while (count($dirs)) { 
                $dir = current($dirs);
                //$zip->addEmptyDir(str_replace(PIMCORE_ASSET_DIRECTORY,"",$dir)); 
                
                $dh = opendir($dir); 
                while($file = readdir($dh)) { 
                    if ($file != '.' && $file != '..') { 
                        $fullFilePath = $dir.$file;
                        if (is_file($fullFilePath)) {
                            $zip->addFile($fullFilePath, str_replace(PIMCORE_ASSET_DIRECTORY."/","",$fullFilePath)); 
                        } elseif (is_dir($fullFilePath)) {
                            $dirs[] = $fullFilePath."/";
                        } 
                    } 
                } 
                closedir($dh); 
                array_shift($dirs); 
            } 
            
            $zip->close();
        } 
        
        $this->getResponse()->setHeader("Content-Type", "application/zip", true);
        $this->getResponse()->setHeader("Content-Disposition", 'attachment; filename="' . $asset->getFilename() . '.zip"');
        
        echo file_get_contents($archive_name);

        $this->removeViewRenderer();
        
        unlink($archive_name);
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

        $this->importFromFileSystem($tmpDirectory, $this->_getParam("parentId"));

        // cleanup
        recursiveDelete($tmpDirectory);

        $this->_helper->json(array("success" => $success));
    }

    public function importServerAction() {
        $success = true;

        $importDirectory = str_replace("/fileexplorer",PIMCORE_DOCUMENT_ROOT, $this->_getParam("serverPath"));
        if(is_dir($importDirectory)) {
            $this->importFromFileSystem($importDirectory, $this->_getParam("parentId"));
        }

        $this->_helper->json(array("success" => $success));
    }

    protected function importFromFileSystem ($path, $parentId) {

        $assetFolder = Asset::getById($parentId);
        $assetFolder->getPermissionsForUser($this->getUser());
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
