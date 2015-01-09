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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

use Pimcore\Tool\Session;
use Pimcore\Tool;
Use Pimcore\Config;
use Pimcore\Model\Document;
use Pimcore\Model\Version;
use Pimcore\Model\Site;

class Admin_DocumentController extends \Pimcore\Controller\Action\Admin\Element
{

    /**
     * @var Document\Service
     */
    protected $_documentService;

    public function init()
    {
        parent::init();

        // check permissions
        $notRestrictedActions = array("doc-types");
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("documents");
        }

        $this->_documentService = new Document\Service($this->getUser());
    }

    public function getDataByIdAction()
    {

        $document = Document::getById($this->getParam("id"));
        if ($document->isAllowed("view")) {
            $this->_helper->json($document);
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }

    public function treeGetChildsByIdAction()
    {

        $document = Document::getById($this->getParam("node"));

        $documents = array();
        if ($document->hasChilds()) {
            $limit = intval($this->getParam("limit"));
            if (!$this->getParam("limit")) {
                $limit = 100000000;
            }
            $offset = intval($this->getParam("start"));

            $list = new Document\Listing();
            if ($this->getUser()->isAdmin()) {
                $list->setCondition("parentId = ? ", $document->getId());
            } else {

                $userIds = $this->getUser()->getRoles();
                $userIds[] = $this->getUser()->getId();
                $list->setCondition("parentId = ? and
                                        (
                                        (select list from users_workspaces_document where userId in (" . implode(',', $userIds) . ") and LOCATE(CONCAT(path,`key`),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                        or
                                        (select list from users_workspaces_document where userId in (" . implode(',', $userIds) . ") and LOCATE(cpath,CONCAT(path,`key`))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                        )", $document->getId());
            }
            $list->setOrderKey("index");
            $list->setOrder("asc");
            $list->setLimit($limit);
            $list->setOffset($offset);

            $childsList = $list->load();

            foreach ($childsList as $childDocument) {
                // only display document if listing is allowed for the current user
                if ($childDocument->isAllowed("list")) {
                    $documents[] = $this->getTreeNodeConfig($childDocument);
                }
            }
        }

        if ($this->getParam("limit")) {
            $this->_helper->json(array(
                "total" => $document->getChildAmount($this->getUser()),
                "nodes" => $documents
            ));
        } else {
            $this->_helper->json($documents);
        }

        $this->_helper->json(false);
    }

    public function addAction()
    {

        $success = false;
        $errorMessage = "";

        // check for permission
        $parentDocument = Document::getById(intval($this->getParam("parentId")));
        if ($parentDocument->isAllowed("create")) {
            $intendedPath = $parentDocument->getFullPath() . "/" . $this->getParam("key");

            if (!Document\Service::pathExists($intendedPath)) {

                $createValues = array(
                    "userOwner" => $this->getUser()->getId(),
                    "userModification" => $this->getUser()->getId(),
                    "published" => false
                );

                $createValues["key"] = $this->getParam("key");

                // check for a docType
                $docType = Document\DocType::getById(intval($this->getParam("docTypeId")));
                if ($docType) {
                    $createValues["template"] = $docType->getTemplate();
                    $createValues["controller"] = $docType->getController();
                    $createValues["action"] = $docType->getAction();
                    $createValues["module"] = $docType->getModule();
                } else if ($this->getParam("type") == "page" || $this->getParam("type") == "snippet" || $this->getParam("type") == "email") {
                    $createValues["controller"] = Config::getSystemConfig()->documents->default_controller;
                    $createValues["action"] = Config::getSystemConfig()->documents->default_action;
                }

                switch ($this->getParam("type")) {
                    case "page":
                        $document = Document\Page::create($this->getParam("parentId"), $createValues, false);
                        $document->setTitle($this->getParam('title', null));
                        $document->setProperty("navigation_name", "text", $this->getParam('name', null), false);
                        $document->save();
                        $success = true;
                        break;
                    case "snippet":
                        $document = Document\Snippet::create($this->getParam("parentId"), $createValues);
                        $success = true;
                        break;
                    case "email": //ckogler
                        $document = Document\Email::create($this->getParam("parentId"), $createValues);
                        $success = true;
                        break;
                    case "link":
                        $document = Document\Link::create($this->getParam("parentId"), $createValues);
                        $success = true;
                        break;
                    case "hardlink":
                        $document = Document\Hardlink::create($this->getParam("parentId"), $createValues);
                        $success = true;
                        break;
                    case "folder":
                        $document = Document\Folder::create($this->getParam("parentId"), $createValues);
                        $document->setPublished(true);
                        try {
                            $document->save();
                            $success = true;
                        } catch (\Exception $e) {
                            $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                        }
                        break;
                    default:
                        $classname = "\\Pimcore\\Model\\Document\\" . ucfirst($this->getParam("type"));

                        // this is the fallback for custom document types using prefixes
                        // so we need to check if the class exists first
                        if(!\Pimcore\Tool::classExists($classname)) {
                            $oldStyleClass = "\\Document_" . ucfirst($this->getParam("type"));
                            if(\Pimcore\Tool::classExists($oldStyleClass)) {
                                $classname = $oldStyleClass;
                            }
                        }

                        if (class_exists($classname)) {
                            $document = $classname::create($this->getParam("parentId"), $createValues);
                            try {
                                $document->save();
                                $success = true;
                            } catch (\Exception $e) {
                                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                            }
                            break;
                        } else {
                            \Logger::debug("Unknown document type, can't add [ " . $this->getParam("type") . " ] ");
                        }
                        break;
                }
            } else {
                $errorMessage = "prevented adding a document because document with same path+key [ $intendedPath ] already exists";
                \Logger::debug($errorMessage);
            }
        } else {
            $errorMessage = "prevented adding a document because of missing permissions";
            \Logger::debug($errorMessage);
        }

        if ($success) {
            $this->_helper->json(array(
                "success" => $success,
                "id" => $document->getId(),
                "type" => $document->getType()
            ));
        } else {
            $this->_helper->json(array(
                "success" => $success,
                "message" => $errorMessage
            ));
        }


    }

    public function deleteAction()
    {
        if ($this->getParam("type") == "childs") {

            $parentDocument = Document::getById($this->getParam("id"));

            $list = new Document\Listing();
            $list->setCondition("path LIKE '" . $parentDocument->getFullPath() . "/%'");
            $list->setLimit(intval($this->getParam("amount")));
            $list->setOrderKey("LENGTH(path)", false);
            $list->setOrder("DESC");

            $documents = $list->load();

            $deletedItems = array();
            foreach ($documents as $document) {
                $deletedItems[] = $document->getFullPath();
                if ($document->isAllowed("delete")) {
                    $document->delete();
                }
            }

            $this->_helper->json(array("success" => true, "deleted" => $deletedItems));

        } else if ($this->getParam("id")) {
            $document = Document::getById($this->getParam("id"));
            if ($document->isAllowed("delete")) {
                try {
                    $document->delete();
                    $this->_helper->json(array("success" => true));
                } catch (\Exception $e) {
                    \Logger::err($e);
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }
            }
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }

    public function deleteInfoAction()
    {
        $hasDependency = false;

        try {
            $document = Document::getById($this->getParam("id"));
            $hasDependency = $document->getDependencies()->isRequired();
        } catch (\Exception $e) {
            \Logger::err("failed to access document with id: " . $this->getParam("id"));
        }

        $deleteJobs = array();

        // check for childs
        if ($document instanceof Document) {

            $deleteJobs[] = array(array(
                "url" => "/admin/recyclebin/add",
                "params" => array(
                    "type" => "document",
                    "id" => $document->getId()
                )
            ));

            $hasChilds = $document->hasChilds();
            if (!$hasDependency) {
                $hasDependency = $hasChilds;
            }

            $childs = 0;
            if ($hasChilds) {
                // get amount of childs
                $list = new Document\Listing();
                $list->setCondition("path LIKE '" . $document->getFullPath() . "/%'");
                $childs = $list->getTotalCount();

                if ($childs > 0) {
                    $deleteObjectsPerRequest = 5;
                    for ($i = 0; $i < ceil($childs / $deleteObjectsPerRequest); $i++) {
                        $deleteJobs[] = array(array(
                            "url" => "/admin/document/delete",
                            "params" => array(
                                "step" => $i,
                                "amount" => $deleteObjectsPerRequest,
                                "type" => "childs",
                                "id" => $document->getId()
                            )
                        ));
                    }
                }
            }

            // the object itself is the last one
            $deleteJobs[] = array(array(
                "url" => "/admin/document/delete",
                "params" => array(
                    "id" => $document->getId()
                )
            ));
        }

        $this->_helper->json(array(
            "hasDependencies" => $hasDependency,
            "childs" => $childs,
            "deletejobs" => $deleteJobs
        ));
    }

    public function updateAction()
    {

        $success = false;
        $allowUpdate = true;

        $document = Document::getById($this->getParam("id"));

        // this prevents the user from renaming, relocating (actions in the tree) if the newest version isn't the published one
        // the reason is that otherwise the content of the newer not published version will be overwritten
        if ($document instanceof Document\PageSnippet) {
            $latestVersion = $document->getLatestVersion();
            if ($latestVersion && $latestVersion->getData()->getModificationDate() != $document->getModificationDate()) {
                $this->_helper->json(array("success" => false, "message" => "You can't relocate if there's a newer not published version"));
            }
        }

        if ($document->isAllowed("settings")) {

            // if the position is changed the path must be changed || also from the childs
            if ($this->getParam("parentId")) {
                $parentDocument = Document::getById($this->getParam("parentId"));

                //check if parent is changed
                if ($document->getParentId() != $parentDocument->getId()) {

                    if (!$parentDocument->isAllowed("create")) {
                        throw new \Exception("Prevented moving document - no create permission on new parent ");
                    }

                    $intendedPath = $parentDocument->getPath();
                    $pKey = $parentDocument->getKey();
                    if (!empty($pKey)) {
                        $intendedPath .= $parentDocument->getKey() . "/";
                    }

                    $documentWithSamePath = Document::getByPath($intendedPath . $document->getKey());

                    if ($documentWithSamePath != null) {
                        $allowUpdate = false;
                    }

                    if($document->isLocked()) {
                        $allowUpdate = false;
                    }
                }
            }

            if ($allowUpdate) {
                $blockedVars = array("controller", "action", "module");

                if (!$document->isAllowed("rename") && $this->getParam("key")) {
                    $blockedVars[] = "key";
                    \Logger::debug("prevented renaming document because of missing permissions ");
                }

                foreach ($this->getAllParams() as $key => $value) {
                    if (!in_array($key, $blockedVars)) {
                        $document->setValue($key, $value);
                    }
                }

                // if changed the index change also all documents on the same level
                if ($this->getParam("index") !== null) {
                    $list = new Document\Listing();
                    $list->setCondition("parentId = ? AND id != ?", array($this->getParam("parentId"), $document->getId()));
                    $list->setOrderKey("index");
                    $list->setOrder("asc");
                    $childsList = $list->load();

                    $count = 0;
                    foreach ($childsList as $child) {
                        if ($count == intval($this->getParam("index"))) {
                            $count++;
                        }
                        $child->saveIndex($count);
                        $count++;
                    }
                }

                $document->setUserModification($this->getUser()->getId());
                try {
                    $document->save();
                    $success = true;
                } catch (\Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }
            } else {
                $msg = "Prevented moving document, because document with same path+key already exists or the document is locked. ID: " . $document->getId();
                \Logger::debug($msg);
                $this->_helper->json(array("success" => false, "message" => $msg));
            }
        } else if ($document->isAllowed("rename") && $this->getParam("key")) {
            //just rename
            try {
                $document->setKey($this->getParam("key"));
                $document->setUserModification($this->getUser()->getId());
                $document->save();
                $success = true;
            } catch (\Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
            }
        } else {
            \Logger::debug("Prevented update document, because of missing permissions.");
        }

        $this->_helper->json(array("success" => $success));
    }

    public function docTypesAction()
    {

        if ($this->getParam("data")) {

            $this->checkPermission("document_types");

            if ($this->getParam("xaction") == "destroy") {

                $id = \Zend_Json::decode($this->getParam("data"));

                $type = Document\DocType::getById($id);
                $type->delete();

                $this->_helper->json(array("success" => true, "data" => array()));
            } else if ($this->getParam("xaction") == "update") {

                $data = \Zend_Json::decode($this->getParam("data"));

                // save type
                $type = Document\DocType::getById($data["id"]);

                $type->setValues($data);
                $type->save();

                $this->_helper->json(array("data" => $type, "success" => true));
            } else if ($this->getParam("xaction") == "create") {
                $data = \Zend_Json::decode($this->getParam("data"));
                unset($data["id"]);

                // save type
                $type = Document\DocType::create();
                $type->setValues($data);

                $type->save();

                $this->_helper->json(array("data" => $type, "success" => true));
            }
        } else {
            // get list of types
            $list = new Document\DocType\Listing();

            if ($this->getParam("sort")) {
                $list->setOrderKey($this->getParam("sort"));
                $list->setOrder($this->getParam("dir"));
            }

            $list->load();

            $docTypes = array();
            foreach ($list->getDocTypes() as $type) {
                if ($this->getUser()->isAllowed($type->getId(), "docType")) {
                    $docTypes[] = $type;
                }
            }

            $this->_helper->json(array("data" => $docTypes, "success" => true, "total" => count($docTypes)));
        }

        $this->_helper->json(false);
    }

    public function getDocTypesAction()
    {

        $list = new Document\DocType\Listing();
        if ($this->getParam("type")) {
            $type = $this->getParam("type");
            if (Document\Service::isValidType($type)) {
                $list->setCondition("type = ?", $type);
            }
        }
        $list->setOrderKey(array("priority", "name"));
        $list->setOrder(array("desc", "ASC"));
        $list->load();


        $docTypes = array();
        foreach ($list->getDocTypes() as $type) {
            $docTypes[] = $type;
        }

        $this->_helper->json(array("docTypes" => $docTypes));
    }

    public function getPathForIdAction()
    {

        $document = Document::getById($this->getParam("id"));
        die($document->getPath() . $document->getKey());
    }

    public function versionUpdateAction()
    {

        $data = \Zend_Json::decode($this->getParam("data"));

        $version = Version::getById($data["id"]);
        $version->setPublic($data["public"]);
        $version->setNote($data["note"]);
        $version->save();

        $this->_helper->json(array("success" => true));
    }

    public function versionToSessionAction()
    {

        $version = Version::getById($this->getParam("id"));
        $document = $version->loadData();

        Session::useSession(function ($session) use ($document) {
            $key = "document_" . $document->getId();
            $session->$key = $document;
        }, "pimcore_documents");

        $this->removeViewRenderer();
    }

    public function publishVersionAction()
    {

        $this->versionToSessionAction();

        $version = Version::getById($this->getParam("id"));
        $document = $version->loadData();

        $currentDocument = Document::getById($document->getId());
        if ($currentDocument->isAllowed("publish")) {
            $document->setPublished(true);
            try {

                $document->setKey($currentDocument->getKey());
                $document->setPath($currentDocument->getPath());
                $document->setUserModification($this->getUser()->getId());

                $document->save();
            } catch (\Exception $e) {
                $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
            }
        }

        $this->_helper->json(array("success" => true));
    }

    public function updateSiteAction()
    {

        $domains = $this->getParam("domains");
        $domains = str_replace(" ", "", $domains);
        $domains = explode("\n", $domains);

        try {
            $site = Site::getByRootId(intval($this->getParam("id")));
        } catch (\Exception $e) {
            $site = Site::create(array(
                "rootId" => intval($this->getParam("id"))
            ));
        }

        $site->setDomains($domains);
        $site->setMainDomain($this->getParam("mainDomain"));
        $site->setErrorDocument($this->getParam("errorDocument"));
        $site->setRedirectToMainDomain(($this->getParam("redirectToMainDomain") == "true") ? true : false);
        $site->save();

        $site->setRootDocument(null); // do not send the document to the frontend
        $this->_helper->json($site);
    }

    public function removeSiteAction()
    {

        $site = Site::getByRootId(intval($this->getParam("id")));
        $site->delete();

        $this->_helper->json(array("success" => true));
    }

    public function copyInfoAction()
    {

        $transactionId = time();
        $pasteJobs = array();

        Session::useSession(function ($session) use ($transactionId) {
            $session->$transactionId = array("idMapping" => array());
        }, "pimcore_copy");

        if ($this->getParam("type") == "recursive" || $this->getParam("type") == "recursive-update-references") {

            $document = Document::getById($this->getParam("sourceId"));

            // first of all the new parent
            $pasteJobs[] = array(array(
                "url" => "/admin/document/copy",
                "params" => array(
                    "sourceId" => $this->getParam("sourceId"),
                    "targetId" => $this->getParam("targetId"),
                    "type" => "child",
                    "enableInheritance" => $this->getParam("enableInheritance"),
                    "transactionId" => $transactionId,
                    "saveParentId" => true
                )
            ));


            $childIds = array();
            if ($document->hasChilds()) {
                // get amount of childs
                $list = new Document\Listing();
                $list->setCondition("path LIKE '" . $document->getFullPath() . "/%'");
                $list->setOrderKey("LENGTH(path)", false);
                $list->setOrder("ASC");
                $childIds = $list->loadIdList();

                if (count($childIds) > 0) {
                    foreach ($childIds as $id) {
                        $pasteJobs[] = array(array(
                            "url" => "/admin/document/copy",
                            "params" => array(
                                "sourceId" => $id,
                                "targetParentId" => $this->getParam("targetId"),
                                "sourceParentId" => $this->getParam("sourceId"),
                                "type" => "child",
                                "enableInheritance" => $this->getParam("enableInheritance"),
                                "transactionId" => $transactionId
                            )
                        ));
                    }
                }
            }


            // add id-rewrite steps
            if ($this->getParam("type") == "recursive-update-references") {
                for ($i = 0; $i < (count($childIds) + 1); $i++) {
                    $pasteJobs[] = array(array(
                        "url" => "/admin/document/copy-rewrite-ids",
                        "params" => array(
                            "transactionId" => $transactionId,
                            "enableInheritance" => $this->getParam("enableInheritance"),
                            "_dc" => uniqid()
                        )
                    ));
                }
            }
        } else if ($this->getParam("type") == "child" || $this->getParam("type") == "replace") {
            // the object itself is the last one
            $pasteJobs[] = array(array(
                "url" => "/admin/document/copy",
                "params" => array(
                    "sourceId" => $this->getParam("sourceId"),
                    "targetId" => $this->getParam("targetId"),
                    "type" => $this->getParam("type"),
                    "enableInheritance" => $this->getParam("enableInheritance"),
                    "transactionId" => $transactionId
                )
            ));
        }


        $this->_helper->json(array(
            "pastejobs" => $pasteJobs
        ));
    }

    public function copyRewriteIdsAction()
    {

        $transactionId = $this->getParam("transactionId");

        $idStore = Session::useSession(function ($session) use ($transactionId) {
            return $session->$transactionId;
        }, "pimcore_copy");

        if (!array_key_exists("rewrite-stack", $idStore)) {
            $idStore["rewrite-stack"] = array_values($idStore["idMapping"]);
        }

        $id = array_shift($idStore["rewrite-stack"]);
        $document = Document::getById($id);

        if ($document) {
            // create rewriteIds() config parameter
            $rewriteConfig = array("document" => $idStore["idMapping"]);

            $document = Document\Service::rewriteIds($document, $rewriteConfig, array(
                "enableInheritance" => ($this->getParam("enableInheritance") == "true") ? true : false
            ));

            $document->setUserModification($this->getUser()->getId());
            $document->save();
        }

        // write the store back to the session
        Session::useSession(function ($session) use ($transactionId, $idStore) {
            $session->$transactionId = $idStore;
        }, "pimcore_copy");

        $this->_helper->json(array(
            "success" => true,
            "id" => $id
        ));
    }

    public function copyAction()
    {
        $success = false;
        $sourceId = intval($this->getParam("sourceId"));
        $source = Document::getById($sourceId);
        $session = Session::get("pimcore_copy");

        $targetId = intval($this->getParam("targetId"));
        if ($this->getParam("targetParentId")) {
            $sourceParent = Document::getById($this->getParam("sourceParentId"));

            // this is because the key can get the prefix "_copy" if the target does already exists
            if ($session->{$this->getParam("transactionId")}["parentId"]) {
                $targetParent = Document::getById($session->{$this->getParam("transactionId")}["parentId"]);
            } else {
                $targetParent = Document::getById($this->getParam("targetParentId"));
            }


            $targetPath = preg_replace("@^" . $sourceParent->getFullPath() . "@", $targetParent . "/", $source->getPath());
            $target = Document::getByPath($targetPath);
        } else {
            $target = Document::getById($targetId);
        }

        if ($target instanceof Document) {
            if ($target->isAllowed("create")) {
                if ($source != null) {
                    if ($this->getParam("type") == "child") {
                        $enableInheritance = ($this->getParam("enableInheritance") == "true") ? true : false;
                        $newDocument = $this->_documentService->copyAsChild($target, $source, $enableInheritance);
                        $session->{$this->getParam("transactionId")}["idMapping"][(int)$source->getId()] = (int)$newDocument->getId();

                        // this is because the key can get the prefix "_copy" if the target does already exists
                        if ($this->getParam("saveParentId")) {
                            $session->{$this->getParam("transactionId")}["parentId"] = $newDocument->getId();
                        }

                        Session::writeClose();
                    } else if ($this->getParam("type") == "replace") {
                        $this->_documentService->copyContents($target, $source);
                    }

                    $success = true;
                } else {
                    \Logger::error("prevended copy/paste because document with same path+key already exists in this location");
                }
            } else {
                \Logger::error("could not execute copy/paste because of missing permissions on target [ " . $targetId . " ]");
                $this->_helper->json(array("success" => false, "message" => "missing_permission"));
            }
        }

        $this->_helper->json(array("success" => $success));
    }


    public function diffVersionsAction()
    {

        include_once 'DaisyDiff/HTMLDiff.php';
        include_once 'simple_html_dom.php';

        $versionFrom = Version::getById($this->getParam("from"));
        $versionTo = Version::getById($this->getParam("to"));

        $docFrom = $versionFrom->loadData();
        $docTo = $versionTo->loadData();

        // unlock the current session to access the version files
        session_write_close();

        $request = $this->getRequest();

        $fromSourceHtml = Tool::getHttpData($request->getScheme() . "://" . $request->getHttpHost() . $docFrom->getFullPath() . "?pimcore_version=" . $this->getParam("from") . "&pimcore_admin_sid=" . $_COOKIE["pimcore_admin_sid"]);
        $toSourceHtml = Tool::getHttpData($request->getScheme() . "://" . $request->getHttpHost() . $docTo->getFullPath() . "?pimcore_version=" . $this->getParam("to") . "&pimcore_admin_sid=" . $_COOKIE["pimcore_admin_sid"]);

        $fromSource = str_get_html($fromSourceHtml);
        $toSource = str_get_html($toSourceHtml);

        if($fromSource && $toSource) {
            if ($docFrom instanceof Document\Page) {
                $from = $fromSource->find("body", 0);
                $to = $toSource->find("body", 0);
            } else {
                $from = $fromSource;
                $to = $toSource;
            }

            $diff = new HTMLDiffer();
            $text = $diff->htmlDiff($from, $to);


            if ($docFrom instanceof Document\Page) {
                $fromSource->find("head", 0)->innertext = $fromSource->find("head", 0)->innertext . '<link rel="stylesheet" type="text/css" href="/pimcore/static/css/daisydiff.css" />';
                $fromSource->find("body", 0)->innertext = $text;

                echo $fromSource;
            } else {
                echo '<link rel="stylesheet" type="text/css" href="/pimcore/static/css/daisydiff.css" />';
                echo $text;
            }
        } else {
            echo "Unable to create diff";
        }


        $this->removeViewRenderer();
    }


    protected function getTreeNodeConfig($childDocument)
    {

        $tmpDocument = array(
            "id" => $childDocument->getId(),
            "text" => $childDocument->getKey(),
            "type" => $childDocument->getType(),
            "path" => $childDocument->getFullPath(),
            "basePath" => $childDocument->getPath(),
            "locked" => $childDocument->isLocked(),
            "lockOwner" => $childDocument->getLocked() ? true : false,
            "published" => $childDocument->isPublished(),
            "elementType" => "document",
            "leaf" => true,
            "permissions" => array(
                "view" => $childDocument->isAllowed("view"),
                "remove" => $childDocument->isAllowed("delete"),
                "settings" => $childDocument->isAllowed("settings"),
                "rename" => $childDocument->isAllowed("rename"),
                "publish" => $childDocument->isAllowed("publish"),
                "unpublish" => $childDocument->isAllowed("unpublish")
            )
        );

        // add icon
        $tmpDocument["iconCls"] = "pimcore_icon_" . $childDocument->getType();

        // set type specific settings
        if ($childDocument->getType() == "page") {
            $tmpDocument["leaf"] = false;
            $tmpDocument["expanded"] = $childDocument->hasNoChilds();
            $tmpDocument["permissions"]["create"] = $childDocument->isAllowed("create");
            $tmpDocument["iconCls"] = "pimcore_icon_page";

            // test for a site
            try {
                $site = Site::getByRootId($childDocument->getId());
                $tmpDocument["iconCls"] = "pimcore_icon_site";
                unset($site->rootDocument);
                $tmpDocument["site"] = $site;
            } catch (\Exception $e) {
            }
        } else if ($childDocument->getType() == "folder" || $childDocument->getType() == "link" || $childDocument->getType() == "hardlink") {
            $tmpDocument["leaf"] = false;
            $tmpDocument["expanded"] = $childDocument->hasNoChilds();

            if ($childDocument->hasNoChilds() && $childDocument->getType() == "folder") {
                $tmpDocument["iconCls"] = "pimcore_icon_folder";
            }
            $tmpDocument["permissions"]["create"] = $childDocument->isAllowed("create");
        } else if (method_exists($childDocument, "getTreeNodeConfig")) {
            $tmp = $childDocument->getTreeNodeConfig();
            $tmpDocument = array_merge($tmpDocument, $tmp);
        }

        $tmpDocument["qtipCfg"] = array(
            "title" => "ID: " . $childDocument->getId(),
            "text" => "Type: " . $childDocument->getType()
        );

        // PREVIEWS temporary disabled, need's to be optimized some time
        if ($childDocument instanceof Document\Page && Config::getSystemConfig()->documents->generatepreview) {
            $thumbnailFile = PIMCORE_TEMPORARY_DIRECTORY . "/document-page-previews/document-page-screenshot-" . $childDocument->getId() . ".jpg";

            // only if the thumbnail exists and isn't out of time
            if (file_exists($thumbnailFile) && filemtime($thumbnailFile) > ($childDocument->getModificationDate() - 20)) {
                $thumbnailPath = str_replace(PIMCORE_DOCUMENT_ROOT, "", $thumbnailFile);
                $tmpDocument["thumbnail"] = $thumbnailPath;
            }
        }

        $tmpDocument["cls"] = "";

        if (!$childDocument->isPublished()) {
            $tmpDocument["cls"] .= "pimcore_unpublished ";
        }

        if ($childDocument->isLocked()) {
            $tmpDocument["cls"] .= "pimcore_treenode_locked ";
        }
        if ($childDocument->getLocked()) {
            $tmpDocument["cls"] .= "pimcore_treenode_lockOwner ";
        }

        return $tmpDocument;
    }

    public function getIdForPathAction()
    {

        if ($doc = Document::getByPath($this->getParam("path"))) {
            $this->_helper->json(array(
                "id" => $doc->getId(),
                "type" => $doc->getType()
            ));
        } else {
            $this->_helper->json(false);
        }

        $this->removeViewRenderer();
    }


    /**
     * SEO PANEL
     */
    public function seopanelTreeRootAction()
    {

        $this->checkPermission("seo_document_editor");

        $root = Document::getById(1);
        if ($root->isAllowed("list")) {

            $nodeConfig = $this->getTreeNodeConfig($root);
            $nodeConfig["title"] = $root->getTitle();
            $nodeConfig["description"] = $root->getDescription();

            $this->_helper->json($nodeConfig);
        }

        $this->_helper->json(array("success" => false, "message" => "missing_permission"));
    }


    public function seopanelTreeAction()
    {

        $this->checkPermission("seo_document_editor");

        $document = Document::getById($this->getParam("node"));

        $documents = array();
        if ($document->hasChilds()) {

            $list = new Document\Listing();
            $list->setCondition("parentId = ?", $document->getId());
            $list->setOrderKey("index");
            $list->setOrder("asc");

            $childsList = $list->load();

            foreach ($childsList as $childDocument) {
                // only display document if listing is allowed for the current user
                if ($childDocument->isAllowed("list")) {

                    $list = new Document\Listing();
                    $list->setCondition("path LIKE ? and type = ?", array($childDocument->getFullPath() . "/%", "page"));

                    if ($childDocument instanceof Document\Page || $list->getTotalCount() > 0) {
                        $nodeConfig = $this->getTreeNodeConfig($childDocument);

                        if (method_exists($childDocument, "getTitle") && method_exists($childDocument, "getDescription")) {

                            // anaylze content
                            $nodeConfig["links"] = 0;
                            $nodeConfig["externallinks"] = 0;
                            $nodeConfig["h1"] = 0;
                            $nodeConfig["h1_text"] = "";
                            $nodeConfig["hx"] = 0;
                            $nodeConfig["imgwithalt"] = 0;
                            $nodeConfig["imgwithoutalt"] = 0;

                            $title = null;
                            $description = null;

                            try {

                                // cannot use the rendering service from Document\Service::render() because of singleton's ...
                                // $content = Document\Service::render($childDocument, array("pimcore_admin" => true, "pimcore_preview" => true), true);

                                $request = $this->getRequest();

                                $contentUrl = $request->getScheme() . "://" . $request->getHttpHost() . $childDocument->getFullPath();
                                $content = Tool::getHttpData($contentUrl, array(
                                    "pimcore_preview" => true,
                                    "pimcore_admin" => true,
                                    "_dc" => time()
                                ));

                                if ($content) {
                                    include_once("simple_html_dom.php");
                                    $html = str_get_html($content);
                                    if ($html) {
                                        $nodeConfig["links"] = count($html->find("a"));
                                        $nodeConfig["externallinks"] = count($html->find("a[href^=http]"));
                                        $nodeConfig["h1"] = count($html->find("h1"));

                                        $h1 = $html->find("h1", 0);
                                        if ($h1) {
                                            $nodeConfig["h1_text"] = strip_tags($h1->innertext);
                                        }

                                        $title = $html->find("title", 0);
                                        if ($title) {
                                            $title = html_entity_decode(trim(strip_tags($title->innertext)), null, "UTF-8");
                                        }

                                        $description = $html->find("meta[name=description]", 0);
                                        if ($description) {
                                            $description = html_entity_decode(trim(strip_tags($description->content)), null, "UTF-8");
                                        }

                                        $nodeConfig["hx"] = count($html->find("h2,h2,h4,h5"));

                                        $images = $html->find("img");
                                        if ($images) {
                                            foreach ($images as $image) {
                                                $alt = $image->alt;
                                                if (empty($alt)) {
                                                    $nodeConfig["imgwithoutalt"]++;
                                                } else {
                                                    $nodeConfig["imgwithalt"]++;
                                                }
                                            }
                                        }

                                        $html->clear();
                                        unset($html);
                                    }
                                }
                            } catch (\Exception $e) {
                                \Logger::debug($e);
                            }

                            if (!$title) {
                                $title = $childDocument->getTitle();
                            }
                            if (!$description) {
                                $description = $childDocument->getDescription();
                            }

                            $nodeConfig["title"] = $title;
                            $nodeConfig["description"] = $description;

                            $nodeConfig["title_length"] = mb_strlen($title);
                            $nodeConfig["description_length"] = mb_strlen($description);

                            if (mb_strlen($title) > 80
                                || mb_strlen($title) < 5
                                || mb_strlen($description) > 180
                                || mb_strlen($description) < 20
                                || $nodeConfig["h1"] != 1
                                || $nodeConfig["hx"] < 1
                            ) {
                                $nodeConfig["cls"] = "pimcore_document_seo_warning";
                            }
                        }

                        $documents[] = $nodeConfig;
                    }
                }
            }
        }

        $this->_helper->json($documents);
    }


    public function convertAction()
    {

        $document = Document::getById($this->getParam("id"));

        $type = $this->getParam("type");
        $class = "\\Pimcore\\Model\\Document\\" . ucfirst($type);
        if (class_exists($class)) {
            $new = new $class;

            // overwrite internal store to avoid "duplicate full path" error
            \Zend_Registry::set("document_" . $document->getId(), $new);

            $props = get_object_vars($document);
            foreach ($props as $name => $value) {
                if (property_exists($new, $name)) {
                    $new->$name = $value;
                }
            }

            if($type == "hardlink" || $type == "folder") {
                // remove navigation settings
                foreach (["name", "title", "target", "exclude", "class", "anchor", "parameters", "relation", "accesskey", "tabindex"] as $propertyName) {
                    $new->removeProperty("navigation_" . $propertyName);
                }
            }

            $new->setType($type);
            $new->save();
        }

        $this->_helper->json(array("success" => true));
    }
}
