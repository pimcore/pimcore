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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\Document;

use Pimcore\Bundle\AdminBundle\Controller\Admin\ElementControllerBase;
use Pimcore\Bundle\AdminBundle\Controller\Traits\DocumentTreeConfigTrait;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Db;
use Pimcore\Event\Admin\ElementAdminStyleEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Image\HtmlToImage;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Redirect;
use Pimcore\Model\Site;
use Pimcore\Model\Version;
use Pimcore\Routing\Dynamic\DocumentRouteHandler;
use Pimcore\Tool;
use Pimcore\Tool\Frontend;
use Pimcore\Tool\Session;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/document")
 */
class DocumentController extends ElementControllerBase implements EventedControllerInterface
{
    use DocumentTreeConfigTrait;

    /**
     * @var Document\Service
     */
    protected $_documentService;

    /**
     * @Route("/tree-get-root", name="pimcore_admin_document_document_treegetroot", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function treeGetRootAction(Request $request)
    {
        return parent::treeGetRootAction($request);
    }

    /**
     * @Route("/delete-info", name="pimcore_admin_document_document_deleteinfo", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteInfoAction(Request $request)
    {
        return parent::deleteInfoAction($request);
    }

    /**
     * @Route("/get-data-by-id", name="pimcore_admin_document_document_getdatabyid", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        $document = Document::getById($request->get('id'));

        if (!$document) {
            throw $this->createNotFoundException('Document not found');
        }

        $document = clone $document;
        $data = $document->getObjectVars();
        $data['versionDate'] = $document->getModificationDate();

        $data['php'] = [
            'classes' => array_merge([get_class($document)], array_values(class_parents($document))),
            'interfaces' => array_values(class_implements($document)),
        ];

        $this->addAdminStyle($document, ElementAdminStyleEvent::CONTEXT_EDITOR, $data);

        $event = new GenericEvent($this, [
            'data' => $data,
            'document' => $document,
        ]);
        $eventDispatcher->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);
        $data = $event->getArgument('data');

        if ($document->isAllowed('view')) {
            return $this->adminJson($data);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @Route("/tree-get-childs-by-id", name="pimcore_admin_document_document_treegetchildsbyid", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function treeGetChildsByIdAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        $allParams = array_merge($request->request->all(), $request->query->all());

        $filter = $request->get('filter');
        $limit = intval($allParams['limit'] ?? 100000000);
        $offset = intval($allParams['start'] ?? 0);

        if (!is_null($filter)) {
            if (substr($filter, -1) != '*') {
                $filter .= '*';
            }
            $filter = str_replace('*', '%', $filter);
            $limit = 100;
            $offset = 0;
        }

        $document = Document::getById($allParams['node']);
        if (!$document) {
            throw $this->createNotFoundException('Document was not found');
        }

        $documents = [];
        $cv = false;
        if ($document->hasChildren()) {
            if ($allParams['view']) {
                $cv = \Pimcore\Model\Element\Service::getCustomViewById($allParams['view']);
            }

            $db = Db::get();

            $list = new Document\Listing();
            if ($this->getAdminUser()->isAdmin()) {
                $condition = 'parentId =  ' . $db->quote($document->getId());
            } else {
                $userIds = $this->getAdminUser()->getRoles();
                $userIds[] = $this->getAdminUser()->getId();
                $condition = 'parentId = ' . $db->quote($document->getId()) . ' AND
                (
                    (SELECT list FROM users_workspaces_document WHERE userId IN (' . implode(',', $userIds) . ') AND LOCATE(CONCAT(path,`key`),cpath)=1  ORDER BY LENGTH(cpath) DESC, FIELD(userId, '. $this->getAdminUser()->getId() .') DESC, list DESC LIMIT 1)=1
                    or
                    (SELECT list FROM users_workspaces_document WHERE userId IN (' . implode(',', $userIds) . ') AND LOCATE(cpath,CONCAT(path,`key`))=1  ORDER BY LENGTH(cpath) DESC, FIELD(userId, '. $this->getAdminUser()->getId() .') DESC, list DESC LIMIT 1)=1
                )';
            }

            if ($filter) {
                $db = Db::get();

                $condition = '(' . $condition . ')' . ' AND CAST(documents.key AS CHAR CHARACTER SET utf8) COLLATE utf8_general_ci LIKE ' . $db->quote($filter);
            }

            $list->setCondition($condition);

            $list->setOrderKey(['index', 'id']);
            $list->setOrder(['asc', 'asc']);

            $list->setLimit($limit);
            $list->setOffset($offset);

            \Pimcore\Model\Element\Service::addTreeFilterJoins($cv, $list);

            $beforeListLoadEvent = new GenericEvent($this, [
                'list' => $list,
                'context' => $allParams,
            ]);

            $eventDispatcher->dispatch(AdminEvents::DOCUMENT_LIST_BEFORE_LIST_LOAD, $beforeListLoadEvent);
            /** @var Document\Listing $list */
            $list = $beforeListLoadEvent->getArgument('list');

            $childsList = $list->load();

