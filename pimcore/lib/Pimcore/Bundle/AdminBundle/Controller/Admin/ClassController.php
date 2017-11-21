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
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Event\AdminEvents;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/class")
 */
class ClassController extends AdminController implements EventedControllerInterface
{
    /**
     * @Route("/get-document-types")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDocumentTypesAction(Request $request)
    {
        $documentTypes = Document::getTypes();
        $typeItems = [];
        foreach ($documentTypes as $documentType) {
            $typeItems[] = [
                'text' => $documentType
            ];
        }

        return $this->json($typeItems);
    }

    /**
     * @Route("/get-asset-types")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAssetTypesAction(Request $request)
    {
        $assetTypes = Asset::getTypes();
        $typeItems = [];
        foreach ($assetTypes as $assetType) {
            $typeItems[] = [
                'text' => $assetType
            ];
        }

        return $this->json($typeItems);
    }

    /**
     * @Route("/get-tree")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getTreeAction(Request $request)
    {
        $defaultIcon = '/pimcore/static6/img/flat-color-icons/timeline.svg';

        $classesList = new DataObject\ClassDefinition\Listing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');
        $classes = $classesList->load();

        // filter classes
        if ($request->get('createAllowed')) {
            $tmpClasses = [];
            foreach ($classes as $class) {
                if ($this->getUser()->isAllowed($class->getId(), 'class')) {
                    $tmpClasses[] = $class;
                }
            }
            $classes = $tmpClasses;
        }

        $getClassConfig = function ($class) use ($defaultIcon) {
            return [
                'id' => $class->getId(),
                'text' => $class->getName(),
                'leaf' => true,
                'icon' => $class->getIcon() ? $class->getIcon() : $defaultIcon,
                'propertyVisibility' => $class->getPropertyVisibility(),
                'qtipCfg' => [
                    'title' => 'ID: ' . $class->getId()
                ]
            ];
        };

        // build groups
        $groups = [];
        foreach ($classes as $class) {
            if ($class->getGroup()) {
                $type = 'manual';
                $groupName = $class->getGroup();
            } else {
                $type = 'auto';
                preg_match('@^([A-Za-z])([^A-Z]+)@', $class->getName(), $matches);
                $groupName = $matches[0];

                if (!$groupName) {
                    // this is eg. the case when class name uses only capital letters
                    $groupName = $class->getName();
                }
            }

            $groupName = \Pimcore\Model\Translation\Admin::getByKeyLocalized($groupName, true, true);

            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'classes' => [],
                    'type' => $type
                ];
            }
            $groups[$groupName]['classes'][] = $class;
        }

        $treeNodes = [];

        if (!$request->get('grouped')) {
            // list output
            foreach ($groups as $groupName => $groupData) {
                foreach ($groupData['classes'] as $class) {
                    $node = $getClassConfig($class);
                    if (count($groupData['classes']) > 1 || $groupData['type'] == 'manual') {
                        $node['group'] = $groupName;
                    }
                    $treeNodes[] = $node;
                }
            }
        } else {
            // create json output
            foreach ($groups as $groupName => $groupData) {
                if (count($groupData['classes']) === 1 && $groupData['type'] == 'auto') {
                    // no group, only one child
                    $node = $getClassConfig($groupData['classes'][0]);
                } else {
                    // group classes
                    $node = [
                        'id' => 'folder_' . $groupName,
                        'text' => $groupName,
                        'leaf' => false,
                        'expandable' => true,
                        'allowChildren' => true,
                        'iconCls' => 'pimcore_icon_folder',
                        'children' => []
                    ];

                    foreach ($groupData['classes'] as $class) {
                        $node['children'][] = $getClassConfig($class);
                    }
                }

                $treeNodes[] = $node;
            }
        }

        return $this->json($treeNodes);
    }

    /**
     * @Route("/get")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $class = DataObject\ClassDefinition::getById(intval($request->get('id')));
        $class->setFieldDefinitions(null);

        return $this->json($class);
    }

    /**
     * @Route("/get-custom-layout")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getCustomLayoutAction(Request $request)
    {
        $customLayout = DataObject\ClassDefinition\CustomLayout::getById(intval($request->get('id')));

        return $this->json(['success' => true, 'data' => $customLayout]);
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
        $class = DataObject\ClassDefinition::create(
            ['name' => $this->correctClassname($request->get('name')),
                'userOwner' => $this->getUser()->getId()]
        );

        $class->save();

        return $this->json(['success' => true, 'id' => $class->getId()]);
    }

    /**
     * @Route("/add-custom-layout")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addCustomLayoutAction(Request $request)
    {
        $customLayout = DataObject\ClassDefinition\CustomLayout::create(
            ['name' => $request->get('name'),
                'userOwner' => $this->getUser()->getId(),
                'classId' => $request->get('classId')]
        );

        $customLayout->save();

        return $this->json(['success' => true, 'id' => $customLayout->getId(), 'name' => $customLayout->getName(),
            'data' => $customLayout]);
    }

    /**
     * @Route("/delete")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAction(Request $request)
    {
        $class = DataObject\ClassDefinition::getById(intval($request->get('id')));
        $class->delete();

        return new Response();
    }

    /**
     * @Route("/delete-custom-layout")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteCustomLayoutAction(Request $request)
    {
        $customLayout = DataObject\ClassDefinition\CustomLayout::getById(intval($request->get('id')));
        if ($customLayout) {
            $customLayout->delete();
        }

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/save-custom-layout")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveCustomLayoutAction(Request $request)
    {
        $customLayout = DataObject\ClassDefinition\CustomLayout::getById($request->get('id'));
        $class = DataObject\ClassDefinition::getById($customLayout->getClassId());

        $configuration = $this->decodeJson($request->get('configuration'));
        $values = $this->decodeJson($request->get('values'));

        $modificationDate = intval($values['modificationDate']);
        if ($modificationDate < $customLayout->getModificationDate()) {
            return $this->json(['success' => false, 'msg' => 'custom_layout_changed']);
        }

        $configuration['datatype'] = 'layout';
        $configuration['fieldtype'] = 'panel';
        $configuration['name'] = 'pimcore_root';

        try {
            $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);
            $customLayout->setLayoutDefinitions($layout);
            $customLayout->setName($values['name']);
            $customLayout->setDescription($values['description']);
            $customLayout->setDefault($values['default']);
            $customLayout->save();

            return $this->json(['success' => true, 'id' => $customLayout->getId(), 'data' => $customLayout]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());

            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/save")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        $class = DataObject\ClassDefinition::getById(intval($request->get('id')));

        $configuration = $this->decodeJson($request->get('configuration'));
        $values = $this->decodeJson($request->get('values'));

        // check if the class was changed during editing in the frontend
        if ($class->getModificationDate() != $values['modificationDate']) {
            throw new \Exception('The class was modified during editing, please reload the class and make your changes again');
        }

        if ($values['name'] != $class->getName()) {
            $classByName = DataObject\ClassDefinition::getByName($values['name']);
            if ($classByName && $classByName->getId() != $class->getId()) {
                throw new \Exception('Class name already exists');
            }

            $values['name'] = $this->correctClassname($values['name']);
            $class->rename($values['name']);
        }

        unset($values['creationDate']);
        unset($values['userOwner']);
        unset($values['layoutDefinitions']);
        unset($values['fieldDefinitions']);

        $configuration['datatype'] = 'layout';
        $configuration['fieldtype'] = 'panel';
        $configuration['name'] = 'pimcore_root';

        $class->setValues($values);

        try {
            $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);

            $class->setLayoutDefinitions($layout);

            $class->setUserModification($this->getUser()->getId());
            $class->setModificationDate(time());

            $propertyVisibility = [];
            foreach ($values as $key => $value) {
                if (preg_match('/propertyVisibility/i', $key)) {
                    if (preg_match("/\.grid\./i", $key)) {
                        $propertyVisibility['grid'][preg_replace("/propertyVisibility\.grid\./i", '', $key)] = (bool) $value;
                    } elseif (preg_match("/\.search\./i", $key)) {
                        $propertyVisibility['search'][preg_replace("/propertyVisibility\.search\./i", '', $key)] = (bool) $value;
                    }
                }
            }
            if (!empty($propertyVisibility)) {
                $class->setPropertyVisibility($propertyVisibility);
            }

            $class->save();

            // set the fielddefinitions to null because we don't need them in the response
            $class->setFieldDefinitions(null);

            return $this->json(['success' => true, 'class' => $class]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());

            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    protected function correctClassname($name)
    {
        $name = preg_replace('/[^a-zA-Z0-9]+/', '', $name);
        $name = preg_replace('/^[0-9]+/', '', $name);

        return $name;
    }

    /**
     * @Route("/import-class")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function importClassAction(Request $request)
    {
        $class = DataObject\ClassDefinition::getById(intval($request->get('id')));
        $json = file_get_contents($_FILES['Filedata']['tmp_name']);

        $success = DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);

        $response = $this->json([
            'success' => $success
        ]);
        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/import-custom-layout")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function importCustomLayoutDefinitionAction(Request $request)
    {
        $success = false;
        $json = file_get_contents($_FILES['Filedata']['tmp_name']);
        $importData = $this->decodeJson($json);

        $customLayoutId = $request->get('id');
        $customLayout = DataObject\ClassDefinition\CustomLayout::getById($customLayoutId);
        if ($customLayout) {
            try {
                $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($importData['layoutDefinitions'], true);
                $customLayout->setLayoutDefinitions($layout);
                $customLayout->setDescription($importData['description']);
                $customLayout->save();
                $success = true;
            } catch (\Exception $e) {
                Logger::error($e->getMessage());
            }
        }

        $response = $this->json([
            'success' => $success
        ]);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/get-custom-layout-definitions")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getCustomLayoutDefinitionsAction(Request $request)
    {
        $classId = $request->get('classId');
        $list = new DataObject\ClassDefinition\CustomLayout\Listing();

        $list->setCondition('classId = ' . $list->quote($classId));
        $list = $list->load();
        $result = [];
        /** @var $item DataObject\ClassDefinition\CustomLayout */
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->getId(),
                'name' => $item->getName() . ' (ID: ' . $item->getId() . ')',
                'default' => $item->getDefault() ?: 0,
            ];
        }

        return $this->json(['success' => true, 'data' => $result]);
    }

    /**
     * @Route("/get-all-layouts")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAllLayoutsAction(Request $request)
    {
        // get all classes
        $resultList = [];
        $mapping = [];

        $customLayouts = new DataObject\ClassDefinition\CustomLayout\Listing();
        $customLayouts->setOrder('ASC');
        $customLayouts->setOrderKey('name');
        $customLayouts = $customLayouts->load();
        foreach ($customLayouts as $layout) {
            $mapping[$layout->getClassId()][] = $layout;
        }

        $classList = new DataObject\ClassDefinition\Listing();
        $classList->setOrder('ASC');
        $classList->setOrderKey('name');
        $classList = $classList->load();

        foreach ($classList as $class) {
            $classMapping = $mapping[$class->getId()];
            if ($classMapping) {
                $resultList[] = [
                    'type' => 'master',
                    'id' => $class->getId() . '_' . 0,
                    'name' => $class->getName()
                ];

                foreach ($classMapping as $layout) {
                    $resultList[] = [
                        'type' => 'custom',
                        'id' => $class->getId() . '_' . $layout->getId(),
                        'name' => $class->getName() . ' - ' . $layout->getName()
                    ];
                }
            }
        }

        return $this->json(['data' => $resultList]);
    }

    /**
     * @Route("/export-class")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportClassAction(Request $request)
    {
        $id = intval($request->get('id'));
        $class = DataObject\ClassDefinition::getById($id);

        if (!$class instanceof DataObject\ClassDefinition) {
            $errorMessage = ': Class with id [ ' . $id . ' not found. ]';
            Logger::error($errorMessage);
            echo $errorMessage;
        } else {
            $json = DataObject\ClassDefinition\Service::generateClassDefinitionJson($class);

            $response = new Response($json);
            $response->headers->set('Content-type', 'application/json');
            $response->headers->set('Content-Disposition', 'attachment; filename="class_' . $class->getName() . '_export.json"');

            return $response;
        }
    }

    /**
     * @Route("/export-custom-layout-definitions")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportCustomLayoutDefinitionAction(Request $request)
    {
        $id = intval($request->get('id'));

        if ($id) {
            $customLayout = DataObject\ClassDefinition\CustomLayout::getById($id);
            if ($customLayout) {
                $name = $customLayout->getName();
                unset($customLayout->id);
                unset($customLayout->classId);
                unset($customLayout->name);
                unset($customLayout->creationDate);
                unset($customLayout->modificationDate);
                unset($customLayout->userOwner);
                unset($customLayout->userModification);
                unset($customLayout->fieldDefinitions);

                $json = json_encode($customLayout, JSON_PRETTY_PRINT);

                $response = new Response($json);
                $response->headers->set('Content-type', 'application/json');
                $response->headers->set('Content-Disposition', 'attachment; filename="custom_definition_' . $name . '_export.json"');

                return $response;
            }
        }

        $errorMessage = ': Custom Layout with id [ ' . $id . ' not found. ]';
        Logger::error($errorMessage);
        echo $errorMessage;
    }

    /**
     * FIELDCOLLECTIONS
     */

    /**
     * @Route("/fieldcollection-get")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fieldcollectionGetAction(Request $request)
    {
        $fc = DataObject\Fieldcollection\Definition::getByKey($request->get('id'));

        return $this->json($fc);
    }

    /**
     * @Route("/fieldcollection-update")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fieldcollectionUpdateAction(Request $request)
    {
        try {
            $key = $request->get('key');

            if ($request->get('task') == 'add') {
                // check for existing fieldcollection with same name with different lower/upper cases
                $list = new DataObject\Fieldcollection\Definition\Listing();
                $list = $list->load();

                foreach ($list as $item) {
                    if (strtolower($key) === strtolower($item->getKey())) {
                        throw new \Exception('FieldCollection with the same name already exists (lower/upper cases may be different)');
                    }
                }
            }

            $fc = new DataObject\Fieldcollection\Definition();
            $fc->setKey($key);

            if ($request->get('values')) {
                $values = $this->decodeJson($request->get('values'));
                $fc->setParentClass($values['parentClass']);
            }

            if ($request->get('configuration')) {
                $configuration = $this->decodeJson($request->get('configuration'));

                $configuration['datatype'] = 'layout';
                $configuration['fieldtype'] = 'panel';

                $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);
                $fc->setLayoutDefinitions($layout);
            }

            $fc->save();

            return $this->json(['success' => true, 'id' => $fc->getKey()]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());

            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/import-fieldcollection")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function importFieldcollectionAction(Request $request)
    {
        $fieldCollection = DataObject\Fieldcollection\Definition::getByKey($request->get('id'));

        $data = file_get_contents($_FILES['Filedata']['tmp_name']);

        $success = DataObject\ClassDefinition\Service::importFieldCollectionFromJson($fieldCollection, $data);

        $response = $this->json([
            'success' => $success
        ]);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/export-fieldcollection")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportFieldcollectionAction(Request $request)
    {
        $fieldCollection = DataObject\Fieldcollection\Definition::getByKey($request->get('id'));
        $key = $fieldCollection->getKey();
        if (!$fieldCollection instanceof DataObject\Fieldcollection\Definition) {
            $errorMessage = ': Field-Collection with id [ ' . $request->get('id') . ' not found. ]';
            Logger::error($errorMessage);
            echo $errorMessage;
        } else {
            $json = DataObject\ClassDefinition\Service::generateFieldCollectionJson($fieldCollection);
            $response = new Response($json);
            $response->headers->set('Content-type', 'application/json');
            $response->headers->set('Content-Disposition', 'attachment; filename="fieldcollection_' . $key . '_export.json"');

            return $response;
        }
    }

    /**
     * @Route("/fieldcollection-delete")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fieldcollectionDeleteAction(Request $request)
    {
        $fc = DataObject\Fieldcollection\Definition::getByKey($request->get('id'));
        $fc->delete();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/fieldcollection-tree")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fieldcollectionTreeAction(Request $request)
    {
        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->load();

        $items = [];

        foreach ($list as $fc) {
            $items[] = [
                'id' => $fc->getKey(),
                'text' => $fc->getKey()
            ];
        }

        return $this->json($items);
    }

    /**
     * @Route("/fieldcollection-list")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fieldcollectionListAction(Request $request)
    {
        $user = \Pimcore\Tool\Admin::getCurrentUser();
        $currentLayoutId = $request->get('layoutId');

        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->load();

        if ($request->query->has('allowedTypes')) {
            $filteredList = [];
            $allowedTypes = explode(',', $request->get('allowedTypes'));
            /** @var $type DataObject\Fieldcollection\Definition */
            foreach ($list as $type) {
                if (in_array($type->getKey(), $allowedTypes)) {
                    $filteredList[] = $type;

                    // mainly for objects-meta data-type
                    $layoutDefinitions = $type->getLayoutDefinitions();
                    $context = [
                        'containerType' => 'fieldcollection',
                        'containerKey' => $type->getKey(),
                        'outerFieldname' => $request->get('field_name')
                    ];

                    $object = DataObject\AbstractObject::getById($request->get('object_id'));

                    DataObject\Service::enrichLayoutDefinition($layoutDefinitions, $object, $context);

                    if ($currentLayoutId == -1 && $user->isAdmin()) {
                        DataObject\Service::createSuperLayout($layoutDefinitions);
                    }
                }
            }

            $list = $filteredList;
        }

        return $this->json(['fieldcollections' => $list]);
    }

    /**
     * @Route("/get-class-definition-for-column-config")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getClassDefinitionForColumnConfigAction(Request $request)
    {
        $class = DataObject\ClassDefinition::getById(intval($request->get('id')));
        $objectId = intval($request->get('oid'));

        $filteredDefinitions = DataObject\Service::getCustomLayoutDefinitionForGridColumnConfig($class, $objectId);

        $layoutDefinitions = isset($filteredDefinitions['layoutDefinition']) ? $filteredDefinitions['layoutDefinition'] : false;
        $filteredFieldDefinition = isset($filteredDefinitions['fieldDefinition']) ? $filteredDefinitions['fieldDefinition'] : false;

        $class->setFieldDefinitions(null);

        $result = [];

        $result['objectColumns']['childs'] = $layoutDefinitions->getChilds();
        $result['objectColumns']['nodeLabel'] = 'object_columns';
        $result['objectColumns']['nodeType'] = 'object';

        // array("id", "fullpath", "published", "creationDate", "modificationDate", "filename", "classname");
        $systemColumnNames = DataObject\Concrete::$systemColumnNames;
        $systemColumns = [];
        foreach ($systemColumnNames as $systemColumn) {
            $systemColumns[] = ['title' => $systemColumn, 'name' => $systemColumn, 'datatype' => 'data', 'fieldtype' => 'system'];
        }
        $result['systemColumns']['nodeLabel'] = 'system_columns';
        $result['systemColumns']['nodeType'] = 'system';
        $result['systemColumns']['childs'] = $systemColumns;

        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();

        foreach ($list as $brickDefinition) {
            $classDefs = $brickDefinition->getClassDefinitions();
            if (!empty($classDefs)) {
                foreach ($classDefs as $classDef) {
                    if ($classDef['classname'] == $class->getName()) {
                        $fieldName = $classDef['fieldname'];
                        if ($filteredFieldDefinition && !$filteredFieldDefinition[$fieldName]) {
                            continue;
                        }

                        $key = $brickDefinition->getKey();

                        $result[$key]['nodeLabel'] = $key;
                        $result[$key]['brickField'] = $fieldName;
                        $result[$key]['nodeType'] = 'objectbricks';
                        $result[$key]['childs'] = $brickDefinition->getLayoutdefinitions()->getChilds();
                        break;
                    }
                }
            }
        }

        return $this->json($result);
    }

    /**
     * OBJECT BRICKS
     */

    /**
     * @Route("/objectbrick-get")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function objectbrickGetAction(Request $request)
    {
        $fc = DataObject\Objectbrick\Definition::getByKey($request->get('id'));

        return $this->json($fc);
    }

    /**
     * @Route("/objectbrick-update")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function objectbrickUpdateAction(Request $request)
    {
        try {
            $key = $request->get('key');

            if ($request->get('task') == 'add') {
                // check for existing brick with same name with different lower/upper cases
                $list = new DataObject\Objectbrick\Definition\Listing();
                $list = $list->load();

                foreach ($list as $item) {
                    if (strtolower($key) === strtolower($item->getKey())) {
                        throw new \Exception('Brick with the same name already exists (lower/upper cases may be different)');
                    }
                }
            }

            // now we create a new definition
            $fc = new DataObject\Objectbrick\Definition();
            $fc->setKey($key);

            if ($request->get('values')) {
                $values = $this->decodeJson($request->get('values'));

                $fc->setParentClass($values['parentClass']);
                $fc->setClassDefinitions($values['classDefinitions']);
            }

            if ($request->get('configuration')) {
                $configuration = $this->decodeJson($request->get('configuration'));

                $configuration['datatype'] = 'layout';
                $configuration['fieldtype'] = 'panel';

                $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);
                $fc->setLayoutDefinitions($layout);
            }

            $fc->save();

            return $this->json(['success' => true, 'id' => $fc->getKey()]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());

            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/import-objectbrick")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function importObjectbrickAction(Request $request)
    {
        $objectBrick = DataObject\Objectbrick\Definition::getByKey($request->get('id'));

        $data = file_get_contents($_FILES['Filedata']['tmp_name']);
        $success = DataObject\ClassDefinition\Service::importObjectBrickFromJson($objectBrick, $data);

        $response = $this->json([
            'success' => $success
        ]);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/export-objectbrick")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportObjectbrickAction(Request $request)
    {
        $objectBrick = DataObject\Objectbrick\Definition::getByKey($request->get('id'));
        $key = $objectBrick->getKey();
        if (!$objectBrick instanceof DataObject\Objectbrick\Definition) {
            $errorMessage = ': Object-Brick with id [ ' . $request->get('id') . ' not found. ]';
            Logger::error($errorMessage);
            echo $errorMessage;
        } else {
            $xml = DataObject\ClassDefinition\Service::generateObjectBrickJson($objectBrick);
            $response = new Response($xml);
            $response->headers->set('Content-type', 'application/json');
            $response->headers->set('Content-Disposition', 'attachment; filename="objectbrick_' . $key . '_export.json"');

            return $response;
        }
    }

    /**
     * @Route("/objectbrick-delete")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function objectbrickDeleteAction(Request $request)
    {
        $fc = DataObject\Objectbrick\Definition::getByKey($request->get('id'));
        $fc->delete();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/objectbrick-tree")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function objectbrickTreeAction(Request $request)
    {
        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();

        $items = [];

        foreach ($list as $fc) {
            $items[] = [
                'id' => $fc->getKey(),
                'text' => $fc->getKey()
            ];
        }

        return $this->json($items);
    }

    /**
     * @Route("/objectbrick-list")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function objectbrickListAction(Request $request)
    {
        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();

        if ($request->query->has('class_id') && $request->query->has('field_name')) {
            $filteredList = [];
            $classId = $request->get('class_id');
            $fieldname = $request->get('field_name');
            $classDefinition = DataObject\ClassDefinition::getById($classId);
            $className = $classDefinition->getName();

            foreach ($list as $type) {
                /** @var $type DataObject\Objectbrick\Definition */
                $clsDefs = $type->getClassDefinitions();
                if (!empty($clsDefs)) {
                    foreach ($clsDefs as $cd) {
                        if ($cd['classname'] == $className && $cd['fieldname'] == $fieldname) {
                            $filteredList[] = $type;
                            continue;
                        }
                    }
                }

                $layout = $type->getLayoutDefinitions();

                $currentLayoutId = $request->get('layoutId', null);

                $user = $this->getUser();
                if ($currentLayoutId == -1 && $user->isAdmin()) {
                    DataObject\Service::createSuperLayout($layout);
                    $objectData['layout'] = $layout;
                }

                $context = [
                    'containerType' => 'objectbrick',
                    'containerKey' => $type->getKey(),
                    'outerFieldname' => $request->get('field_name')
                ];

                $object = DataObject\AbstractObject::getById($request->get('object_id'));

                DataObject\Service::enrichLayoutDefinition($layout, $object, $context);
                $type->setLayoutDefinitions($layout);
            }

            $list = $filteredList;
        }

        $event = new GenericEvent($this, [
            'list' => $list,
            'objectId'=>$request->get('object_id')
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::CLASS_OBJECTBRICK_LIST_PRE_SEND_DATA, $event);
        $list = $event->getArgument('list');

        return $this->json(['objectbricks' => $list]);
    }

    /**
     * See http://www.pimcore.org/issues/browse/PIMCORE-2358
     * Add option to export/import all class definitions/brick definitions etc. at once
     */

    /**
     * @Route("/bulk-import")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function bulkImportAction(Request $request)
    {
        $result = [];

        $tmpName = $_FILES['Filedata']['tmp_name'];
        $json = file_get_contents($tmpName);

        $tmpName = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/bulk-import.tmp';
        file_put_contents($tmpName, $json);

        $json = json_decode($json, true);

        foreach ($json as $groupName => $group) {
            foreach ($group as $groupItem) {
                $displayName = null;

                if ($groupName == 'class') {
                    $name = $groupItem['name'];
                    $icon = 'class';
                } elseif ($groupName == 'customlayout') {
                    $className = $groupItem['className'];

                    $layoutData = ['className' => $className, 'name' => $groupItem['name']];
                    $name = serialize($layoutData);
                    $displayName = $className . ' / ' . $groupItem['name'];
                    $icon = 'database_lightning';
                } else {
                    if ($groupName == 'objectbrick') {
                        $icon = 'objectbricks';
                    } elseif ($groupName == 'fieldcollection') {
                        $icon = 'fieldcollection';
                    }
                    $name = $groupItem['key'];
                }

                if (!$displayName) {
                    $displayName = $name;
                }
                $result[] = ['icon' => $icon, 'checked' => true, 'type' => $groupName, 'name' => $name, 'displayName' => $displayName];
            }
        }

        $response = $this->json(['success' => true, 'filename' => $tmpName, 'data' => $result]);
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * See http://www.pimcore.org/issues/browse/PIMCORE-2358
     * Add option to export/import all class definitions/brick definitions etc. at once
     */

    /**
     * @Route("/bulk-commit")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function bulkCommitAction(Request $request)
    {
        $filename = $request->get('filename');
        $data = json_decode($request->get('data'), true);

        $json = @file_get_contents($filename);
        $json = json_decode($json, true);

        $type = $data['type'];
        $name = $data['name'];
        $list = $json[$type];

        foreach ($list as $item) {
            unset($item['creationDate']);
            unset($item['modificationDate']);
            unset($item['userOwner']);
            unset($item['userModification']);

            unset($item['id']);

            if ($type == 'class' && $item['name'] == $name) {
                $class = DataObject\ClassDefinition::getByName($name);
                if (!$class) {
                    $class = new DataObject\ClassDefinition();
                    $class->setName($name);
                }
                $success = DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, json_encode($item), true);

                return $this->json(['success' => $success !== false]);
            } elseif ($type == 'objectbrick' && $item['key'] == $name) {
                try {
                    $brick = DataObject\Objectbrick\Definition::getByKey($name);
                } catch (\Exception $e) {
                    $brick = new DataObject\Objectbrick\Definition();
                    $brick->setKey($name);
                }

                $success = DataObject\ClassDefinition\Service::importObjectBrickFromJson($brick, json_encode($item), true);

                return $this->json(['success' => $success !== false]);
            } elseif ($type == 'fieldcollection' && $item['key'] == $name) {
                try {
                    $fieldCollection = DataObject\Fieldcollection\Definition::getByKey($name);
                } catch (\Exception $e) {
                    $fieldCollection = new DataObject\Fieldcollection\Definition();
                    $fieldCollection->setKey($name);
                }
                $success = DataObject\ClassDefinition\Service::importFieldCollectionFromJson($fieldCollection, json_encode($item), true);

                return $this->json(['success' => $success !== false]);
            } elseif ($type == 'customlayout') {
                $layoutData = unserialize($data['name']);
                $className = $layoutData['className'];
                $layoutName = $layoutData['name'];

                if ($item['name'] == $layoutName && $item['className'] == $className) {
                    $class = DataObject\ClassDefinition::getByName($className);
                    if (!$class) {
                        throw new \Exception('Class does not exist');
                    }

                    $classId = $class->getId();

                    $layoutList = new DataObject\ClassDefinition\CustomLayout\Listing();
                    $db = \Pimcore\Db::get();
                    $layoutList->setCondition('name = ' . $db->quote($layoutName) . ' AND classId = ' . $classId);
                    $layoutList = $layoutList->load();

                    $layoutDefinition = null;
                    if ($layoutList) {
                        $layoutDefinition = $layoutList[0];
                    }

                    if (!$layoutDefinition) {
                        $layoutDefinition = new DataObject\ClassDefinition\CustomLayout();
                        $layoutDefinition->setName($layoutName);
                        $layoutDefinition->setClassId($classId);
                    }

                    try {
                        $layoutDefinition->setDescription($item['description']);
                        $layoutDef = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($item['layoutDefinitions'], true);
                        $layoutDefinition->setLayoutDefinitions($layoutDef);
                        $layoutDefinition->save();
                    } catch (\Exception $e) {
                        Logger::error($e->getMessage());

                        return $this->json(['success' => false, 'message' => $e->getMessage()]);
                    }
                }
            }
        }

        return $this->json(['success' => true]);
    }

    /**
     * See http://www.pimcore.org/issues/browse/PIMCORE-2358
     * Add option to export/import all class definitions/brick definitions etc. at once
     */

    /**
     * @Route("/bulk-export")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function bulkExportAction(Request $request)
    {
        $result = [];

        $fieldCollections = new DataObject\Fieldcollection\Definition\Listing();
        $fieldCollections = $fieldCollections->load();

        foreach ($fieldCollections as $fieldCollection) {
            $key = $fieldCollection->key;
            $fieldCollectionJson = json_decode(DataObject\ClassDefinition\Service::generateFieldCollectionJson($fieldCollection));
            $fieldCollectionJson->key = $key;
            $result['fieldcollection'][] = $fieldCollectionJson;
        }

        $classes = new DataObject\ClassDefinition\Listing();
        $classes->setOrder('ASC');
        $classes->setOrderKey('id');
        $classes = $classes->load();

        foreach ($classes as $class) {
            $data = Model\Webservice\Data\Mapper::map($class, '\\Pimcore\\Model\\Webservice\\Data\\ClassDefinition\\Out', 'out');
            unset($data->fieldDefinitions);
            $result['class'][] = $data;
        }

        $objectBricks = new DataObject\Objectbrick\Definition\Listing();
        $objectBricks = $objectBricks->load();

        foreach ($objectBricks as $objectBrick) {
            $key = $objectBrick->key;
            $objectBrickJson = json_decode(DataObject\ClassDefinition\Service::generateObjectBrickJson($objectBrick));
            $objectBrickJson->key = $key;
            $result['objectbrick'][] = $objectBrickJson;
        }

        $customLayouts = new DataObject\ClassDefinition\CustomLayout\Listing();
        $customLayouts = $customLayouts->load();
        foreach ($customLayouts as $customLayout) {
            /** @var $customLayout DataObject\ClassDefinition\CustomLayout */
            $classId = $customLayout->getClassId();
            $class = DataObject\ClassDefinition::getById($classId);
            $customLayout->className = $class->getName();
            $result['customlayout'][] = $customLayout;
        }

        $result = json_encode($result);
        $response = new Response($result);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="bulk_export.json"');

        return $response;
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
        $unrestrictedActions = [
            'getTreeAction', 'fieldcollectionListAction', 'fieldcollectionTreeAction', 'fieldcollectionGetAction',
            'getClassDefinitionForColumnConfigAction', 'objectbrickListAction', 'objectbrickTreeAction', 'objectbrickGetAction'
        ];

        $this->checkActionPermission($event, 'classes', $unrestrictedActions);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
