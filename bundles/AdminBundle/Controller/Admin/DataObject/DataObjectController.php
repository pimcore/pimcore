<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\DataObject;

use Pimcore\Bundle\AdminBundle\Controller\Admin\ElementControllerBase;
use Pimcore\Bundle\AdminBundle\Controller\Traits\AdminStyleTrait;
use Pimcore\Bundle\AdminBundle\Controller\Traits\ApplySchedulerDataTrait;
use Pimcore\Bundle\AdminBundle\Helper\GridHelperService;
use Pimcore\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Controller\Traits\ElementEditLockHelperTrait;
use Pimcore\Db;
use Pimcore\Event\Admin\ElementAdminStyleEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\Element;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\Version;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route("/object", name="pimcore_admin_dataobject_dataobject_")
 *
 * @internal
 */
class DataObjectController extends ElementControllerBase implements KernelControllerEventInterface
{
    use AdminStyleTrait;
    use ElementEditLockHelperTrait;
    use ApplySchedulerDataTrait;
    use DataObjectActionsTrait;

    /**
     * @var DataObject\Service
     */
    protected DataObject\Service $_objectService;

    /**
     * @var array
     */
    private array $objectData = [];

    /**
     * @var array
     */
    private array $metaData = [];

    /**
     * @Route("/tree-get-childs-by-id", name="treegetchildsbyid", methods={"GET"})
     *
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     */
    public function treeGetChildsByIdAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        $allParams = array_merge($request->request->all(), $request->query->all());
        $filter = $request->get('filter');
        $object = DataObject::getById((int) $request->get('node'));
        $objectTypes = [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER];
        $objects = [];
        $cv = false;
        $offset = $total = $limit = $filteredTotalCount = 0;

        if ($object instanceof DataObject\Concrete) {
            $class = $object->getClass();
            if ($class->getShowVariants()) {
                $objectTypes = DataObject::$types;
            }
        }

        if ($object->hasChildren($objectTypes)) {
            $offset = (int)$request->get('start');
            $limit = (int)$request->get('limit', 100000000);
            if ($view = $request->get('view', false)) {
                $cv = Element\Service::getCustomViewById($request->get('view'));
            }

            if (!is_null($filter)) {
                if (substr($filter, -1) != '*') {
                    $filter .= '*';
                }
                $filter = str_replace('*', '%', $filter);
                $limit = 100;
            }

            $childrenList = new DataObject\Listing();
            $childrenList->setCondition($this->buildChildrenCondition($object, $filter, $view));
            $childrenList->setLimit($limit);
            $childrenList->setOffset($offset);

            if ($object->getChildrenSortBy() === 'index') {
                $childrenList->setOrderKey('objects.o_index ASC', false);
            } else {
                $childrenList->setOrderKey(
                    sprintf(
                        'CAST(objects.o_%s AS CHAR CHARACTER SET utf8) COLLATE utf8_general_ci %s',
                        $object->getChildrenSortBy(), $object->getChildrenSortOrder()
                    ),
                    false
                );
            }
            $childrenList->setObjectTypes($objectTypes);

            Element\Service::addTreeFilterJoins($cv, $childrenList);

            $beforeListLoadEvent = new GenericEvent($this, [
                'list' => $childrenList,
                'context' => $allParams,
            ]);
            $eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::OBJECT_LIST_BEFORE_LIST_LOAD);

            /** @var DataObject\Listing $childrenList */
            $childrenList = $beforeListLoadEvent->getArgument('list');

            $children = $childrenList->load();
            $filteredTotalCount = $childrenList->getTotalCount();

            foreach ($children as $child) {
                $objectTreeNode = $this->getTreeNodeConfig($child);
                // this if is obsolete since as long as the change with #11714 about list on line 175-179 are working fine, we already filter the list=1 there
                if ($objectTreeNode['permissions']['list'] == 1) {
                    $objects[] = $objectTreeNode;
                }
            }

