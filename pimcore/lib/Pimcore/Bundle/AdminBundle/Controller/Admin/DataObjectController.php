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

use Pimcore\Controller\Configuration\TemplatePhp;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Db;
use Pimcore\Event\AdminEvents;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element;
use Pimcore\Tool;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * @Route("/object")
 */
class DataObjectController extends ElementControllerBase implements EventedControllerInterface
{
    /**
     * @var DataObject\Service
     */
    protected $_objectService;

    /**
     * @Route("/tree-get-childs-by-id")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function treeGetChildsByIdAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        $allParams = array_merge($request->request->all(), $request->query->all());

        $object = DataObject\AbstractObject::getById($request->get('node'));
        $objectTypes = null;
        $objects = [];
        $cv = false;
        $offset = 0;
        $total = 0;
        if ($object instanceof DataObject\Concrete) {
            $class = $object->getClass();
            if ($class->getShowVariants()) {
                $objectTypes = [DataObject\AbstractObject::OBJECT_TYPE_FOLDER, DataObject\AbstractObject::OBJECT_TYPE_OBJECT, DataObject\AbstractObject::OBJECT_TYPE_VARIANT];
            }
        }

        if (!$objectTypes) {
            $objectTypes = [DataObject\AbstractObject::OBJECT_TYPE_OBJECT, DataObject\AbstractObject::OBJECT_TYPE_FOLDER];
        }

        if ($object->hasChildren($objectTypes)) {
            $limit = intval($request->get('limit'));
            if (!$request->get('limit')) {
                $limit = 100000000;
            }
            $offset = intval($request->get('start'));

            $childsList = new DataObject\Listing();
            $condition = "objects.o_parentId = '" . $object->getId() . "'";

            // custom views start
            if ($request->get('view')) {
                $cv = \Pimcore\Model\Element\Service::getCustomViewById($request->get('view'));

                if ($cv['classes']) {
                    $cvConditions = [];
                    $cvClasses = explode(',', $cv['classes']);
                    foreach ($cvClasses as $cvClass) {
                        $cvConditions[] = "objects.o_classId = '" . $cvClass . "'";
                    }

                    $cvConditions[] = "objects.o_type = 'folder'";

                    if (count($cvConditions) > 0) {
                        $condition .= ' AND (' . implode(' OR ', $cvConditions) . ')';
                    }
                }
            }
            // custom views end

            if (!$this->getAdminUser()->isAdmin()) {
                $userIds = $this->getAdminUser()->getRoles();
                $userIds[] = $this->getAdminUser()->getId();
                $condition .= ' AND (
                                                    (select list from users_workspaces_object where userId in (' . implode(',', $userIds) . ') and LOCATE(CONCAT(o_path,o_key),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_object where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(o_path,o_key))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )';
            }

            $childsList->setCondition($condition);
            $childsList->setLimit($limit);
            $childsList->setOffset($offset);
            $childsList->setOrderKey(
                sprintf('objects.o_%s ASC', $object->getChildrenSortBy()),
                false
            );
            $childsList->setObjectTypes($objectTypes);

            Element\Service::addTreeFilterJoins($cv, $childsList);

            $beforeListLoadEvent = new GenericEvent($this, [
                'list' => $childsList,
                'context' => $allParams
            ]);
            $eventDispatcher->dispatch(AdminEvents::OBJECT_LIST_BEFORE_LIST_LOAD, $beforeListLoadEvent);
            $childsList = $beforeListLoadEvent->getArgument('list');

            $childs = $childsList->load();

            foreach ($childs as $child) {
                $tmpObject = $this->getTreeNodeConfig($child);

                if ($child->isAllowed('list')) {
                    $objects[] = $tmpObject;
                }
            }
            //pagination for custom view
            $total = $cv
                ? $childsList->count()
                : $object->getChildAmount(null, $this->getAdminUser());
        }

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $event = new GenericEvent($this, [
            'objects' => $objects,
        ]);
        $eventDispatcher->dispatch(AdminEvents::OBJECT_TREE_GET_CHILDREN_BY_ID_PRE_SEND_DATA, $event);
        $objects = $event->getArgument('objects');

        if ($request->get('limit')) {
            return $this->adminJson([
                'offset' => $offset,
                'limit' => $limit,
                'total' => $total,
                'nodes' => $objects,
                'fromPaging' => intval($request->get('fromPaging'))
            ]);
        } else {
            return $this->adminJson($objects);
        }
    }

    /**
     * @param DataObject\AbstractObject $element
     *
     * @return array
     */
    protected function getTreeNodeConfig($element)
    {
        $child = $element;

        $tmpObject = [
            'id' => $child->getId(),
            'idx' => intval($child->getIndex()),
            'sortBy' => $child->getChildrenSortBy(),
            'text' => htmlspecialchars($child->getKey()),
            'type' => $child->getType(),
            'path' => $child->getRealFullPath(),
            'basePath' => $child->getRealPath(),
            'elementType' => 'object',
            'locked' => $child->isLocked(),
            'lockOwner' => $child->getLocked() ? true : false
        ];

        $allowedTypes = [DataObject\AbstractObject::OBJECT_TYPE_OBJECT, DataObject\AbstractObject::OBJECT_TYPE_FOLDER];
        if ($child instanceof DataObject\Concrete && $child->getClass()->getShowVariants()) {
            $allowedTypes[] = DataObject\AbstractObject::OBJECT_TYPE_VARIANT;
        }

        $hasChildren = $child->hasChildren($allowedTypes);

        $tmpObject['isTarget'] = false;
        $tmpObject['allowDrop'] = false;
        $tmpObject['allowChildren'] = false;

        $tmpObject['leaf'] = !$hasChildren;

        $tmpObject['isTarget'] = true;
        if ($tmpObject['type'] != 'variant') {
            $tmpObject['allowDrop'] = true;
        }

        $tmpObject['allowChildren'] = true;
        $tmpObject['leaf'] = !$hasChildren;
        $tmpObject['cls'] = 'pimcore_class_icon ';

        $tmpObject['qtipCfg'] = $child->getElementAdminStyle()->getElementQtipConfig();

        if ($child->getType() != 'folder') {
            $tmpObject['published'] = $child->isPublished();
            $tmpObject['className'] = $child->getClass()->getName();

            if (!$child->isPublished()) {
                $tmpObject['cls'] .= 'pimcore_unpublished ';
            }

            $tmpObject['allowVariants'] = $child->getClass()->getAllowVariants();
        }
        if ($tmpObject['type'] == 'variant') {
            $tmpObject['iconCls'] = 'pimcore_icon_variant';
        } else {
            if ($child->getElementAdminStyle()->getElementIcon()) {
                $tmpObject['icon'] = $child->getElementAdminStyle()->getElementIcon();
            }

            if ($child->getElementAdminStyle()->getElementIconClass()) {
                $tmpObject['iconCls'] = $child->getElementAdminStyle()->getElementIconClass();
            }
        }

        if ($child->getElementAdminStyle()->getElementCssClass()) {
            $tmpObject['cls'] .= $child->getElementAdminStyle()->getElementCssClass() . ' ';
        }

        $tmpObject['expanded'] = !$hasChildren;
        $tmpObject['permissions'] = $child->getUserPermissions();

        if ($child->isLocked()) {
            $tmpObject['cls'] .= 'pimcore_treenode_locked ';
        }
        if ($child->getLocked()) {
            $tmpObject['cls'] .= 'pimcore_treenode_lockOwner ';
        }

        if ($tmpObject['leaf']) {
            $tmpObject['expandable'] = false;
            $tmpObject['expanded'] = true;
            $tmpObject['leaf'] = false;
            $tmpObject['loaded'] = true;
        }

        return $tmpObject;
    }

