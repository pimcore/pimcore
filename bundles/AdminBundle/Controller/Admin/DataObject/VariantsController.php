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

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\Helper\GridHelperService;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/variants", name="pimcore_admin_dataobject_variants_")
 *
 * @internal
 */
class VariantsController extends AdminController
{
    use DataObjectActionsTrait;

    /**
     * @Route("/update-key", name="updatekey", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateKeyAction(Request $request): JsonResponse
    {
        $id = $request->get('id');
        $key = $request->get('key');
        $object = DataObject\Concrete::getById($id);

        $this->adminJson($this->renameObject($object, $key));
    }

    /**
     * @Route("/get-variants", name="getvariants", methods={"GET", "POST"})
     *
     * @param Request $request
     * @param GridHelperService $gridHelperService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getVariantsAction(Request $request, GridHelperService $gridHelperService): JsonResponse
    {
        // get list of variants
        $requestedLanguage = $allParams['language'] ?? $request->getLocale();
        if ($requestedLanguage !== 'default') {
            $request->setLocale($requestedLanguage);
        }

        if ($request->get('xaction') == 'update') {
            $data = $this->decodeJson($request->get('data'));

            // save
            $object = DataObject\Concrete::getById($data['id']);

            if ($object->isAllowed('publish')) {
                try {
                    $objectData = $this->prepareObjectData($data, $object); //new
                    $object->setValues($objectData);
                    $object->save();

                    return $this->adminJson(['data' => DataObject\Service::gridObjectData($object, $request->get('fields')), 'success' => true]);
                } catch (\Exception $e) {
                    return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                }
            } else {
                throw new \Exception('Permission denied');
            }
        } else {
            $parentObject = DataObject\Concrete::getById($request->get('objectId'));

            if (empty($parentObject)) {
                throw new \Exception('No Object found with id ' . $request->get('objectId'));
            }

            if ($parentObject->isAllowed('view')) {
                $allParams = array_merge($request->request->all(), $request->query->all());

                //specify a few special params
                $allParams['folderId'] = $parentObject->getId();
                $allParams['only_direct_children'] = 'true';
                $allParams['classId'] = $parentObject->getClassId();

                $list = $gridHelperService->prepareListingForGrid($allParams, $request->getLocale(), $this->getAdminUser());

                $list->setObjectTypes([DataObject::OBJECT_TYPE_VARIANT]);

                $list->load();

                $objects = [];
                foreach ($list->getObjects() as $object) {
                    if ($object->isAllowed('view')) {
                        $o = DataObject\Service::gridObjectData($object, $request->get('fields'), $requestedLanguage);
                        $objects[] = $o;
                    }
                }

                return $this->adminJson(['data' => $objects, 'success' => true, 'total' => $list->getTotalCount()]);
            } else {
                throw new \Exception('Permission denied');
            }
        }
    }

    /**
     * @param DataObject\ClassDefinition $class
     * @param string $key
     *
     * @return DataObject\ClassDefinition\Data|null
     */
    protected function getFieldDefinition($class, $key): ?DataObject\ClassDefinition\Data
    {
        $fieldDefinition = $class->getFieldDefinition($key);
        if ($fieldDefinition) {
            return $fieldDefinition;
        }

        $localized = $class->getFieldDefinition('localizedfields');
        if ($localized instanceof DataObject\ClassDefinition\Data\Localizedfields) {
            $fieldDefinition = $localized->getFieldDefinition($key);
        }

        return $fieldDefinition;
    }

    /**
     * @param string $brickType
     * @param string $key
     *
     * @return DataObject\ClassDefinition\Data|null
     */
    protected function getFieldDefinitionFromBrick($brickType, $key): ?DataObject\ClassDefinition\Data
    {
        $brickDefinition = DataObject\Objectbrick\Definition::getByKey($brickType);
        $fieldDefinition = null;
        if ($brickDefinition) {
            $fieldDefinition = $brickDefinition->getFieldDefinition($key);
        }

        return $fieldDefinition;
    }
}