            foreach ($childsList as $childDocument) {
                // only display document if listing is allowed for the current user
                if ($childDocument->isAllowed('list')) {
                    $documents[] = $this->getTreeNodeConfig($childDocument);
                }
            }
        }

        //Hook for modifying return value - e.g. for changing permissions based on document data
        $event = new GenericEvent($this, [
            'documents' => $documents,
        ]);
        $eventDispatcher->dispatch(AdminEvents::DOCUMENT_TREE_GET_CHILDREN_BY_ID_PRE_SEND_DATA, $event);
        $documents = $event->getArgument('documents');

        if ($allParams['limit']) {
            return $this->adminJson([
                'offset' => $offset,
                'limit' => $limit,
                'total' => $document->getChildAmount($this->getAdminUser()),
                'nodes' => $documents,
                'filter' => $request->get('filter') ? $request->get('filter') : '',
                'inSearch' => intval($request->get('inSearch')),
            ]);
        } else {
            return $this->adminJson($documents);
        }
    }

    /**
     * @Route("/add", name="pimcore_admin_document_document_add", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAction(Request $request)
    {
        $success = false;
        $errorMessage = '';

        // check for permission
        $parentDocument = Document::getById(intval($request->get('parentId')));
        $document = null;
        if ($parentDocument->isAllowed('create')) {
            $intendedPath = $parentDocument->getRealFullPath() . '/' . $request->get('key');

            if (!Document\Service::pathExists($intendedPath)) {
                $createValues = [
                    'userOwner' => $this->getAdminUser()->getId(),
                    'userModification' => $this->getAdminUser()->getId(),
                    'published' => false,
                ];

                $createValues['key'] = \Pimcore\Model\Element\Service::getValidKey($request->get('key'), 'document');

                // check for a docType
                $docType = Document\DocType::getById(intval($request->get('docTypeId')));
                if ($docType) {
                    $createValues['template'] = $docType->getTemplate();
                    $createValues['controller'] = $docType->getController();
                    $createValues['action'] = $docType->getAction();
                    $createValues['module'] = $docType->getModule();
                } elseif ($request->get('translationsBaseDocument')) {
                    $translationsBaseDocument = Document\PageSnippet::getById($request->get('translationsBaseDocument'));
                    $createValues['template'] = $translationsBaseDocument->getTemplate();
                    $createValues['controller'] = $translationsBaseDocument->getController();
                    $createValues['action'] = $translationsBaseDocument->getAction();
                    $createValues['module'] = $translationsBaseDocument->getModule();
                } elseif ($request->get('type') == 'page' || $request->get('type') == 'snippet' || $request->get('type') == 'email') {
                    $createValues += Tool::getRoutingDefaults();
                }

                if ($request->get('inheritanceSource')) {
                    $createValues['contentMasterDocumentId'] = $request->get('inheritanceSource');
                }

                switch ($request->get('type')) {
                    case 'page':
                        $document = Document\Page::create($request->get('parentId'), $createValues, false);
                        $document->setTitle($request->get('title', null));
                        $document->setProperty('navigation_name', 'text', $request->get('name', null), false, false);
                        $document->save();
                        $success = true;
                        break;
                    case 'snippet':
                        $document = Document\Snippet::create($request->get('parentId'), $createValues);
                        $success = true;
                        break;
                    case 'email': //ckogler
                        $document = Document\Email::create($request->get('parentId'), $createValues);
                        $success = true;
                        break;
                    case 'link':
                        $document = Document\Link::create($request->get('parentId'), $createValues);
                        $success = true;
                        break;
                    case 'hardlink':
                        $document = Document\Hardlink::create($request->get('parentId'), $createValues);
                        $success = true;
                        break;
                    case 'folder':
                        $document = Document\Folder::create($request->get('parentId'), $createValues);
                        $document->setPublished(true);
                        try {
                            $document->save();
                            $success = true;
                        } catch (\Exception $e) {
                            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                        }
                        break;
                    default:
                        $classname = '\\Pimcore\\Model\\Document\\' . ucfirst($request->get('type'));

                        // this is the fallback for custom document types using prefixes
                        // so we need to check if the class exists first
                        if (!\Pimcore\Tool::classExists($classname)) {
                            $oldStyleClass = '\\Document_' . ucfirst($request->get('type'));
                            if (\Pimcore\Tool::classExists($oldStyleClass)) {
                                $classname = $oldStyleClass;
                            }
                        }

                        if (Tool::classExists($classname)) {
                            $document = $classname::create($request->get('parentId'), $createValues);
                            try {
                                $document->save();
                                $success = true;
                            } catch (\Exception $e) {
                                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                            }
                            break;
                        } else {
                            Logger::debug("Unknown document type, can't add [ " . $request->get('type') . ' ] ');
                        }
                        break;
                }
            } else {
                $errorMessage = "prevented adding a document because document with same path+key [ $intendedPath ] already exists";
                Logger::debug($errorMessage);
            }
        } else {
            $errorMessage = 'prevented adding a document because of missing permissions';
            Logger::debug($errorMessage);
        }

        if ($success && $document instanceof Document) {
            if ($request->get('translationsBaseDocument')) {
                $translationsBaseDocument = Document::getById($request->get('translationsBaseDocument'));

                $properties = $translationsBaseDocument->getProperties();
                $properties = array_merge($properties, $document->getProperties());
                $document->setProperties($properties);
                $document->setProperty('language', 'text', $request->get('language'));
                $document->save();

                $service = new Document\Service();
                $service->addTranslation($translationsBaseDocument, $document);
            }

            return $this->adminJson([
                'success' => $success,
                'id' => $document->getId(),
                'type' => $document->getType(),
            ]);
        }

        return $this->adminJson([
            'success' => $success,
            'message' => $errorMessage,
        ]);
    }

    /**
     * @Route("/delete", name="pimcore_admin_document_document_delete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request)
    {
        if ($request->get('type') == 'childs') {
            $parentDocument = Document::getById($request->get('id'));

            $list = new Document\Listing();
            $list->setCondition('path LIKE ?', [$list->escapeLike($parentDocument->getRealFullPath()) . '/%']);
            $list->setLimit(intval($request->get('amount')));
            $list->setOrderKey('LENGTH(path)', false);
            $list->setOrder('DESC');

            $documents = $list->load();

            $deletedItems = [];
            foreach ($documents as $document) {
                $deletedItems[$document->getId()] = $document->getRealFullPath();
                if ($document->isAllowed('delete') && !$document->isLocked()) {
                    $document->delete();
                }
            }

            return $this->adminJson(['success' => true, 'deleted' => $deletedItems]);
        } elseif ($request->get('id')) {
            $document = Document::getById($request->get('id'));
            if ($document && $document->isAllowed('delete')) {
                try {
                    if ($document->isLocked()) {
                        throw new \Exception('prevented deleting document, because it is locked: ID: ' . $document->getId());
                    }
                    $document->delete();

                    return $this->adminJson(['success' => true]);
                } catch (\Exception $e) {
                    Logger::err($e);

                    return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                }
            }
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @Route("/update", name="pimcore_admin_document_document_update", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function updateAction(Request $request)
    {
        $success = false;
        $allowUpdate = true;

        $document = Document::getById($request->get('id'));

        $oldPath = $document->getDao()->getCurrentFullPath();
        $oldDocument = Document::getById($document->getId(), true);

        // this prevents the user from renaming, relocating (actions in the tree) if the newest version isn't the published one
        // the reason is that otherwise the content of the newer not published version will be overwritten
        if ($document instanceof Document\PageSnippet) {
            $latestVersion = $document->getLatestVersion();
            if ($latestVersion && $latestVersion->getData()->getModificationDate() != $document->getModificationDate()) {
                return $this->adminJson(['success' => false, 'message' => "You can't rename or relocate if there's a newer not published version"]);
            }
        }

        if ($document->isAllowed('settings')) {
            // if the position is changed the path must be changed || also from the children
            if ($request->get('parentId')) {
                $parentDocument = Document::getById($request->get('parentId'));

                //check if parent is changed
                if ($document->getParentId() != $parentDocument->getId()) {
                    if (!$parentDocument->isAllowed('create')) {
                        throw new \Exception('Prevented moving document - no create permission on new parent ');
                    }

                    $intendedPath = $parentDocument->getRealPath();
                    $pKey = $parentDocument->getKey();
                    if (!empty($pKey)) {
                        $intendedPath .= $parentDocument->getKey() . '/';
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
                $blockedVars = ['controller', 'action', 'module'];

                if (!$document->isAllowed('rename') && $request->get('key')) {
                    $blockedVars[] = 'key';
                    Logger::debug('prevented renaming document because of missing permissions ');
                }

                $updateData = array_merge($request->request->all(), $request->query->all());

                foreach ($updateData as $key => $value) {
                    if (!in_array($key, $blockedVars)) {
                        $document->setValue($key, $value);
                    }
                }

                $document->setUserModification($this->getAdminUser()->getId());
                try {
                    $document->save();

                    if ($request->get('index') !== null) {
                        $this->updateIndexesOfDocumentSiblings($document, $request->get('index'));
                    }

                    $success = true;

                    $this->createRedirectForFormerPath($request, $document, $oldPath, $oldDocument);
                } catch (\Exception $e) {
                    return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                }
            } else {
                $msg = 'Prevented moving document, because document with same path+key already exists or the document is locked. ID: ' . $document->getId();
                Logger::debug($msg);

                return $this->adminJson(['success' => false, 'message' => $msg]);
            }
        } elseif ($document->isAllowed('rename') && $request->get('key')) {
            //just rename
            try {
                $document->setKey($request->get('key'));
                $document->setUserModification($this->getAdminUser()->getId());
                $document->save();
                $success = true;

                $this->createRedirectForFormerPath($request, $document, $oldPath, $oldDocument);
            } catch (\Exception $e) {
                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            Logger::debug('Prevented update document, because of missing permissions.');
        }

        return $this->adminJson(['success' => $success]);
    }

    private function createRedirectForFormerPath(Request $request, Document $document, string $oldPath, Document $oldDocument)
    {
        if ($document instanceof Document\Page || $document instanceof Document\Hardlink) {
            if ($request->get('create_redirects') === 'true' && $this->getAdminUser()->isAllowed('redirects')) {
                if ($oldPath && $oldPath != $document->getRealFullPath()) {
                    $sourceSite = null;
                    if ($oldDocument) {
                        $sourceSite = Frontend::getSiteForDocument($oldDocument);
                        if ($sourceSite) {
                            $oldPath = preg_replace('@^' . preg_quote($sourceSite->getRootPath(), '@') . '@', '', $oldPath);
                        }
                    }

                    $targetSite = Frontend::getSiteForDocument($document);

                    $this->doCreateRedirectForFormerPath($oldPath, $document->getId(), $sourceSite, $targetSite);

                    if ($document->hasChildren()) {
                        $list = new Document\Listing();
                        $list->setCondition('path LIKE :path', [
                            'path' => $list->escapeLike($document->getRealFullPath()) . '/%',
                        ]);

                        $childrenList = $list->loadIdPathList();

                        $count = 0;

                        foreach ($childrenList as $child) {
                            $source = preg_replace('@^' . preg_quote($document->getRealFullPath(), '@') . '@', $oldDocument, $child['path']);
                            if ($sourceSite) {
                                $source = preg_replace('@^' . preg_quote($sourceSite->getRootPath(), '@') . '@', '', $source);
                            }

                            $target = $child['id'];

                            $this->doCreateRedirectForFormerPath($source, $target, $sourceSite, $targetSite);

                            $count++;
                            if ($count % 10 === 0) {
                                \Pimcore::collectGarbage();
                            }
                        }
                    }
                }
            }
        }
    }

    private function doCreateRedirectForFormerPath(string $source, int $targetId, ?Site $sourceSite, ?Site $targetSite)
    {
        $redirect = new Redirect();
        $redirect->setType(Redirect::TYPE_AUTO_CREATE);
        $redirect->setRegex(false);
        $redirect->setTarget($targetId);
        $redirect->setSource($source);
        $redirect->setStatusCode(301);
        $redirect->setExpiry(time() + 86400 * 365); // this entry is removed automatically after 1 year

        if ($sourceSite) {
            $redirect->setSourceSite($sourceSite->getId());
        }

        if ($targetSite) {
            $redirect->setTargetSite($targetSite->getId());
        }

        $redirect->save();
    }

    /**
     * @param Document $document
     * @param int $newIndex
     */
    protected function updateIndexesOfDocumentSiblings(Document $document, $newIndex)
    {
        $updateLatestVersionIndex = function ($document, $newIndex) {
            if ($document instanceof Document\PageSnippet && $latestVersion = $document->getLatestVersion()) {
                $document = $latestVersion->loadData();
                $document->setIndex($newIndex);
                $latestVersion->save();
            }
        };

        // if changed the index change also all documents on the same level
        $newIndex = intval($newIndex);
        $document->saveIndex($newIndex);

        $list = new Document\Listing();
        $list->setCondition('parentId = ? AND id != ?', [$document->getParentId(), $document->getId()]);
        $list->setOrderKey('index');
        $list->setOrder('asc');
        $childsList = $list->load();

        $count = 0;
        foreach ($childsList as $child) {
            if ($count == $newIndex) {
                $count++;
            }
            $child->saveIndex($count);
            $updateLatestVersionIndex($child, $count);
            $count++;
        }
    }

    /**
     * @Route("/doc-types", name="pimcore_admin_document_document_doctypesget", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function docTypesGetAction(Request $request)
    {
        // get list of types
        $list = new Document\DocType\Listing();
        $list->load();

        $docTypes = [];
        foreach ($list->getDocTypes() as $type) {
            if ($this->getAdminUser()->isAllowed($type->getId(), 'docType')) {
                $docTypes[] = $type->getObjectVars();
            }
        }

        return $this->adminJson(['data' => $docTypes, 'success' => true, 'total' => count($docTypes)]);
    }

    /**
     * @Route("/doc-types", name="pimcore_admin_document_document_doctypes", methods={"PUT", "POST","DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function docTypesAction(Request $request)
    {
        if ($request->get('data')) {
            $this->checkPermission('document_types');

            if ($request->get('xaction') == 'destroy') {
                $data = $this->decodeJson($request->get('data'));
                $id = $data['id'];
                $type = Document\DocType::getById($id);
                $type->delete();

                return $this->adminJson(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $data = $this->decodeJson($request->get('data'));

                // save type
                $type = Document\DocType::getById($data['id']);

                $type->setValues($data);
                $type->save();

                return $this->adminJson(['data' => $type->getObjectVars(), 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                $data = $this->decodeJson($request->get('data'));
                unset($data['id']);

                // save type
                $type = Document\DocType::create();
                $type->setValues($data);

                $type->save();

                return $this->adminJson(['data' => $type->getObjectVars(), 'success' => true]);
            }
        }

        return $this->adminJson(false);
    }

    /**
     * @Route("/get-doc-types", name="pimcore_admin_document_document_getdoctypes", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDocTypesAction(Request $request)
    {
        $list = new Document\DocType\Listing();
        if ($request->get('type')) {
            $type = $request->get('type');
            if (Document\Service::isValidType($type)) {
                $list->setFilter(function ($row) use ($type) {
                    if ($row['type'] == $type) {
                        return true;
                    }

                    return false;
                });
            }
        }
        $list->load();

        $docTypes = [];
        foreach ($list->getDocTypes() as $type) {
            $docTypes[] = $type->getObjectVars();
        }

        return $this->adminJson(['docTypes' => $docTypes]);
    }

    /**
     * @Route("/version-to-session", name="pimcore_admin_document_document_versiontosession", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function versionToSessionAction(Request $request)
    {
        $version = Version::getById($request->get('id'));
        $document = $version->loadData();
        Document\Service::saveElementToSession($document);

        return new Response();
    }

    /**
     * @Route("/publish-version", name="pimcore_admin_document_document_publishversion", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function publishVersionAction(Request $request)
    {
        $this->versionToSessionAction($request);

        $version = Version::getById($request->get('id'));
        $document = $version->loadData();

        $currentDocument = Document::getById($document->getId());
        if ($currentDocument->isAllowed('publish')) {
            $document->setPublished(true);
            try {
                $document->setKey($currentDocument->getKey());
                $document->setPath($currentDocument->getRealPath());
                $document->setUserModification($this->getAdminUser()->getId());

                $document->save();
            } catch (\Exception $e) {
                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        $this->addAdminStyle($document, ElementAdminStyleEvent::CONTEXT_EDITOR, $treeData);

        return $this->adminJson(['success' => true, 'treeData' => $treeData]);
    }

    /**
     * @Route("/update-site", name="pimcore_admin_document_document_updatesite", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateSiteAction(Request $request)
    {
        $domains = $request->get('domains');
        $domains = str_replace(' ', '', $domains);
        $domains = explode("\n", $domains);

        if (!$site = Site::getByRootId(intval($request->get('id')))) {
            $site = Site::create([
                'rootId' => intval($request->get('id')),
            ]);
        }

        $site->setDomains($domains);
        $site->setMainDomain($request->get('mainDomain'));
        $site->setErrorDocument($request->get('errorDocument'));
        $site->setRedirectToMainDomain(($request->get('redirectToMainDomain') == 'true') ? true : false);
        $site->save();

        $site->setRootDocument(null); // do not send the document to the frontend
        return $this->adminJson($site);
    }

    /**
     * @Route("/remove-site", name="pimcore_admin_document_document_removesite", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function removeSiteAction(Request $request)
    {
        $site = Site::getByRootId(intval($request->get('id')));
        $site->delete();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/copy-info", name="pimcore_admin_document_document_copyinfo", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function copyInfoAction(Request $request)
    {
        $transactionId = time();
        $pasteJobs = [];

        Session::useSession(function (AttributeBagInterface $session) use ($transactionId) {
            $session->set($transactionId, ['idMapping' => []]);
        }, 'pimcore_copy');

        if ($request->get('type') == 'recursive' || $request->get('type') == 'recursive-update-references') {
            $document = Document::getById($request->get('sourceId'));

            // first of all the new parent
            $pasteJobs[] = [[
                'url' => $this->generateUrl('pimcore_admin_document_document_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $request->get('sourceId'),
                    'targetId' => $request->get('targetId'),
                    'type' => 'child',
                    'language' => $request->get('language'),
                    'enableInheritance' => $request->get('enableInheritance'),
                    'transactionId' => $transactionId,
                    'saveParentId' => true,
                    'resetIndex' => true,
                ],
            ]];

            $childIds = [];
            if ($document->hasChildren()) {
                // get amount of childs
                $list = new Document\Listing();
                $list->setCondition('path LIKE ?', [$list->escapeLike($document->getRealFullPath()) . '/%']);
                $list->setOrderKey('LENGTH(path)', false);
                $list->setOrder('ASC');
                $childIds = $list->loadIdList();

                if (count($childIds) > 0) {
                    foreach ($childIds as $id) {
                        $pasteJobs[] = [[
                            'url' => $this->generateUrl('pimcore_admin_document_document_copy'),
                            'method' => 'POST',
                            'params' => [
                                'sourceId' => $id,
                                'targetParentId' => $request->get('targetId'),
                                'sourceParentId' => $request->get('sourceId'),
                                'type' => 'child',
                                'language' => $request->get('language'),
                                'enableInheritance' => $request->get('enableInheritance'),
                                'transactionId' => $transactionId,
                            ],
                        ]];
                    }
                }
            }

            // add id-rewrite steps
            if ($request->get('type') == 'recursive-update-references') {
                for ($i = 0; $i < (count($childIds) + 1); $i++) {
                    $pasteJobs[] = [[
                        'url' => $this->generateUrl('pimcore_admin_document_document_copyrewriteids'),
                        'method' => 'PUT',
                        'params' => [
                            'transactionId' => $transactionId,
                            'enableInheritance' => $request->get('enableInheritance'),
                            '_dc' => uniqid(),
                        ],
                    ]];
                }
            }
        } elseif ($request->get('type') == 'child' || $request->get('type') == 'replace') {
            // the object itself is the last one
            $pasteJobs[] = [[
                'url' => $this->generateUrl('pimcore_admin_document_document_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $request->get('sourceId'),
                    'targetId' => $request->get('targetId'),
                    'type' => $request->get('type'),
                    'language' => $request->get('language'),
                    'enableInheritance' => $request->get('enableInheritance'),
                    'transactionId' => $transactionId,
                    'resetIndex' => ($request->get('type') == 'child'),
                ],
            ]];
        }

        return $this->adminJson([
            'pastejobs' => $pasteJobs,
        ]);
    }

    /**
     * @Route("/copy-rewrite-ids", name="pimcore_admin_document_document_copyrewriteids", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function copyRewriteIdsAction(Request $request)
    {
        $transactionId = $request->get('transactionId');

        $idStore = Session::useSession(function (AttributeBagInterface $session) use ($transactionId) {
            return $session->get($transactionId);
        }, 'pimcore_copy');

        if (!array_key_exists('rewrite-stack', $idStore)) {
            $idStore['rewrite-stack'] = array_values($idStore['idMapping']);
        }

        $id = array_shift($idStore['rewrite-stack']);
        $document = Document::getById($id);

        if ($document) {
            // create rewriteIds() config parameter
            $rewriteConfig = ['document' => $idStore['idMapping']];

            $document = Document\Service::rewriteIds($document, $rewriteConfig, [
                'enableInheritance' => ($request->get('enableInheritance') == 'true') ? true : false,
            ]);

            $document->setUserModification($this->getAdminUser()->getId());
            $document->save();
        }

        // write the store back to the session
        Session::useSession(function (AttributeBagInterface $session) use ($transactionId, $idStore) {
            $session->set($transactionId, $idStore);
        }, 'pimcore_copy');

        return $this->adminJson([
            'success' => true,
            'id' => $id,
        ]);
    }

    /**
     * @Route("/copy", name="pimcore_admin_document_document_copy", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function copyAction(Request $request)
    {
        $success = false;
        $sourceId = intval($request->get('sourceId'));
        $source = Document::getById($sourceId);
        $session = Session::get('pimcore_copy');

        $targetId = intval($request->get('targetId'));

        $sessionBag = $session->get($request->get('transactionId'));

        if ($request->get('targetParentId')) {
            $sourceParent = Document::getById($request->get('sourceParentId'));

            // this is because the key can get the prefix "_copy" if the target does already exists
            if ($sessionBag['parentId']) {
                $targetParent = Document::getById($sessionBag['parentId']);
            } else {
                $targetParent = Document::getById($request->get('targetParentId'));
            }

            $targetPath = preg_replace('@^' . $sourceParent->getRealFullPath() . '@', $targetParent . '/', $source->getRealPath());
            $target = Document::getByPath($targetPath);
        } else {
            $target = Document::getById($targetId);
        }

        if ($target instanceof Document) {
            if ($target->isAllowed('create')) {
                if ($source != null) {
                    if ($source instanceof Document\PageSnippet && $latestVersion = $source->getLatestVersion()) {
                        $source = $latestVersion->loadData();
                        $source->setPublished(false); //as latest version is used which is not published
                    }

                    if ($request->get('type') == 'child') {
                        $enableInheritance = ($request->get('enableInheritance') == 'true') ? true : false;

                        $language = false;
                        if (Tool::isValidLanguage($request->get('language'))) {
                            $language = $request->get('language');
                        }

                        $resetIndex = ($request->get('resetIndex') == 'true') ? true : false;

                        $newDocument = $this->_documentService->copyAsChild($target, $source, $enableInheritance, $resetIndex, $language);

                        $sessionBag['idMapping'][(int)$source->getId()] = (int)$newDocument->getId();

                        // this is because the key can get the prefix "_copy" if the target does already exists
                        if ($request->get('saveParentId')) {
                            $sessionBag['parentId'] = $newDocument->getId();
                        }
                        $session->set($request->get('transactionId'), $sessionBag);
                        Session::writeClose();
                    } elseif ($request->get('type') == 'replace') {
                        $this->_documentService->copyContents($target, $source);
                    }

                    $success = true;
                } else {
                    Logger::error('prevended copy/paste because document with same path+key already exists in this location');
                }
            } else {
                Logger::error('could not execute copy/paste because of missing permissions on target [ ' . $targetId . ' ]');
                throw $this->createAccessDeniedHttpException();
            }
        }

        return $this->adminJson(['success' => $success]);
    }

    /**
     * @Route("/diff-versions/from/{from}/to/{to}", name="pimcore_admin_document_document_diffversions", requirements={"from": "\d+", "to": "\d+"}, methods={"GET"})
     *
     * @param Request $request
     * @param int $from
     * @param int $to
     *
     * @return Response
     */
    public function diffVersionsAction(Request $request, $from, $to)
    {
        // return with error if prerequisites do not match
        if (!HtmlToImage::isSupported() || !class_exists('Imagick')) {
            return $this->render('PimcoreAdminBundle:Admin/Document/Document:diff-versions-unsupported.html.php');
        }

        $versionFrom = Version::getById($from);
        $docFrom = $versionFrom->loadData();
        $prefix = $request->getSchemeAndHttpHost() . $docFrom->getRealFullPath() . '?pimcore_version=';

        $fromUrl = $prefix . $from;
        $toUrl = $prefix . $to;

        $toFileId = uniqid();
        $fromFileId = uniqid();
        $diffFileId = uniqid();
        $fromFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/version-diff-tmp-' . $fromFileId . '.png';
        $toFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/version-diff-tmp-' . $toFileId . '.png';
        $diffFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/version-diff-tmp-' . $diffFileId . '.png';

        $viewParams = [];

        HtmlToImage::convert($fromUrl, $fromFile);
        HtmlToImage::convert($toUrl, $toFile);

        $image1 = new \Imagick($fromFile);
        $image2 = new \Imagick($toFile);

        if ($image1->getImageWidth() == $image2->getImageWidth() && $image1->getImageHeight() == $image2->getImageHeight()) {
            $result = $image1->compareImages($image2, \Imagick::METRIC_MEANSQUAREERROR);
            $result[0]->setImageFormat('png');

            $result[0]->writeImage($diffFile);
            $result[0]->clear();
            $result[0]->destroy();

            $viewParams['image'] = $diffFileId;
        } else {
            $viewParams['image1'] = $fromFileId;
            $viewParams['image2'] = $toFileId;
        }

        // cleanup
        $image1->clear();
        $image1->destroy();
        $image2->clear();
        $image2->destroy();

        return $this->render('PimcoreAdminBundle:Admin/Document/Document:diff-versions.html.php', $viewParams);
    }

    /**
     * @Route("/diff-versions-image", name="pimcore_admin_document_document_diffversionsimage", methods={"GET"})
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function diffVersionsImageAction(Request $request)
    {
        $file = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/version-diff-tmp-' . $request->get('id') . '.png';
        if (file_exists($file)) {
            $response = new BinaryFileResponse($file);
            $response->headers->set('Content-Type', 'image/png');

            return $response;
        }

        throw $this->createNotFoundException('Version diff file not found');
    }

    /**
     * @Route("/get-id-for-path", name="pimcore_admin_document_document_getidforpath", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getIdForPathAction(Request $request)
    {
        if ($doc = Document::getByPath($request->get('path'))) {
            return $this->adminJson([
                'id' => $doc->getId(),
                'type' => $doc->getType(),
            ]);
        } else {
            return $this->adminJson(false);
        }
    }

    /**
     * SEO PANEL
     */

    /**
     * @Route("/seopanel-tree-root", name="pimcore_admin_document_document_seopaneltreeroot", methods={"GET"})
     *
     * @param DocumentRouteHandler $documentRouteHandler
     *
     * @return JsonResponse
     */
    public function seopanelTreeRootAction(DocumentRouteHandler $documentRouteHandler)
    {
        $this->checkPermission('seo_document_editor');

        /** @var Document\Page $root */
        $root = Document\Page::getById(1);
        if ($root->isAllowed('list')) {
            // make sure document routes are also built for unpublished documents
            $documentRouteHandler->setForceHandleUnpublishedDocuments(true);

            $nodeConfig = $this->getSeoNodeConfig($root);

            return $this->adminJson($nodeConfig);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @Route("/seopanel-tree", name="pimcore_admin_document_document_seopaneltree", methods={"GET"})
     *
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     * @param DocumentRouteHandler $documentRouteHandler
     *
     * @return JsonResponse
     */
    public function seopanelTreeAction(
        Request $request,
        EventDispatcherInterface $eventDispatcher,
        DocumentRouteHandler $documentRouteHandler
    ) {
        $allParams = array_merge($request->request->all(), $request->query->all());

        $filterPrepareEvent = new GenericEvent($this, [
            'requestParams' => $allParams,
        ]);
        $eventDispatcher->dispatch(AdminEvents::DOCUMENT_LIST_BEFORE_FILTER_PREPARE, $filterPrepareEvent);

        $allParams = $filterPrepareEvent->getArgument('requestParams');

        $this->checkPermission('seo_document_editor');

        // make sure document routes are also built for unpublished documents
        $documentRouteHandler->setForceHandleUnpublishedDocuments(true);

        $document = Document::getById($allParams['node']);

        $documents = [];
        if ($document->hasChildren()) {
            $list = new Document\Listing();
            $list->setCondition('parentId = ?', $document->getId());
            $list->setOrderKey('index');
            $list->setOrder('asc');

            $beforeListLoadEvent = new GenericEvent($this, [
                'list' => $list,
                'context' => $allParams,
            ]);
            $eventDispatcher->dispatch(AdminEvents::DOCUMENT_LIST_BEFORE_LIST_LOAD, $beforeListLoadEvent);
            /** @var Document\Listing $list */
            $list = $beforeListLoadEvent->getArgument('list');

            $childsList = $list->load();

            foreach ($childsList as $childDocument) {
                // only display document if listing is allowed for the current user
                if ($childDocument->isAllowed('list')) {
                    $list = new Document\Listing();
                    $list->setCondition('path LIKE ? and type = ?', [$list->escapeLike($childDocument->getRealFullPath()). '/%', 'page']);

                    if ($childDocument instanceof Document\Page || $list->getTotalCount() > 0) {
                        $documents[] = $this->getSeoNodeConfig($childDocument);
                    }
                }
            }
        }

        $result = ['data' => $documents, 'success' => true, 'total' => count($documents)];

        $afterListLoadEvent = new GenericEvent($this, [
            'list' => $result,
            'context' => $allParams,
        ]);
        $eventDispatcher->dispatch(AdminEvents::DOCUMENT_LIST_AFTER_LIST_LOAD, $afterListLoadEvent);
        $result = $afterListLoadEvent->getArgument('list');

        return $this->adminJson($result['data']);
    }

    /**
     * @Route("/language-tree", name="pimcore_admin_document_document_languagetree", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function languageTreeAction(Request $request)
    {
        $document = Document::getById($request->query->get('node'));

        $service = new Document\Service();

        $languages = explode(',', $request->get('languages'));

        $result = [];
        foreach ($document->getChildren() as $child) {
            $result[] = $this->getTranslationTreeNodeConfig($child, $languages);
        }

        return $this->adminJson($result);
    }

    /**
     * @Route("/language-tree-root", name="pimcore_admin_document_document_languagetreeroot", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function languageTreeRootAction(Request $request)
    {
        $document = Document::getById($request->query->get('id'));

        if (!$document) {
            return $this->adminJson([
                'success' => false,
            ]);
        }
        $service = new Document\Service();

        $locales = Tool::getSupportedLocales();

        $lang = $document->getProperty('language');

        $columns = [
            [
                'xtype' => 'treecolumn',
                'text' => $lang ? $locales[$lang] : '',
                'dataIndex' => 'text',
                'cls' => $lang ? 'x-column-header_' . strtolower($lang) : null,
                'width' => 300,
                'sortable' => false,
            ],
        ];

        $translations = $service->getTranslations($document);

        $combinedTranslations = $translations;

        if ($parentDocument = $document->getParent()) {
            $parentTranslations = $service->getTranslations($parentDocument);
            foreach ($parentTranslations as $language => $languageDocumentId) {
                $combinedTranslations[$language] = $translations[$language] ?? $languageDocumentId;
            }
        }

        foreach ($combinedTranslations as $language => $languageDocumentId) {
            $languageDocument = Document::getById($languageDocumentId);

            if ($languageDocument && $languageDocument->isAllowed('list') && $language != $document->getProperty('language')) {
                $columns[] = [
                    'text' => $locales[$language],
                    'dataIndex' => $language,
                    'cls' => 'x-column-header_' . strtolower($language),
                    'width' => 300,
                    'sortable' => false,
                ];
            }
        }

        return $this->adminJson([
            'root' => $this->getTranslationTreeNodeConfig($document, array_keys($translations), $translations),
            'columns' => $columns,
            'languages' => array_keys($translations),
        ]);
    }

    private function getTranslationTreeNodeConfig($document, array $languages, array $translations = null)
    {
        $service = new Document\Service();

        $config = $this->getTreeNodeConfig($document);

        $translations = is_null($translations) ? $service->getTranslations($document) : $translations;

        foreach ($languages as $language) {
            if ($languageDocument = $translations[$language]) {
                $languageDocument = Document::getById($languageDocument);
                $config[$language] = [
                    'text' => $languageDocument->getKey(),
                    'id' => $languageDocument->getId(),
                    'type' => $languageDocument->getType(),
                    'fullPath' => $languageDocument->getFullPath(),
                    'published' => $languageDocument->getPublished(),
                    'itemType' => 'document',
                    'permissions' => $languageDocument->getUserPermissions(),
                ];
            } elseif (!$document instanceof Document\Folder) {
                $config[$language] = [
                    'text' => '--',
                    'itemType' => 'empty',
                ];
            }
        }

        return $config;
    }

    /**
     * @Route("/convert", name="pimcore_admin_document_document_convert", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function convertAction(Request $request)
    {
        $document = Document::getById($request->get('id'));

        $type = $request->get('type');
        $class = '\\Pimcore\\Model\\Document\\' . ucfirst($type);
        if (Tool::classExists($class)) {
            $new = new $class;

            // overwrite internal store to avoid "duplicate full path" error
            \Pimcore\Cache\Runtime::set('document_' . $document->getId(), $new);

            $props = $document->getObjectVars();
            foreach ($props as $name => $value) {
                $new->setValue($name, $value);
            }

            if ($type == 'hardlink' || $type == 'folder') {
                // remove navigation settings
                foreach (['name', 'title', 'target', 'exclude', 'class', 'anchor', 'parameters', 'relation', 'accesskey', 'tabindex'] as $propertyName) {
                    $new->removeProperty('navigation_' . $propertyName);
                }
            }

            $new->setType($type);
            $new->save();
        }

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/translation-determine-parent", name="pimcore_admin_document_document_translationdetermineparent", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function translationDetermineParentAction(Request $request)
    {
        $success = false;
        $targetDocument = null;

        $document = Document::getById($request->get('id'));
        if ($document) {
            $service = new Document\Service;
            $document = $document->getId() === 1 ? $document : $document->getParent();

            $translations = $service->getTranslations($document);
            if (isset($translations[$request->get('language')])) {
                $targetDocument = Document::getById($translations[$request->get('language')]);
                $success = true;
            }
        }

        return $this->adminJson([
            'success' => $success,
            'targetPath' => $targetDocument ? $targetDocument->getRealFullPath() : null,
            'targetId' => $targetDocument ? $targetDocument->getid() : null,
        ]);
    }

    /**
     * @Route("/translation-add", name="pimcore_admin_document_document_translationadd", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function translationAddAction(Request $request)
    {
        $sourceDocument = Document::getById($request->get('sourceId'));
        $targetDocument = Document::getByPath($request->get('targetPath'));

        if ($sourceDocument && $targetDocument) {
            if (empty($sourceDocument->getProperty('language'))) {
                throw new \Exception(sprintf('Source Document(ID:%s) Language(Properties) missing', $sourceDocument->getId()));
            }

            if (empty($targetDocument->getProperty('language'))) {
                throw new \Exception(sprintf('Target Document(ID:%s) Language(Properties) missing', $sourceDocument->getId()));
            }

            $service = new Document\Service;
            if ($service->getTranslationSourceId($targetDocument) != $targetDocument->getId()) {
                throw new \Exception('Target Document already linked to Source Document ID('.$service->getTranslationSourceId($targetDocument).'). Please unlink existing relation first.');
            }
            $service->addTranslation($sourceDocument, $targetDocument);
        }

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @Route("/translation-remove", name="pimcore_admin_document_document_translationremove", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function translationRemoveAction(Request $request)
    {
        $sourceDocument = Document::getById($request->get('sourceId'));
        $targetDocument = Document::getById($request->get('targetId'));
        if ($sourceDocument && $targetDocument) {
            $service = new Document\Service;
            $service->removeTranslationLink($sourceDocument, $targetDocument);
        }

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @Route("/translation-check-language", name="pimcore_admin_document_document_translationchecklanguage", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function translationCheckLanguageAction(Request $request)
    {
        $success = false;
        $language = null;
        $translationLinks = null;

        $document = Document::getByPath($request->get('path'));
        if ($document) {
            $language = $document->getProperty('language');
            if ($language) {
                $success = true;
            }

            //check if document is already linked to other langauges
            $translationLinks = array_keys($this->_documentService->getTranslations($document));
        }

        return $this->adminJson([
            'success' => $success,
            'language' => $language,
            'translationLinks' => $translationLinks,
        ]);
    }

    /**
     * @param Document\Page $document
     *
     * @return array
     */
    private function getSeoNodeConfig($document)
    {
        $nodeConfig = $this->getTreeNodeConfig($document);

        if (method_exists($document, 'getTitle') && method_exists($document, 'getDescription')) {
            // analyze content
            $nodeConfig['prettyUrl'] = $document->getPrettyUrl();

            $title = $document->getTitle();
            $description = $document->getDescription();

            $nodeConfig['title'] = $title;
            $nodeConfig['description'] = $description;

            $nodeConfig['title_length'] = mb_strlen($title);
            $nodeConfig['description_length'] = mb_strlen($description);
        }

        return $nodeConfig;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        // check permissions
        $this->checkActionPermission($event, 'documents', ['docTypesGetAction']);

        $this->_documentService = new Document\Service($this->getAdminUser());
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
