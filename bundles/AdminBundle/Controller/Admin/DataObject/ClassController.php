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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\DataObject;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Db;
use Pimcore\Event\AdminEvents;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Tool\Session;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/class")
 */
class ClassController extends AdminController implements EventedControllerInterface
{
    /**
     * @Route("/get-document-types", name="pimcore_admin_dataobject_class_getdocumenttypes", methods={"GET"})
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
                'text' => $documentType,
            ];
        }

        return $this->adminJson($typeItems);
    }

    /**
     * @Route("/get-asset-types", name="pimcore_admin_dataobject_class_getassettypes", methods={"GET"})
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
                'text' => $assetType,
            ];
        }

        return $this->adminJson($typeItems);
    }

    /**
     * @Route("/get-tree", name="pimcore_admin_dataobject_class_gettree", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getTreeAction(Request $request)
    {
        $defaultIcon = '/bundles/pimcoreadmin/img/flat-color-icons/class.svg';

        $classesList = new DataObject\ClassDefinition\Listing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');
        $classes = $classesList->load();

        // filter classes
        if ($request->get('createAllowed')) {
            $tmpClasses = [];
            foreach ($classes as $class) {
                if ($this->getAdminUser()->isAllowed($class->getId(), 'class')) {
                    $tmpClasses[] = $class;
                }
            }
            $classes = $tmpClasses;
        }

        $withId = $request->get('withId');
        $getClassConfig = function ($class) use ($defaultIcon, $withId) {
            $text = $class->getname();
            if ($withId) {
                $text .= ' (' . $class->getId() . ')';
            }

            return [
                'id' => $class->getId(),
                'text' => $text,
                'leaf' => true,
                'icon' => $class->getIcon() ? $class->getIcon() : $defaultIcon,
                'cls' => 'pimcore_class_icon',
                'propertyVisibility' => $class->getPropertyVisibility(),
                'enableGridLocking' => $class->isEnableGridLocking(),
            ];
        };

        // build groups
        $groups = [];
        foreach ($classes as $class) {
            if (!$class) {
                continue;
            }
            $groupName = null;

            if ($class->getGroup()) {
                $type = 'manual';
                $groupName = $class->getGroup();
            } else {
                $type = 'auto';
                if (preg_match('@^([A-Za-z])([^A-Z]+)@', $class->getName(), $matches)) {
                    $groupName = $matches[0];
                }

                if (!$groupName) {
                    // this is eg. the case when class name uses only capital letters
                    $groupName = $class->getName();
                }
            }

            $groupName = \Pimcore\Model\Translation\Admin::getByKeyLocalized($groupName, true, true);

            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'classes' => [],
                    'type' => $type,
                ];
            }
            $groups[$groupName]['classes'][] = $class;
        }

        $treeNodes = [];
        if (!empty($groups)) {
            $types = array_column($groups, 'type');
            array_multisort($types, SORT_ASC, array_keys($groups), SORT_ASC, $groups);
        }

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
                        'children' => [],
                    ];

                    foreach ($groupData['classes'] as $class) {
                        $node['children'][] = $getClassConfig($class);
                    }
                }

                $treeNodes[] = $node;
            }
        }

        return $this->adminJson($treeNodes);
    }

    /**
     * @Route("/get", name="pimcore_admin_dataobject_class_get", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $class = DataObject\ClassDefinition::getById($request->get('id'));
        $class->setFieldDefinitions([]);

        return $this->adminJson($class);
    }

    /**
     * @Route("/get-custom-layout", name="pimcore_admin_dataobject_class_getcustomlayout", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getCustomLayoutAction(Request $request)
    {
        $customLayout = DataObject\ClassDefinition\CustomLayout::getById($request->get('id'));

        return $this->adminJson(['success' => true, 'data' => $customLayout]);
    }

    /**
     * @Route("/add", name="pimcore_admin_dataobject_class_add", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAction(Request $request)
    {
        $className = $request->get('className');
        $className = $this->correctClassname($className);

        $classId = $request->get('classIdentifier');
        $existingClass = DataObject\ClassDefinition::getById($classId);
        if ($existingClass) {
            throw new \Exception('Class identifier already exists');
        }

        $class = DataObject\ClassDefinition::create(
            ['name' => $className,
                'userOwner' => $this->getAdminUser()->getId(), ]
        );

        $class->setId($classId);

        $class->save(true);

        return $this->adminJson(['success' => true, 'id' => $class->getId()]);
    }

    /**
     * @Route("/add-custom-layout", name="pimcore_admin_dataobject_class_addcustomlayout", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addCustomLayoutAction(Request $request)
    {
        $layoutId = $request->get('layoutIdentifier');
        $existingLayout = DataObject\ClassDefinition\CustomLayout::getById($layoutId);
        if ($existingLayout) {
            throw new \Exception('Custom Layout identifier already exists');
        }

        $customLayout = DataObject\ClassDefinition\CustomLayout::create(
            [
                'name' => $request->get('layoutName'),
                'userOwner' => $this->getAdminUser()->getId(),
                'classId' => $request->get('classId'),
            ]
        );

        $customLayout->setId($layoutId);
        $customLayout->save();

        return $this->adminJson(['success' => true, 'id' => $customLayout->getId(), 'name' => $customLayout->getName(),
                                 'data' => $customLayout, ]);
    }

    /**
     * @Route("/delete", name="pimcore_admin_dataobject_class_delete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAction(Request $request)
    {
        $class = DataObject\ClassDefinition::getById($request->get('id'));
        $class->delete();

        return new Response();
    }

    /**
     * @Route("/delete-custom-layout", name="pimcore_admin_dataobject_class_deletecustomlayout", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteCustomLayoutAction(Request $request)
    {
        $customLayout = DataObject\ClassDefinition\CustomLayout::getById($request->get('id'));
        if ($customLayout) {
            $customLayout->delete();
        }

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/save-custom-layout", name="pimcore_admin_dataobject_class_savecustomlayout", methods={"PUT"})
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
            return $this->adminJson(['success' => false, 'msg' => 'custom_layout_changed']);
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

            return $this->adminJson(['success' => true, 'id' => $customLayout->getId(), 'data' => $customLayout]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());

            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/save", name="pimcore_admin_dataobject_class_save", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        $class = DataObject\ClassDefinition::getById($request->get('id'));

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

            $class->setUserModification($this->getAdminUser()->getId());
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

            // set the fielddefinitions to [] because we don't need them in the response
            $class->setFieldDefinitions([]);

            return $this->adminJson(['success' => true, 'class' => $class]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());

            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function correctClassname($name)
    {
        $name = preg_replace('/[^a-zA-Z0-9_]+/', '', $name);
        $name = preg_replace('/^[0-9]+/', '', $name);

        return $name;
    }

    /**
     * @Route("/import-class", name="pimcore_admin_dataobject_class_importclass", methods={"POST", "PUT"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function importClassAction(Request $request)
    {
        $class = DataObject\ClassDefinition::getById($request->get('id'));
        $json = file_get_contents($_FILES['Filedata']['tmp_name']);

        $success = DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, $json, false, true);

        $response = $this->adminJson([
            'success' => $success,
        ]);
        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/import-custom-layout-definition", name="pimcore_admin_dataobject_class_importcustomlayoutdefinition", methods={"POST", "PUT"})
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

        $response = $this->adminJson([
            'success' => $success,
        ]);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/get-custom-layout-definitions", name="pimcore_admin_dataobject_class_getcustomlayoutdefinitions", methods={"GET"})
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
        /** @var DataObject\ClassDefinition\CustomLayout $item */
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->getId(),
                'name' => $item->getName() . ' (ID: ' . $item->getId() . ')',
                'default' => $item->getDefault() ?: 0,
            ];
        }

        return $this->adminJson(['success' => true, 'data' => $result]);
    }

    /**
     * @Route("/get-all-layouts", name="pimcore_admin_dataobject_class_getalllayouts", methods={"GET"})
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
            if (isset($mapping[$class->getId()])) {
                $classMapping = $mapping[$class->getId()];
                $resultList[] = [
                    'type' => 'master',
                    'id' => $class->getId() . '_' . 0,
                    'name' => $class->getName(),
                ];

                foreach ($classMapping as $layout) {
                    $resultList[] = [
                        'type' => 'custom',
                        'id' => $class->getId() . '_' . $layout->getId(),
                        'name' => $class->getName() . ' - ' . $layout->getName(),
                    ];
                }
            }
        }

        return $this->adminJson(['data' => $resultList]);
    }

    /**
     * @Route("/export-class", name="pimcore_admin_dataobject_class_exportclass", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportClassAction(Request $request)
    {
        $id = $request->get('id');
        $class = DataObject\ClassDefinition::getById($id);

        if (!$class instanceof DataObject\ClassDefinition) {
            $errorMessage = ': Class with id [ ' . $id . ' not found. ]';
            Logger::error($errorMessage);
            throw $this->createNotFoundException($errorMessage);
        }

        $json = DataObject\ClassDefinition\Service::generateClassDefinitionJson($class);

        $response = new Response($json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="class_' . $class->getName() . '_export.json"');

        return $response;
    }

    /**
     * @Route("/export-custom-layout-definition", name="pimcore_admin_dataobject_class_exportcustomlayoutdefinition", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportCustomLayoutDefinitionAction(Request $request)
    {
        $id = $request->get('id');

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
        throw $this->createNotFoundException($errorMessage);
    }

    /**
     * FIELDCOLLECTIONS
     */

    /**
     * @Route("/fieldcollection-get", name="pimcore_admin_dataobject_class_fieldcollectionget", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fieldcollectionGetAction(Request $request)
    {
        $fc = DataObject\Fieldcollection\Definition::getByKey($request->get('id'));

        return $this->adminJson($fc);
    }

    /**
     * @Route("/fieldcollection-update", name="pimcore_admin_dataobject_class_fieldcollectionupdate", methods={"PUT", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fieldcollectionUpdateAction(Request $request)
    {
        try {
            $key = $request->get('key');
            $title = $request->get('title');
            $group = $request->get('group');

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

            $fcDef = new DataObject\Fieldcollection\Definition();
            $fcDef->setKey($key);
            $fcDef->setTitle($title);
            $fcDef->setGroup($group);

            if ($request->get('values')) {
                $values = $this->decodeJson($request->get('values'));
                $fcDef->setParentClass($values['parentClass']);
                $fcDef->setImplementsInterfaces($values['implementsInterfaces']);
                $fcDef->setGenerateTypeDeclarations($values['generateTypeDeclarations']);
            }

            if ($request->get('configuration')) {
                $configuration = $this->decodeJson($request->get('configuration'));

                $configuration['datatype'] = 'layout';
                $configuration['fieldtype'] = 'panel';

                $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);
                $fcDef->setLayoutDefinitions($layout);
            }

            $fcDef->save();

            return $this->adminJson(['success' => true, 'id' => $fcDef->getKey()]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());

            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/import-fieldcollection", name="pimcore_admin_dataobject_class_importfieldcollection", methods={"POST"})
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

        $response = $this->adminJson([
            'success' => $success,
        ]);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/export-fieldcollection", name="pimcore_admin_dataobject_class_exportfieldcollection", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportFieldcollectionAction(Request $request)
    {
        $fieldCollection = DataObject\Fieldcollection\Definition::getByKey($request->get('id'));

        if (!$fieldCollection instanceof DataObject\Fieldcollection\Definition) {
            $errorMessage = ': Field-Collection with id [ ' . $request->get('id') . ' not found. ]';
            Logger::error($errorMessage);
            throw $this->createNotFoundException($errorMessage);
        }

        $json = DataObject\ClassDefinition\Service::generateFieldCollectionJson($fieldCollection);
        $response = new Response($json);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="fieldcollection_' . $fieldCollection->getKey() . '_export.json"');

        return $response;
    }

    /**
     * @Route("/fieldcollection-delete", name="pimcore_admin_dataobject_class_fieldcollectiondelete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fieldcollectionDeleteAction(Request $request)
    {
        $fc = DataObject\Fieldcollection\Definition::getByKey($request->get('id'));
        $fc->delete();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/fieldcollection-tree", name="pimcore_admin_dataobject_class_fieldcollectiontree", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fieldcollectionTreeAction(Request $request)
    {
        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->load();

        $forObjectEditor = $request->get('forObjectEditor');

        $layoutDefinitions = [];

        $definitions = [];

        $allowedTypes = null;
        if ($request->query->has('allowedTypes')) {
            $allowedTypes = explode(',', $request->get('allowedTypes'));
        }
        $object = DataObject\AbstractObject::getById($request->get('object_id'));

        $currentLayoutId = $request->get('layoutId', null);
        $user = \Pimcore\Tool\Admin::getCurrentUser();

        $groups = [];
        /** @var DataObject\Fieldcollection\Definition $item */
        foreach ($list as $item) {
            if ($allowedTypes && !in_array($item->getKey(), $allowedTypes)) {
                continue;
            }

            if ($item->getGroup()) {
                if (!isset($groups[$item->getGroup()])) {
                    $groups[$item->getGroup()] = [
                        'id' => 'group_' . $item->getKey(),
                        'text' => $item->getGroup(),
                        'expandable' => true,
                        'leaf' => false,
                        'allowChildren' => true,
                        'iconCls' => 'pimcore_icon_folder',
                        'group' => $item->getGroup(),
                        'children' => [],
                    ];
                }
                if ($forObjectEditor) {
                    $itemLayoutDefinitions = $item->getLayoutDefinitions();
                    DataObject\Service::enrichLayoutDefinition($itemLayoutDefinitions, $object);

                    if ($currentLayoutId == -1 && $user->isAdmin()) {
                        DataObject\Service::createSuperLayout($itemLayoutDefinitions);
                    }
                    $layoutDefinitions[$item->getKey()] = $itemLayoutDefinitions;
                }
                $groups[$item->getGroup()]['children'][] =
                    [
                        'id' => $item->getKey(),
                        'text' => $item->getKey(),
                        'title' => $item->getTitle(),
                        'key' => $item->getKey(),
                        'leaf' => true,
                        'iconCls' => 'pimcore_icon_fieldcollection',
                    ];
            } else {
                if ($forObjectEditor) {
                    $itemLayoutDefinitions = $item->getLayoutDefinitions();
                    DataObject\Service::enrichLayoutDefinition($itemLayoutDefinitions, $object);

                    if ($currentLayoutId == -1 && $user->isAdmin()) {
                        DataObject\Service::createSuperLayout($itemLayoutDefinitions);
                    }

                    $layoutDefinitions[$item->getKey()] = $itemLayoutDefinitions;
                }
                $definitions[] = [
                    'id' => $item->getKey(),
                    'text' => $item->getKey(),
                    'title' => $item->getTitle(),
                    'key' => $item->getKey(),
                    'leaf' => true,
                    'iconCls' => 'pimcore_icon_fieldcollection',
                ];
            }
        }

        foreach ($groups as $group) {
            $definitions[] = $group;
        }

        $event = new GenericEvent($this, [
            'list' => $definitions,
            'objectId' => $request->get('object_id'),
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::CLASS_FIELDCOLLECTION_LIST_PRE_SEND_DATA, $event);
        $definitions = $event->getArgument('list');

        if ($forObjectEditor) {
            return $this->adminJson(['fieldcollections' => $definitions, 'layoutDefinitions' => $layoutDefinitions]);
        } else {
            return $this->adminJson($definitions);
        }
    }

    /**
     * @Route("/fieldcollection-list", name="pimcore_admin_dataobject_class_fieldcollectionlist", methods={"GET"})
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
            /** @var DataObject\Fieldcollection\Definition $type */
            foreach ($list as $type) {
                if (in_array($type->getKey(), $allowedTypes)) {
                    $filteredList[] = $type;

                    // mainly for objects-meta data-type
                    $layoutDefinitions = $type->getLayoutDefinitions();
                    $context = [
                        'containerType' => 'fieldcollection',
                        'containerKey' => $type->getKey(),
                        'outerFieldname' => $request->get('field_name'),
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

        $event = new GenericEvent($this, [
            'list' => $list,
            'objectId' => $request->get('object_id'),
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::CLASS_FIELDCOLLECTION_LIST_PRE_SEND_DATA, $event);
        $list = $event->getArgument('list');

        return $this->adminJson(['fieldcollections' => $list]);
    }

    /**
     * @Route("/get-class-definition-for-column-config", name="pimcore_admin_dataobject_class_getclassdefinitionforcolumnconfig", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getClassDefinitionForColumnConfigAction(Request $request)
    {
        $class = DataObject\ClassDefinition::getById($request->get('id'));
        $objectId = intval($request->get('oid'));

        $filteredDefinitions = DataObject\Service::getCustomLayoutDefinitionForGridColumnConfig($class, $objectId);

        $layoutDefinitions = isset($filteredDefinitions['layoutDefinition']) ? $filteredDefinitions['layoutDefinition'] : false;
        $filteredFieldDefinition = isset($filteredDefinitions['fieldDefinition']) ? $filteredDefinitions['fieldDefinition'] : false;

        $class->setFieldDefinitions([]);

        $result = [];

        DataObject\Service::enrichLayoutDefinition($layoutDefinitions);

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

                        $brickLayoutDefinitions = $brickDefinition->getLayoutDefinitions();
                        $context = [
                            'containerType' => 'objectbrick',
                            'containerKey' => $key,
                            'outerFieldname' => $fieldName,
                        ];
                        DataObject\Service::enrichLayoutDefinition($brickLayoutDefinitions, null, $context);

                        $result[$key]['nodeLabel'] = $key;
                        $result[$key]['brickField'] = $fieldName;
                        $result[$key]['nodeType'] = 'objectbricks';
                        $result[$key]['childs'] = $brickLayoutDefinitions->getChildren();
                        break;
                    }
                }
            }
        }

        return $this->adminJson($result);
    }

    /**
     * OBJECT BRICKS
     */

    /**
     * @Route("/objectbrick-get", name="pimcore_admin_dataobject_class_objectbrickget", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function objectbrickGetAction(Request $request)
    {
        $fc = DataObject\Objectbrick\Definition::getByKey($request->get('id'));

        return $this->adminJson($fc);
    }

    /**
     * @Route("/objectbrick-update", name="pimcore_admin_dataobject_class_objectbrickupdate", methods={"PUT", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function objectbrickUpdateAction(Request $request)
    {
        try {
            $key = $request->get('key');
            $title = $request->get('title');
            $group = $request->get('group');

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
            $brickDef = new DataObject\Objectbrick\Definition();
            $brickDef->setKey($key);
            $brickDef->setTitle($title);
            $brickDef->setGroup($group);

            if ($request->get('values')) {
                $values = $this->decodeJson($request->get('values'));

                $brickDef->setParentClass($values['parentClass']);
                $brickDef->setImplementsInterfaces($values['implementsInterfaces']);
                $brickDef->setClassDefinitions($values['classDefinitions']);
                $brickDef->setGenerateTypeDeclarations($values['generateTypeDeclarations']);
            }

            if ($request->get('configuration')) {
                $configuration = $this->decodeJson($request->get('configuration'));

                $configuration['datatype'] = 'layout';
                $configuration['fieldtype'] = 'panel';

                $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);
                $brickDef->setLayoutDefinitions($layout);
            }

            $event = new GenericEvent($this, [
                'brickDefinition' => $brickDef,
            ]);
            \Pimcore::getEventDispatcher()->dispatch(AdminEvents::CLASS_OBJECTBRICK_UPDATE_DEFINITION, $event);
            $brickDef = $event->getArgument('brickDefinition');

            $brickDef->save();

            return $this->adminJson(['success' => true, 'id' => $brickDef->getKey()]);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());

            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/import-objectbrick", name="pimcore_admin_dataobject_class_importobjectbrick", methods={"POST"})
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

        $response = $this->adminJson([
            'success' => $success,
        ]);

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/export-objectbrick", name="pimcore_admin_dataobject_class_exportobjectbrick", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportObjectbrickAction(Request $request)
    {
        $objectBrick = DataObject\Objectbrick\Definition::getByKey($request->get('id'));

        if (!$objectBrick instanceof DataObject\Objectbrick\Definition) {
            $errorMessage = ': Object-Brick with id [ ' . $request->get('id') . ' not found. ]';
            Logger::error($errorMessage);
            throw $this->createNotFoundException($errorMessage);
        }

        $xml = DataObject\ClassDefinition\Service::generateObjectBrickJson($objectBrick);
        $response = new Response($xml);
        $response->headers->set('Content-type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="objectbrick_' . $objectBrick->getKey() . '_export.json"');

        return $response;
    }

    /**
     * @Route("/objectbrick-delete", name="pimcore_admin_dataobject_class_objectbrickdelete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function objectbrickDeleteAction(Request $request)
    {
        $fc = DataObject\Objectbrick\Definition::getByKey($request->get('id'));
        $fc->delete();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/objectbrick-tree", name="pimcore_admin_dataobject_class_objectbricktree", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function objectbrickTreeAction(Request $request)
    {
        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();

        $forObjectEditor = $request->get('forObjectEditor');

        $layoutDefinitions = [];
        $groups = [];
        $definitions = [];
        $fieldname = null;
        $className = null;

        $object = DataObject\AbstractObject::getById($request->get('object_id'));

        if ($request->query->has('class_id') && $request->query->has('field_name')) {
            $classId = $request->get('class_id');
            $fieldname = $request->get('field_name');
            $classDefinition = DataObject\ClassDefinition::getById($classId);
            $className = $classDefinition->getName();
        }

        /** @var DataObject\Objectbrick\Definition $item */
        foreach ($list as $item) {
            if ($request->query->has('class_id') && $request->query->has('field_name')) {
                $keep = false;
                $clsDefs = $item->getClassDefinitions();
                if (!empty($clsDefs)) {
                    foreach ($clsDefs as $cd) {
                        if ($cd['classname'] == $className && $cd['fieldname'] == $fieldname) {
                            $keep = true;
                            continue;
                        }
                    }
                }
                if (!$keep) {
                    continue;
                }
            }

            if ($item->getGroup()) {
                if (!isset($groups[$item->getGroup()])) {
                    $groups[$item->getGroup()] = [
                        'id' => 'group_' . $item->getKey(),
                        'text' => $item->getGroup(),
                        'expandable' => true,
                        'leaf' => false,
                        'allowChildren' => true,
                        'iconCls' => 'pimcore_icon_folder',
                        'group' => $item->getGroup(),
                        'children' => [],
                    ];
                }
                if ($forObjectEditor) {
                    $itemLayoutDefinitions = $item->getLayoutDefinitions();
                    DataObject\Service::enrichLayoutDefinition($itemLayoutDefinitions, $object);

                    $layoutDefinitions[$item->getKey()] = $itemLayoutDefinitions;
                }
                $groups[$item->getGroup()]['children'][] =
                    [
                        'id' => $item->getKey(),
                        'text' => $item->getKey(),
                        'title' => $item->getTitle(),
                        'key' => $item->getKey(),
                        'leaf' => true,
                        'iconCls' => 'pimcore_icon_objectbricks',
                    ];
            } else {
                if ($forObjectEditor) {
                    $layout = $item->getLayoutDefinitions();

                    $currentLayoutId = $request->get('layoutId', null);

                    $user = $this->getAdminUser();
                    if ($currentLayoutId == -1 && $user->isAdmin()) {
                        DataObject\Service::createSuperLayout($layout);
                        $objectData['layout'] = $layout;
                    }

                    $context = [
                        'containerType' => 'objectbrick',
                        'containerKey' => $item->getKey(),
                        'outerFieldname' => $request->get('field_name'),
                    ];

                    DataObject\Service::enrichLayoutDefinition($layout, $object, $context);

                    $layoutDefinitions[$item->getKey()] = $layout;
                }
                $definitions[] = [
                    'id' => $item->getKey(),
                    'text' => $item->getKey(),
                    'title' => $item->getTitle(),
                    'key' => $item->getKey(),
                    'leaf' => true,
                    'iconCls' => 'pimcore_icon_objectbricks',
                ];
            }
        }

        foreach ($groups as $group) {
            $definitions[] = $group;
        }

        $event = new GenericEvent($this, [
            'list' => $definitions,
            'objectId' => $request->get('object_id'),
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::CLASS_OBJECTBRICK_LIST_PRE_SEND_DATA, $event);
        $definitions = $event->getArgument('list');

        if ($forObjectEditor) {
            return $this->adminJson(['objectbricks' => $definitions, 'layoutDefinitions' => $layoutDefinitions]);
        } else {
            return $this->adminJson($definitions);
        }
    }

    /**
     * @Route("/objectbrick-list", name="pimcore_admin_dataobject_class_objectbricklist", methods={"GET"})
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

                $user = $this->getAdminUser();
                if ($currentLayoutId == -1 && $user->isAdmin()) {
                    DataObject\Service::createSuperLayout($layout);
                    $objectData['layout'] = $layout;
                }

                $context = [
                    'containerType' => 'objectbrick',
                    'containerKey' => $type->getKey(),
                    'outerFieldname' => $request->get('field_name'),
                ];

                $object = DataObject\AbstractObject::getById($request->get('object_id'));

                DataObject\Service::enrichLayoutDefinition($layout, $object, $context);
                $type->setLayoutDefinitions($layout);
            }

            $list = $filteredList;
        }

        $event = new GenericEvent($this, [
            'list' => $list,
            'objectId' => $request->get('object_id'),
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::CLASS_OBJECTBRICK_LIST_PRE_SEND_DATA, $event);
        $list = $event->getArgument('list');

        return $this->adminJson(['objectbricks' => $list]);
    }

    /**
     * See http://www.pimcore.org/issues/browse/PIMCORE-2358
     * Add option to export/import all class definitions/brick definitions etc. at once
     */

    /**
     * @Route("/bulk-import", name="pimcore_admin_dataobject_class_bulkimport", methods={"POST"})
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

        $tmpName = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/bulk-import-' . uniqid() . '.tmp';
        file_put_contents($tmpName, $json);

        Session::useSession(function (AttributeBagInterface $session) use ($tmpName) {
            $session->set('class_bulk_import_file', $tmpName);
        }, 'pimcore_objects');

        $json = json_decode($json, true);

        foreach ($json as $groupName => $group) {
            foreach ($group as $groupItem) {
                $displayName = null;
                $icon = null;

                if ($groupName == 'class') {
                    $name = $groupItem['name'];
                    $icon = 'class';
                } elseif ($groupName == 'customlayout') {
                    $className = $groupItem['className'];

                    $layoutData = ['className' => $className, 'name' => $groupItem['name']];
                    $name = base64_encode(json_encode($layoutData));
                    $displayName = $className . ' / ' . $groupItem['name'];
                    $icon = 'custom_views';
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

        $response = $this->adminJson(['success' => true, 'data' => $result]);
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * See http://www.pimcore.org/issues/browse/PIMCORE-2358
     * Add option to export/import all class definitions/brick definitions etc. at once
     */

    /**
     * @Route("/bulk-commit", name="pimcore_admin_dataobject_class_bulkcommit", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function bulkCommitAction(Request $request)
    {
        $data = json_decode($request->get('data'), true);

        $session = Session::get('pimcore_objects');
        $filename = $session->get('class_bulk_import_file');
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

            if ($type == 'class' && $item['name'] == $name) {
                $class = DataObject\ClassDefinition::getByName($name);
                if (!$class) {
                    $class = new DataObject\ClassDefinition();
                    $class->setName($name);
                }
                $success = DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, json_encode($item), true);

                return $this->adminJson(['success' => $success !== false]);
            } elseif ($type == 'objectbrick' && $item['key'] == $name) {
                if (!$brick = DataObject\Objectbrick\Definition::getByKey($name)) {
                    $brick = new DataObject\Objectbrick\Definition();
                    $brick->setKey($name);
                }

                $success = DataObject\ClassDefinition\Service::importObjectBrickFromJson($brick, json_encode($item), true);

                return $this->adminJson(['success' => $success !== false]);
            } elseif ($type == 'fieldcollection' && $item['key'] == $name) {
                if (!$fieldCollection = DataObject\Fieldcollection\Definition::getByKey($name)) {
                    $fieldCollection = new DataObject\Fieldcollection\Definition();
                    $fieldCollection->setKey($name);
                }

                $success = DataObject\ClassDefinition\Service::importFieldCollectionFromJson($fieldCollection, json_encode($item), true);

                return $this->adminJson(['success' => $success !== false]);
            } elseif ($type == 'customlayout') {
                $layoutData = json_decode(base64_decode($data['name']), true);
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

                        return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                    }
                }
            }
        }

        return $this->adminJson(['success' => true]);
    }

    /**
     * See http://www.pimcore.org/issues/browse/PIMCORE-2358
     * Add option to export/import all class definitions/brick definitions etc. at once
     */

    /**
     * @Route("/bulk-export-prepare", name="pimcore_admin_dataobject_class_bulkexportprepare", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function bulkExportPrepareAction(Request $request)
    {
        $data = $request->get('data');

        Session::useSession(function (AttributeBagInterface $session) use ($data) {
            $session->set('class_bulk_export_settings', $data);
        }, 'pimcore_objects');

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/bulk-export", name="pimcore_admin_dataobject_class_bulkexport", methods={"GET"})
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
            $result[] = [
                'icon' => 'fieldcollection',
                'checked' => true,
                'type' => 'fieldcollection',
                'name' => $fieldCollection->getKey(),
                'displayName' => $fieldCollection->getKey(),
            ];
        }

        $classes = new DataObject\ClassDefinition\Listing();
        $classes->setOrder('ASC');
        $classes->setOrderKey('id');
        $classes = $classes->load();

        foreach ($classes as $class) {
            $result[] = [
                'icon' => 'class',
                'checked' => true,
                'type' => 'class',
                'name' => $class->getName(),
                'displayName' => $class->getName(),
            ];
        }

        $objectBricks = new DataObject\Objectbrick\Definition\Listing();
        $objectBricks = $objectBricks->load();

        foreach ($objectBricks as $objectBrick) {
            $result[] = [
                'icon' => 'objectbricks',
                'checked' => true,
                'type' => 'objectbrick',
                'name' => $objectBrick->getKey(),
                'displayName' => $objectBrick->getKey(),
            ];
        }

        $customLayouts = new DataObject\ClassDefinition\CustomLayout\Listing();
        $customLayouts = $customLayouts->load();
        foreach ($customLayouts as $customLayout) {
            $class = DataObject\ClassDefinition::getById($customLayout->getClassId());
            $displayName = $class->getName() . ' / ' .  $customLayout->getName();

            $result[] = [
                'icon' => 'custom_views',
                'checked' => true,
                'type' => 'customlayout',
                'name' => $customLayout->getId(),
                'displayName' => $displayName,
            ];
        }

        return new JsonResponse(['success' => true, 'data' => $result]);
    }

    /**
     * @Route("/do-bulk-export", name="pimcore_admin_dataobject_class_dobulkexport", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function doBulkExportAction(Request $request)
    {
        $session = Session::get('pimcore_objects');
        $list = $session->get('class_bulk_export_settings');
        $list = json_decode($list, true);
        $result = [];

        foreach ($list as $item) {
            if ($item['type'] == 'fieldcollection') {
                $fieldCollection = DataObject\Fieldcollection\Definition::getByKey($item['name']);
                $key = $fieldCollection->getKey();
                $fieldCollectionJson = json_decode(DataObject\ClassDefinition\Service::generateFieldCollectionJson($fieldCollection));
                $fieldCollectionJson->key = $key;
                $result['fieldcollection'][] = $fieldCollectionJson;
            } elseif ($item['type'] == 'class') {
                $class = DataObject\ClassDefinition::getByName($item['name']);
                $data = json_decode(json_encode($class));
                unset($data->fieldDefinitions);
                $result['class'][] = $data;
            } elseif ($item['type'] == 'objectbrick') {
                $objectBrick = DataObject\Objectbrick\Definition::getByKey($item['name']);
                $key = $objectBrick->getKey();
                $objectBrickJson = json_decode(DataObject\ClassDefinition\Service::generateObjectBrickJson($objectBrick));
                $objectBrickJson->key = $key;
                $result['objectbrick'][] = $objectBrickJson;
            } elseif ($item['type'] == 'customlayout') {
                /** @var DataObject\ClassDefinition\CustomLayout $customLayout */
                $customLayout = DataObject\ClassDefinition\CustomLayout::getById($item['name']);
                $classId = $customLayout->getClassId();
                $class = DataObject\ClassDefinition::getById($classId);
                $customLayout = $customLayout->getObjectVars();
                $customLayout['className'] = $class->getName();
                $result['customlayout'][] = $customLayout;
            }
        }

        $result = json_encode($result, JSON_PRETTY_PRINT);
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
            'getClassDefinitionForColumnConfigAction', 'objectbrickListAction', 'objectbrickTreeAction', 'objectbrickGetAction',
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

    /**
     * @Route("/get-fieldcollection-usages", name="pimcore_admin_dataobject_class_getfieldcollectionusages", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getFieldcollectionUsagesAction(Request $request)
    {
        $key = $request->get('key');
        $result = [];

        $classes = new DataObject\ClassDefinition\Listing();
        $classes = $classes->load();
        foreach ($classes as $class) {
            $fieldDefs = $class->getFieldDefinitions();
            foreach ($fieldDefs as $fieldDef) {
                if ($fieldDef instanceof DataObject\ClassDefinition\Data\Fieldcollections) {
                    $allowedKeys = $fieldDef->getAllowedTypes();
                    if (is_array($allowedKeys) && in_array($key, $allowedKeys)) {
                        $result[] = [
                            'class' => $class->getName(),
                            'field' => $fieldDef->getName(),
                        ];
                    }
                }
            }
        }

        return $this->adminJson($result);
    }

    /**
     * @Route("/get-bricks-usages", name="pimcore_admin_dataobject_class_getbrickusages", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getBrickUsagesAction(Request $request)
    {
        $classId = $request->get('classId');
        $myclass = DataObject\ClassDefinition::getById($classId);

        $result = [];

        $brickDefinitions = new DataObject\Objectbrick\Definition\Listing();
        $brickDefinitions = $brickDefinitions->load();
        foreach ($brickDefinitions as $brickDefinition) {
            $classes = $brickDefinition->getClassDefinitions();
            foreach ($classes as $class) {
                if ($myclass->getName() == $class['classname']) {
                    $result[] = [
                        'objectbrick' => $brickDefinition->getKey(),
                        'field' => $class['fieldname'],
                    ];
                }
            }
        }

        return $this->adminJson($result);
    }

    /**
     * @Route("/get-icons", name="pimcore_admin_dataobject_class_geticons", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getIconsAction(Request $request)
    {
        $classId = $request->get('classId');

        $iconDir = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img';
        $classIcons = rscandir($iconDir . '/object-icons/');
        $colorIcons = rscandir($iconDir . '/flat-color-icons/');
        $twemoji = rscandir($iconDir . '/twemoji/');

        $icons = array_merge($classIcons, $colorIcons, $twemoji);

        foreach ($icons as &$icon) {
            $icon = str_replace(PIMCORE_WEB_ROOT, '', $icon);
        }

        $event = new GenericEvent($this, [
            'icons' => $icons,
            'classId' => $classId,
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::CLASS_OBJECT_ICONS_PRE_SEND_DATA, $event);
        $icons = $event->getArgument('icons');

        $result = [];
        foreach ($icons as $icon) {
            $result[] = [
                'text' => "<img src='{$icon}'>",
                'value' => $icon,
            ];
        }

        return $this->adminJson($result);
    }

    /**
     * @Route("/suggest-class-identifier", name="pimcore_admin_dataobject_class_suggestclassidentifier")
     *
     * @return Response
     */
    public function suggestClassIdentifierAction()
    {
        $db = Db::get();
        $maxId = $db->fetchOne('SELECT MAX(CAST(id AS SIGNED)) FROM classes;');

        $existingIds = $db->fetchCol('select LOWER(id) from classes');

        $result = [
            'suggestedIdentifier' => $maxId ? $maxId + 1 : 1,
            'existingIds' => $existingIds,
            ];

        return $this->adminJson($result);
    }

    /**
     * @Route("/suggest-custom-layout-identifier", name="pimcore_admin_dataobject_class_suggestcustomlayoutidentifier")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function suggestCustomLayoutIdentifierAction(Request $request)
    {
        $classId = $request->get('classId');

        $identifier = DataObject\ClassDefinition\CustomLayout::getIdentifier($classId);

        $list = new DataObject\ClassDefinition\CustomLayout\Listing();

        $list = $list->load();
        $existingIds = [];
        $existingNames = [];

        /** @var DataObject\ClassDefinition\CustomLayout $item */
        foreach ($list as $item) {
            $existingIds[] = $item->getId();
            if ($item->getClassId() == $classId) {
                $existingNames[] = $item->getName();
            }
        }

        $result = [
            'suggestedIdentifier' => $identifier,
            'existingIds' => $existingIds,
            'existingNames' => $existingNames,
            ];

        return $this->adminJson($result);
    }
}