    /**
     * @Route("/get-id-path-paging-info")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getIdPathPagingInfoAction(Request $request)
    {
        $path = $request->get('path');
        $pathParts = explode('/', $path);
        $id = array_pop($pathParts);

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
                'total' => $total
            ];

            if ($total > $limit) {
                $idList = $list->loadIdList();
                $position = array_search($object->getId(), $idList);
                $info['position'] = $position + 1;

                $info['page'] = ceil($info['position'] / $limit);
                $containsPaging = true;
            }

            $data[$parent->getId()] = $info;

            $object = $parent;
        }

        return $this->adminJson($data);
    }

    /**
     * @Route("/get")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        // check for lock
        if (Element\Editlock::isLocked($request->get('id'), 'object')) {
            return $this->adminJson([
                'editlock' => Element\Editlock::getByElement($request->get('id'), 'object')
            ]);
        }
        Element\Editlock::lock($request->get('id'), 'object');

        $object = DataObject::getById(intval($request->get('id')));
        $object = clone $object;

        // set the latest available version for editmode
        $latestObject = $this->getLatestVersion($object);

        // we need to know if the latest version is published or not (a version), because of lazy loaded fields in $this->getDataForObject()
        $objectFromVersion = $latestObject === $object ? false : true;
        $object = $latestObject;

        if ($object->isAllowed('view')) {
            $objectData = [];

            $objectData['idPath'] = Element\Service::getIdPath($object);

            $objectData['hasPreview'] = false;
            if ($object->getClass()->getPreviewUrl() || $object->getClass()->getLinkGeneratorReference()) {
                $objectData['hasPreview'] = true;
            }

            $objectData['general'] = [];
            $allowedKeys = ['o_published', 'o_key', 'o_id', 'o_modificationDate', 'o_creationDate', 'o_classId', 'o_className', 'o_locked', 'o_type', 'o_parentId', 'o_userOwner', 'o_userModification'];

            foreach (get_object_vars($object) as $key => $value) {
                if (strstr($key, 'o_') && in_array($key, $allowedKeys)) {
                    $objectData['general'][$key] = $value;
                }
            }

            $objectData['general']['o_locked'] = $object->isLocked();

            $this->getDataForObject($object, $objectFromVersion);
            $objectData['data'] = $this->objectData;

            $objectData['metaData'] = $this->metaData;

            $objectData['layout'] = $object->getClass()->getLayoutDefinitions();

            $objectData['properties'] = Element\Service::minimizePropertiesForEditmode($object->getProperties());
            $objectData['userPermissions'] = $object->getUserPermissions();
            $objectVersions = Element\Service::getSafeVersionInfo($object->getVersions());
            $objectData['versions'] = array_splice($objectVersions, 0, 1);
            $objectData['scheduledTasks'] = $object->getScheduledTasks();
            $objectData['general']['allowVariants'] = $object->getClass()->getAllowVariants();
            $objectData['general']['showVariants'] = $object->getClass()->getShowVariants();
            $objectData['general']['showAppLoggerTab'] = $object->getClass()->getShowAppLoggerTab();
            $objectData['general']['fullpath'] = $object->getRealFullPath();
            $objectData['general']['versionDate'] = $object->getModificationDate();

            if ($object->getElementAdminStyle()->getElementIcon()) {
                $objectData['general']['icon'] = $object->getElementAdminStyle()->getElementIcon();
            }
            if ($object->getElementAdminStyle()->getElementIconClass()) {
                $objectData['general']['iconCls'] = $object->getElementAdminStyle()->getElementIconClass();
            }

            if ($object instanceof DataObject\Concrete) {
                $objectData['lazyLoadedFields'] = $object->getLazyLoadedFields();
                $objectData['general']['linkGeneratorReference'] = $object->getClass()->getLinkGeneratorReference();
            }

            $objectData['childdata']['id'] = $object->getId();
            $objectData['childdata']['data']['classes'] = $this->prepareChildClasses($object->getDao()->getClasses());

            $currentLayoutId = $request->get('layoutId', null);

            $validLayouts = DataObject\Service::getValidLayouts($object);

            //master layout has id 0 so we check for is_null()
            if (is_null($currentLayoutId) && !empty($validLayouts)) {
                if (count($validLayouts) == 1) {
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
            if (!empty($validLayouts)) {
                $objectData['validLayouts'] = [ ];

                foreach ($validLayouts as $validLayout) {
                    $objectData['validLayouts'][] = ['id' => $validLayout->getId(), 'name' => $validLayout->getName()];
                }

                $user = Tool\Admin::getCurrentUser();

                if (!is_null($currentLayoutId)) {
                    if ($currentLayoutId == 0 && !$user->isAdmin()) {
                        $first = reset($validLayouts);
                        $currentLayoutId = $first->getId();
                    }
                }

                if ($currentLayoutId > 0) {
                    // check if user has sufficient rights
                    if ($validLayouts && $validLayouts[$currentLayoutId]) {
                        $customLayout = DataObject\ClassDefinition\CustomLayout::getById($currentLayoutId);
                        $customLayoutDefinition = $customLayout->getLayoutDefinitions();
                        $objectData['layout'] = $customLayoutDefinition;
                    } else {
                        $currentLayoutId = 0;
                    }
                } elseif ($currentLayoutId == -1 && $user->isAdmin()) {
                    $layout = DataObject\Service::getSuperLayoutDefinition($object);
                    $objectData['layout'] = $layout;
                }

                $objectData['currentLayoutId'] = $currentLayoutId;
            }

            $objectData = $this->filterLocalizedFields($object, $objectData);
            DataObject\Service::enrichLayoutDefinition($objectData['layout'], $object);

            //Hook for modifying return value - e.g. for changing permissions based on object data
            //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
            $event = new GenericEvent($this, [
                'data' => $objectData,
                'object' => $object,
            ]);
            $eventDispatcher->dispatch(AdminEvents::OBJECT_GET_PRE_SEND_DATA, $event);
            $data = $event->getArgument('data');

            DataObject\Service::removeObjectFromSession($object->getId());

            return $this->adminJson($data);
        } else {
            Logger::debug('prevented getting object id [ ' . $object->getId() . ' ] because of missing permissions');

            return $this->adminJson(['success' => false, 'message' => 'missing_permission']);
        }
    }

    /**
     * @var
     */
    private $objectData;

