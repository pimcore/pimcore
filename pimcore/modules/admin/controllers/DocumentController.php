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

use Pimcore\Tool\Session;
use Pimcore\File;
use Pimcore\Tool;
use Pimcore\Config;
use Pimcore\Model\Document;
use Pimcore\Model\Version;
use Pimcore\Model\Site;
use Pimcore\Logger;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

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
        $notRestrictedActions = ["doc-types"];
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("documents");
        }

        $this->_documentService = new Document\Service($this->getUser());
    }

    public function getDataByIdAction()
    {
        $document = Document::getById($this->getParam("id"));
        $document = clone $document;


        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $returnValueContainer = new \Pimcore\Model\Tool\Admin\EventDataContainer(object2array($document));
        \Pimcore::getEventManager()->trigger("admin.document.get.preSendData", $this, [
            "document" => $document,
            "returnValueContainer" => $returnValueContainer
        ]);

        if ($document->isAllowed("view")) {
            $this->_helper->json($returnValueContainer->getData());
        }

        $this->_helper->json(["success" => false, "message" => "missing_permission"]);
    }

    public function treeGetChildsByIdAction()
    {
        $document = Document::getById($this->getParam("node"));

        $documents = [];
        $cv = false;
        if ($document->hasChilds()) {
            $limit = intval($this->getParam("limit"));
            if (!$this->getParam("limit")) {
                $limit = 100000000;
            }

            $offset = intval($this->getParam("start"));

            if ($this->getParam("view")) {
                $cv = \Pimcore\Model\Element\Service::getCustomViewById($this->getParam("view"));
            }

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

            $list->setOrderKey(["index", "id"]);
            $list->setOrder(["asc", "asc"]);

            $list->setLimit($limit);
            $list->setOffset($offset);

            \Pimcore\Model\Element\Service::addTreeFilterJoins($cv, $list);
            $childsList = $list->load();

            foreach ($childsList as $childDocument) {
                // only display document if listing is allowed for the current user
                if ($childDocument->isAllowed("list")) {
                    $documents[] = $this->getTreeNodeConfig($childDocument);
                }
            }
        }

        if ($this->getParam("limit")) {
            $this->_helper->json([
                "offset" => $offset,
                "limit" => $limit,
                "total" => $document->getChildAmount($this->getUser()),
                "nodes" => $documents
            ]);
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
            $intendedPath = $parentDocument->getRealFullPath() . "/" . $this->getParam("key");

            if (!Document\Service::pathExists($intendedPath)) {
                $createValues = [
                    "userOwner" => $this->getUser()->getId(),
                    "userModification" => $this->getUser()->getId(),
                    "published" => false
                ];

                $createValues["key"] = \Pimcore\Model\Element\Service::getValidKey($this->getParam("key"), "document");

                // check for a docType
                $docType = Document\DocType::getById(intval($this->getParam("docTypeId")));
                if ($docType) {
                    $createValues["template"] = $docType->getTemplate();
                    $createValues["controller"] = $docType->getController();
                    $createValues["action"] = $docType->getAction();
                    $createValues["module"] = $docType->getModule();
                } elseif ($this->getParam("translationsBaseDocument")) {
                    $translationsBaseDocument = Document::getById($this->getParam("translationsBaseDocument"));
                    $createValues["template"] = $translationsBaseDocument->getTemplate();
                    $createValues["controller"] = $translationsBaseDocument->getController();
                    $createValues["action"] = $translationsBaseDocument->getAction();
                    $createValues["module"] = $translationsBaseDocument->getModule();
                } elseif ($this->getParam("type") == "page" || $this->getParam("type") == "snippet" || $this->getParam("type") == "email") {
                    $createValues["controller"] = Config::getSystemConfig()->documents->default_controller;
                    $createValues["action"] = Config::getSystemConfig()->documents->default_action;
                }

                if ($this->getParam("inheritanceSource")) {
                    $createValues["contentMasterDocumentId"] = $this->getParam("inheritanceSource");
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
                            $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                        }
                        break;
                    default:
                        $classname = "\\Pimcore\\Model\\Document\\" . ucfirst($this->getParam("type"));

                        // this is the fallback for custom document types using prefixes
                        // so we need to check if the class exists first
                        if (!\Pimcore\Tool::classExists($classname)) {
                            $oldStyleClass = "\\Document_" . ucfirst($this->getParam("type"));
                            if (\Pimcore\Tool::classExists($oldStyleClass)) {
                                $classname = $oldStyleClass;
                            }
                        }

                        if (Tool::classExists($classname)) {
                            $document = $classname::create($this->getParam("parentId"), $createValues);
                            try {
                                $document->save();
                                $success = true;
                            } catch (\Exception $e) {
                                $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                            }
                            break;
                        } else {
                            Logger::debug("Unknown document type, can't add [ " . $this->getParam("type") . " ] ");
                        }
                        break;
                }
            } else {
                $errorMessage = "prevented adding a document because document with same path+key [ $intendedPath ] already exists";
                Logger::debug($errorMessage);
            }
        } else {
            $errorMessage = "prevented adding a document because of missing permissions";
            Logger::debug($errorMessage);
        }

        if ($success) {
            if ($this->getParam("translationsBaseDocument")) {
                $translationsBaseDocument = Document::getById($this->getParam("translationsBaseDocument"));

                $properties = $translationsBaseDocument->getProperties();
                $properties = array_merge($properties, $document->getProperties());
                $document->setProperties($properties);
                $document->setProperty("language", "text", $this->getParam("language"));
                $document->save();

                $service = new Document\Service();
                $service->addTranslation($translationsBaseDocument, $document);
            }

            $this->_helper->json([
                "success" => $success,
                "id" => $document->getId(),
                "type" => $document->getType()
            ]);
        } else {
            $this->_helper->json([
                "success" => $success,
                "message" => $errorMessage
            ]);
        }
    }

    public function deleteAction()
    {
        if ($this->getParam("type") == "childs") {
            $parentDocument = Document::getById($this->getParam("id"));

            $list = new Document\Listing();
            $list->setCondition("path LIKE '" . $parentDocument->getRealFullPath() . "/%'");
            $list->setLimit(intval($this->getParam("amount")));
            $list->setOrderKey("LENGTH(path)", false);
            $list->setOrder("DESC");

            $documents = $list->load();

            $deletedItems = [];
            foreach ($documents as $document) {
                $deletedItems[] = $document->getRealFullPath();
                if ($document->isAllowed("delete")) {
                    $document->delete();
                }
            }

            $this->_helper->json(["success" => true, "deleted" => $deletedItems]);
        } elseif ($this->getParam("id")) {
            $document = Document::getById($this->getParam("id"));
            if ($document->isAllowed("delete")) {
                try {
                    $document->delete();
                    $this->_helper->json(["success" => true]);
                } catch (\Exception $e) {
                    Logger::err($e);
                    $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                }
            }
        }

        $this->_helper->json(["success" => false, "message" => "missing_permission"]);
    }

    public function deleteInfoAction()
    {
        $hasDependency = false;

        try {
            $document = Document::getById($this->getParam("id"));
            $hasDependency = $document->getDependencies()->isRequired();
        } catch (\Exception $e) {
            Logger::err("failed to access document with id: " . $this->getParam("id"));
        }

        $deleteJobs = [];

        // check for childs
        if ($document instanceof Document) {
            $deleteJobs[] = [[
                "url" => "/admin/recyclebin/add",
                "params" => [
                    "type" => "document",
                    "id" => $document->getId()
                ]
            ]];

            $hasChilds = $document->hasChilds();
            if (!$hasDependency) {
                $hasDependency = $hasChilds;
            }

            $childs = 0;
            if ($hasChilds) {
                // get amount of childs
                $list = new Document\Listing();
                $list->setCondition("path LIKE '" . $document->getRealFullPath() . "/%'");
                $childs = $list->getTotalCount();

                if ($childs > 0) {
                    $deleteObjectsPerRequest = 5;
                    for ($i = 0; $i < ceil($childs / $deleteObjectsPerRequest); $i++) {
                        $deleteJobs[] = [[
                            "url" => "/admin/document/delete",
                            "params" => [
                                "step" => $i,
                                "amount" => $deleteObjectsPerRequest,
                                "type" => "childs",
                                "id" => $document->getId()
                            ]
                        ]];
                    }
                }
            }

            // the object itself is the last one
            $deleteJobs[] = [[
                "url" => "/admin/document/delete",
                "params" => [
                    "id" => $document->getId()
                ]
            ]];
        }

        // get the element key
        $elementKey = $document->getKey();

        $this->_helper->json([
            "hasDependencies" => $hasDependency,
            "childs" => $childs,
            "deletejobs" => $deleteJobs,
            "elementKey" => $elementKey
        ]);
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
                $this->_helper->json(["success" => false, "message" => "You can't relocate if there's a newer not published version"]);
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

                    $intendedPath = $parentDocument->getRealPath();
                    $pKey = $parentDocument->getKey();
                    if (!empty($pKey)) {
                        $intendedPath .= $parentDocument->getKey() . "/";
                    }

                    $documentWithSamePath = Document::getByPath($intendedPath . $document->getKey());

                    if ($documentWithSamePath != null) {
                        $allowUpdate = false;
                    }

                    if ($document->isLocked()) {
                        $allowUpdate = false;
                    }
                }
            }

            if ($allowUpdate) {
                $blockedVars = ["controller", "action", "module"];

                if (!$document->isAllowed("rename") && $this->getParam("key")) {
                    $blockedVars[] = "key";
                    Logger::debug("prevented renaming document because of missing permissions ");
                }

                foreach ($this->getAllParams() as $key => $value) {
                    if (!in_array($key, $blockedVars)) {
                        $document->setValue($key, $value);
                    }
                }

                // if changed the index change also all documents on the same level
                if ($this->getParam("index") !== null) {
                    $list = new Document\Listing();
                    $list->setCondition("parentId = ? AND id != ?", [$this->getParam("parentId"), $document->getId()]);
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
                    $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                }
            } else {
                $msg = "Prevented moving document, because document with same path+key already exists or the document is locked. ID: " . $document->getId();
                Logger::debug($msg);
                $this->_helper->json(["success" => false, "message" => $msg]);
            }
        } elseif ($document->isAllowed("rename") && $this->getParam("key")) {
            //just rename
            try {
                $document->setKey($this->getParam("key"));
                $document->setUserModification($this->getUser()->getId());
                $document->save();
                $success = true;
            } catch (\Exception $e) {
                $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
            }
        } else {
            Logger::debug("Prevented update document, because of missing permissions.");
        }

        $this->_helper->json(["success" => $success]);
    }

    public function docTypesAction()
    {
        if ($this->getParam("data")) {
            $this->checkPermission("document_types");

            if ($this->getParam("xaction") == "destroy") {
                $data = \Zend_Json::decode($this->getParam("data"));
                $id = $data["id"];
                $type = Document\DocType::getById($id);
                $type->delete();

                $this->_helper->json(["success" => true, "data" => []]);
            } elseif ($this->getParam("xaction") == "update") {
                $data = \Zend_Json::decode($this->getParam("data"));

                // save type
                $type = Document\DocType::getById($data["id"]);

                $type->setValues($data);
                $type->save();

                $this->_helper->json(["data" => $type, "success" => true]);
            } elseif ($this->getParam("xaction") == "create") {
                $data = \Zend_Json::decode($this->getParam("data"));
                unset($data["id"]);

                // save type
                $type = Document\DocType::create();
                $type->setValues($data);

                $type->save();

                $this->_helper->json(["data" => $type, "success" => true]);
            }
        } else {
            // get list of types
            $list = new Document\DocType\Listing();
            $list->load();

            $docTypes = [];
            foreach ($list->getDocTypes() as $type) {
                if ($this->getUser()->isAllowed($type->getId(), "docType")) {
                    $docTypes[] = $type;
                }
            }

            $this->_helper->json(["data" => $docTypes, "success" => true, "total" => count($docTypes)]);
        }

        $this->_helper->json(false);
    }

    public function getDocTypesAction()
    {
        $list = new Document\DocType\Listing();
        if ($this->getParam("type")) {
            $type = $this->getParam("type");
            if (Document\Service::isValidType($type)) {
                $list->setFilter(function ($row) use ($type) {
                    if ($row["type"] == $type) {
                        return true;
                    }

                    return false;
                });
            }
        }
        $list->load();


        $docTypes = [];
        foreach ($list->getDocTypes() as $type) {
            $docTypes[] = $type;
        }

        $this->_helper->json(["docTypes" => $docTypes]);
    }

    public function versionToSessionAction()
    {
        $version = Version::getById($this->getParam("id"));
        $document = $version->loadData();

        Session::useSession(function (AttributeBagInterface $session) use ($document) {
            $key = "document_" . $document->getId();
            $session->set($key, $document);
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
                $document->setPath($currentDocument->getRealPath());
                $document->setUserModification($this->getUser()->getId());

                $document->save();
            } catch (\Exception $e) {
                $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
            }
        }

        $this->_helper->json(["success" => true]);
    }

    public function updateSiteAction()
    {
        $domains = $this->getParam("domains");
        $domains = str_replace(" ", "", $domains);
        $domains = explode("\n", $domains);

        try {
            $site = Site::getByRootId(intval($this->getParam("id")));
        } catch (\Exception $e) {
            $site = Site::create([
                "rootId" => intval($this->getParam("id"))
            ]);
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

        $this->_helper->json(["success" => true]);
    }

    public function copyInfoAction()
    {
        $transactionId = time();
        $pasteJobs = [];

        Session::useSession(function (AttributeBagInterface $session) use ($transactionId) {
            $session->set($transactionId, ["idMapping" => []]);
        }, "pimcore_copy");

        if ($this->getParam("type") == "recursive" || $this->getParam("type") == "recursive-update-references") {
            $document = Document::getById($this->getParam("sourceId"));

            // first of all the new parent
            $pasteJobs[] = [[
                "url" => "/admin/document/copy",
                "params" => [
                    "sourceId" => $this->getParam("sourceId"),
                    "targetId" => $this->getParam("targetId"),
                    "type" => "child",
                    "enableInheritance" => $this->getParam("enableInheritance"),
                    "transactionId" => $transactionId,
                    "saveParentId" => true,
                    "resetIndex" => true
                ]
            ]];


            $childIds = [];
            if ($document->hasChilds()) {
                // get amount of childs
                $list = new Document\Listing();
                $list->setCondition("path LIKE '" . $document->getRealFullPath() . "/%'");
                $list->setOrderKey("LENGTH(path)", false);
                $list->setOrder("ASC");
                $childIds = $list->loadIdList();

                if (count($childIds) > 0) {
                    foreach ($childIds as $id) {
                        $pasteJobs[] = [[
                            "url" => "/admin/document/copy",
                            "params" => [
                                "sourceId" => $id,
                                "targetParentId" => $this->getParam("targetId"),
                                "sourceParentId" => $this->getParam("sourceId"),
                                "type" => "child",
                                "enableInheritance" => $this->getParam("enableInheritance"),
                                "transactionId" => $transactionId
                            ]
                        ]];
                    }
                }
            }


            // add id-rewrite steps
            if ($this->getParam("type") == "recursive-update-references") {
                for ($i = 0; $i < (count($childIds) + 1); $i++) {
                    $pasteJobs[] = [[
                        "url" => "/admin/document/copy-rewrite-ids",
                        "params" => [
                            "transactionId" => $transactionId,
                            "enableInheritance" => $this->getParam("enableInheritance"),
                            "_dc" => uniqid()
                        ]
                    ]];
                }
            }
        } elseif ($this->getParam("type") == "child" || $this->getParam("type") == "replace") {
            // the object itself is the last one
            $pasteJobs[] = [[
                "url" => "/admin/document/copy",
                "params" => [
                    "sourceId" => $this->getParam("sourceId"),
                    "targetId" => $this->getParam("targetId"),
                    "type" => $this->getParam("type"),
                    "enableInheritance" => $this->getParam("enableInheritance"),
                    "transactionId" => $transactionId,
                    "resetIndex" => ($this->getParam("type") == "child")
                ]
            ]];
        }


        $this->_helper->json([
            "pastejobs" => $pasteJobs
        ]);
    }

    public function copyRewriteIdsAction()
    {
        $transactionId = $this->getParam("transactionId");

        $idStore = Session::useSession(function (AttributeBagInterface $session) use ($transactionId) {
            return $session->get($transactionId);
        }, "pimcore_copy");

        if (!array_key_exists("rewrite-stack", $idStore)) {
            $idStore["rewrite-stack"] = array_values($idStore["idMapping"]);
        }

        $id = array_shift($idStore["rewrite-stack"]);
        $document = Document::getById($id);

        if ($document) {
            // create rewriteIds() config parameter
            $rewriteConfig = ["document" => $idStore["idMapping"]];

            $document = Document\Service::rewriteIds($document, $rewriteConfig, [
                "enableInheritance" => ($this->getParam("enableInheritance") == "true") ? true : false
            ]);

            $document->setUserModification($this->getUser()->getId());
            $document->save();
        }

        // write the store back to the session
        Session::useSession(function (AttributeBagInterface $session) use ($transactionId, $idStore) {
            $session->set($transactionId, $idStore);
        }, "pimcore_copy");

        $this->_helper->json([
            "success" => true,
            "id" => $id
        ]);
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


            $targetPath = preg_replace("@^" . $sourceParent->getRealFullPath() . "@", $targetParent . "/", $source->getRealPath());
            $target = Document::getByPath($targetPath);
        } else {
            $target = Document::getById($targetId);
        }

        if ($target instanceof Document) {
            if ($target->isAllowed("create")) {
                if ($source != null) {
                    if ($this->getParam("type") == "child") {
                        $enableInheritance = ($this->getParam("enableInheritance") == "true") ? true : false;
                        $resetIndex = ($this->getParam("resetIndex") == "true") ? true : false;

                        $newDocument = $this->_documentService->copyAsChild($target, $source, $enableInheritance, $resetIndex);
                        $session->{$this->getParam("transactionId")}["idMapping"][(int)$source->getId()] = (int)$newDocument->getId();

                        // this is because the key can get the prefix "_copy" if the target does already exists
                        if ($this->getParam("saveParentId")) {
                            $session->{$this->getParam("transactionId")}["parentId"] = $newDocument->getId();
                        }

                        Session::writeClose();
                    } elseif ($this->getParam("type") == "replace") {
                        $this->_documentService->copyContents($target, $source);
                    }

                    $success = true;
                } else {
                    Logger::error("prevended copy/paste because document with same path+key already exists in this location");
                }
            } else {
                Logger::error("could not execute copy/paste because of missing permissions on target [ " . $targetId . " ]");
                $this->_helper->json(["success" => false, "message" => "missing_permission"]);
            }
        }

        $this->_helper->json(["success" => $success]);
    }


    public function diffVersionsAction()
    {
        $versionFrom = Version::getById($this->getParam("from"));
        $docFrom = $versionFrom->loadData();
        $request = $this->getRequest();

        $sessionName = Tool\Session::getOption("name");
        $prefix = $request->getScheme() . "://" . $request->getHttpHost() . $docFrom->getRealFullPath() . "?pimcore_version=";
        $fromUrl = $prefix . $this->getParam("from") . "&" . $sessionName . "=" . $_COOKIE[$sessionName];
        $toUrl = $prefix . $this->getParam("to") . "&" . $sessionName . "=" . $_COOKIE[$sessionName];

        $fromFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/version-diff-tmp-" . uniqid() . ".png";
        $toFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/version-diff-tmp-" . uniqid() . ".png";
        $diffFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/version-diff-tmp-" . uniqid() . ".png";

        if (\Pimcore\Image\HtmlToImage::isSupported() && class_exists("Imagick")) {
            \Pimcore\Image\HtmlToImage::convert($fromUrl, $fromFile);
            \Pimcore\Image\HtmlToImage::convert($toUrl, $toFile);

            $image1 = new Imagick($fromFile);
            $image2 = new Imagick($toFile);

            if ($image1->getImageWidth() == $image2->getImageWidth() && $image1->getImageHeight() == $image2->getImageHeight()) {
                $result = $image1->compareImages($image2, Imagick::METRIC_MEANSQUAREERROR);
                $result[0]->setImageFormat("png");

                $result[0]->writeImage($diffFile);
                $result[0]->clear();
                $result[0]->destroy();

                $this->view->image = base64_encode(file_get_contents($diffFile));
                unlink($diffFile);
            } else {
                $this->view->image1 = base64_encode(file_get_contents($fromFile));
                $this->view->image2 = base64_encode(file_get_contents($toFile));
            }

            // cleanup
            $image1->clear();
            $image1->destroy();
            $image2->clear();
            $image2->destroy();

            unlink($fromFile);
            unlink($toFile);
        } else {
            $this->renderScript("document/diff-versions-unsupported.php");
        }
    }

    /**
     * @param $element Document
     * @return array
     */
    protected function getTreeNodeConfig($element)
    {
        $childDocument = $element;

        $tmpDocument = [
            "id" => $childDocument->getId(),
            "idx" => intval($childDocument->getIndex()),
            "text" => $childDocument->getKey(),
            "type" => $childDocument->getType(),
            "path" => $childDocument->getRealFullPath(),
            "basePath" => $childDocument->getRealPath(),
            "locked" => $childDocument->isLocked(),
            "lockOwner" => $childDocument->getLocked() ? true : false,
            "published" => $childDocument->isPublished(),
            "elementType" => "document",
            "leaf" => true,
            "permissions" => [
                "view" => $childDocument->isAllowed("view"),
                "remove" => $childDocument->isAllowed("delete"),
                "settings" => $childDocument->isAllowed("settings"),
                "rename" => $childDocument->isAllowed("rename"),
                "publish" => $childDocument->isAllowed("publish"),
                "unpublish" => $childDocument->isAllowed("unpublish")
            ]
        ];

        // add icon
        $tmpDocument["iconCls"] = "pimcore_icon_" . $childDocument->getType();
        $tmpDocument["expandable"] = $childDocument->hasChilds();
        $tmpDocument["loaded"] = !$childDocument->hasChilds();

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
        } elseif ($childDocument->getType() == "folder" || $childDocument->getType() == "link" || $childDocument->getType() == "hardlink") {
            $tmpDocument["leaf"] = false;
            $tmpDocument["expanded"] = $childDocument->hasNoChilds();

            if ($childDocument->hasNoChilds() && $childDocument->getType() == "folder") {
                $tmpDocument["iconCls"] = "pimcore_icon_folder";
            }
            $tmpDocument["permissions"]["create"] = $childDocument->isAllowed("create");
        } elseif (method_exists($childDocument, "getTreeNodeConfig")) {
            $tmp = $childDocument->getTreeNodeConfig();
            $tmpDocument = array_merge($tmpDocument, $tmp);
        }

        $tmpDocument["qtipCfg"] = [
            "title" => "ID: " . $childDocument->getId(),
            "text" => "Type: " . $childDocument->getType()
        ];

        if ($site) {
            $tmpDocument["qtipCfg"]["text"] .= "<br>" . $this->view->translate("site_id") . ": " . $site->getId();
        }

        // PREVIEWS temporary disabled, need's to be optimized some time
        if ($childDocument instanceof Document\Page && Config::getSystemConfig()->documents->generatepreview) {
            $thumbnailFile = PIMCORE_TEMPORARY_DIRECTORY . "/document-page-previews/document-page-screenshot-" . $childDocument->getId() . ".jpg";

            // only if the thumbnail exists and isn't out of time
            if (file_exists($thumbnailFile) && filemtime($thumbnailFile) > ($childDocument->getModificationDate() - 20)) {
                $thumbnailPath = str_replace(PIMCORE_DOCUMENT_ROOT, "", $thumbnailFile);
                $tmpDocument["thumbnail"] = $thumbnailPath;
            }
        }

        if ($childDocument instanceof Document\Page) {
            $tmpDocument["url"] = $childDocument->getFullPath();
            $site = Tool\Frontend::getSiteForDocument($childDocument);
            if ($site) {
                $tmpDocument["url"] = "http://" . $site->getMainDomain() . preg_replace("@^" . $site->getRootPath() . "/?@", "/", $childDocument->getRealFullPath());
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
            $this->_helper->json([
                "id" => $doc->getId(),
                "type" => $doc->getType()
            ]);
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
            $nodeConfig = $this->getSeoNodeConfig($root);

            $this->_helper->json($nodeConfig);
        }

        $this->_helper->json(["success" => false, "message" => "missing_permission"]);
    }


    public function seopanelTreeAction()
    {
        $this->checkPermission("seo_document_editor");

        $document = Document::getById($this->getParam("node"));

        $documents = [];
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
                    $list->setCondition("path LIKE ? and type = ?", [$childDocument->getRealFullPath() . "/%", "page"]);

                    if ($childDocument instanceof Document\Page || $list->getTotalCount() > 0) {
                        $documents[] = $this->getSeoNodeConfig($childDocument);
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
        if (Tool::classExists($class)) {
            $new = new $class;

            // overwrite internal store to avoid "duplicate full path" error
            \Zend_Registry::set("document_" . $document->getId(), $new);

            $props = get_object_vars($document);
            foreach ($props as $name => $value) {
                if (property_exists($new, $name)) {
                    $new->$name = $value;
                }
            }

            if ($type == "hardlink" || $type == "folder") {
                // remove navigation settings
                foreach (["name", "title", "target", "exclude", "class", "anchor", "parameters", "relation", "accesskey", "tabindex"] as $propertyName) {
                    $new->removeProperty("navigation_" . $propertyName);
                }
            }

            $new->setType($type);
            $new->save();
        }

        $this->_helper->json(["success" => true]);
    }


    public function translationDetermineParentAction()
    {
        $success = false;
        $targetPath = null;

        $document = Document::getById($this->getParam("id"));
        if ($document) {
            $service = new Document\Service;
            $translations = $service->getTranslations($document->getParent());
            if (isset($translations[$this->getParam("language")])) {
                $targetDocument = Document::getById($translations[$this->getParam("language")]);
                $targetPath = $targetDocument->getRealFullPath();
                $success = true;
            }
        }


        $this->_helper->json([
            "success" => $success,
            "targetPath" => $targetPath
        ]);
    }

    public function translationAddAction()
    {
        $sourceDocument = Document::getById($this->getParam("sourceId"));
        $targetDocument = Document::getByPath($this->getParam("targetPath"));

        if ($sourceDocument && $targetDocument) {
            $service = new Document\Service;
            $service->addTranslation($sourceDocument, $targetDocument);
        }

        $this->_helper->json([
            "success" => true
        ]);
    }

    public function translationCheckLanguageAction()
    {
        $success = false;
        $language = null;

        $document = Document::getByPath($this->getParam("path"));
        if ($document) {
            $language = $document->getProperty("language");
            if ($language) {
                $success = true;
            }
        }

        $this->_helper->json([
            "success" => $success,
            "language" => $language
        ]);
    }

    /**
     * @param $document
     * @return array
     */
    private function getSeoNodeConfig($document)
    {
        $nodeConfig = $this->getTreeNodeConfig($document);

        if (method_exists($document, "getTitle") && method_exists($document, "getDescription")) {

            // anaylze content
            $nodeConfig["links"]         = 0;
            $nodeConfig["externallinks"] = 0;
            $nodeConfig["h1"]            = 0;
            $nodeConfig["h1_text"]       = "";
            $nodeConfig["hx"]            = 0;
            $nodeConfig["imgwithalt"]    = 0;
            $nodeConfig["imgwithoutalt"] = 0;

            $title       = null;
            $description = null;

            try {

                // cannot use the rendering service from Document\Service::render() because of singleton's ...
                // $content = Document\Service::render($childDocument, array("pimcore_admin" => true, "pimcore_preview" => true), true);

                $request = $this->getRequest();

                $contentUrl = $request->getScheme() . "://" . $request->getHttpHost() . $document->getFullPath();
                $content    = Tool::getHttpData($contentUrl, ["pimcore_preview" => true, "pimcore_admin" => true, "_dc" => time()]);

                if ($content) {
                    include_once("simple_html_dom.php");
                    $html = str_get_html($content);
                    if ($html) {
                        $nodeConfig["links"]         = count($html->find("a"));
                        $nodeConfig["externallinks"] = count($html->find("a[href^=http]"));
                        $nodeConfig["h1"]            = count($html->find("h1"));

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
                Logger::debug($e);
            }

            if (!$title) {
                $title = $document->getTitle();
            }
            if (!$description) {
                $description = $document->getDescription();
            }

            $nodeConfig["title"]       = $title;
            $nodeConfig["description"] = $description;

            $nodeConfig["title_length"]       = mb_strlen($title);
            $nodeConfig["description_length"] = mb_strlen($description);

            $qtip = "";
            /** @var \Zend_Translate_Adapter $t */
            $t = \Zend_Registry::get("Zend_Translate");
            if (mb_strlen($title) > 80) {
                $nodeConfig["cls"] = "pimcore_document_seo_warning";
                $qtip .= $t->translate("The title is too long, it should have 5 to 80 characters.<br>");
            }

            if (mb_strlen($title) < 5) {
                $nodeConfig["cls"] = "pimcore_document_seo_warning";
                $qtip .= $t->translate("The title is too short, it should have 5 to 80 characters.<br>");
            }

            if (mb_strlen($description) > 180) {
                $nodeConfig["cls"] = "pimcore_document_seo_warning";
                $qtip .= $t->translate("The description is too long, it should have 20 to 180 characters.<br>");
            }

            if (mb_strlen($description) < 20) {
                $nodeConfig["cls"] = "pimcore_document_seo_warning";
                $qtip .= $t->translate("The description is too short, it should have 20 to 180 characters.<br>");
            }

            if ($nodeConfig["h1"] != 1) {
                $nodeConfig["cls"] = "pimcore_document_seo_warning";
                $qtip .= sprintf($t->translate("The document should have one h1, but has %s.<br>"), $nodeConfig["h1"]);
            }

            if ($nodeConfig["hx"] < 1) {
                $nodeConfig["cls"] = "pimcore_document_seo_warning";
                $qtip .= $t->translate("The document should some headlines other than h1, but has none.<br>");
            }

            if ($qtip) {
                $nodeConfig["qtip"] = $qtip;
            }
        }

        return $nodeConfig;
    }
}
