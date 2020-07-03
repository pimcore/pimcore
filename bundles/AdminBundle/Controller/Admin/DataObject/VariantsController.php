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
use Pimcore\Bundle\AdminBundle\Helper\GridHelperService;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/variants")
 */
class VariantsController extends AdminController
{
    /**
     * @Route("/update-key", name="pimcore_admin_dataobject_variants_updatekey", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateKeyAction(Request $request)
    {
        $id = $request->get('id');
        $key = $request->get('key');
        $object = DataObject\Concrete::getById($id);

        try {
            if (!empty($object)) {
                $object->setKey($key);
                $object->save();

                return $this->adminJson(['success' => true]);
            } else {
                throw new \Exception('No Object found for given id.');
            }
        } catch (\Exception $e) {
            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/get-variants", name="pimcore_admin_dataobject_variants_getvariants", methods={"GET", "POST"})
     *
     * @param Request $request
     * @param GridHelperService $gridHelperService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getVariantsAction(Request $request, GridHelperService $gridHelperService)
    {
        // get list of variants

        if ($request->get('language')) {
            $request->setLocale($request->get('language'));
        }

        if ($request->get('xaction') == 'update') {
            $data = $this->decodeJson($request->get('data'));

            // save
            $object = DataObject\Concrete::getById($data['id']);

            if ($object->isAllowed('publish')) {
                $objectData = [];
                foreach ($data as $key => $value) {
                    $parts = explode('~', $key);

                    if (count($parts) > 1) {
                        $brickType = $parts[0];
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
                        $brick->$valueSetter($value);
                    } else {
                        $objectData[$key] = $value;
                    }
                }

                $object->setValues($objectData);

                try {
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

                $list->setObjectTypes([DataObject\AbstractObject::OBJECT_TYPE_VARIANT]);

                $list->load();

                $objects = [];
                foreach ($list->getObjects() as $object) {
                    if ($object->isAllowed('view')) {
                        $o = DataObject\Service::gridObjectData($object, $request->get('fields'));
                        $objects[] = $o;
                    }
                }

                return $this->adminJson(['data' => $objects, 'success' => true, 'total' => $list->getTotalCount()]);
            } else {
                throw new \Exception('Permission denied');
            }
        }
    }
}
