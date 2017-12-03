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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Config;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Event\AdminEvents;
use Pimcore\Image\HtmlToImage;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Model\Version;
use Pimcore\Tool;
use Pimcore\Tool\Session;
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
    /**
     * @var Document\Service
     */
    protected $_documentService;

    /**
     * @Route("/get-data-by-id")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request)
    {
        $document = Document::getById($request->get('id'));
        $document = clone $document;

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $data = object2array($document);
        $event = new GenericEvent($this, [
            'data' => $data,
            'document' => $document
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);
        $data = $event->getArgument('data');

        if ($document->isAllowed('view')) {
            return $this->json($data);
        }

        return $this->json(['success' => false, 'message' => 'missing_permission']);
    }

    /**
     * @Route("/tree-get-childs-by-id")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function treeGetChildsByIdAction(Request $request)
    {
        $allParams = array_merge($request->request->all(), $request->query->all());

        $document = Document::getById($allParams['node']);

        $documents = [];
        $cv = false;
        if ($document->hasChildren()) {
            $limit = intval($allParams['limit']);
            if (!$allParams['limit']) {
                $limit = 100000000;
            }

            $offset = intval($allParams['start']);

            if ($allParams['view']) {
                $cv = \Pimcore\Model\Element\Service::getCustomViewById($allParams['view']);
            }

            $list = new Document\Listing();
            if ($this->getUser()->isAdmin()) {
                $list->setCondition('parentId = ? ', $document->getId());
            } else {
                $userIds = $this->getUser()->getRoles();
                $userIds[] = $this->getUser()->getId();
                $list->setCondition('parentId = ? and
                                        (
                                        (select list from users_workspaces_document where userId in (' . implode(',', $userIds) . ') and LOCATE(CONCAT(path,`key`),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                        or
                                        (select list from users_workspaces_document where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(path,`key`))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                        )', $document->getId());
            }

            $list->setOrderKey(['index', 'id']);
            $list->setOrder(['asc', 'asc']);

            $list->setLimit($limit);
            $list->setOffset($offset);

            \Pimcore\Model\Element\Service::addTreeFilterJoins($cv, $list);

            $beforeListLoadEvent = new GenericEvent($this, [
                'list' => $childsList,
                'context' => $allParams
            ]);
            \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_LIST_BEFORE_LIST_LOAD, $beforeListLoadEvent);
            $childsList = $beforeListLoadEvent->getArgument('list');

            $childsList = $list->load();

            foreach ($childsList as $childDocument) {
                // only display document if listing is allowed for the current user
                if ($childDocument->isAllowed('list')) {
                    $documents[] = $this->getTreeNodeConfig($childDocument);
                }
            }
        }

        if ($allParams['limit']) {
            return $this->json([
                'offset' => $offset,
                'limit' => $limit,
                'total' => $document->getChildAmount($this->getUser()),
                'nodes' => $documents
            ]);
        } else {
            return $this->json($documents);
        }
    }

    /**
     * @Route("/add")
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
        if ($parentDocument->isAllowed('create')) {
            $intendedPath = $parentDocument->getRealFullPath() . '/' . $request->get('key');

            if (!Document\Service::pathExists($intendedPath)) {
                $createValues = [
                    'userOwner' => $this->getUser()->getId(),
                    'userModification' => $this->getUser()->getId(),
                    'published' => false
                ];

                $createValues['key'] = \Pimcore\Model\Element\Service::getValidKey($request->get('key'), 'document');

                // check for a docType
                $docType = Document\DocType::getById(intval($request->get('docTypeId')));
                if ($docType) {
                    $createValues['template'] = $docType->getTemplate();
                    $createValues['controller'] = $docType->getController();
                    $createValues['action'] = $docType->getAction();
                    $createValues['module'] = $docType->getModule();
                    $createValues['legacy'] = $docType->getLegacy();
                } elseif ($request->get('translationsBaseDocument')) {
                    $translationsBaseDocument = Document::getById($request->get('translationsBaseDocument'));
                    $createValues['template'] = $translationsBaseDocument->getTemplate();
                    $createValues['controller'] = $translationsBaseDocument->getController();
                    $createValues['action'] = $translationsBaseDocument->getAction();
                    $createValues['module'] = $translationsBaseDocument->getModule();
                } elseif ($request->get('type') == 'page' || $request->get('type') == 'snippet' || $request->get('type') == 'email') {
                    $createValues['controller'] = Config::getSystemConfig()->documents->default_controller;
                    $createValues['action'] = Config::getSystemConfig()->documents->default_action;
                }

                if ($request->get('inheritanceSource')) {
                    $createValues['contentMasterDocumentId'] = $request->get('inheritanceSource');
                }

                switch ($request->get('type')) {
                    case 'page':
                        $document = Document\Page::create($request->get('parentId'), $createValues, false);
                        $document->setTitle($request->get('title', null));
                        $document->setProperty('navigation_name', 'text', $request->get('name', null), false);
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
                            return $this->json(['success' => false, 'message' => $e->getMessage()]);
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
                                return $this->json(['success' => false, 'message' => $e->getMessage()]);
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

        if ($success) {
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

            return $this->json([
                'success' => $success,
                'id' => $document->getId(),
                'type' => $document->getType()
            ]);
        } else {
            return $this->json([
                'success' => $success,
                'message' => $errorMessage
            ]);
        }
    }

    /**
     * @Route("/delete")
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
            $list->setCondition('path LIKE ?', [$parentDocument->getRealFullPath() . '/%']);
            $list->setLimit(intval($request->get('amount')));
            $list->setOrderKey('LENGTH(path)', false);
            $list->setOrder('DESC');

            $documents = $list->load();

            $deletedItems = [];
            foreach ($documents as $document) {
                $deletedItems[] = $document->getRealFullPath();
                if ($document->isAllowed('delete')) {
                    $document->delete();
                }
            }

            return $this->json(['success' => true, 'deleted' => $deletedItems]);
        } elseif ($request->get('id')) {
            $document = Document::getById($request->get('id'));
            if ($document->isAllowed('delete')) {
                try {
                    $document->delete();

                    return $this->json(['success' => true]);
                } catch (\Exception $e) {
                    Logger::err($e);

                    return $this->json(['success' => false, 'message' => $e->getMessage()]);
                }
            }
        }

        return $this->json(['success' => false, 'message' => 'missing_permission']);
    }

    /**
     * @Route("/delete-info")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteInfoAction(Request $request)
    {
        $hasDependency = false;

        try {
            $document = Document::getById($request->get('id'));
            $hasDependency = $document->getDependencies()->isRequired();
        } catch (\Exception $e) {
            Logger::err('failed to access document with id: ' . $request->get('id'));
        }

        $deleteJobs = [];

        // check for childs
        if ($document instanceof Document) {
            $deleteJobs[] = [[
                'url' => '/admin/recyclebin/add',
                'params' => [
                    'type' => 'document',
                    'id' => $document->getId()
                ]
            ]];

            $hasChilds = $document->hasChildren();
            if (!$hasDependency) {
                $hasDependency = $hasChilds;
            }

            $childs = 0;
            if ($hasChilds) {
                // get amount of childs
                $list = new Document\Listing();
                $list->setCondition('path LIKE ?', [$document->getRealFullPath() . '/%']);
                $childs = $list->getTotalCount();

                if ($childs > 0) {
                    $deleteObjectsPerRequest = 5;
                    for ($i = 0; $i < ceil($childs / $deleteObjectsPerRequest); $i++) {
                        $deleteJobs[] = [[
                            'url' => '/admin/document/delete',
                            'params' => [
                                'step' => $i,
                                'amount' => $deleteObjectsPerRequest,
                                'type' => 'childs',
                                'id' => $document->getId()
                            ]
                        ]];
                    }
                }
            }

            // the object itself is the last one
            $deleteJobs[] = [[
                'url' => '/admin/document/delete',
                'params' => [
                    'id' => $document->getId()
                ]
            ]];
        }

        // get the element key
        $elementKey = $document->getKey();

        return $this->json([
            'hasDependencies' => $hasDependency,
            'childs' => $childs,
            'deletejobs' => $deleteJobs,
            'elementKey' => $elementKey
        ]);
    }

    /**
     * @Route("/update")
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

        // this prevents the user from renaming, relocating (actions in the tree) if the newest version isn't the published one
        // the reason is that otherwise the content of the newer not published version will be overwritten
        if ($document instanceof Document\PageSnippet) {
            $latestVersion = $document->getLatestVersion();
            if ($latestVersion && $latestVersion->getData()->getModificationDate() != $document->getModificationDate()) {
                return $this->json(['success' => false, 'message' => "You can't relocate if there's a newer not published version"]);
            }
        }

        if ($document->isAllowed('settings')) {

            // if the position is changed the path must be changed || also from the childs
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

                // if changed the index change also all documents on the same level
                if ($request->get('index') !== null) {
                    $list = new Document\Listing();
                    $list->setCondition('parentId = ? AND id != ?', [$request->get('parentId'), $document->getId()]);
                    $list->setOrderKey('index');
                    $list->setOrder('asc');
                    $childsList = $list->load();

                    $count = 0;
                    foreach ($childsList as $child) {
                        if ($count == intval($request->get('index'))) {
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
                    return $this->json(['success' => false, 'message' => $e->getMessage()]);
                }
            } else {
                $msg = 'Prevented moving document, because document with same path+key already exists or the document is locked. ID: ' . $document->getId();
                Logger::debug($msg);

                return $this->json(['success' => false, 'message' => $msg]);
            }
        } elseif ($document->isAllowed('rename') && $request->get('key')) {
            //just rename
            try {
                $document->setKey($request->get('key'));
                $document->setUserModification($this->getUser()->getId());
                $document->save();
                $success = true;
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            Logger::debug('Prevented update document, because of missing permissions.');
        }

        return $this->json(['success' => $success]);
    }

    /**
     * @Route("/doc-types")
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

                return $this->json(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $data = $this->decodeJson($request->get('data'));

                // save type
                $type = Document\DocType::getById($data['id']);

                $type->setValues($data);
                $type->save();

                return $this->json(['data' => $type, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                $data = $this->decodeJson($request->get('data'));
                unset($data['id']);

                // save type
                $type = Document\DocType::create();
                $type->setValues($data);

                $type->save();

                return $this->json(['data' => $type, 'success' => true]);
            }
        } else {
            // get list of types
            $list = new Document\DocType\Listing();
            $list->load();

            $docTypes = [];
            foreach ($list->getDocTypes() as $type) {
                if ($this->getUser()->isAllowed($type->getId(), 'docType')) {
                    $docTypes[] = $type;
                }
            }

            return $this->json(['data' => $docTypes, 'success' => true, 'total' => count($docTypes)]);
        }

        return $this->json(false);
    }

    /**
     * @Route("/get-doc-types")
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
            $docTypes[] = $type;
        }

        return $this->json(['docTypes' => $docTypes]);
    }

    /**
     * @Route("/version-to-session")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function versionToSessionAction(Request $request)
    {
        $version = Version::getById($request->get('id'));
        $document = $version->loadData();

        Session::useSession(function (AttributeBagInterface $session) use ($document) {
            $key = 'document_' . $document->getId();
            $session->set($key, $document);
        }, 'pimcore_documents');

        return new Response();
    }

    /**
     * @Route("/publish-version")
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
                $document->setUserModification($this->getUser()->getId());

                $document->save();
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/update-site")
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

        try {
            $site = Site::getByRootId(intval($request->get('id')));
        } catch (\Exception $e) {
            $site = Site::create([
                'rootId' => intval($request->get('id'))
            ]);
        }

        $site->setDomains($domains);
        $site->setMainDomain($request->get('mainDomain'));
        $site->setErrorDocument($request->get('errorDocument'));
        $site->setRedirectToMainDomain(($request->get('redirectToMainDomain') == 'true') ? true : false);
        $site->save();

        $site->setRootDocument(null); // do not send the document to the frontend
        return $this->json($site);
    }

    /**
     * @Route("/remove-site")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function removeSiteAction(Request $request)
    {
        $site = Site::getByRootId(intval($request->get('id')));
        $site->delete();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/copy-info")
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
                'url' => '/admin/document/copy',
                'params' => [
                    'sourceId' => $request->get('sourceId'),
                    'targetId' => $request->get('targetId'),
                    'type' => 'child',
                    'enableInheritance' => $request->get('enableInheritance'),
                    'transactionId' => $transactionId,
                    'saveParentId' => true,
                    'resetIndex' => true
                ]
            ]];

            $childIds = [];
            if ($document->hasChildren()) {
                // get amount of childs
                $list = new Document\Listing();
                $list->setCondition('path LIKE ?', [$document->getRealFullPath() . '/%']);
                $list->setOrderKey('LENGTH(path)', false);
                $list->setOrder('ASC');
                $childIds = $list->loadIdList();

                if (count($childIds) > 0) {
                    foreach ($childIds as $id) {
                        $pasteJobs[] = [[
                            'url' => '/admin/document/copy',
                            'params' => [
                                'sourceId' => $id,
                                'targetParentId' => $request->get('targetId'),
                                'sourceParentId' => $request->get('sourceId'),
                                'type' => 'child',
                                'enableInheritance' => $request->get('enableInheritance'),
                                'transactionId' => $transactionId
                            ]
                        ]];
                    }
                }
            }

            // add id-rewrite steps
            if ($request->get('type') == 'recursive-update-references') {
                for ($i = 0; $i < (count($childIds) + 1); $i++) {
                    $pasteJobs[] = [[
                        'url' => '/admin/document/copy-rewrite-ids',
                        'params' => [
                            'transactionId' => $transactionId,
                            'enableInheritance' => $request->get('enableInheritance'),
                            '_dc' => uniqid()
                        ]
                    ]];
                }
            }
        } elseif ($request->get('type') == 'child' || $request->get('type') == 'replace') {
            // the object itself is the last one
            $pasteJobs[] = [[
                'url' => '/admin/document/copy',
                'params' => [
                    'sourceId' => $request->get('sourceId'),
                    'targetId' => $request->get('targetId'),
                    'type' => $request->get('type'),
                    'enableInheritance' => $request->get('enableInheritance'),
                    'transactionId' => $transactionId,
                    'resetIndex' => ($request->get('type') == 'child')
                ]
            ]];
        }

        return $this->json([
            'pastejobs' => $pasteJobs
        ]);
    }

    /**
     * @Route("/copy-rewrite-ids")
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
                'enableInheritance' => ($request->get('enableInheritance') == 'true') ? true : false
            ]);

            $document->setUserModification($this->getUser()->getId());
            $document->save();
        }

        // write the store back to the session
        Session::useSession(function (AttributeBagInterface $session) use ($transactionId, $idStore) {
            $session->set($transactionId, $idStore);
        }, 'pimcore_copy');

        return $this->json([
            'success' => true,
            'id' => $id
        ]);
    }

    /**
     * @Route("/copy")
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
                    if ($request->get('type') == 'child') {
                        $enableInheritance = ($request->get('enableInheritance') == 'true') ? true : false;
                        $resetIndex = ($request->get('resetIndex') == 'true') ? true : false;

                        $newDocument = $this->_documentService->copyAsChild($target, $source, $enableInheritance, $resetIndex);

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

                return $this->json(['success' => false, 'message' => 'missing_permission']);
            }
        }

        return $this->json(['success' => $success]);
    }

    /**
     * @Route("/diff-versions/from/{from}/to/{to}", requirements={"from": "\d+", "to": "\d+"})
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
            return $this->render('PimcoreAdminBundle:Admin/Document:diff-versions-unsupported.html.php');
        }

        $versionFrom = Version::getById($from);
        $docFrom = $versionFrom->loadData();

        $sessionName = Tool\Session::getSessionName();
        $sessionId = Tool\Session::getSessionId();

        $prefix = $request->getSchemeAndHttpHost() . $docFrom->getRealFullPath() . '?pimcore_version=';

        $fromUrl = $prefix . $from . '&' . $sessionName . '=' . $sessionId;
        $toUrl   = $prefix . $to . '&' . $sessionName . '=' . $sessionId;

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

        return $this->render('PimcoreAdminBundle:Admin/Document:diff-versions.html.php', $viewParams);
    }

    /**
     * @Route("/diff-versions-image")
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
    }

    /**
     * @param $element Document
     *
     * @return array
     */
    protected function getTreeNodeConfig($element)
    {
        $site = null;
        $childDocument = $element;

        $tmpDocument = [
            'id' => $childDocument->getId(),
            'idx' => intval($childDocument->getIndex()),
            'text' => $childDocument->getKey(),
            'type' => $childDocument->getType(),
            'path' => $childDocument->getRealFullPath(),
            'basePath' => $childDocument->getRealPath(),
            'locked' => $childDocument->isLocked(),
            'lockOwner' => $childDocument->getLocked() ? true : false,
            'published' => $childDocument->isPublished(),
            'elementType' => 'document',
            'leaf' => true,
            'permissions' => [
                'view' => $childDocument->isAllowed('view'),
                'remove' => $childDocument->isAllowed('delete'),
                'settings' => $childDocument->isAllowed('settings'),
                'rename' => $childDocument->isAllowed('rename'),
                'publish' => $childDocument->isAllowed('publish'),
                'unpublish' => $childDocument->isAllowed('unpublish')
            ]
        ];

        // add icon
        $tmpDocument['iconCls'] = 'pimcore_icon_' . $childDocument->getType();
        $tmpDocument['expandable'] = $childDocument->hasChildren();
        $tmpDocument['loaded'] = !$childDocument->hasChildren();

        // set type specific settings
        if ($childDocument->getType() == 'page') {
            $tmpDocument['leaf'] = false;
            $tmpDocument['expanded'] = $childDocument->hasNoChilds();
            $tmpDocument['permissions']['create'] = $childDocument->isAllowed('create');
            $tmpDocument['iconCls'] = 'pimcore_icon_page';

            // test for a site
            try {
                $site = Site::getByRootId($childDocument->getId());
                $tmpDocument['iconCls'] = 'pimcore_icon_site';
                unset($site->rootDocument);
                $tmpDocument['site'] = $site;
            } catch (\Exception $e) {
            }
        } elseif ($childDocument->getType() == 'folder' || $childDocument->getType() == 'link' || $childDocument->getType() == 'hardlink') {
            $tmpDocument['leaf'] = false;
            $tmpDocument['expanded'] = $childDocument->hasNoChilds();

            if ($childDocument->hasNoChilds() && $childDocument->getType() == 'folder') {
                $tmpDocument['iconCls'] = 'pimcore_icon_folder';
            }
            $tmpDocument['permissions']['create'] = $childDocument->isAllowed('create');
        } elseif (method_exists($childDocument, 'getTreeNodeConfig')) {
            $tmp = $childDocument->getTreeNodeConfig();
            $tmpDocument = array_merge($tmpDocument, $tmp);
        }

        $tmpDocument['qtipCfg'] = [
            'title' => 'ID: ' . $childDocument->getId(),
            'text' => 'Type: ' . $childDocument->getType()
        ];

        if ($site instanceof Site) {
            $tmpDocument['qtipCfg']['text'] .= '<br>' . $this->trans('site_id') . ': ' . $site->getId();
        }

        // PREVIEWS temporary disabled, need's to be optimized some time
        if ($childDocument instanceof Document\Page && Config::getSystemConfig()->documents->generatepreview) {
            $thumbnailFile = PIMCORE_TEMPORARY_DIRECTORY . '/document-page-previews/document-page-screenshot-' . $childDocument->getId() . '.jpg';

            // only if the thumbnail exists and isn't out of time
            if (file_exists($thumbnailFile) && filemtime($thumbnailFile) > ($childDocument->getModificationDate() - 20)) {
                $thumbnailPath = str_replace(PIMCORE_WEB_ROOT, '', $thumbnailFile);
                $tmpDocument['thumbnail'] = $thumbnailPath;
            }
        }

        if ($childDocument instanceof Document\Page) {
            $tmpDocument['url'] = $childDocument->getFullPath();
            $site = Tool\Frontend::getSiteForDocument($childDocument);
            if ($site instanceof Site) {
                $tmpDocument['url'] = 'http://' . $site->getMainDomain() . preg_replace('@^' . $site->getRootPath() . '/?@', '/', $childDocument->getRealFullPath());
            }
        }

        $tmpDocument['cls'] = '';

        if (!$childDocument->isPublished()) {
            $tmpDocument['cls'] .= 'pimcore_unpublished ';
        }

        if ($childDocument->isLocked()) {
            $tmpDocument['cls'] .= 'pimcore_treenode_locked ';
        }
        if ($childDocument->getLocked()) {
            $tmpDocument['cls'] .= 'pimcore_treenode_lockOwner ';
        }

        return $tmpDocument;
    }

    /**
     * @Route("/get-id-for-path")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getIdForPathAction(Request $request)
    {
        if ($doc = Document::getByPath($request->get('path'))) {
            return $this->json([
                'id' => $doc->getId(),
                'type' => $doc->getType()
            ]);
        } else {
            return $this->json(false);
        }
    }

    /**
     * SEO PANEL
     */

    /**
     * @Route("/seopanel-tree-root")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function seopanelTreeRootAction(Request $request)
    {
        $this->checkPermission('seo_document_editor');

        $root = Document::getById(1);
        if ($root->isAllowed('list')) {
            $nodeConfig = $this->getSeoNodeConfig($request, $root);

            return $this->json($nodeConfig);
        }

        return $this->json(['success' => false, 'message' => 'missing_permission']);
    }

    /**
     * @Route("/seopanel-tree")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function seopanelTreeAction(Request $request)
    {
        $allParams = array_merge($request->request->all(), $request->query->all());

        $filterPrepareEvent = new GenericEvent($this, [
            'requestParams' => $allParams
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_LIST_BEFORE_FILTER_PREPARE, $filterPrepareEvent);

        $allParams = $filterPrepareEvent->getArgument('requestParams');

        $this->checkPermission('seo_document_editor');

        $document = Document::getById($allParams['node']);

        $documents = [];
        if ($document->hasChildren()) {
            $list = new Document\Listing();
            $list->setCondition('parentId = ?', $document->getId());
            $list->setOrderKey('index');
            $list->setOrder('asc');

            $beforeListLoadEvent = new GenericEvent($this, [
                'list' => $list,
                'context' => $allParams
            ]);
            \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_LIST_BEFORE_LIST_LOAD, $beforeListLoadEvent);
            $list = $beforeListLoadEvent->getArgument('list');

            $childsList = $list->load();

            foreach ($childsList as $childDocument) {
                // only display document if listing is allowed for the current user
                if ($childDocument->isAllowed('list')) {
                    $list = new Document\Listing();
                    $list->setCondition('path LIKE ? and type = ?', [$childDocument->getRealFullPath() . '/%', 'page']);

                    if ($childDocument instanceof Document\Page || $list->getTotalCount() > 0) {
                        $documents[] = $this->getSeoNodeConfig($request, $childDocument);
                    }
                }
            }
        }

        $result = ['data' => $documents, 'success' => true, 'total' => count($documents)];

        $afterListLoadEvent = new GenericEvent($this, [
            'list' => $result,
            'context' => $allParams
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_LIST_AFTER_LIST_LOAD, $afterListLoadEvent);
        $result = $afterListLoadEvent->getArgument('list');

        return $this->json($result['data']);
    }

    /**
     * @Route("/convert")
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

            $props = get_object_vars($document);
            foreach ($props as $name => $value) {
                if (property_exists($new, $name)) {
                    $new->$name = $value;
                }
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

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/translation-determine-parent")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function translationDetermineParentAction(Request $request)
    {
        $success = false;
        $targetPath = null;

        $document = Document::getById($request->get('id'));
        if ($document) {
            $service = new Document\Service;
            $translations = $service->getTranslations($document->getParent());
            if (isset($translations[$request->get('language')])) {
                $targetDocument = Document::getById($translations[$request->get('language')]);
                $targetPath = $targetDocument->getRealFullPath();
                $success = true;
            }
        }

        return $this->json([
            'success' => $success,
            'targetPath' => $targetPath
        ]);
    }

    /**
     * @Route("/translation-add")
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
            $service = new Document\Service;
            $service->addTranslation($sourceDocument, $targetDocument);
        }

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/translation-check-language")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function translationCheckLanguageAction(Request $request)
    {
        $success = false;
        $language = null;

        $document = Document::getByPath($request->get('path'));
        if ($document) {
            $language = $document->getProperty('language');
            if ($language) {
                $success = true;
            }
        }

        return $this->json([
            'success' => $success,
            'language' => $language
        ]);
    }

    /**
     * @param Request $request
     * @param Document\PageSnippet|Document\Page $document
     *
     * @return array
     */
    private function getSeoNodeConfig(Request $request, $document)
    {
        $nodeConfig = $this->getTreeNodeConfig($document);

        if (method_exists($document, 'getTitle') && method_exists($document, 'getDescription')) {

            // anaylze content
            $nodeConfig['prettyUrl']     = $document->getPrettyUrl();
            $nodeConfig['links']         = 0;
            $nodeConfig['externallinks'] = 0;
            $nodeConfig['h1']            = 0;
            $nodeConfig['h1_text']       = '';
            $nodeConfig['hx']            = 0;
            $nodeConfig['imgwithalt']    = 0;
            $nodeConfig['imgwithoutalt'] = 0;

            $title       = null;
            $description = null;

            try {

                // cannot use the rendering service from Document\Service::render() because of singleton's ...
                // $content = Document\Service::render($childDocument, array("pimcore_admin" => true, "pimcore_preview" => true), true);

                $contentUrl = $request->getScheme() . '://' . $request->getHttpHost() . $document->getFullPath();
                $content    = Tool::getHttpData($contentUrl, ['pimcore_preview' => true, 'pimcore_admin' => true, '_dc' => time()]);

                if ($content) {
                    include_once(PIMCORE_PATH . '/lib/simple_html_dom.php');
                    $html = str_get_html($content);
                    if ($html) {
                        $nodeConfig['links']         = count($html->find('a'));
                        $nodeConfig['externallinks'] = count($html->find('a[href^=http]'));
                        $nodeConfig['h1']            = count($html->find('h1'));

                        $h1 = $html->find('h1', 0);
                        if ($h1) {
                            $nodeConfig['h1_text'] = strip_tags($h1->innertext);
                        }

                        $title = $html->find('title', 0);
                        if ($title) {
                            $title = html_entity_decode(trim(strip_tags($title->innertext)), null, 'UTF-8');
                        }

                        $description = $html->find('meta[name=description]', 0);
                        if ($description) {
                            $description = html_entity_decode(trim(strip_tags($description->content)), null, 'UTF-8');
                        }

                        $nodeConfig['hx'] = count($html->find('h2,h2,h4,h5'));

                        $images = $html->find('img');
                        if ($images) {
                            foreach ($images as $image) {
                                $alt = $image->alt;
                                if (empty($alt)) {
                                    $nodeConfig['imgwithoutalt']++;
                                } else {
                                    $nodeConfig['imgwithalt']++;
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

            $nodeConfig['title']       = $title;
            $nodeConfig['description'] = $description;

            $nodeConfig['title_length']       = mb_strlen($title);
            $nodeConfig['description_length'] = mb_strlen($description);

            $qtip = '';

            $translator = \Pimcore::getContainer()->get('translator');

            if (mb_strlen($title) > 80) {
                $nodeConfig['cls'] = 'pimcore_document_seo_warning';
                $qtip .= $translator->trans('The title is too long, it should have 5 to 80 characters.', [], 'admin') . '<br>';
            }

            if (mb_strlen($title) < 5) {
                $nodeConfig['cls'] = 'pimcore_document_seo_warning';
                $qtip .= $translator->trans('The title is too short, it should have 5 to 80 characters.', [], 'admin') . '<br>';
            }

            if (mb_strlen($description) > 180) {
                $nodeConfig['cls'] = 'pimcore_document_seo_warning';
                $qtip .= $translator->trans('The description is too long, it should have 20 to 180 characters.', [], 'admin') . '<br>';
            }

            if (mb_strlen($description) < 20) {
                $nodeConfig['cls'] = 'pimcore_document_seo_warning';
                $qtip .= $translator->trans('The description is too short, it should have 20 to 180 characters.', [], 'admin') . '<br>';
            }

            if ($nodeConfig['h1'] != 1) {
                $nodeConfig['cls'] = 'pimcore_document_seo_warning';
                $qtip .= sprintf($translator->trans('The document should have one h1, but has %s.', [], 'admin'), $nodeConfig['h1']) . '<br>';
            }

            if ($nodeConfig['hx'] < 1) {
                $nodeConfig['cls'] = 'pimcore_document_seo_warning';
                $qtip .= $translator->trans('The document should some headlines other than h1, but has none.', [], 'admin') . '<br>';
            }

            if ($qtip) {
                $nodeConfig['qtip'] = $qtip;
            }
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
        $this->checkActionPermission($event, 'documents', ['docTypesAction']);

        $this->_documentService = new Document\Service($this->getUser());
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