            //pagination for custom view
            $total = $cv
                ? $filteredTotalCount
                : $object->getChildAmount(null, $this->getAdminUser());
        }

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $event = new GenericEvent($this, [
            'objects' => $objects,
        ]);
        $eventDispatcher->dispatch($event, AdminEvents::OBJECT_TREE_GET_CHILDREN_BY_ID_PRE_SEND_DATA);

        $objects = $event->getArgument('objects');

        if ($limit) {
            return $this->adminJson([
                'offset' => $offset,
                'limit' => $limit,
                'total' => $total,
                'overflow' => !is_null($filter) && ($filteredTotalCount > $limit),
                'nodes' => $objects,
                'fromPaging' => (int)$request->get('fromPaging'),
                'filter' => $request->get('filter') ? $request->get('filter') : '',
                'inSearch' => (int)$request->get('inSearch'),
            ]);
        }

        return $this->adminJson($objects);
    }

    /**
     * @param DataObject\AbstractObject $object
     * @param string|null $filter
     * @param string|null $view
     *
     * @return string
     */
    private function buildChildrenCondition(DataObject\AbstractObject $object, ?string $filter, ?string $view): string
    {
        $condition = "objects.o_parentId = '" . $object->getId() . "'";

        // custom views start
        if ($view) {
            $cv = Element\Service::getCustomViewById($view);

            if (!empty($cv['classes'])) {
                $cvConditions = [];
                $cvClasses = $cv['classes'];
                foreach ($cvClasses as $key => $cvClass) {
                    $cvConditions[] = "objects.o_classId = '" . $key . "'";
                }

                $cvConditions[] = "objects.o_type = 'folder'";
                $condition .= ' AND (' . implode(' OR ', $cvConditions) . ')';
            }
        }
        // custom views end

        if (!$this->getAdminUser()->isAdmin()) {
            $userIds = $this->getAdminUser()->getRoles();
            $currentUserId = $this->getAdminUser()->getId();
            $userIds[] = $currentUserId;

            $inheritedPermission = $object->getDao()->isInheritingPermission('list', $userIds);

            $anyAllowedRowOrChildren = 'EXISTS(SELECT list FROM users_workspaces_object uwo WHERE userId IN (' . implode(',', $userIds) . ') AND list=1 AND LOCATE(CONCAT(objects.o_path,objects.o_key),cpath)=1 AND
                NOT EXISTS(SELECT list FROM users_workspaces_object WHERE userId =' . $currentUserId . '  AND list=0 AND cpath = uwo.cpath))';
            $isDisallowedCurrentRow = 'EXISTS(SELECT list FROM users_workspaces_object WHERE userId IN (' . implode(',', $userIds) . ')  AND cid = objects.o_id AND list=0)';

            $condition .= ' AND IF(' . $anyAllowedRowOrChildren . ',1,IF(' . $inheritedPermission . ', ' . $isDisallowedCurrentRow . ' = 0, 0)) = 1';
        }

        if (!is_null($filter)) {
            $db = Db::get();
            $condition .= ' AND CAST(objects.o_key AS CHAR CHARACTER SET utf8) COLLATE utf8_general_ci LIKE ' . $db->quote($filter);
        }

        return $condition;
    }

    /**
     * @param DataObject\AbstractObject $element
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function getTreeNodeConfig($element): array
    {
        $child = $element;

        $tmpObject = [
            'id' => $child->getId(),
            'idx' => (int)$child->getIndex(),
            'key' => $child->getKey(),
            'sortBy' => $child->getChildrenSortBy(),
            'sortOrder' => $child->getChildrenSortOrder(),
            'text' => htmlspecialchars($child->getKey()),
            'type' => $child->getType(),
            'path' => $child->getRealFullPath(),
            'basePath' => $child->getRealPath(),
            'elementType' => 'object',
            'locked' => $child->isLocked(),
            'lockOwner' => $child->getLocked() ? true : false,
        ];

        $allowedTypes = [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER];
        if ($child instanceof DataObject\Concrete && $child->getClass()->getShowVariants()) {
            $allowedTypes[] = DataObject::OBJECT_TYPE_VARIANT;
        }

        $hasChildren = $child->getDao()->hasChildren($allowedTypes, null, $this->getAdminUser());

        $tmpObject['allowDrop'] = false;

        $tmpObject['isTarget'] = true;
        if ($tmpObject['type'] != DataObject::OBJECT_TYPE_VARIANT) {
            $tmpObject['allowDrop'] = true;
        }

        $tmpObject['allowChildren'] = true;
        $tmpObject['leaf'] = !$hasChildren;
        $tmpObject['cls'] = 'pimcore_class_icon ';

        if ($child instanceof DataObject\Concrete) {
            $tmpObject['published'] = $child->isPublished();
            $tmpObject['className'] = $child->getClass()->getName();

            if (!$child->isPublished()) {
                $tmpObject['cls'] .= 'pimcore_unpublished ';
            }

            $tmpObject['allowVariants'] = $child->getClass()->getAllowVariants();
        }

        $this->addAdminStyle($child, ElementAdminStyleEvent::CONTEXT_TREE, $tmpObject);

        $tmpObject['expanded'] = !$hasChildren;
        $tmpObject['permissions'] = $child->getUserPermissions($this->getAdminUser());

        if ($child->isLocked()) {
            $tmpObject['cls'] .= 'pimcore_treenode_locked ';
        }
        if ($child->getLocked()) {
            $tmpObject['cls'] .= 'pimcore_treenode_lockOwner ';
        }

        if ($tmpObject['leaf']) {
            $tmpObject['expandable'] = false;
            $tmpObject['leaf'] = false; //this is required to allow drag&drop
            $tmpObject['expanded'] = true;
            $tmpObject['loaded'] = true;
        }

        return $tmpObject;
    }

    /**
     * @Route("/get-id-path-paging-info", name="getidpathpaginginfo", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getIdPathPagingInfoAction(Request $request): JsonResponse
    {
        $path = $request->get('path');
        $pathParts = explode('/', $path);
        $id = (int) array_pop($pathParts);

        $limit = $request->get('limit');

        if (empty($limit)) {
            $limit = 30;
        }

        $data = [];

        $targetObject = DataObject::getById($id);
        $object = $targetObject;

        while ($parent = $object->getParent()) {
            $list = new DataObject\Listing();
            $list->setCondition('o_parentId = ?', $parent->getId());
            $list->setUnpublished(true);
            $total = $list->getTotalCount();

            $info = [
                'total' => $total,
            ];

            if ($total > $limit) {
                $idList = $list->loadIdList();
                $position = array_search($object->getId(), $idList);
                $info['position'] = $position + 1;
                $info['page'] = ceil($info['position'] / $limit);
            }

            $data[$parent->getId()] = $info;

            $object = $parent;
        }

        return $this->adminJson($data);
    }

    /**
     * @Route("/get", name="get", methods={"GET"})
     *
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getAction(Request $request, EventDispatcherInterface $eventDispatcher): JsonResponse
    {
        $objectId = (int)$request->get('id');
        $objectFromDatabase = DataObject\Concrete::getById($objectId);
        if ($objectFromDatabase === null) {
            return $this->adminJson(['success' => false, 'message' => 'element_not_found'], JsonResponse::HTTP_NOT_FOUND);
        }
        $objectFromDatabase = clone $objectFromDatabase;

        // set the latest available version for editmode
        $draftVersion = null;
        $object = $this->getLatestVersion($objectFromDatabase, $draftVersion);

        // check for lock
        if ($object->isAllowed('save') || $object->isAllowed('publish') || $object->isAllowed('unpublish') || $object->isAllowed('delete')) {
            if (Element\Editlock::isLocked($objectId, 'object')) {
                return $this->getEditLockResponse($objectId, 'object');
            }

            Element\Editlock::lock($request->get('id'), 'object');
        }

        // we need to know if the latest version is published or not (a version), because of lazy loaded fields in $this->getDataForObject()
        $objectFromVersion = $object !== $objectFromDatabase;

        if ($object->isAllowed('view')) {
            $objectData = [];

            /** -------------------------------------------------------------
             *   Load some general data from published object (if existing)
             *  ------------------------------------------------------------- */
            $objectData['idPath'] = Element\Service::getIdPath($objectFromDatabase);

            $previewGenerator = $objectFromDatabase->getClass()->getPreviewGenerator();
            $linkGeneratorReference = $objectFromDatabase->getClass()->getLinkGeneratorReference();

            $objectData['hasPreview'] = false;
            if ($objectFromDatabase->getClass()->getPreviewUrl() || $linkGeneratorReference || $previewGenerator) {
                $objectData['hasPreview'] = true;
            }

            if ($draftVersion && $objectFromDatabase->getModificationDate() < $draftVersion->getDate()) {
                $objectData['draft'] = [
                    'id' => $draftVersion->getId(),
                    'modificationDate' => $draftVersion->getDate(),
                    'isAutoSave' => $draftVersion->isAutoSave(),
                ];
            }

            $objectData['general'] = [];

            $allowedKeys = ['o_published', 'o_key', 'o_id', 'o_creationDate', 'o_classId', 'o_className', 'o_type', 'o_parentId', 'o_userOwner'];
            foreach ($objectFromDatabase->getObjectVars() as $key => $value) {
                if (in_array($key, $allowedKeys)) {
                    $objectData['general'][$key] = $value;
                }
            }
            $objectData['general']['fullpath'] = $objectFromDatabase->getRealFullPath();
            $objectData['general']['o_locked'] = $objectFromDatabase->isLocked();
            $objectData['general']['php'] = [
                'classes' => array_merge([get_class($objectFromDatabase)], array_values(class_parents($objectFromDatabase))),
                'interfaces' => array_values(class_implements($objectFromDatabase)),
            ];
            $objectData['general']['allowInheritance'] = $objectFromDatabase->getClass()->getAllowInherit();
            $objectData['general']['allowVariants'] = $objectFromDatabase->getClass()->getAllowVariants();
            $objectData['general']['showVariants'] = $objectFromDatabase->getClass()->getShowVariants();
            $objectData['general']['showAppLoggerTab'] = $objectFromDatabase->getClass()->getShowAppLoggerTab();
            $objectData['general']['showFieldLookup'] = $objectFromDatabase->getClass()->getShowFieldLookup();
            if ($objectFromDatabase instanceof DataObject\Concrete) {
                $objectData['general']['linkGeneratorReference'] = $linkGeneratorReference;
                if ($previewGenerator) {
                    $objectData['general']['previewConfig'] = $previewGenerator->getPreviewConfig($objectFromDatabase);
                }
            }

            $objectData['layout'] = $objectFromDatabase->getClass()->getLayoutDefinitions();
            $objectData['userPermissions'] = $objectFromDatabase->getUserPermissions($this->getAdminUser());
            $objectVersions = Element\Service::getSafeVersionInfo($objectFromDatabase->getVersions());
            $objectData['versions'] = array_splice($objectVersions, -1, 1);
            $objectData['scheduledTasks'] = array_map(
                static function (Task $task) {
                    return $task->getObjectVars();
                },
                $objectFromDatabase->getScheduledTasks()
            );

            $objectData['childdata']['id'] = $objectFromDatabase->getId();
            $objectData['childdata']['data']['classes'] = $this->prepareChildClasses($objectFromDatabase->getDao()->getClasses());
            $objectData['childdata']['data']['general'] = $objectData['general'];
            /** -------------------------------------------------------------
             *   Load remaining general data from latest version
             *  ------------------------------------------------------------- */
            $allowedKeys = ['o_modificationDate', 'o_userModification'];
            foreach ($object->getObjectVars() as $key => $value) {
                if (in_array($key, $allowedKeys)) {
                    $objectData['general'][$key] = $value;
                }
            }

            $this->getDataForObject($object, $objectFromVersion);
            $objectData['data'] = $this->objectData;
            $objectData['metaData'] = $this->metaData;
            $objectData['properties'] = Element\Service::minimizePropertiesForEditmode($object->getProperties());

            // this used for the "this is not a published version" hint
            // and for adding the published icon to version overview
            $objectData['general']['versionDate'] = $objectFromDatabase->getModificationDate();
            $objectData['general']['versionCount'] = $objectFromDatabase->getVersionCount();

            $this->addAdminStyle($object, ElementAdminStyleEvent::CONTEXT_EDITOR, $objectData['general']);

            $currentLayoutId = $request->get('layoutId', 0);

            $validLayouts = DataObject\Service::getValidLayouts($object);

            //Fallback if $currentLayoutId is not set or empty string
            //Uses first valid layout instead of admin layout when empty
            $ok = false;
            foreach ($validLayouts as $layout) {
                if ($currentLayoutId == $layout->getId()) {
                    $ok = true;
                }
            }

            if (!$ok) {
                $currentLayoutId = null;
            }

            //master layout has id 0 so we check for is_null()
            if ($currentLayoutId === null && !empty($validLayouts)) {
                if (count($validLayouts) === 1) {
                    $firstLayout = reset($validLayouts);
                    $currentLayoutId = $firstLayout->getId();
                } else {
                    foreach ($validLayouts as $checkDefaultLayout) {
                        if ($checkDefaultLayout->getDefault()) {
                            $currentLayoutId = $checkDefaultLayout->getId();
                        }
                    }
                }
            }

            if ($currentLayoutId === null && count($validLayouts) > 0) {
                $currentLayoutId = reset($validLayouts)->getId();
            }

            if (!empty($validLayouts)) {
                $objectData['validLayouts'] = [];

                foreach ($validLayouts as $validLayout) {
                    $objectData['validLayouts'][] = ['id' => $validLayout->getId(), 'name' => $validLayout->getName()];
                }

                $user = Tool\Admin::getCurrentUser();

                if ($currentLayoutId == -1 && $user->isAdmin()) {
                    $layout = DataObject\Service::getSuperLayoutDefinition($object);
                    $objectData['layout'] = $layout;
                } elseif (!empty($currentLayoutId)) {
                    $objectData['layout'] = $validLayouts[$currentLayoutId]->getLayoutDefinitions();
                }

                $objectData['currentLayoutId'] = $currentLayoutId;
            }

            //Hook for modifying return value - e.g. for changing permissions based on object data
            //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
            $event = new GenericEvent($this, [
                'data' => $objectData,
                'object' => $object,
            ]);

            DataObject\Service::enrichLayoutDefinition($objectData['layout'], $object);
            $eventDispatcher->dispatch($event, AdminEvents::OBJECT_GET_PRE_SEND_DATA);
            $data = $event->getArgument('data');

            DataObject\Service::removeElementFromSession('object', $object->getId());

            return $this->adminJson($data);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @param DataObject\Concrete $object
     * @param bool $objectFromVersion
     */
    private function getDataForObject(DataObject\Concrete $object, $objectFromVersion = false)
    {
        foreach ($object->getClass()->getFieldDefinitions(['object' => $object]) as $key => $def) {
            $this->getDataForField($object, $key, $def, $objectFromVersion);
        }
    }

    /**
     * gets recursively attribute data from parent and fills objectData and metaData
     *
     * @param DataObject\Concrete $object
     * @param string $key
     * @param DataObject\ClassDefinition\Data $fielddefinition
     * @param bool $objectFromVersion
     * @param int $level
     */
    private function getDataForField($object, $key, $fielddefinition, $objectFromVersion, $level = 0)
    {
        $parent = DataObject\Service::hasInheritableParentObject($object);
        $getter = 'get' . ucfirst($key);

        // Editmode optimization for lazy loaded relations (note that this is just for AbstractRelations, not for all
        // LazyLoadingSupportInterface types. It tries to optimize fetching the data needed for the editmode without
        // loading the entire target element.
        // ReverseObjectRelation should go in there anyway (regardless if it a version or not),
        // so that the values can be loaded.
        if (
            (!$objectFromVersion && $fielddefinition instanceof AbstractRelations)
            || $fielddefinition instanceof ReverseObjectRelation
        ) {
            $refId = null;

            if ($fielddefinition instanceof ReverseObjectRelation) {
                $refKey = $fielddefinition->getOwnerFieldName();
                $refClass = DataObject\ClassDefinition::getByName($fielddefinition->getOwnerClassName());
                if ($refClass) {
                    $refId = $refClass->getId();
                }
            } else {
                $refKey = $key;
            }

            $relations = $object->getRelationData($refKey, !$fielddefinition instanceof ReverseObjectRelation, $refId);

            if ($fielddefinition->supportsInheritance() && empty($relations) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $objectFromVersion, $level + 1);
            } else {
                $data = [];

                if ($fielddefinition instanceof DataObject\ClassDefinition\Data\ManyToOneRelation) {
                    if (isset($relations[0])) {
                        $data = $relations[0];
                        $data['published'] = (bool)$data['published'];
                    } else {
                        $data = null;
                    }
                } elseif (
                    ($fielddefinition instanceof DataObject\ClassDefinition\Data\OptimizedAdminLoadingInterface && $fielddefinition->isOptimizedAdminLoading())
                    || ($fielddefinition instanceof ManyToManyObjectRelation && !$fielddefinition->getVisibleFields() && !$fielddefinition instanceof DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation)
                ) {
                    foreach ($relations as $rkey => $rel) {
                        $index = $rkey + 1;
                        $rel['fullpath'] = $rel['path'];
                        $rel['classname'] = $rel['subtype'];
                        $rel['rowId'] = $rel['id'] . AbstractRelations::RELATION_ID_SEPARATOR . $index . AbstractRelations::RELATION_ID_SEPARATOR . $rel['type'];
                        $rel['published'] = (bool)$rel['published'];
                        $data[] = $rel;
                    }
                } else {
                    $fieldData = $object->$getter();
                    $data = $fielddefinition->getDataForEditmode($fieldData, $object, ['objectFromVersion' => $objectFromVersion]);
                }
                $this->objectData[$key] = $data;
                $this->metaData[$key]['objectid'] = $object->getId();
                $this->metaData[$key]['inherited'] = $level != 0;
            }
        } else {
            $fieldData = $object->$getter();
            $isInheritedValue = false;

            if ($fielddefinition instanceof DataObject\ClassDefinition\Data\CalculatedValue) {
                $fieldData = new DataObject\Data\CalculatedValue($fielddefinition->getName());
                $fieldData->setContextualData('object', null, null, null, null, null, $fielddefinition);
                $value = $fielddefinition->getDataForEditmode($fieldData, $object, ['objectFromVersion' => $objectFromVersion]);
            } else {
                $value = $fielddefinition->getDataForEditmode($fieldData, $object, ['objectFromVersion' => $objectFromVersion]);
            }

            // following some exceptions for special data types (localizedfields, objectbricks)
            if ($value && ($fieldData instanceof DataObject\Localizedfield || $fieldData instanceof DataObject\Classificationstore)) {
                // make sure that the localized field participates in the inheritance detection process
                $isInheritedValue = $value['inherited'];
            }
            if ($fielddefinition instanceof DataObject\ClassDefinition\Data\Objectbricks && is_array($value)) {
                // make sure that the objectbricks participate in the inheritance detection process
                foreach ($value as $singleBrickData) {
                    if (!empty($singleBrickData['inherited'])) {
                        $isInheritedValue = true;
                    }
                }
            }

            if ($fielddefinition->isEmpty($fieldData) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $objectFromVersion, $level + 1);
                // exception for classification store. if there are no items then it is empty by definition.
                // consequence is that we have to preserve the metadata information
                // see https://github.com/pimcore/pimcore/issues/9329
                if ($fielddefinition instanceof DataObject\ClassDefinition\Data\Classificationstore && $level == 0) {
                    $this->objectData[$key]['metaData'] = $value['metaData'] ?? [];
                    $this->objectData[$key]['inherited'] = true;
                }
            } else {
                $isInheritedValue = $isInheritedValue || ($level != 0);
                $this->metaData[$key]['objectid'] = $object->getId();

                $this->objectData[$key] = $value;
                $this->metaData[$key]['inherited'] = $isInheritedValue;

                if ($isInheritedValue && !$fielddefinition->isEmpty($fieldData) && !$fielddefinition->supportsInheritance()) {
                    $this->objectData[$key] = null;
                    $this->metaData[$key]['inherited'] = false;
                    $this->metaData[$key]['hasParentValue'] = true;
                }
            }
        }
    }

    /**
     * @Route("/get-folder", name="getfolder", methods={"GET"})
     *
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     */
    public function getFolderAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        $objectId = (int)$request->get('id');
        $object = DataObject::getById($objectId);

        if (!$object) {
            throw $this->createNotFoundException();
        }

        if ($object->isAllowed('view')) {
            $objectData = [];

            $objectData['general'] = [];
            $objectData['idPath'] = Element\Service::getIdPath($object);
            $objectData['type'] = $object->getType();
            $allowedKeys = ['o_published', 'o_key', 'o_id', 'o_type', 'o_path', 'o_modificationDate', 'o_creationDate', 'o_userOwner', 'o_userModification'];
            foreach ($object->getObjectVars() as $key => $value) {
                if (strstr($key, 'o_') && in_array($key, $allowedKeys)) {
                    $objectData['general'][$key] = $value;
                }
            }
            $objectData['general']['fullpath'] = $object->getRealFullPath();

            $objectData['general']['o_locked'] = $object->isLocked();

            $objectData['properties'] = Element\Service::minimizePropertiesForEditmode($object->getProperties());
            $objectData['userPermissions'] = $object->getUserPermissions($this->getAdminUser());
            $objectData['classes'] = $this->prepareChildClasses($object->getDao()->getClasses());

            // grid-config
            $configFile = PIMCORE_CONFIGURATION_DIRECTORY . '/object/grid/' . $object->getId() . '-user_' . $this->getAdminUser()->getId() . '.psf';
            if (is_file($configFile)) {
                $gridConfig = Tool\Serialize::unserialize(file_get_contents($configFile));
                if ($gridConfig) {
                    $selectedClassId = $gridConfig['classId'];

                    foreach ($objectData['classes'] as $class) {
                        if ($class['id'] == $selectedClassId) {
                            $objectData['selectedClass'] = $selectedClassId;

                            break;
                        }
                    }
                }
            }

            //Hook for modifying return value - e.g. for changing permissions based on object data
            //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
            $event = new GenericEvent($this, [
                'data' => $objectData,
                'object' => $object,
            ]);
            $eventDispatcher->dispatch($event, AdminEvents::OBJECT_GET_PRE_SEND_DATA);
            $objectData = $event->getArgument('data');

            return $this->adminJson($objectData);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @param DataObject\ClassDefinition[] $classes
     *
     * @return array
     */
    protected function prepareChildClasses(array $classes): array
    {
        $reduced = [];
        foreach ($classes as $class) {
            $reduced[] = [
                'id' => $class->getId(),
                'name' => $class->getName(),
                'inheritance' => $class->getAllowInherit(),
            ];
        }

        return $reduced;
    }

    /**
     * @Route("/add", name="add", methods={"POST"})
     *
     * @param Request $request
     * @param Model\FactoryInterface $modelFactory
     *
     * @return JsonResponse
     */
    public function addAction(Request $request, Model\FactoryInterface $modelFactory): JsonResponse
    {
        $message = '';
        $parent = DataObject::getById((int) $request->get('parentId'));

        if (!$parent->isAllowed('create')) {
            $message = 'prevented adding object because of missing permissions';
            Logger::debug($message);
        }

        $intendedPath = $parent->getRealFullPath() . '/' . $request->get('key');
        if (DataObject\Service::pathExists($intendedPath)) {
            $message = 'prevented creating object because object with same path+key already exists';
            Logger::debug($message);
        }

        //return false if missing permissions or path+key already exists
        if (!empty($message)) {
            return $this->adminJson([
                'success' => false,
                'message' => $message,
            ]);
        }

        $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($request->get('className'));
        /** @var DataObject\Concrete $object */
        $object = $modelFactory->build($className);
        $object->setOmitMandatoryCheck(true); // allow to save the object although there are mandatory fields
        $object->setClassId($request->get('classId'));

        if ($request->get('variantViaTree')) {
            $parentId = $request->get('parentId');
            $parent = DataObject\Concrete::getById($parentId);
            $object->setClassId($parent->getClass()->getId());
        }

        $object->setClassName($request->get('className'));
        $object->setParentId($request->get('parentId'));
        $object->setKey($request->get('key'));
        $object->setCreationDate(time());
        $object->setUserOwner($this->getAdminUser()->getId());
        $object->setUserModification($this->getAdminUser()->getId());
        $object->setPublished(false);

        $objectType = $request->get('objecttype');
        if (in_array($objectType, [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_VARIANT])) {
            $object->setType($objectType);
        }

        try {
            $object->save();
            $return = [
                'success' => true,
                'id' => $object->getId(),
                'type' => $object->getType(),
                'message' => $message,
            ];
        } catch (\Exception $e) {
            $return = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $this->adminJson($return);
    }

    /**
     * @Route("/add-folder", name="addfolder", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addFolderAction(Request $request)
    {
        $success = false;

        $parent = DataObject::getById((int) $request->get('parentId'));
        if ($parent->isAllowed('create')) {
            if (!DataObject\Service::pathExists($parent->getRealFullPath() . '/' . $request->get('key'))) {
                $folder = DataObject\Folder::create([
                    'o_parentId' => $request->get('parentId'),
                    'o_creationDate' => time(),
                    'o_userOwner' => $this->getAdminUser()->getId(),
                    'o_userModification' => $this->getAdminUser()->getId(),
                    'o_key' => $request->get('key'),
                    'o_published' => true,
                ]);

                try {
                    $folder->save();
                    $success = true;
                } catch (\Exception $e) {
                    return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                }
            }
        } else {
            Logger::debug('prevented creating object id because of missing permissions');
        }

        return $this->adminJson(['success' => $success]);
    }

    /**
     * @Route("/delete", name="delete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function deleteAction(Request $request)
    {
        $type = $request->get('type');

        if ($type === 'childs') {
            trigger_deprecation(
                'pimcore/pimcore',
                '10.4',
                'Type childs is deprecated. Use children instead'
            );
            $type = 'children';
        }
        if ($type === 'children') {
            $parentObject = DataObject::getById((int) $request->get('id'));

            $list = new DataObject\Listing();
            $list->setCondition('o_path LIKE ' . $list->quote($list->escapeLike($parentObject->getRealFullPath()) . '/%'));
            $list->setLimit((int)$request->get('amount'));
            $list->setOrderKey('LENGTH(o_path)', false);
            $list->setOrder('DESC');

            $deletedItems = [];
            foreach ($list as $object) {
                $deletedItems[$object->getId()] = $object->getRealFullPath();
                if ($object->isAllowed('delete') && !$object->isLocked()) {
                    $object->delete();
                }
            }

            return $this->adminJson(['success' => true, 'deleted' => $deletedItems]);
        }
        if ($id = $request->get('id')) {
            $object = DataObject::getById((int) $id);
            if ($object) {
                if (!$object->isAllowed('delete')) {
                    throw $this->createAccessDeniedHttpException();
                }
                if ($object->isLocked()) {
                    return $this->adminJson(['success' => false, 'message' => 'prevented deleting object, because it is locked: ID: ' . $object->getId()]);
                }
                $object->delete();
            }

            // return true, even when the object doesn't exist, this can be the case when using batch delete incl. children
            return $this->adminJson(['success' => true]);
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * @Route("/change-children-sort-by", name="changechildrensortby", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function changeChildrenSortByAction(Request $request)
    {
        $object = DataObject::getById((int) $request->get('id'));
        if ($object) {
            $sortBy = $request->get('sortBy');
            $sortOrder = $request->get('childrenSortOrder');
            if (!\in_array($sortOrder, ['ASC', 'DESC'])) {
                $sortOrder = 'ASC';
            }

            $currentSortBy = $object->getChildrenSortBy();

            $object->setChildrenSortBy($sortBy);
            $object->setChildrenSortOrder($sortOrder);

            if ($currentSortBy != $sortBy) {
                $user = Tool\Admin::getCurrentUser();

                if (!$user->isAdmin() && !$user->isAllowed('objects_sort_method')) {
                    return $this->json(['success' => false, 'message' => 'Changing the sort method is only allowed for admin users']);
                }

                if ($sortBy == 'index') {
                    $this->reindexBasedOnSortOrder($object, $sortOrder);
                }
            }

            $object->save();

            return $this->json(['success' => true]);
        }

        return $this->json(['success' => false, 'message' => 'Unable to change a sorting way of children items.']);
    }

    /**
     * @Route("/update", name="update", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function updateAction(Request $request)
    {
        $values = $this->decodeJson($request->get('values'));

        $ids = $this->decodeJson($request->get('id'));

        if (is_array($ids)) {
            foreach ($ids as $id) {
                $object = DataObject::getById((int)$id);
                if (!$this->executeUpdateAction($object, $values)) {
                    return $this->adminJson(['success' => false]);
                }
            }
        } else {
            $object = DataObject::getById((int)$ids);
            $this->executeUpdateAction($object, $values);
        }

        return $this->adminJson(['success' => true]);
    }

    private function executeUpdateAction(DataObject $object, mixed $values)
    {
        $success = false;

        if ($object instanceof DataObject\Concrete) {
            $object->setOmitMandatoryCheck(true);
        }

        // this prevents the user from renaming, relocating (actions in the tree) if the newest version isn't the published one
        // the reason is that otherwise the content of the newer not published version will be overwritten
        if ($object instanceof DataObject\Concrete) {
            $latestVersion = $object->getLatestVersion();
            if ($latestVersion && $latestVersion->getData()->getModificationDate() != $object->getModificationDate()) {
                return $this->adminJson(['success' => false, 'message' => "You can't rename or relocate if there's a newer not published version"]);
            }
        }

        $key = $values['key'] ?? null;

        if ($object->isAllowed('settings')) {
            if ($key) {
                if ($object->isAllowed('rename')) {
                    $object->setKey($key);
                } elseif ($key !== $object->getKey()) {
                    Logger::debug('prevented renaming object because of missing permissions ');
                }
            }

            if (!empty($values['parentId'])) {
                $parent = DataObject::getById($values['parentId']);

                //check if parent is changed
                if ($object->getParentId() != $parent->getId()) {
                    if (!$parent->isAllowed('create')) {
                        throw new \Exception('Prevented moving object - no create permission on new parent ');
                    }

                    $objectWithSamePath = DataObject::getByPath($parent->getRealFullPath() . '/' . $object->getKey());

                    if ($objectWithSamePath != null) {
                        return $this->adminJson(['success' => false, 'message' => 'prevented creating object because object with same path+key already exists']);
                    }

                    if ($object->isLocked()) {
                        return $this->adminJson(['success' => false, 'message' => 'prevented moving object, because it is locked: ID: ' . $object->getId()]);
                    }

                    $object->setParentId($values['parentId']);
                }
            }

            if (array_key_exists('locked', $values)) {
                $object->setLocked($values['locked']);
            }

            $object->setModificationDate(time());
            $object->setUserModification($this->getAdminUser()->getId());

            try {
                $isIndexUpdate = isset($values['indices']);

                if ($isIndexUpdate) {
                    // Ensure the update sort index is already available in the postUpdate eventListener
                    $indexUpdate = is_int($values['indices']) ? $values['indices'] : $values['indices'][$object->getId()];
                    $object->setIndex($indexUpdate);
                }

                $object->save();

                if ($isIndexUpdate) {
                    $this->updateIndexesOfObjectSiblings($object, $indexUpdate);
                }

                $success = true;
            } catch (\Exception $e) {
                Logger::error((string) $e);

                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
            }
        } elseif ($key && $object->isAllowed('rename')) {
            return $this->adminJson($this->renameObject($object, $key));
        } else {
            Logger::debug('prevented update object because of missing permissions.');
        }

        return $success;
    }

    private function executeInsideTransaction(callable $fn)
    {
        $maxRetries = 5;
        for ($retries = 0; $retries < $maxRetries; $retries++) {
            try {
                Db::get()->beginTransaction();

                $fn();

                Db::get()->commit();

                break;
            } catch (\Exception $e) {
                Db::get()->rollBack();

                // we try to start the transaction $maxRetries times again (deadlocks, ...)
                if ($retries < ($maxRetries - 1)) {
                    $run = $retries + 1;
                    $waitTime = rand(1, 5) * 100000; // microseconds
                    Logger::warn('Unable to finish transaction (' . $run . ". run) because of the following reason '" . $e->getMessage() . "'. --> Retrying in " . $waitTime . ' microseconds ... (' . ($run + 1) . ' of ' . $maxRetries . ')');

                    usleep($waitTime); // wait specified time until we restart the transaction
                } else {
                    // if the transaction still fail after $maxRetries retries, we throw out the exception
                    Logger::error('Finally giving up restarting the same transaction again and again, last message: ' . $e->getMessage());

                    throw $e;
                }
            }
        }
    }

    /**
     * @param DataObject\AbstractObject $parentObject
     * @param string $currentSortOrder
     */
    protected function reindexBasedOnSortOrder(DataObject\AbstractObject $parentObject, string $currentSortOrder)
    {
        $fn = function () use ($parentObject, $currentSortOrder) {
            $list = new DataObject\Listing();

            $db = Db::get();
            $result = $db->executeStatement(
                'UPDATE '.$list->getDao()->getTableName().' o,
                    (
                    SELECT newIndex, o_id FROM (
                        SELECT @n := @n +1 AS newIndex, o_id
                        FROM '.$list->getDao()->getTableName().',
                                (SELECT @n := -1) variable
                                 WHERE o_parentId = ? ORDER BY o_key ' . $currentSortOrder
                               .') tmp
                    ) order_table
                    SET o.o_index = order_table.newIndex
                    WHERE o.o_id=order_table.o_id',
                [
                    $parentObject->getId(),
                ]
            );

            $db = Db::get();
            $children = $db->fetchAllAssociative(
                'SELECT o_id, o_modificationDate, o_versionCount FROM objects'
                .' WHERE o_parentId = ? ORDER BY o_index ASC',
                [$parentObject->getId()]
            );
            $index = 0;

            foreach ($children as $child) {
                $this->updateLatestVersionIndex($child['o_id'], $child['o_modificationDate']);
                $index++;

                DataObject::clearDependentCacheByObjectId($child['o_id']);
            }
        };

        $this->executeInsideTransaction($fn);
    }

    private function updateLatestVersionIndex($objectId, $newIndex)
    {
        $object = DataObject\Concrete::getById($objectId);

        if (
            $object &&
            $object->getType() != DataObject::OBJECT_TYPE_FOLDER &&
            $latestVersion = $object->getLatestVersion()
        ) {
            // don't renew references (which means loading the target elements)
            // Not needed as we just save a new version with the updated index
            $object = $latestVersion->loadData(false);
            if ($newIndex !== $object->getIndex()) {
                $object->setIndex($newIndex);
            }
            $latestVersion->save();
        }
    }

    /**
     * @param DataObject\AbstractObject $updatedObject
     * @param int $newIndex
     */
    protected function updateIndexesOfObjectSiblings(DataObject\AbstractObject $updatedObject, $newIndex)
    {
        $fn = function () use ($updatedObject, $newIndex) {
            $list = new DataObject\Listing();
            $updatedObject->saveIndex($newIndex);

            // The cte and the limit are needed to order the data before the newIndex is set
            $db = Db::get();
            $db->executeStatement(
                'UPDATE '.$list->getDao()->getTableName().' o,
                    (
                        SELECT newIndex, o_id
                        FROM (
                            With cte As (SELECT o_index, o_id FROM ' . $list->getDao()->getTableName() . ' WHERE o_parentId = ? AND o_id != ? AND o_type IN (\''.implode(
                    "','", [
                        DataObject::OBJECT_TYPE_OBJECT,
                        DataObject::OBJECT_TYPE_VARIANT,
                        DataObject::OBJECT_TYPE_FOLDER,
                    ]
                ).'\') ORDER BY o_index LIMIT '. $updatedObject->getParent()->getChildAmount() .')
                            SELECT @n := IF(@n = ? - 1,@n + 2,@n + 1) AS newIndex, o_id
                            FROM cte,
                            (SELECT @n := -1) variable
                        ) tmp
                    ) order_table
                    SET o.o_index = order_table.newIndex
                    WHERE o.o_id=order_table.o_id',
                [
                    $updatedObject->getParentId(),
                    $updatedObject->getId(),
                    $newIndex,
                ]
            );

            $siblings = $db->fetchAllAssociative(
                'SELECT o_id, o_modificationDate, o_versionCount, o_key, o_index FROM objects'
                ." WHERE o_parentId = ? AND o_id != ? AND o_type IN ('object', 'variant','folder') ORDER BY o_index ASC",
                [$updatedObject->getParentId(), $updatedObject->getId()]
            );
            $index = 0;

            foreach ($siblings as $sibling) {
                if ($index == $newIndex) {
                    $index++;
                }

                $this->updateLatestVersionIndex($sibling['o_id'], $index);
                $index++;

                DataObject::clearDependentCacheByObjectId($sibling['o_id']);
            }
        };

        $this->executeInsideTransaction($fn);
    }

    /**
     * @Route("/save", name="save", methods={"POST", "PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        $objectFromDatabase = DataObject\Concrete::getById((int) $request->get('id'));

        // set the latest available version for editmode
        $object = $this->getLatestVersion($objectFromDatabase);
        $object->setUserModification($this->getAdminUser()->getId());

        $objectFromVersion = $object !== $objectFromDatabase;
        $originalModificationDate = $objectFromVersion ? $object->getModificationDate() : $objectFromDatabase->getModificationDate();
        if ($objectFromVersion) {
            if (method_exists($object, 'getLocalizedFields')) {
                /** @var DataObject\Localizedfield $localizedFields */
                $localizedFields = $object->getLocalizedFields();
                $localizedFields->setLoadedAllLazyData();
            }
        }

        // data
        $data = [];
        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));
            foreach ($data as $key => $value) {
                $fd = $object->getClass()->getFieldDefinition($key);
                if ($fd) {
                    if ($fd instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                        $user = Tool\Admin::getCurrentUser();
                        if (!$user->getAdmin()) {
                            $allowedLanguages = DataObject\Service::getLanguagePermissions($object, $user, 'lEdit');
                            if (!is_null($allowedLanguages)) {
                                $allowedLanguages = array_keys($allowedLanguages);
                                $submittedLanguages = array_keys($data[$key]);
                                foreach ($submittedLanguages as $submittedLanguage) {
                                    if (!in_array($submittedLanguage, $allowedLanguages)) {
                                        unset($value[$submittedLanguage]);
                                    }
                                }
                            }
                        }
                    }

                    if ($fd instanceof ReverseObjectRelation) {
                        $remoteClass = DataObject\ClassDefinition::getByName($fd->getOwnerClassName());
                        $relations = $object->getRelationData($fd->getOwnerFieldName(), false, $remoteClass->getId());
                        $toAdd = $this->detectAddedRemoteOwnerRelations($relations, $value);
                        $toDelete = $this->detectDeletedRemoteOwnerRelations($relations, $value);
                        if (count($toAdd) > 0 || count($toDelete) > 0) {
                            $this->processRemoteOwnerRelations($object, $toDelete, $toAdd, $fd->getOwnerFieldName());
                        }
                    } else {
                        $object->setValue($key, $fd->getDataFromEditmode($value, $object, ['objectFromVersion' => $objectFromVersion]));
                    }
                }
            }
        }

        // general settings
        // @TODO: IS THIS STILL NECESSARY?
        if ($request->get('general')) {
            $general = $this->decodeJson($request->get('general'));

            // do not allow all values to be set, will cause problems (eg. icon)
            if (is_array($general) && count($general) > 0) {
                foreach ($general as $key => $value) {
                    if (!in_array($key, ['o_id', 'o_classId', 'o_className', 'o_type', 'icon', 'o_userOwner', 'o_userModification', 'o_modificationDate'])) {
                        $object->setValue($key, $value);
                    }
                }
            }
        }

        $this->assignPropertiesFromEditmode($request, $object);
        $this->applySchedulerDataToElement($request, $object);

        if (($request->get('task') === 'unpublish' && !$object->isAllowed('unpublish')) || ($request->get('task') === 'publish' && !$object->isAllowed('publish'))) {
            throw $this->createAccessDeniedHttpException();
        }

        if ($request->get('task') == 'unpublish') {
            $object->setPublished(false);
        }

        if ($request->get('task') == 'publish') {
            $object->setPublished(true);
        }

        // unpublish and save version is possible without checking mandatory fields
        if (in_array($request->get('task'), ['unpublish', 'version', 'autoSave'])) {
            $object->setOmitMandatoryCheck(true);
        }

        if (($request->get('task') == 'publish') || ($request->get('task') == 'unpublish')) {
            // disabled for now: see different approach [Elements] Show users who are working on the same element #9381
            // https://github.com/pimcore/pimcore/issues/9381
            //            if ($data) {
            //                if (!$this->performFieldcollectionModificationCheck($request, $object, $originalModificationDate, $data)) {
            //                    return $this->adminJson(['success' => false, 'message' => 'Could be that someone messed around with the fieldcollection in the meantime. Please reload and try again']);
            //                }
            //            }

            $object->save();
            $treeData = $this->getTreeNodeConfig($object);

            $newObject = DataObject::getById($object->getId(), ['force' => true]);

            if ($request->get('task') == 'publish') {
                $object->deleteAutoSaveVersions($this->getAdminUser()->getId());
            }

            return $this->adminJson([
                'success' => true,
                'general' => ['o_modificationDate' => $object->getModificationDate(),
                    'versionDate' => $newObject->getModificationDate(),
                    'versionCount' => $newObject->getVersionCount(),
                ],
                'treeData' => $treeData,
            ]);
        } elseif ($request->get('task') == 'session') {
            //TODO https://github.com/pimcore/pimcore/issues/9536
            DataObject\Service::saveElementToSession($object, '', false);

            return $this->adminJson(['success' => true]);
        } elseif ($request->get('task') == 'scheduler') {
            if ($object->isAllowed('settings')) {
                $object->saveScheduledTasks();

                return $this->adminJson(['success' => true]);
            }
        } elseif ($object->isAllowed('save') || $object->isAllowed('publish')) {
            $isAutoSave = $request->get('task') == 'autoSave';
            $draftData = [];

            if ($object->isPublished() || $isAutoSave) {
                $version = $object->saveVersion(true, true, null, $isAutoSave);
                $draftData = [
                    'id' => $version->getId(),
                    'modificationDate' => $version->getDate(),
                    'isAutoSave' => $version->isAutoSave(),
                ];
            } else {
                $object->save();
            }

            if ($request->get('task') == 'version') {
                $object->deleteAutoSaveVersions($this->getAdminUser()->getId());
            }

            $treeData = $this->getTreeNodeConfig($object);

            $newObject = DataObject::getById($object->getId(), ['force' => true]);

            return $this->adminJson([
                'success' => true,
                'general' => ['o_modificationDate' => $object->getModificationDate(),
                    'versionDate' => $newObject->getModificationDate(),
                    'versionCount' => $newObject->getVersionCount(),
                ],
                'draft' => $draftData,
                'treeData' => $treeData,
            ]);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @param Request $request
     * @param DataObject\Concrete $object
     * @param int $originalModificationDate
     * @param array $data
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function performFieldcollectionModificationCheck(Request $request, DataObject\Concrete $object, $originalModificationDate, $data)
    {
        $modificationDate = $request->get('modificationDate');
        if ($modificationDate != $originalModificationDate) {
            $fielddefinitions = $object->getClass()->getFieldDefinitions();
            foreach ($fielddefinitions as $fd) {
                if ($fd instanceof DataObject\ClassDefinition\Data\Fieldcollections) {
                    if (isset($data[$fd->getName()])) {
                        $allowedTypes = $fd->getAllowedTypes();
                        foreach ($allowedTypes as $type) {
                            /** @var DataObject\Fieldcollection\Definition $fdDef */
                            $fdDef = DataObject\Fieldcollection\Definition::getByKey($type);
                            $childDefinitions = $fdDef->getFieldDefinitions();
                            foreach ($childDefinitions as $childDef) {
                                if ($childDef instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                                    return false;
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * @Route("/save-folder", name="savefolder", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveFolderAction(Request $request)
    {
        $object = DataObject::getById((int) $request->get('id'));

        if (!$object) {
            throw $this->createNotFoundException('Object not found');
        }

        if ($object->isAllowed('publish')) {
            try {
                // general settings
                $general = $this->decodeJson($request->get('general'));
                $object->setValues($general);
                $object->setUserModification($this->getAdminUser()->getId());

                $this->assignPropertiesFromEditmode($request, $object);

                $object->save();

                return $this->adminJson(['success' => true]);
            } catch (\Exception $e) {
                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @param Request $request
     * @param DataObject\AbstractObject $object
     */
    protected function assignPropertiesFromEditmode(Request $request, $object)
    {
        if ($request->get('properties')) {
            $properties = [];
            // assign inherited properties
            foreach ($object->getProperties() as $p) {
                if ($p->isInherited()) {
                    $properties[$p->getName()] = $p;
                }
            }

            $propertiesData = $this->decodeJson($request->get('properties'));

            if (is_array($propertiesData)) {
                foreach ($propertiesData as $propertyName => $propertyData) {
                    $value = $propertyData['data'];

                    try {
                        $property = new Model\Property();
                        $property->setType($propertyData['type']);
                        $property->setName($propertyName);
                        $property->setCtype('object');
                        $property->setDataFromEditmode($value);
                        $property->setInheritable($propertyData['inheritable']);

                        $properties[$propertyName] = $property;
                    } catch (\Exception $e) {
                        Logger::err("Can't add " . $propertyName . ' to object ' . $object->getRealFullPath());
                    }
                }
            }
            $object->setProperties($properties);
        }
    }

    /**
     * @Route("/publish-version", name="publishversion", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function publishVersionAction(Request $request)
    {
        $version = Model\Version::getById((int) $request->get('id'));
        if (!$version) {
            throw $this->createNotFoundException();
        }
        $object = $version->loadData();

        $currentObject = DataObject::getById($object->getId());
        if ($currentObject->isAllowed('publish')) {
            $object->setPublished(true);
            $object->setUserModification($this->getAdminUser()->getId());

            try {
                $object->save();

                $this->addAdminStyle($object, ElementAdminStyleEvent::CONTEXT_TREE, $treeData);

                return $this->adminJson(
                    [
                        'success' => true,
                        'general' => ['o_modificationDate' => $object->getModificationDate() ],
                        'treeData' => $treeData, ]
                );
            } catch (\Exception $e) {
                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @Route("/preview-version", name="previewversion", methods={"GET"})
     *
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function previewVersionAction(Request $request)
    {
        DataObject::setDoNotRestoreKeyAndPath(true);

        $id = (int)$request->get('id');
        $version = Model\Version::getById($id);
        $object = $version->loadData();

        if (method_exists($object, 'getLocalizedFields')) {
            /** @var DataObject\Localizedfield $localizedFields */
            $localizedFields = $object->getLocalizedFields();
            $localizedFields->setLoadedAllLazyData();
        }

        DataObject::setDoNotRestoreKeyAndPath(false);

        if ($object) {
            if ($object->isAllowed('versions')) {
                return $this->render('@PimcoreAdmin/Admin/DataObject/DataObject/previewVersion.html.twig',
                    [
                        'object' => $object,
                        'versionNote' => $version->getNote(),
                        'validLanguages' => Tool::getValidLanguages(),
                    ]);
            }

            throw $this->createAccessDeniedException('Permission denied, version id [' . $id . ']');
        }

        throw $this->createNotFoundException('Version with id [' . $id . "] doesn't exist");
    }

    /**
     * @Route("/diff-versions/from/{from}/to/{to}", name="diffversions", methods={"GET"})
     *
     * @param Request $request
     * @param int $from
     * @param int $to
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function diffVersionsAction(Request $request, $from, $to)
    {
        DataObject::setDoNotRestoreKeyAndPath(true);

        $id1 = (int)$from;
        $id2 = (int)$to;

        $version1 = Model\Version::getById($id1);
        $object1 = $version1->loadData();

        if (method_exists($object1, 'getLocalizedFields')) {
            /** @var DataObject\Localizedfield $localizedFields1 */
            $localizedFields1 = $object1->getLocalizedFields();
            $localizedFields1->setLoadedAllLazyData();
        }

        $version2 = Model\Version::getById($id2);
        $object2 = $version2->loadData();

        if (method_exists($object2, 'getLocalizedFields')) {
            /** @var DataObject\Localizedfield $localizedFields2 */
            $localizedFields2 = $object2->getLocalizedFields();
            $localizedFields2->setLoadedAllLazyData();
        }

        DataObject::setDoNotRestoreKeyAndPath(false);

        if ($object1 && $object2) {
            if ($object1->isAllowed('versions') && $object2->isAllowed('versions')) {
                return $this->render('@PimcoreAdmin/Admin/DataObject/DataObject/diffVersions.html.twig',
                    [
                        'object1' => $object1,
                        'versionNote1' => $version1->getNote(),
                        'object2' => $object2,
                        'versionNote2' => $version2->getNote(),
                        'validLanguages' => Tool::getValidLanguages(),
                    ]);
            }

            throw $this->createAccessDeniedException('Permission denied, version ids [' . $id1 . ', ' . $id2 . ']');
        }

        throw $this->createNotFoundException('Version with ids [' . $id1 . ', ' . $id2 . "] doesn't exist");
    }

    /**
     * @Route("/grid-proxy", name="gridproxy", methods={"GET", "POST", "PUT"})
     *
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     * @param GridHelperService $gridHelperService
     * @param LocaleServiceInterface $localeService
     * @param CsrfProtectionHandler $csrfProtection
     *
     * @return JsonResponse
     */
    public function gridProxyAction(
        Request $request,
        EventDispatcherInterface $eventDispatcher,
        GridHelperService $gridHelperService,
        LocaleServiceInterface $localeService,
        CsrfProtectionHandler $csrfProtection
    ): JsonResponse {
        $allParams = array_merge($request->request->all(), $request->query->all());
        if (isset($allParams['context']) && $allParams['context']) {
            $allParams['context'] = json_decode($allParams['context'], true);
        } else {
            $allParams['context'] = [];
        }

        $filterPrepareEvent = new GenericEvent($this, [
            'requestParams' => $allParams,
        ]);
        $eventDispatcher->dispatch($filterPrepareEvent, AdminEvents::OBJECT_LIST_BEFORE_FILTER_PREPARE);

        $allParams = $filterPrepareEvent->getArgument('requestParams');

        $csrfProtection->checkCsrfToken($request);

        $result = $this->gridProxy(
            $allParams,
            DataObject::OBJECT_TYPE_OBJECT,
            $request,
            $eventDispatcher,
            $gridHelperService,
            $localeService
        );

        return $this->adminJson($result);
    }

    /**
     * @Route("/copy-info", name="copyinfo", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function copyInfoAction(Request $request)
    {
        $transactionId = time();
        $pasteJobs = [];

        Tool\Session::useSession(function (AttributeBagInterface $session) use ($transactionId) {
            $session->set((string) $transactionId, ['idMapping' => []]);
        }, 'pimcore_copy');

        if ($request->get('type') == 'recursive' || $request->get('type') == 'recursive-update-references') {
            $object = DataObject::getById((int) $request->get('sourceId'));

            // first of all the new parent
            $pasteJobs[] = [[
                'url' => $this->generateUrl('pimcore_admin_dataobject_dataobject_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $request->get('sourceId'),
                    'targetId' => $request->get('targetId'),
                    'type' => 'child',
                    'transactionId' => $transactionId,
                    'saveParentId' => true,
                ],
            ]];

            if ($object->hasChildren(DataObject::$types)) {
                // get amount of children
                $list = new DataObject\Listing();
                $list->setCondition('o_path LIKE ' . $list->quote($list->escapeLike($object->getRealFullPath()) . '/%'));
                $list->setOrderKey('LENGTH(o_path)', false);
                $list->setOrder('ASC');
                $list->setObjectTypes(DataObject::$types);
                $childIds = $list->loadIdList();

                if (count($childIds) > 0) {
                    foreach ($childIds as $id) {
                        $pasteJobs[] = [[
                            'url' => $this->generateUrl('pimcore_admin_dataobject_dataobject_copy'),
                            'method' => 'POST',
                            'params' => [
                                'sourceId' => $id,
                                'targetParentId' => $request->get('targetId'),
                                'sourceParentId' => $request->get('sourceId'),
                                'type' => 'child',
                                'transactionId' => $transactionId,
                            ],
                        ]];
                    }
                }

                // add id-rewrite steps
                if ($request->get('type') == 'recursive-update-references') {
                    for ($i = 0; $i < (count($childIds) + 1); $i++) {
                        $pasteJobs[] = [[
                            'url' => $this->generateUrl('pimcore_admin_dataobject_dataobject_copyrewriteids'),
                            'method' => 'PUT',
                            'params' => [
                                'transactionId' => $transactionId,
                                '_dc' => uniqid(),
                            ],
                        ]];
                    }
                }
            }
        } elseif ($request->get('type') == 'child' || $request->get('type') == 'replace') {
            // the object itself is the last one
            $pasteJobs[] = [[
                'url' => $this->generateUrl('pimcore_admin_dataobject_dataobject_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $request->get('sourceId'),
                    'targetId' => $request->get('targetId'),
                    'type' => $request->get('type'),
                    'transactionId' => $transactionId,
                ],
            ]];
        }

        return $this->adminJson([
            'pastejobs' => $pasteJobs,
        ]);
    }

    /**
     * @Route("/copy-rewrite-ids", name="copyrewriteids", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function copyRewriteIdsAction(Request $request)
    {
        $transactionId = $request->get('transactionId');

        $idStore = Tool\Session::useSession(function (AttributeBagInterface $session) use ($transactionId) {
            return $session->get($transactionId);
        }, 'pimcore_copy');

        if (!array_key_exists('rewrite-stack', $idStore)) {
            $idStore['rewrite-stack'] = array_values($idStore['idMapping']);
        }

        $id = array_shift($idStore['rewrite-stack']);
        $object = DataObject::getById($id);

        // create rewriteIds() config parameter
        $rewriteConfig = ['object' => $idStore['idMapping']];

        $object = DataObject\Service::rewriteIds($object, $rewriteConfig);

        $object->setUserModification($this->getAdminUser()->getId());
        $object->save();

        // write the store back to the session
        Tool\Session::useSession(function (AttributeBagInterface $session) use ($transactionId, $idStore) {
            $session->set($transactionId, $idStore);
        }, 'pimcore_copy');

        return $this->adminJson([
            'success' => true,
            'id' => $id,
        ]);
    }

    /**
     * @Route("/copy", name="copy", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function copyAction(Request $request)
    {
        $message = '';
        $sourceId = (int)$request->get('sourceId');
        $source = DataObject::getById($sourceId);

        $session = Tool\Session::get('pimcore_copy');
        $sessionBag = $session->get($request->get('transactionId'));

        $targetId = (int)$request->get('targetId');
        if ($request->get('targetParentId')) {
            $sourceParent = DataObject::getById((int) $request->get('sourceParentId'));

            // this is because the key can get the prefix "_copy" if the target does already exists
            if ($sessionBag['parentId']) {
                $targetParent = DataObject::getById($sessionBag['parentId']);
            } else {
                $targetParent = DataObject::getById((int) $request->get('targetParentId'));
            }

            $targetPath = preg_replace('@^' . preg_quote($sourceParent->getRealFullPath(), '@') . '@', $targetParent . '/', $source->getRealPath());
            $target = DataObject::getByPath($targetPath);
        } else {
            $target = DataObject::getById($targetId);
        }

        if ($target->isAllowed('create')) {
            $source = DataObject::getById($sourceId);
            if ($source != null) {
                if ($source instanceof DataObject\Concrete && $latestVersion = $source->getLatestVersion()) {
                    $source = $latestVersion->loadData();
                    $source->setPublished(false); //as latest version is used which is not published
                }

                if ($request->get('type') == 'child') {
                    $newObject = $this->_objectService->copyAsChild($target, $source);

                    $sessionBag['idMapping'][(int)$source->getId()] = (int)$newObject->getId();

                    // this is because the key can get the prefix "_copy" if the target does already exists
                    if ($request->get('saveParentId')) {
                        $sessionBag['parentId'] = $newObject->getId();
                    }
                } elseif ($request->get('type') == 'replace') {
                    $this->_objectService->copyContents($target, $source);
                }

                $session->set($request->get('transactionId'), $sessionBag);
                Tool\Session::writeClose();

                return $this->adminJson(['success' => true, 'message' => $message]);
            } else {
                Logger::error("could not execute copy/paste, source object with id [ $sourceId ] not found");

                return $this->adminJson(['success' => false, 'message' => 'source object not found']);
            }
        } else {
            throw $this->createAccessDeniedHttpException();
        }
    }

    /**
     * @Route("/preview", name="preview", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response|RedirectResponse
     */
    public function previewAction(Request $request)
    {
        $id = $request->get('id');
        $object = DataObject\Service::getElementFromSession('object', $id);

        if ($object instanceof DataObject\Concrete) {
            $url = $object->getClass()->getPreviewUrl();
            if ($url) {
                // replace named variables
                $vars = $object->getObjectVars();
                foreach ($vars as $key => $value) {
                    if (!empty($value) && \is_scalar($value)) {
                        $url = str_replace('%' . $key, urlencode($value), $url);
                    } else {
                        if (strpos($url, '%' . $key) !== false) {
                            return new Response('No preview available, please ensure that all fields which are required for the preview are filled correctly.');
                        }
                    }
                }
                $url = str_replace('%_locale', $this->getAdminUser()->getLanguage(), $url);
            } elseif ($previewService = $object->getClass()->getPreviewGenerator()) {
                $url = $previewService->generatePreviewUrl($object, array_merge(['preview' => true, 'context' => $this], $request->query->all()));
            } elseif ($linkGenerator = $object->getClass()->getLinkGenerator()) {
                $url = $linkGenerator->generate($object, ['preview' => true, 'context' => $this]);
            }

            if (!$url) {
                return new Response("Preview not available, it seems that there's a problem with this object.");
            }

            // replace all remainaing % signs
            $url = str_replace('%', '%25', $url);

            $urlParts = parse_url($url);

            return $this->redirect($urlParts['path'] . '?pimcore_object_preview=' . $id . '&_dc=' . time() . (isset($urlParts['query']) ? '&' . $urlParts['query'] : ''));
        } else {
            return new Response("Preview not available, it seems that there's a problem with this object.");
        }
    }

    /**
     * @param  DataObject\Concrete $object
     * @param  array $toDelete
     * @param  array $toAdd
     * @param  string $ownerFieldName
     */
    protected function processRemoteOwnerRelations($object, $toDelete, $toAdd, $ownerFieldName)
    {
        $getter = 'get' . ucfirst($ownerFieldName);
        $setter = 'set' . ucfirst($ownerFieldName);

        foreach ($toDelete as $id) {
            $owner = DataObject::getById($id);
            //TODO: lock ?!
            if (method_exists($owner, $getter)) {
                $currentData = $owner->$getter();
                if (is_array($currentData)) {
                    for ($i = 0; $i < count($currentData); $i++) {
                        if ($currentData[$i]->getId() == $object->getId()) {
                            unset($currentData[$i]);
                            $owner->$setter($currentData);

                            break;
                        }
                    }
                } else {
                    if ($currentData->getId() == $object->getId()) {
                        $owner->$setter(null);
                    }
                }
            }
            $owner->setUserModification($this->getAdminUser()->getId());
            $owner->save();
            Logger::debug('Saved object id [ ' . $owner->getId() . ' ] by remote modification through [' . $object->getId() . '], Action: deleted [ ' . $object->getId() . " ] from [ $ownerFieldName]");
        }

        foreach ($toAdd as $id) {
            $owner = DataObject::getById($id);
            //TODO: lock ?!
            if (method_exists($owner, $getter)) {
                $currentData = $owner->$getter();
                if (is_array($currentData)) {
                    $currentData[] = $object;
                } else {
                    $currentData = $object;
                }
                $owner->$setter($currentData);
                $owner->setUserModification($this->getAdminUser()->getId());
                $owner->save();
                Logger::debug('Saved object id [ ' . $owner->getId() . ' ] by remote modification through [' . $object->getId() . '], Action: added [ ' . $object->getId() . " ] to [ $ownerFieldName ]");
            }
        }
    }

    /**
     * @param  array $relations
     * @param  array $value
     *
     * @return array
     */
    protected function detectDeletedRemoteOwnerRelations($relations, $value)
    {
        $originals = [];
        $changed = [];
        foreach ($relations as $r) {
            $originals[] = $r['dest_id'];
        }
        if (is_array($value)) {
            foreach ($value as $row) {
                $changed[] = $row['id'];
            }
        }
        $diff = array_diff($originals, $changed);

        return $diff;
    }

    /**
     * @param  array $relations
     * @param  array $value
     *
     * @return array
     */
    protected function detectAddedRemoteOwnerRelations($relations, $value)
    {
        $originals = [];
        $changed = [];
        foreach ($relations as $r) {
            $originals[] = $r['dest_id'];
        }
        if (is_array($value)) {
            foreach ($value as $row) {
                $changed[] = $row['id'];
            }
        }
        $diff = array_diff($changed, $originals);

        return $diff;
    }

    /**
     * @template T of DataObject\Concrete
     *
     * @param T $object
     * @param null|Version $draftVersion
     *
     * @return T
     */
    protected function getLatestVersion(DataObject\Concrete $object, &$draftVersion = null): ?DataObject\Concrete
    {
        $latestVersion = $object->getLatestVersion($this->getAdminUser()->getId());
        if ($latestVersion) {
            $latestObj = $latestVersion->loadData();
            if ($latestObj instanceof DataObject\Concrete) {
                $draftVersion = $latestVersion;

                return $latestObj;
            }
        }

        return $object;
    }

    /**
     * @param ControllerEvent $event
     */
    public function onKernelControllerEvent(ControllerEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // check permissions
        $this->checkPermission('objects');

        $this->_objectService = new DataObject\Service($this->getAdminUser());
    }
}
