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

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\DependencyInjection\PimcoreAdminExtension;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Version;
use Pimcore\Tool;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ElementController extends AdminController
{
    /**
     * @Route("/element/lock-element")
     * @Method({"PUT"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function lockElementAction(Request $request)
    {
        Element\Editlock::lock($request->get('id'), $request->get('type'));

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/element/unlock-element")
     * @Method({"PUT"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function unlockElementAction(Request $request)
    {
        Element\Editlock::unlock($request->get('id'), $request->get('type'));

        return $this->adminJson(['success' => true]);
    }

    /**
     * Returns the element data denoted by the given type and ID or path.
     *
     * @Route("/element/get-subtype")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getSubtypeAction(Request $request)
    {
        $idOrPath = trim($request->get('id'));
        $type = $request->get('type');
        if (is_numeric($idOrPath)) {
            $el = Element\Service::getElementById($type, (int) $idOrPath);
        } else {
            if ($type == 'document') {
                $el = Document\Service::getByUrl($idOrPath);
            } else {
                $el = Element\Service::getElementByPath($type, $idOrPath);
            }
        }

        if ($el) {
            if ($el instanceof Asset || $el instanceof Document) {
                $subtype = $el->getType();
            } elseif ($el instanceof DataObject\Concrete) {
                $subtype = $el->getClassName();
            } elseif ($el instanceof DataObject\Folder) {
                $subtype = 'folder';
            }

            return $this->adminJson([
                'subtype' => $subtype,
                'id' => $el->getId(),
                'type' => $type,
                'success' => true
            ]);
        } else {
            return $this->adminJson([
                'success' => false
            ]);
        }
    }

    /**
     * @param string $parameterName
     *
     * @return \Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse
     */
    protected function processNoteTypesFromParameters(string $parameterName)
    {
        $config = $this->container->getParameter($parameterName);
        $result = [];
        foreach ($config as $configEntry) {
            $result[] = [
                'name' => $configEntry
            ];
        }

        return $this->adminJson(['noteTypes' => $result]);
    }

    /**
     * @Route("/element/note-types")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function noteTypes(Request $request)
    {
        switch ($request->get('ctype')) {
            case 'document':
                return $this->processNoteTypesFromParameters(PimcoreAdminExtension::PARAM_DOCUMENTS_NOTES_EVENTS_TYPES);
            case 'asset':
                return $this->processNoteTypesFromParameters(PimcoreAdminExtension::PARAM_ASSETS_NOTES_EVENTS_TYPES);
            case 'object':
                return $this->processNoteTypesFromParameters(PimcoreAdminExtension::PARAM_DATAOBJECTS_NOTES_EVENTS_TYPES);
            default:
                return $this->adminJson(['noteTypes' => []]);

        }
    }

    /**
     * @Route("/element/note-list")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function noteListAction(Request $request)
    {
        $this->checkPermission('notes_events');

        $list = new Element\Note\Listing();

        $list->setLimit($request->get('limit'));
        $list->setOffset($request->get('start'));

        $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
        if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
            $list->setOrderKey($sortingSettings['orderKey']);
            $list->setOrder($sortingSettings['order']);
        } else {
            $list->setOrderKey(['date', 'id']);
            $list->setOrder(['DESC', 'DESC']);
        }

        $conditions = [];
        $filterText = $request->get('filterText');

        if ($filterText) {
            $conditions[] = '('
                . '`title` LIKE ' . $list->quote('%'. $filterText .'%')
                . ' OR `description` LIKE ' . $list->quote('%'.$filterText.'%')
                . ' OR `type` LIKE ' . $list->quote('%'.$filterText.'%')
                . ' OR `user` IN (SELECT `id` FROM `users` WHERE `name` LIKE ' . $list->quote('%'.$filterText.'%') . ')'
                . " OR DATE_FORMAT(FROM_UNIXTIME(`date`), '%Y-%m-%d') LIKE " . $list->quote('%'.$filterText.'%')
                . ')';
        }

        $filterJson = $request->get('filter');
        if ($filterJson) {
            $db = Db::get();
            $filters = $this->decodeJson($filterJson);
            $propertyKey = 'property';
            $comparisonKey = 'operator';

            foreach ($filters as $filter) {
                $operator = '=';

                if ($filter['type'] == 'string') {
                    $operator = 'LIKE';
                } elseif ($filter['type'] == 'numeric') {
                    if ($filter[$comparisonKey] == 'lt') {
                        $operator = '<';
                    } elseif ($filter[$comparisonKey] == 'gt') {
                        $operator = '>';
                    } elseif ($filter[$comparisonKey] == 'eq') {
                        $operator = '=';
                    }
                } elseif ($filter['type'] == 'date') {
                    if ($filter[$comparisonKey] == 'lt') {
                        $operator = '<';
                    } elseif ($filter[$comparisonKey] == 'gt') {
                        $operator = '>';
                    } elseif ($filter[$comparisonKey] == 'eq') {
                        $operator = '=';
                    }
                    $filter['value'] = strtotime($filter['value']);
                } elseif ($filter[$comparisonKey] == 'list') {
                    $operator = '=';
                } elseif ($filter[$comparisonKey] == 'boolean') {
                    $operator = '=';
                    $filter['value'] = (int) $filter['value'];
                }
                // system field
                $value = $filter['value'];
                if ($operator == 'LIKE') {
                    $value = '%' . $value . '%';
                }

                if ($filter[$propertyKey] == 'user') {
                    $conditions[] = '`user` IN (SELECT `id` FROM `users` WHERE `name` LIKE ' . $list->quote('%'.$filter['value'].'%') . ')';
                } else {
                    if ($filter['type'] == 'date' && $filter[$comparisonKey] == 'eq') {
                        $maxTime = $filter['value'] + (86400 - 1); //specifies the top point of the range used in the condition
                        $dateCondition =  '`' . $filter[$propertyKey] . '` ' . ' BETWEEN ' . $db->quote($filter['value']) . ' AND ' . $db->quote($maxTime);
                        $conditions[] = $dateCondition;
                    } else {
                        $field = '`'.$filter[$propertyKey].'` ';
                        $conditions[] = $field.$operator.' '.$db->quote($value);
                    }
                }
            }
        }

        if ($request->get('cid') && $request->get('ctype')) {
            $conditions[] = '(cid = ' . $list->quote($request->get('cid')) . ' AND ctype = ' . $list->quote($request->get('ctype')) . ')';
        }

        if (!empty($conditions)) {
            $condition = implode(' AND ', $conditions);
            $list->setCondition($condition);
        }

        $list->load();

        $notes = [];

        foreach ($list->getNotes() as $note) {
            $e = Element\Service::getNoteData($note);
            $notes[] = $e;
        }

        return $this->adminJson([
            'data' => $notes,
            'success' => true,
            'total' => $list->getTotalCount()
        ]);
    }

    /**
     * @Route("/element/note-add")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function noteAddAction(Request $request)
    {
        $this->checkPermission('notes_events');

        $note = new Element\Note();
        $note->setCid((int) $request->get('cid'));
        $note->setCtype($request->get('ctype'));
        $note->setDate(time());
        $note->setTitle($request->get('title'));
        $note->setDescription($request->get('description'));
        $note->setType($request->get('type'));
        $note->save();

        return $this->adminJson([
            'success' => true
        ]);
    }

    /**
     * @Route("/element/find-usages")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function findUsagesAction(Request $request)
    {
        if ($request->get('id')) {
            $element = Element\Service::getElementById($request->get('type'), $request->get('id'));
        } elseif ($request->get('path')) {
            $element = Element\Service::getElementByPath($request->get('type'), $request->get('path'));
        }

        $results = [];
        $success = false;
        $hasHidden = false;

        if ($element) {
            $elements = $element->getDependencies()->getRequiredBy();
            foreach ($elements as $el) {
                $item = Element\Service::getElementById($el['type'], $el['id']);
                if ($item instanceof Element\ElementInterface) {
                    if ($item->isAllowed('list')) {
                        $el['path'] = $item->getRealFullPath();
                        $results[] = $el;
                    } else {
                        $hasHidden = true;
                    }
                }
            }
            $success = true;
        }

        return $this->adminJson([
            'data' => $results,
            'hasHidden' => $hasHidden,
            'success' => $success
        ]);
    }

    /**
     * @Route("/element/replace-assignments")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function replaceAssignmentsAction(Request $request)
    {
        $success = false;
        $message = '';
        $element = Element\Service::getElementById($request->get('type'), $request->get('id'));
        $sourceEl = Element\Service::getElementById($request->get('sourceType'), $request->get('sourceId'));
        $targetEl = Element\Service::getElementById($request->get('targetType'), $request->get('targetId'));

        if ($element && $sourceEl && $targetEl
            && $request->get('sourceType') == $request->get('targetType')
            && $sourceEl->getType() == $targetEl->getType()
        ) {
            $rewriteConfig = [
                $request->get('sourceType') => [
                    $sourceEl->getId() => $targetEl->getId()
                ]
            ];

            if ($element instanceof Document) {
                $element = Document\Service::rewriteIds($element, $rewriteConfig);
            } elseif ($element instanceof DataObject\AbstractObject) {
                $element = DataObject\Service::rewriteIds($element, $rewriteConfig);
            } elseif ($element instanceof Asset) {
                $element = Asset\Service::rewriteIds($element, $rewriteConfig);
            }

            $element->setUserModification($this->getAdminUser()->getId());
            $element->save();

            $success = true;
        } else {
            $message = 'source-type and target-type do not match';
        }

        return $this->adminJson([
            'success' => $success,
            'message' => $message
        ]);
    }

    /**
     * @Route("/element/unlock-propagate")
     * @Method({"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function unlockPropagateAction(Request $request)
    {
        $success = false;

        $element = Element\Service::getElementById($request->get('type'), $request->get('id'));
        if ($element) {
            $element->unlockPropagate();
            $success = true;
        }

        return $this->adminJson([
            'success' => $success
        ]);
    }

    /**
     * @Route("/element/type-path")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function typePathAction(Request $request)
    {
        $id = $request->get('id');
        $type = $request->get('type');
        $data = [];

        if ($type == 'asset') {
            $element = Asset::getById($id);
        } elseif ($type == 'document') {
            $element = Document::getById($id);
            $data['index'] = $element->getIndex();
        } else {
            $element = DataObject::getById($id);
            $data['index'] = $element->getIndex();
        }
        $typePath = Element\Service::getTypePath($element);

        $data['success'] = true;
        $data['idPath'] = Element\Service::getIdPath($element);
        $data['typePath'] = $typePath;
        $data['fullpath'] = $element->getRealFullPath();

        return $this->adminJson($data);
    }

    /**
     * @Route("/element/version-update")
     * @Method({"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function versionUpdateAction(Request $request)
    {
        $data = $this->decodeJson($request->get('data'));

        $version = Version::getById($data['id']);
        $version->setPublic($data['public']);
        $version->setNote($data['note']);
        $version->save();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/element/get-nice-path")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getNicePathAction(Request $request)
    {
        $source = $this->decodeJson($request->get('source'));
        if ($source['type'] != 'object') {
            throw new \Exception('currently only objects as source elements are supported');
        }

        $result = [];

        $id = $source['id'];
        $source = DataObject\Concrete::getById($id);

        if ($request->get('context')) {
            $context = $this->decodeJson($request->get('context'));
        } else {
            $context = [];
        }

        $ownerType = $context['containerType'];
        $fieldname = $context['fieldname'];

        if ($ownerType == 'object') {
            $fd = $source->getClass()->getFieldDefinition($fieldname);
        } elseif ($ownerType == 'localizedfield') {
            $fd = $source->getClass()->getFieldDefinition('localizedfields')->getFieldDefinition($fieldname);
        } elseif ($ownerType == 'objectbrick') {
            $fdBrick = DataObject\Objectbrick\Definition::getByKey($context['containerKey']);
            $fd = $fdBrick->getFieldDefinition($fieldname);
        } elseif ($ownerType == 'fieldcollection') {
            $containerKey = $context['containerKey'];
            $fdCollection = DataObject\Fieldcollection\Definition::getByKey($containerKey);
            if ($context['subContainerType'] == 'localizedfield') {
                $fdLocalizedFields = $fdCollection->getFieldDefinition('localizedfields');
                $fd = $fdLocalizedFields->getFieldDefinition($fieldname);
            } else {
                $fd = $fdCollection->getFieldDefinition($fieldname);
            }
        }

        if (method_exists($fd, 'getPathFormatterClass')) {
            $formatterClass = $fd->getPathFormatterClass();
            if (Tool::classExists($formatterClass)) {
                $targets = $this->decodeJson($request->get('targets'));

                $result = call_user_func(
                    $formatterClass . '::formatPath',
                    $result,
                    $source,
                    $targets,
                    [
                        'fd' => $fd,
                        'context' => $context
                    ]
                );
            } else {
                Logger::error('Formatter Class does not exist: ' . $formatterClass);
            }
        }

        return $this->adminJson(['success' => true, 'data' => $result]);
    }

    /**
     * @Route("/element/get-versions")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getVersionsAction(Request $request)
    {
        $id = intval($request->get('id'));
        $type = $request->get('elementType');
        $allowedTypes = ['asset', 'document', 'object'];

        if ($id && in_array($type, $allowedTypes)) {
            $element = Model\Element\Service::getElementById($type, $id);
            if ($element) {
                if ($element->isAllowed('versions')) {
                    $schedule = $element->getScheduledTasks();
                    $schedules = [];
                    foreach ($schedule as $task) {
                        if ($task->getActive()) {
                            $schedules[$task->getVersion()] = $task->getDate();
                        }
                    }

                    $versions = $element->getVersions();
                    $versions = Model\Element\Service::getSafeVersionInfo($versions);
                    foreach ($versions as &$version) {
                        $version['scheduled'] = null;
                        if (array_key_exists($version['id'], $schedules)) {
                            $version['scheduled'] = $schedules[$version['id']];
                        }
                    }

                    return $this->adminJson(['versions' => $versions]);
                } else {
                    throw new \Exception('Permission denied, ' . $type . ' id [' . $id . ']');
                }
            } else {
                throw new \Exception($type . ' with id [' . $id . "] doesn't exist");
            }
        }
    }

    /**
     * @Route("/element/delete-version")
     * @Method({"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteVersionAction(Request $request)
    {
        $version = Model\Version::getById($request->get('id'));
        $version->delete();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/element/delete-all-versions")
     * @Method({"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteAllVersionAction(Request $request)
    {
        $elementId = $request->get('id');
        $elementModificationdate = $request->get('date');

        $versions = new Model\Version\Listing();
        $versions->setCondition('cid = ' . $versions->quote($elementId) . ' AND date <> ' . $versions->quote($elementModificationdate));

        foreach ($versions->load() as $vkey => $version) {
            $version->delete();
        }

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/element/get-requires-dependencies")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getRequiresDependenciesAction(Request $request)
    {
        $id = $request->get('id');
        $type = $request->get('elementType');
        $allowedTypes = ['asset', 'document', 'object'];
        $offset = $request->get('start');
        $limit = $request->get('limit');

        if ($id && in_array($type, $allowedTypes)) {
            $element = Model\Element\Service::getElementById($type, $id);
            $dependencies = $element->getDependencies();

            if ($element instanceof Model\Element\ElementInterface) {
                $dependenciesResult = Model\Element\Service::getRequiresDependenciesForFrontend($dependencies, $offset, $limit);

                $dependenciesResult['start'] = $offset;
                $dependenciesResult['limit'] = $limit;
                $dependenciesResult['total'] = $dependencies->getRequiresTotalCount();

                return $this->adminJson($dependenciesResult);
            }
        }

        return $this->adminJson(false);
    }

    /**
     * @Route("/element/get-required-by-dependencies")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getRequiredByDependenciesAction(Request $request)
    {
        $id = $request->get('id');
        $type = $request->get('elementType');
        $allowedTypes = ['asset', 'document', 'object'];
        $offset = $request->get('start');
        $limit = $request->get('limit');

        if ($id && in_array($type, $allowedTypes)) {
            $element = Model\Element\Service::getElementById($type, $id);
            $dependencies = $element->getDependencies();

            if ($element instanceof Model\Element\ElementInterface) {
                $dependenciesResult = Model\Element\Service::getRequiredByDependenciesForFrontend($dependencies, $offset, $limit);

                $dependenciesResult['start'] = $offset;
                $dependenciesResult['limit'] = $limit;
                $dependenciesResult['total'] = $dependencies->getRequiredByTotalCount();

                return $this->adminJson($dependenciesResult);
            }
        }

        return $this->adminJson(false);
    }

    /**
     * @Route("/element/get-predefined-properties")
     * @Method({"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getPredefinedPropertiesAction(Request $request)
    {
        $properties = [];
        $type = $request->get('elementType');
        $allowedTypes = ['asset', 'document', 'object'];

        if (in_array($type, $allowedTypes)) {
            $list = new Model\Property\Predefined\Listing();
            $list->setFilter(function ($row) use ($type) {
                if (is_array($row['ctype'])) {
                    $row['ctype'] = implode(',', $row['ctype']);
                }
                if (strpos($row['ctype'], $type) !== false) {
                    return true;
                }

                return false;
            });

            $list->load();

            foreach ($list->getProperties() as $type) {
                $properties[] = $type;
            }
        }

        return $this->adminJson(['properties' => $properties]);
    }

    /**
     * @Route("/element/analyze-permissions")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function analyzePermissionsAction(Request $request)
    {
        $userId = $request->get('userId');
        if ($userId) {
            $user = Model\User::getById($userId);
            $userList = [$user];
        } else {
            $userList = new Model\User\Listing();
            $userList = $userList->load();
        }

        $elementType = $request->get('elementType');
        $elementId = $request->get('elementId');

        $element = Element\Service::getElementById($elementType, $elementId);

        $result = Element\PermissionChecker::check($element, $userList);

        return $this->adminJson(
            [
                'data' => $result,
                'success' => true
            ]
        );
    }
}