    /**
     * @var
     */
    private $metaData;

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
     * @param $object
     * @param $key
     * @param $fielddefinition
     * @param $objectFromVersion
     * @param int $level
     */
    private function getDataForField($object, $key, $fielddefinition, $objectFromVersion, $level = 0)
    {
        $parent = DataObject\Service::hasInheritableParentObject($object);
        $getter = 'get' . ucfirst($key);

        // relations but not for objectsMetadata, because they have additional data which cannot be loaded directly from the DB
        // nonownerobjects should go in there anyway (regardless if it a version or not), so that the values can be loaded
        if (
            (
                !$objectFromVersion
                && $fielddefinition instanceof DataObject\ClassDefinition\Data\Relations\AbstractRelations
                && $fielddefinition->getLazyLoading()
                && !$fielddefinition instanceof DataObject\ClassDefinition\Data\ObjectsMetadata
                && !$fielddefinition instanceof DataObject\ClassDefinition\Data\MultihrefMetadata
            )
            || $fielddefinition instanceof DataObject\ClassDefinition\Data\Nonownerobjects
        ) {

            //lazy loading data is fetched from DB differently, so that not every relation object is instantiated
            $refId = null;

            if ($fielddefinition->isRemoteOwner()) {
                $refKey = $fielddefinition->getOwnerFieldName();
                $refClass = DataObject\ClassDefinition::getByName($fielddefinition->getOwnerClassName());
                if ($refClass) {
                    $refId = $refClass->getId();
                }
            } else {
                $refKey = $key;
            }
            $relations = $object->getRelationData($refKey, !$fielddefinition->isRemoteOwner(), $refId);
            if (empty($relations) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $objectFromVersion, $level + 1);
            } else {
                $data = [];

                if ($fielddefinition instanceof DataObject\ClassDefinition\Data\Href) {
                    $data = $relations[0];
                } else {
                    foreach ($relations as $rel) {
                        if ($fielddefinition instanceof DataObject\ClassDefinition\Data\Objects) {
                            $data[] = [$rel['id'], $rel['path'], $rel['subtype']];
                        } else {
                            $data[] = [$rel['id'], $rel['path'], $rel['type'], $rel['subtype']];
                        }
                    }
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
                $fieldData->setContextualData('object', null, null, null);
                $value = $fielddefinition->getDataForEditmode($fieldData, $object, $objectFromVersion);
            } else {
                $value = $fielddefinition->getDataForEditmode($fieldData, $object, $objectFromVersion);
            }

            // following some exceptions for special data types (localizedfields, objectbricks)
            if ($value && ($fieldData instanceof DataObject\Localizedfield || $fieldData instanceof DataObject\Classificationstore)) {
                // make sure that the localized field participates in the inheritance detection process
                $isInheritedValue = $value['inherited'];
            }
            if ($fielddefinition instanceof DataObject\ClassDefinition\Data\Objectbricks && is_array($value)) {
                // make sure that the objectbricks participate in the inheritance detection process
                foreach ($value as $singleBrickData) {
                    if ($singleBrickData['inherited']) {
                        $isInheritedValue = true;
                    }
                }
            }

            if ($fielddefinition->isEmpty($fieldData) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $objectFromVersion, $level + 1);
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
     * @param $object
     * @param $key
     *
     * @return mixed
     */
    private function getParentValue($object, $key)
    {
        $parent = DataObject\Service::hasInheritableParentObject($object);
        $getter = 'get' . ucfirst($key);
        if ($parent) {
            $value = $parent->$getter();
            if ($value) {
                $result = new \stdClass();
                $result->value = $value;
                $result->id = $parent->getId();

                return $result;
            } else {
                return $this->getParentValue($parent, $key);
            }
        }
    }

    /**
     * @param $layout
     * @param $allowedView
     * @param $allowedEdit
     */
    protected function setLayoutPermission(&$layout, $allowedView, $allowedEdit)
    {
        if ($layout->{'fieldtype'} == 'localizedfields' || $layout->{'fieldtype'} == 'classificationstore') {
            if (is_array($allowedView) && count($allowedView) > 0) {
                if ($layout->{'fieldtype'} == 'localizedfields') {
                    $haveAllowedViewDefault = isset($allowedView['default']);
                    if ($haveAllowedViewDefault) {
                        unset($allowedView['default']);
                    }
                }
                if (!($haveAllowedViewDefault && count($allowedView) == 0)) {
                    $layout->{'permissionView'} = \Pimcore\Tool\Admin::reorderWebsiteLanguages(
                        \Pimcore\Tool\Admin::getCurrentUser(),
                        array_keys($allowedView),
                        true
                    );
                }
            }
            if (is_array($allowedEdit) && count($allowedEdit) > 0) {
                if ($layout->{'fieldtype'} == 'localizedfields') {
                    $haveAllowedEditDefault = isset($allowedEdit['default']);
                    if ($haveAllowedEditDefault) {
                        unset($allowedEdit['default']);
                    }
                }

                if (!($haveAllowedEditDefault && count($allowedEdit) == 0)) {
                    $layout->{'permissionEdit'} = \Pimcore\Tool\Admin::reorderWebsiteLanguages(
                        \Pimcore\Tool\Admin::getCurrentUser(),
                        array_keys($allowedEdit),
                        true
                    );
                }
            }
        } else {
            if (method_exists($layout, 'getChilds')) {
                $children = $layout->getChilds();
                if (is_array($children)) {
                    foreach ($children as $child) {
                        $this->setLayoutPermission($child, $allowedView, $allowedEdit);
                    }
                }
            }
        }
    }

    /**
     * @param DataObject\AbstractObject $object
     * @param $objectData
     *
     * @return mixed
     */
    protected function filterLocalizedFields(DataObject\AbstractObject $object, $objectData)
    {
        if (!($object instanceof DataObject\Concrete)) {
            return $objectData;
        }

        $user = Tool\Admin::getCurrentUser();
        if ($user->getAdmin()) {
            return $objectData;
        }

        $fieldDefinitions = $object->getClass()->getFieldDefinitions();
        if ($fieldDefinitions) {
            $languageAllowedView = DataObject\Service::getLanguagePermissions($object, $user, 'lView');
            $languageAllowedEdit = DataObject\Service::getLanguagePermissions($object, $user, 'lEdit');

            foreach ($fieldDefinitions as $key => $fd) {
                if ($fd->getFieldtype() == 'localizedfields') {
                    foreach ($objectData['data'][$key]['data'] as $language => $languageData) {
                        if (!is_null($languageAllowedView) && !$languageAllowedView[$language]) {
                            unset($objectData['data'][$key]['data'][$language]);
                        }
                    }
                }
            }
            $this->setLayoutPermission($objectData['layout'], $languageAllowedView, $languageAllowedEdit);
        }

        return $objectData;
    }

    /**
     * @Route("/get-folder")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getFolderAction(Request $request)
    {
        // check for lock
        if (Element\Editlock::isLocked($request->get('id'), 'object')) {
            return $this->adminJson([
                'editlock' => Element\Editlock::getByElement($request->get('id'), 'object')
            ]);
        }
        Element\Editlock::lock($request->get('id'), 'object');

        $object = DataObject::getById(intval($request->get('id')));
        if ($object->isAllowed('view')) {
            $objectData = [];

            $objectData['general'] = [];
            $objectData['idPath'] = Element\Service::getIdPath($object);
            $allowedKeys = ['o_published', 'o_key', 'o_id', 'o_type', 'o_path', 'o_modificationDate', 'o_creationDate', 'o_userOwner', 'o_userModification'];
            foreach (get_object_vars($object) as $key => $value) {
                if (strstr($key, 'o_') && in_array($key, $allowedKeys)) {
                    $objectData['general'][$key] = $value;
                }
            }
            $objectData['general']['fullpath'] = $object->getRealFullPath();

            $objectData['general']['o_locked'] = $object->isLocked();

            $objectData['properties'] = Element\Service::minimizePropertiesForEditmode($object->getProperties());
            $objectData['userPermissions'] = $object->getUserPermissions();
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

            return $this->adminJson($objectData);
        } else {
            Logger::debug('prevented getting folder id [ ' . $object->getId() . ' ] because of missing permissions');

            return $this->adminJson(['success' => false, 'message' => 'missing_permission']);
        }
    }

    /**
     * @param $classes
     *
     * @return array
     */
    protected function prepareChildClasses($classes)
    {
        $reduced = [];
        foreach ($classes as $class) {
            $reduced[] = [
                'id' => $class->getId(),
                'name' => $class->getName()
            ];
        }

        return $reduced;
    }

    /**
     * @Route("/add")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAction(Request $request)
    {
        $success = false;

        $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($request->get('className'));
        $parent = DataObject::getById($request->get('parentId'));

        $message = '';
        if ($parent->isAllowed('create')) {
            $intendedPath = $parent->getRealFullPath() . '/' . $request->get('key');

            if (!DataObject\Service::pathExists($intendedPath)) {
                $object = $this->get('pimcore.model.factory')->build($className);
                if ($object instanceof DataObject\Concrete) {
                    $object->setOmitMandatoryCheck(true); // allow to save the object although there are mandatory fields
                }

                if ($request->get('variantViaTree')) {
                    $parentId = $request->get('parentId');
                    $parent = DataObject::getById($parentId);
                    $object->setClassId($parent->getClass()->getId());
                } else {
                    $object->setClassId($request->get('classId'));
                }

                $object->setClassName($request->get('className'));
                $object->setParentId($request->get('parentId'));
                $object->setKey($request->get('key'));
                $object->setCreationDate(time());
                $object->setUserOwner($this->getAdminUser()->getId());
                $object->setUserModification($this->getAdminUser()->getId());
                $object->setPublished(false);

                if ($request->get('objecttype') == DataObject\AbstractObject::OBJECT_TYPE_OBJECT
                    || $request->get('objecttype') == DataObject\AbstractObject::OBJECT_TYPE_VARIANT) {
                    $object->setType($request->get('objecttype'));
                }

                try {
                    $object->save();
                    $success = true;
                } catch (\Exception $e) {
                    return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                }
            } else {
                $message = 'prevented creating object because object with same path+key already exists';
                Logger::debug($message);
            }
        } else {
            $message = 'prevented adding object because of missing permissions';
            Logger::debug($message);
        }

        if ($success) {
            return $this->adminJson([
                'success' => $success,
                'id' => $object->getId(),
                'type' => $object->getType(),
                'message' => $message
            ]);
        } else {
            return $this->adminJson([
                'success' => $success,
                'message' => $message
            ]);
        }
    }

    /**
     * @Route("/add-folder")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addFolderAction(Request $request)
    {
        $success = false;

        $parent = DataObject::getById($request->get('parentId'));
        if ($parent->isAllowed('create')) {
            if (!DataObject\Service::pathExists($parent->getRealFullPath() . '/' . $request->get('key'))) {
                $folder = DataObject\Folder::create([
                    'o_parentId' => $request->get('parentId'),
                    'o_creationDate' => time(),
                    'o_userOwner' => $this->getAdminUser()->getId(),
                    'o_userModification' => $this->getAdminUser()->getId(),
                    'o_key' => $request->get('key'),
                    'o_published' => true
                ]);

                $folder->setCreationDate(time());
                $folder->setUserOwner($this->getAdminUser()->getId());
                $folder->setUserModification($this->getAdminUser()->getId());

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
     * @Route("/delete")
     * @Method({"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request)
    {
        if ($request->get('type') == 'childs') {
            $parentObject = DataObject::getById($request->get('id'));

            $list = new DataObject\Listing();
            $list->setCondition('o_path LIKE ' . $list->quote($parentObject->getRealFullPath() . '/%'));
            $list->setLimit(intval($request->get('amount')));
            $list->setOrderKey('LENGTH(o_path)', false);
            $list->setOrder('DESC');

            $objects = $list->load();

            $deletedItems = [];
            foreach ($objects as $object) {
                $deletedItems[] = $object->getRealFullPath();
                if ($object->isAllowed('delete')) {
                    $object->delete();
                }
            }

            return $this->adminJson(['success' => true, 'deleted' => $deletedItems]);
        } elseif ($request->get('id')) {
            $object = DataObject::getById($request->get('id'));
            if ($object) {
                if (!$object->isAllowed('delete')) {
                    return $this->adminJson(['success' => false, 'message' => 'missing_permission']);
                } else {
                    $object->delete();
                }
            }

            // return true, even when the object doesn't exist, this can be the case when using batch delete incl. children
            return $this->adminJson(['success' => true]);
        }
    }

    /**
     * @Route("/change-children-sort-by")
     * @Method({"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function changeChildrenSortByAction(Request $request)
    {
        /** @var Model\Object $object */
        $object = DataObject::getById($request->get('id'));
        if ($object) {
            $object->setChildrenSortBy($request->get('sortBy'));
            $object->save();

            return $this->json(['success' => true]);
        }

        return $this->json(['success' => false, 'message' => 'Unable to change a sorting way of children items.']);
    }

    /**
     * @Route("/delete-info")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteInfoAction(Request $request)
    {
        $hasDependency = false;
        $deleteJobs = [];
        $recycleJobs = [];

        $totalChilds = 0;

        $ids = $request->get('id');
        $ids = explode(',', $ids);

        foreach ($ids as $id) {
            try {
                $object = DataObject::getById($id);
                if (!$object) {
                    continue;
                }
                $hasDependency |= $object->getDependencies()->isRequired();
            } catch (\Exception $e) {
                Logger::err('failed to access object with id: ' . $id);
                continue;
            }

            // check for children
            if ($object instanceof DataObject\AbstractObject) {
                $recycleJobs[] = [[
                    'url' => '/admin/recyclebin/add',
                    'method' => 'POST',
                    'params' => [
                        'type' => 'object',
                        'id' => $object->getId()
                    ]
                ]];

                $hasChilds = $object->hasChildren();
                if (!$hasDependency) {
                    $hasDependency = $hasChilds;
                }

                $childs = 0;
                if ($hasChilds) {
                    // get amount of childs
                    $list = new DataObject\Listing();
                    $list->setCondition('o_path LIKE ' . $list->quote($object->getRealFullPath() . '/%'));
                    $childs = $list->getTotalCount();

                    $totalChilds += $childs;
                    if ($childs > 0) {
                        $deleteObjectsPerRequest = 5;
                        for ($i = 0; $i < ceil($childs / $deleteObjectsPerRequest); $i++) {
                            $deleteJobs[] = [[
                                'url' => '/admin/object/delete',
                                'method' => 'DELETE',
                                'params' => [
                                    'step' => $i,
                                    'amount' => $deleteObjectsPerRequest,
                                    'type' => 'childs',
                                    'id' => $object->getId()
                                ]
                            ]];
                        }
                    }
                }

                // the object itself is the last one
                $deleteJobs[] = [[
                    'url' => '/admin/object/delete',
                    'method' => 'DELETE',
                    'params' => [
                        'id' => $object->getId()
                    ]
                ]];
            }
        }

        // get the element key in case of just one
        $elementKey = false;
        if (count($ids) === 1) {
            $elementKey = DataObject::getById($id)->getKey();
        }

        $deleteJobs = array_merge($recycleJobs, $deleteJobs);

        return $this->adminJson([
            'hasDependencies' => $hasDependency,
            'childs' => $totalChilds,
            'deletejobs' => $deleteJobs,
            'batchDelete' => count($ids) > 1,
            'elementKey' => $elementKey
        ]);
    }

    /**
     * @Route("/update")
     * @Method({"PUT"})
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

        $object = DataObject::getById($request->get('id'));
        if ($object instanceof DataObject\Concrete) {
            $object->setOmitMandatoryCheck(true);
        }

        // this prevents the user from renaming, relocating (actions in the tree) if the newest version isn't the published one
        // the reason is that otherwise the content of the newer not published version will be overwritten
        if ($object instanceof DataObject\Concrete) {
            $latestVersion = $object->getLatestVersion();
            if ($latestVersion && $latestVersion->getData()->getModificationDate() != $object->getModificationDate()) {
                return $this->adminJson(['success' => false, 'message' => "You can't relocate if there's a newer not published version"]);
            }
        }

        $values = $this->decodeJson($request->get('values'));

        if ($object->isAllowed('settings')) {
            if ($values['key'] && $object->isAllowed('rename')) {
                $object->setKey($values['key']);
            } elseif ($values['key'] != $object->getKey()) {
                Logger::debug('prevented renaming object because of missing permissions ');
            }

            if ($values['parentId']) {
                $parent = DataObject::getById($values['parentId']);

                //check if parent is changed
                if ($object->getParentId() != $parent->getId()) {
                    if (!$parent->isAllowed('create')) {
                        throw new \Exception('Prevented moving object - no create permission on new parent ');
                    }

                    $objectWithSamePath = DataObject::getByPath($parent->getRealFullPath() . '/' . $object->getKey());

                    if ($objectWithSamePath != null) {
                        $allowUpdate = false;

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

            if ($allowUpdate) {
                $newIndex = $values['index'] ?? null;
                if (is_int($newIndex)) {
                    $object->setIndex($newIndex);
                    $this->updateIndexesOfObjectSiblings($object);
                }

                $object->setModificationDate(time());
                $object->setUserModification($this->getAdminUser()->getId());

                try {
                    $object->save();
                    $success = true;
                } catch (\Exception $e) {
                    Logger::error($e);

                    return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                }
            } else {
                Logger::debug('prevented move of object, object with same path+key already exists in this location.');
            }
        } elseif ($object->isAllowed('rename') && $values['key']) {
            //just rename
            try {
                $object->setKey($values['key']);
                $object->save();
                $success = true;
            } catch (\Exception $e) {
                Logger::error($e);

                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            Logger::debug('prevented update object because of missing permissions.');
        }

        return $this->adminJson(['success' => $success]);
    }

    /**
     * @param DataObject\AbstractObject $updatedObject
     */
    protected function updateIndexesOfObjectSiblings(DataObject\AbstractObject $updatedObject)
    {
        $list = new DataObject\Listing();
        $list->setCondition(
            'o_parentId = ? AND o_id != ?',
            [$updatedObject->getParentId(), $updatedObject->getId()]
        );
        $list->setOrderKey('o_index');
        $list->setOrder('asc');
        $siblings = $list->load();

        $index = 0;
        /** @var DataObject\AbstractObject $child */
        foreach ($siblings as $sibling) {
            if ($index == intval($updatedObject->getIndex())) {
                $index++;
            }
            if (method_exists($sibling, 'setOmitMandatoryCheck')) {
                $sibling->setOmitMandatoryCheck(true);
            }
            $sibling
                ->setIndex($index)
                ->save();
            $index++;
        }
    }

    /**
     * @Route("/save")
     * @Method({"POST", "PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        try {
            $object = DataObject::getById($request->get('id'));
            $originalModificationDate = $object->getModificationDate();

            // set the latest available version for editmode
            $object = $this->getLatestVersion($object);
            $object->setUserModification($this->getAdminUser()->getId());

            // data
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

                        if (method_exists($fd, 'isRemoteOwner') and $fd->isRemoteOwner()) {
                            $remoteClass = DataObject\ClassDefinition::getByName($fd->getOwnerClassName());
                            $relations = $object->getRelationData($fd->getOwnerFieldName(), false, $remoteClass->getId());
                            $toAdd = $this->detectAddedRemoteOwnerRelations($relations, $value);
                            $toDelete = $this->detectDeletedRemoteOwnerRelations($relations, $value);
                            if (count($toAdd) > 0 or count($toDelete) > 0) {
                                $this->processRemoteOwnerRelations($object, $toDelete, $toAdd, $fd->getOwnerFieldName());
                            }
                        } else {
                            $object->setValue($key, $fd->getDataFromEditmode($value, $object));
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
                        if (!in_array($key, ['o_id', 'o_classId', 'o_className', 'o_type', 'icon', 'o_userOwner', 'o_userModification'])) {
                            $object->setValue($key, $value);
                        }
                    }
                }
            }

            $object = $this->assignPropertiesFromEditmode($request, $object);

            // scheduled tasks
            if ($request->get('scheduler')) {
                $tasks = [];
                $tasksData = $this->decodeJson($request->get('scheduler'));

                if (!empty($tasksData)) {
                    foreach ($tasksData as $taskData) {
                        $taskData['date'] = strtotime($taskData['date'] . ' ' . $taskData['time']);

                        $task = new Model\Schedule\Task($taskData);
                        $tasks[] = $task;
                    }
                }

                $object->setScheduledTasks($tasks);
            }

            if ($request->get('task') == 'unpublish') {
                $object->setPublished(false);
            }
            if ($request->get('task') == 'publish') {
                $object->setPublished(true);
            }

            // unpublish and save version is possible without checking mandatory fields
            if ($request->get('task') == 'unpublish' || $request->get('task') == 'version') {
                $object->setOmitMandatoryCheck(true);
            }

            if (($request->get('task') == 'publish' && $object->isAllowed('publish')) or ($request->get('task') == 'unpublish' && $object->isAllowed('unpublish'))) {
                if ($data) {
                    if (!$this->performFieldcollectionModificationCheck($request, $object, $originalModificationDate, $data)) {
                        return $this->adminJson(['success' => false, 'message' => 'Could be that someone messed around with the fieldcollection in the meantime. Please reload and try again']);
                    }
                }

                $object->save();
                $treeData = $this->getTreeNodeConfig($object);

                $newObject = DataObject\AbstractObject::getById($object->getId(), true);

                return $this->adminJson([
                    'success' => true,
                    'general' => ['o_modificationDate' => $object->getModificationDate(),
                        'versionDate' => $newObject->getModificationDate()
                    ],
                    'treeData' => $treeData]);
            } elseif ($request->get('task') == 'session') {

                //$object->_fulldump = true; // not working yet, donno why

                Tool\Session::useSession(function (AttributeBagInterface $session) use ($object) {
                    $key = 'object_' . $object->getId();
                    $session->set($key, $object);
                }, 'pimcore_objects');

                return $this->adminJson(['success' => true]);
            } else {
                if ($object->isAllowed('save')) {
                    $object->saveVersion();
                    $treeData = $this->getTreeNodeConfig($object);

                    $newObject = DataObject\AbstractObject::getById($object->getId(), true);

                    return $this->adminJson([
                        'success' => true,
                        'general' => ['o_modificationDate' => $object->getModificationDate(),
                            'versionDate' => $newObject->getModificationDate()
                        ],

                        'treeData' => $treeData]);
                }
            }
        } catch (\Exception $e) {
            Logger::log($e);
            if ($e instanceof Element\ValidationException) {
                $detailedInfo = '<b>Message:</b><br>';
                $detailedInfo .= $e->getMessage();

                $detailedInfo .= '<br><br><b>Trace:</b> ' . $e->getTraceAsString();
                if ($e->getPrevious()) {
                    $detailedInfo .= '<br><br><b>Previous Message:</b><br>';
                    $detailedInfo .= $e->getPrevious()->getMessage();
                    $detailedInfo .= '<br><br><b>Previous Trace:</b><br>' . $e->getPrevious()->getTraceAsString();
                }

                return $this->adminJson(['success' => false, 'type' => 'ValidationException', 'message' => $e->getMessage(), 'stack' => $detailedInfo, 'code' => $e->getCode()]);
            }
            throw $e;
        }
    }

    /**
     * @param Request $request
     * @param DataObject\Concrete $object
     * @param $originalModificationDate
     * @param $data
     *
     * @return JsonResponse
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
                            /** @var $fdDef DataObject\Fieldcollection\Definition */
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
     * @Route("/save-folder")
     * @Method({"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveFolderAction(Request $request)
    {
        $object = DataObject::getById($request->get('id'));

        if ($object->isAllowed('publish')) {
            try {
                $classId = $request->get('class_id');

                // general settings
                $general = $this->decodeJson($request->get('general'));
                $object->setValues($general);
                $object->setUserModification($this->getAdminUser()->getId());

                $object = $this->assignPropertiesFromEditmode($request, $object);

                $object->save();

                return $this->adminJson(['success' => true]);
            } catch (\Exception $e) {
                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        return $this->adminJson(['success' => false, 'message' => 'missing_permission']);
    }

    /**
     * @param Request $request
     * @param $object
     *
     * @return mixed
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

        return $object;
    }

    /**
     * @Route("/publish-version")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function publishVersionAction(Request $request)
    {
        $version = Model\Version::getById($request->get('id'));
        $object = $version->loadData();

        $currentObject = DataObject::getById($object->getId());
        if ($currentObject->isAllowed('publish')) {
            $object->setPublished(true);
            $object->setUserModification($this->getAdminUser()->getId());
            try {
                $object->save();
                $treeData = [];
                $treeData['qtipCfg'] = $object->getElementAdminStyle()->getElementQtipConfig();

                return $this->adminJson(
                    [
                        'success' => true,
                        'general' => ['o_modificationDate' => $object->getModificationDate() ],
                        'treeData' => $treeData]
                );
            } catch (\Exception $e) {
                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        return $this->adminJson(['success' => false, 'message' => 'missing_permission']);
    }

    /**
     * @Route("/preview-version")
     * @Method({"GET"})
     *
     * @param Request $request
     * @TemplatePhp()
     *
     * @throws \Exception
     *
     * @return array
     */
    public function previewVersionAction(Request $request)
    {
        DataObject\AbstractObject::setDoNotRestoreKeyAndPath(true);

        $id = intval($request->get('id'));
        $version = Model\Version::getById($id);
        $object = $version->loadData();

        DataObject\AbstractObject::setDoNotRestoreKeyAndPath(false);

        if ($object) {
            if ($object->isAllowed('versions')) {
                return ['object' => $object];
            } else {
                throw new \Exception('Permission denied, version id [' . $id . ']');
            }
        } else {
            throw new \Exception('Version with id [' . $id . "] doesn't exist");
        }
    }

    /**
     * @Route("/diff-versions/from/{from}/to/{to}")
     * @Method({"GET"})
     * @TemplatePhp()
     *
     * @param Request $request
     * @param from
     * @param to
     *
     * @return array
     *
     * @throws \Exception
     */
    public function diffVersionsAction(Request $request, $from, $to)
    {
        DataObject\AbstractObject::setDoNotRestoreKeyAndPath(true);

        $id1 = intval($from);
        $id2 = intval($to);

        $version1 = Model\Version::getById($id1);
        $object1 = $version1->loadData();

        $version2 = Model\Version::getById($id2);
        $object2 = $version2->loadData();

        DataObject\AbstractObject::setDoNotRestoreKeyAndPath(false);

        if ($object1 && $object2) {
            if ($object1->isAllowed('versions') && $object2->isAllowed('versions')) {
                return [
                    'object1' => $object1,
                    'object2' => $object2
                ];
            } else {
                throw new \Exception('Permission denied, version ids [' . $id1 . ', ' . $id2 . ']');
            }
        } else {
            throw new \Exception('Version with ids [' . $id1 . ', ' . $id2 . "] doesn't exist");
        }
    }

    /**
     * @Route("/grid-proxy")
     * @Method({"GET", "POST", "PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridProxyAction(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        $allParams = array_merge($request->request->all(), $request->query->all());

        $filterPrepareEvent = new GenericEvent($this, [
            'requestParams' => $allParams
        ]);
        $eventDispatcher->dispatch(AdminEvents::OBJECT_LIST_BEFORE_FILTER_PREPARE, $filterPrepareEvent);

        $allParams = $filterPrepareEvent->getArgument('requestParams');

        $requestedLanguage = $allParams['language'];
        if ($requestedLanguage) {
            if ($requestedLanguage != 'default') {
                //                $this->get('translator')->setLocale($requestedLanguage);
                $request->setLocale($requestedLanguage);
            }
        } else {
            $requestedLanguage = $request->getLocale();
        }

        if ($allParams['data']) {
            $this->checkCsrfToken($request);
            if ($allParams['xaction'] == 'update') {
                try {
                    $data = $this->decodeJson($allParams['data']);

                    // save
                    $object = DataObject::getById($data['id']);
                    /** @var DataObject\ClassDefinition $class */
                    $class = $object->getClass();

                    if (!$object->isAllowed('publish')) {
                        throw new \Exception("Permission denied. You don't have the rights to save this object.");
                    }

                    $user = Tool\Admin::getCurrentUser();
                    $allLanguagesAllowed = false;
                    if (!$user->isAdmin()) {
                        $languagePermissions = $object->getPermissions('lEdit', $user);

                        //sets allowed all languages modification when the lEdit column is empty
                        $allLanguagesAllowed = $languagePermissions['lEdit'] == '';

                        $languagePermissions = explode(',', $languagePermissions['lEdit']);
                    }

                    $objectData = [];
                    foreach ($data as $key => $value) {
                        $parts = explode('~', $key);
                        if (substr($key, 0, 1) == '~') {
                            $type = $parts[1];
                            $field = $parts[2];
                            $keyid = $parts[3];

                            if ($type == 'classificationstore') {
                                $groupKeyId = explode('-', $keyid);
                                $groupId = $groupKeyId[0];
                                $keyid = $groupKeyId[1];

                                $getter = 'get' . ucfirst($field);
                                if (method_exists($object, $getter)) {

                                    /** @var $csFieldDefinition Model\DataObject\ClassDefinition\Data\Classificationstore */
                                    $csFieldDefinition = $object->getClass()->getFieldDefinition($field);
                                    $csLanguage = $requestedLanguage;
                                    if (!$csFieldDefinition->isLocalized()) {
                                        $csLanguage = 'default';
                                    }

                                    /** @var $classificationStoreData DataObject\Classificationstore */
                                    $classificationStoreData = $object->$getter();

                                    $keyConfig = DataObject\Classificationstore\KeyConfig::getById($keyid);
                                    if ($keyConfig) {
                                        $fieldDefinition = $keyDef = DataObject\Classificationstore\Service::getFieldDefinitionFromJson(
                                            json_decode($keyConfig->getDefinition()),
                                            $keyConfig->getType()
                                        );
                                        if ($fieldDefinition && method_exists($fieldDefinition, 'getDataFromGridEditor')) {
                                            $value = $fieldDefinition->getDataFromGridEditor($value, $object, []);
                                        }
                                    }

                                    $classificationStoreData->setLocalizedKeyValue($groupId, $keyid, $value, $csLanguage);
                                }
                            }
                        } elseif (count($parts) > 1) {
                            $brickType = $parts[0];

                            if (strpos($brickType, '?') !== false) {
                                $brickDescriptor = substr($brickType, 1);
                                $brickDescriptor = json_decode($brickDescriptor, true);
                                $brickType = $brickDescriptor['containerKey'];
                            }
                            $brickKey = $parts[1];
                            $brickField = DataObject\Service::getFieldForBrickType($object->getClass(), $brickType);

                            $fieldGetter = 'get' . ucfirst($brickField);
                            $brickGetter = 'get' . ucfirst($brickType);
                            $valueSetter = 'set' . ucfirst($brickKey);

                            $brick = $object->$fieldGetter()->$brickGetter();
                            if (empty($brick)) {
                                $classname = '\\Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickType);
                                $brickSetter = 'set' . ucfirst($brickType);
                                $brick = new $classname($object);
                                $object->$fieldGetter()->$brickSetter($brick);
                            }

                            if ($brickDescriptor) {
                                $brickDefinition = Model\DataObject\Objectbrick\Definition::getByKey($brickType);
                                $fieldDefinitionLocalizedFields = $brickDefinition->getFieldDefinition('localizedfields');
                                $fieldDefinition = $fieldDefinitionLocalizedFields->getFieldDefinition($brickKey);
                            } else {
                                $fieldDefinition = $this->getFieldDefinitionFromBrick($brickType, $brickKey);
                            }

                            if ($fieldDefinition && method_exists($fieldDefinition, 'getDataFromGridEditor')) {
                                $value = $fieldDefinition->getDataFromGridEditor($value, $object, []);
                            }

                            if ($brickDescriptor) {
                                /** @var $localizedFields DataObject\Localizedfield */
                                $localizedFields = $brick->getLocalizedfields();
                                $localizedFields->setLocalizedValue($brickKey, $value);
                            } else {
                                $brick->$valueSetter($value);
                            }
                        } else {
                            if (!$user->isAdmin() && $languagePermissions) {
                                $fd = $class->getFieldDefinition($key);
                                if (!$fd) {
                                    // try to get via localized fields
                                    $localized = $class->getFieldDefinition('localizedfields');
                                    if ($localized instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                                        $field = $localized->getFieldDefinition($key);
                                        if ($field) {
                                            $currentLocale = \Pimcore::getContainer()->get('pimcore.locale')->findLocale();
                                            if (!$allLanguagesAllowed && !in_array($currentLocale, $languagePermissions)) {
                                                continue;
                                            }
                                        }
                                    }
                                }
                            }

                            $fieldDefinition = $this->getFieldDefinition($class, $key);
                            if ($fieldDefinition && method_exists($fieldDefinition, 'getDataFromGridEditor')) {
                                $value = $fieldDefinition->getDataFromGridEditor($value, $object, []);
                            }

                            $objectData[$key] = $value;
                        }
                    }

                    $object->setValues($objectData);

                    $object->save();

                    return $this->adminJson(['data' => DataObject\Service::gridObjectData($object, $allParams['fields'], $requestedLanguage), 'success' => true]);
                } catch (\Exception $e) {
                    return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                }
            }
        } else {
            // get list of objects
            $folder = DataObject::getById($allParams['folderId']);
            $class = DataObject\ClassDefinition::getById($allParams['classId']);
            $className = $class->getName();

            $colMappings = [
                'key' => 'o_key',
                'filename' => 'o_key',
                'fullpath' => ['o_path', 'o_key'],
                'id' => 'oo_id',
                'published' => 'o_published',
                'modificationDate' => 'o_modificationDate',
                'creationDate' => 'o_creationDate'
            ];

            $start = 0;
            $limit = 20;
            $orderKey = 'oo_id';
            $order = 'ASC';

            $fields = [];
            $bricks = [];
            if ($allParams['fields']) {
                $fields = $allParams['fields'];

                foreach ($fields as $f) {
                    $parts = explode('~', $f);
                    if (substr($f, 0, 1) == '~') {
                        $type = $parts[1];
                    } elseif (count($parts) > 1) {
                        $brickType = $parts[0];

                        if (strpos($brickType, '?') !== false) {
                            $brickDescriptor = substr($brickType, 1);
                            $brickDescriptor = json_decode($brickDescriptor, true);
                            $brickType = $brickDescriptor['containerKey'];
                            $bricks[$brickType] = $brickDescriptor;
                        } else {
                            $bricks[$parts[0]] = $brickType;
                        }
                    }
                }
            }

            if ($allParams['limit']) {
                $limit = $allParams['limit'];
            }
            if ($allParams['start']) {
                $start = $allParams['start'];
            }

            $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings($allParams);

            $doNotQuote = false;

            if ($sortingSettings['order']) {
                $order = $sortingSettings['order'];
            }
            if (strlen($sortingSettings['orderKey']) > 0) {
                $orderKey = $sortingSettings['orderKey'];
                if (!(substr($orderKey, 0, 1) == '~')) {
                    if (array_key_exists($orderKey, $colMappings)) {
                        $orderKey = $colMappings[$orderKey];
                    } elseif ($class->getFieldDefinition($orderKey) instanceof  DataObject\ClassDefinition\Data\QuantityValue) {
                        $orderKey = 'concat(' . $orderKey . '__unit, ' . $orderKey . '__value)';
                        $doNotQuote = true;
                    } elseif ($class->getFieldDefinition($orderKey) instanceof  DataObject\ClassDefinition\Data\RgbaColor) {
                        $orderKey = 'concat(' . $orderKey . '__rgb, ' . $orderKey . '__a)';
                        $doNotQuote = true;
                    } elseif (strpos($orderKey, '~') !== false) {
                        $orderKeyParts = explode('~', $orderKey);

                        if (strpos($orderKey, '?') !== false) {
                            $brickDescriptor = substr($orderKeyParts[0], 1);
                            $brickDescriptor = json_decode($brickDescriptor, true);
                            $db = Db::get();
                            $orderKey = $db->quoteIdentifier($brickDescriptor['containerKey'] . '_localized') . '.' . $db->quoteIdentifier($brickDescriptor['brickfield']);
                            $doNotQuote = true;
                        } else {
                            if (count($orderKeyParts) == 2) {
                                $orderKey = $orderKeyParts[1];
                            }
                        }
                    }
                }
            }

            $listClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\Listing';
            /**
             * @var $list DataObject\Listing\Concrete
             */
            $list = new $listClass();

            $conditionFilters = [];
            if ($allParams['only_direct_children'] == 'true') {
                $conditionFilters[] = 'o_parentId = ' . $folder->getId();
            } else {
                $quotedPath = $list->quote($folder->getRealFullPath());
                $quotedWildcardPath = $list->quote(str_replace('//', '/', $folder->getRealFullPath() . '/') . '%');
                $conditionFilters[] = '(o_path = ' . $quotedPath . ' OR o_path LIKE ' . $quotedWildcardPath . ')';
            }

            if (!$this->getAdminUser()->isAdmin()) {
                $userIds = $this->getAdminUser()->getRoles();
                $userIds[] = $this->getAdminUser()->getId();
                $conditionFilters[] .= ' (
                                                    (select list from users_workspaces_object where userId in (' . implode(',', $userIds) . ') and LOCATE(CONCAT(o_path,o_key),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_object where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(o_path,o_key))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )';
            }

            $featureJoins = [];
            $featureFilters = false;

            // create filter condition
            if ($allParams['filter']) {
                $conditionFilters[] = DataObject\Service::getFilterCondition($allParams['filter'], $class);
                $featureFilters = DataObject\Service::getFeatureFilters($allParams['filter'], $class);
                if ($featureFilters) {
                    $featureJoins = array_merge($featureJoins, $featureFilters['joins']);
                }
            }

            if ($allParams['condition'] && $this->getAdminUser()->isAdmin()) {
                $conditionFilters[] = '(' . $allParams['condition'] . ')';
            }

            if (!empty($bricks)) {
                foreach ($bricks as $b) {
                    $brickType = $b;
                    if (is_array($brickType)) {
                        $brickType = $brickType['containerKey'];
                    }
                    $list->addObjectbrick($brickType);
                }
            }

            $list->setCondition(implode(' AND ', $conditionFilters));
            $list->setLimit($limit);
            $list->setOffset($start);

            if (isset($sortingSettings['isFeature']) && $sortingSettings['isFeature']) {
                $orderKey = 'cskey_' . $sortingSettings['fieldname'] . '_' . $sortingSettings['groupId']. '_' . $sortingSettings['keyId'];
                $list->setOrderKey($orderKey);
                $list->setGroupBy('o_id');

                $featureJoins[] = $sortingSettings;
            } else {
                $list->setOrderKey($orderKey, !$doNotQuote);
            }
            $list->setOrder($order);

            if ($class->getShowVariants()) {
                $list->setObjectTypes([DataObject\AbstractObject::OBJECT_TYPE_OBJECT, DataObject\AbstractObject::OBJECT_TYPE_VARIANT]);
            }

            DataObject\Service::addGridFeatureJoins($list, $featureJoins, $class, $featureFilters, $requestedLanguage);

            $beforeListLoadEvent = new GenericEvent($this, [
                'list' => $list,
                'context' => $allParams
            ]);
            $eventDispatcher->dispatch(AdminEvents::OBJECT_LIST_BEFORE_LIST_LOAD, $beforeListLoadEvent);
            $list = $beforeListLoadEvent->getArgument('list');

            $list->load();

            $objects = [];
            foreach ($list->getObjects() as $object) {
                $o = DataObject\Service::gridObjectData($object, $fields, $requestedLanguage);
                // Like for treeGetChildsByIdAction, so we respect isAllowed method which can be extended (object DI) for custom permissions, so relying only users_workspaces_object is insufficient and could lead security breach
                if ($object->isAllowed('list')) {
                    $objects[] = $o;
                }
            }

            $result = ['data' => $objects, 'success' => true, 'total' => $list->getTotalCount()];

            $afterListLoadEvent = new GenericEvent($this, [
                'list' => $result,
                'context' => $allParams
            ]);
            $eventDispatcher->dispatch(AdminEvents::OBJECT_LIST_AFTER_LIST_LOAD, $afterListLoadEvent);
            $result = $afterListLoadEvent->getArgument('list');

            return $this->adminJson($result);
        }
    }

    /**
     * @param string $class
     * @param string $key
     *
     * @return DataObject\ClassDefinition\Data
     */
    protected function getFieldDefinition($class, $key)
    {
        $fieldDefinition = $class->getFieldDefinition($key);
        if ($fieldDefinition) {
            return $fieldDefinition;
        }

        $localized = $class->getFieldDefinition('localizedfields');
        if ($localized instanceof DataObject\ClassDefinition\Data\Localizedfields) {
            $fieldDefinition = $localized->getFielddefinition($key);
        }

        return $fieldDefinition;
    }

    /**
     * @param string $brickType
     * @param string $key
     *
     * @return DataObject\ClassDefinition\Data
     */
    protected function getFieldDefinitionFromBrick($brickType, $key)
    {
        $brickDefinition = DataObject\Objectbrick\Definition::getByKey($brickType);
        if ($brickDefinition) {
            $fieldDefinition = $brickDefinition->getFieldDefinition($key);
        }

        return $fieldDefinition;
    }

    /**
     * @Route("/copy-info")
     * @Method({"GET"})
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
            $session->set($transactionId, ['idMapping' => []]);
        }, 'pimcore_copy');

        if ($request->get('type') == 'recursive' || $request->get('type') == 'recursive-update-references') {
            $object = DataObject::getById($request->get('sourceId'));

            // first of all the new parent
            $pasteJobs[] = [[
                'url' => '/admin/object/copy',
                'method' => 'POST',
                'params' => [
                    'sourceId' => $request->get('sourceId'),
                    'targetId' => $request->get('targetId'),
                    'type' => 'child',
                    'transactionId' => $transactionId,
                    'saveParentId' => true
                ]
            ]];

            if ($object->hasChildren([DataObject\AbstractObject::OBJECT_TYPE_OBJECT, DataObject\AbstractObject::OBJECT_TYPE_FOLDER, DataObject\AbstractObject::OBJECT_TYPE_VARIANT])) {
                // get amount of children
                $list = new DataObject\Listing();
                $list->setCondition('o_path LIKE ' . $list->quote($object->getRealFullPath() . '/%'));
                $list->setOrderKey('LENGTH(o_path)', false);
                $list->setOrder('ASC');
                $list->setObjectTypes([DataObject\AbstractObject::OBJECT_TYPE_OBJECT, DataObject\AbstractObject::OBJECT_TYPE_FOLDER, DataObject\AbstractObject::OBJECT_TYPE_VARIANT]);
                $childIds = $list->loadIdList();

                if (count($childIds) > 0) {
                    foreach ($childIds as $id) {
                        $pasteJobs[] = [[
                            'url' => '/admin/object/copy',
                            'method' => 'POST',
                            'params' => [
                                'sourceId' => $id,
                                'targetParentId' => $request->get('targetId'),
                                'sourceParentId' => $request->get('sourceId'),
                                'type' => 'child',
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
                        'url' => '/admin/object/copy-rewrite-ids',
                        'method' => 'PUT',
                        'params' => [
                            'transactionId' => $transactionId,
                            '_dc' => uniqid()
                        ]
                    ]];
                }
            }
        } elseif ($request->get('type') == 'child' || $request->get('type') == 'replace') {
            // the object itself is the last one
            $pasteJobs[] = [[
                'url' => '/admin/object/copy',
                'method' => 'POST',
                'params' => [
                    'sourceId' => $request->get('sourceId'),
                    'targetId' => $request->get('targetId'),
                    'type' => $request->get('type'),
                    'transactionId' => $transactionId
                ]
            ]];
        }

        return $this->adminJson([
            'pastejobs' => $pasteJobs
        ]);
    }

    /**
     * @Route("/copy-rewrite-ids")
     * @Method({"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
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
            'id' => $id
        ]);
    }

    /**
     * @Route("/copy")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function copyAction(Request $request)
    {
        $success = false;
        $message = '';
        $sourceId = intval($request->get('sourceId'));
        $source = DataObject::getById($sourceId);

        $session = Tool\Session::get('pimcore_copy');
        $sessionBag = $session->get($request->get('transactionId'));

        $targetId = intval($request->get('targetId'));
        if ($request->get('targetParentId')) {
            $sourceParent = DataObject::getById($request->get('sourceParentId'));

            // this is because the key can get the prefix "_copy" if the target does already exists
            if ($sessionBag['parentId']) {
                $targetParent = DataObject::getById($sessionBag['parentId']);
            } else {
                $targetParent = DataObject::getById($request->get('targetParentId'));
            }

            $targetPath = preg_replace('@^' . $sourceParent->getRealFullPath() . '@', $targetParent . '/', $source->getRealPath());
            $target = DataObject::getByPath($targetPath);
        } else {
            $target = DataObject::getById($targetId);
        }

        if ($target->isAllowed('create')) {
            $source = DataObject::getById($sourceId);
            if ($source != null) {
                try {
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

                    $success = true;
                } catch (\Exception $e) {
                    Logger::err($e);
                    $success = false;
                    $message = $e->getMessage() . ' in object ' . $source->getRealFullPath() . ' [id: ' . $source->getId() . ']';
                }
            } else {
                Logger::error("could not execute copy/paste, source object with id [ $sourceId ] not found");

                return $this->adminJson(['success' => false, 'message' => 'source object not found']);
            }
        } else {
            Logger::error('could not execute copy/paste because of missing permissions on target [ ' . $targetId . ' ]');

            return $this->adminJson(['error' => false, 'message' => 'missing_permission']);
        }

        return $this->adminJson(['success' => $success, 'message' => $message]);
    }

    /**
     * @Route("/preview")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return Response|RedirectResponse
     */
    public function previewAction(Request $request)
    {
        $id = $request->get('id');
        $key = 'object_' . $id;

        $session = Tool\Session::getReadOnly('pimcore_objects');
        if ($session->has($key)) {
            /**
             * @var DataObject\Concrete $object
             */
            $object = $session->get($key);
        } else {
            return new Response("Preview not available, it seems that there's a problem with this object.");
        }

        $url = $object->getClass()->getPreviewUrl();
        if ($url) {
            // replace named variables
            $vars = get_object_vars($object);
            foreach ($vars as $key => $value) {
                if (!empty($value) && (is_string($value) || is_numeric($value))) {
                    $url = str_replace('%' . $key, urlencode($value), $url);
                } else {
                    if (strpos($url, '%' . $key) !== false) {
                        return new Response('No preview available, please ensure that all fields which are required for the preview are filled correctly.');
                    }
                }
            }
        } elseif ($linkGenerator = $object->getClass()->getLinkGenerator()) {
            $url = $linkGenerator->generate($object, [['preview' => true, 'context' => $this]]);
        }

        if (!$url) {
            return new Response("Preview not available, it seems that there's a problem with this object.");
        }

        // replace all remainaing % signs
        $url = str_replace('%', '%25', $url);

        $urlParts = parse_url($url);

        return $this->redirect($urlParts['path'] . '?pimcore_object_preview=' . $id . '&_dc=' . time() . (isset($urlParts['query']) ? '&' . $urlParts['query'] : ''));
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
                            $owner->setUserModification($this->getAdminUser()->getId());
                            $owner->save();
                            Logger::debug('Saved object id [ ' . $owner->getId() . ' ] by remote modification through [' . $object->getId() . '], Action: deleted [ ' . $object->getId() . " ] from [ $ownerFieldName]");
                            break;
                        }
                    }
                }
            }
        }

        foreach ($toAdd as $id) {
            $owner = DataObject::getById($id);
            //TODO: lock ?!
            if (method_exists($owner, $getter)) {
                $currentData = $owner->$getter();
                $currentData[] = $object;

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
     * @param  DataObject\Concrete $object
     *
     * @return DataObject\Concrete
     */
    protected function getLatestVersion(DataObject\Concrete $object)
    {
        $modificationDate = $object->getModificationDate();
        $latestVersion = $object->getLatestVersion();
        if ($latestVersion) {
            $latestObj = $latestVersion->loadData();
            if ($latestObj instanceof DataObject\Concrete) {
                $object = $latestObj;
                $object->setModificationDate($modificationDate); // set de modification-date from published version to compare it in js-frontend
            }
        }

        return $object;
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
        $this->checkPermission('objects');

        $this->_objectService = new DataObject\Service($this->getAdminUser());
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
