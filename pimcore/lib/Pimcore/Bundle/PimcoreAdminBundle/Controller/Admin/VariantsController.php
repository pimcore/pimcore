<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Model\Object;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/variants")
 */
class VariantsController extends AdminController
{
    /**
     * @Route("/update-key")
     * @param Request $request
     * @return JsonResponse
     */
    public function updateKeyAction(Request $request)
    {
        $id = $request->get("id");
        $key = $request->get("key");
        $object = Object\Concrete::getById($id);

        try {
            if (!empty($object)) {
                $object->setKey($key);
                $object->save();
                return $this->json(["success" => true]);
            } else {
                throw new \Exception("No Object found for given id.");
            }
        } catch (\Exception $e) {
            return $this->json(["success" => false, "message" => $e->getMessage()]);
        }
    }

    /**
     * @Route("/get-variants")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function getVariantsAction(Request $request)
    {
        // get list of variants

        if ($request->get("language")) {
            $request->setLocale($request->get("language"));
        }

        if ($request->get("xaction") == "update") {
            $data = $this->decodeJson($request->get("data"));

            // save
            $object = Object::getById($data["id"]);

            if ($object->isAllowed("publish")) {
                $objectData = [];
                foreach ($data as $key => $value) {
                    $parts = explode("~", $key);
                    if (substr($key, 0, 1) == "~") {
                        $type = $parts[1];
                        $field = $parts[2];
                        $keyid = $parts[3];

                        $getter = "get" . ucfirst($field);
                        $setter = "set" . ucfirst($field);
                        $keyValuePairs = $object->$getter();

                        if (!$keyValuePairs) {
                            $keyValuePairs = new Object\Data\KeyValue();
                            $keyValuePairs->setObjectId($object->getId());
                            $keyValuePairs->setClass($object->getClass());
                        }

                        $keyValuePairs->setPropertyWithId($keyid, $value, true);
                        $object->$setter($keyValuePairs);
                    } elseif (count($parts) > 1) {
                        $brickType = $parts[0];
                        $brickKey = $parts[1];
                        $brickField = Object\Service::getFieldForBrickType($object->getClass(), $brickType);

                        $fieldGetter = "get" . ucfirst($brickField);
                        $brickGetter = "get" . ucfirst($brickType);
                        $valueSetter = "set" . ucfirst($brickKey);

                        $brick = $object->$fieldGetter()->$brickGetter();
                        if (empty($brick)) {
                            $classname = "\\Pimcore\\Model\\Object\\Objectbrick\\Data\\" . ucfirst($brickType);
                            $brickSetter = "set" . ucfirst($brickType);
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
                    return $this->json(["data" => Object\Service::gridObjectData($object, $request->get("fields")), "success" => true]);
                } catch (\Exception $e) {
                    return $this->json(["success" => false, "message" => $e->getMessage()]);
                }
            } else {
                throw new \Exception("Permission denied");
            }
        } else {
            $parentObject = Object\Concrete::getById($request->get("objectId"));

            if (empty($parentObject)) {
                throw new \Exception("No Object found with id " . $request->get("objectId"));
            }

            if ($parentObject->isAllowed("view")) {
                $class = $parentObject->getClass();
                $className = $parentObject->getClass()->getName();

                $start = 0;
                $limit = 15;
                $orderKey = "o_id";
                $order = "ASC";

                $fields = [];
                $bricks = [];
                if ($request->get("fields")) {
                    $fields = $request->get("fields");

                    foreach ($fields as $f) {
                        $parts = explode("~", $f);
                        if (count($parts) > 1) {
                            $bricks[$parts[0]] = $parts[0];
                        }
                    }
                }

                if ($request->get("limit")) {
                    $limit = $request->get("limit");
                }
                if ($request->get("start")) {
                    $start = $request->get("start");
                }

                $orderKey = "o_id";
                $order = "ASC";

                $colMappings = [
                    "filename" => "o_key",
                    "fullpath" => ["o_path", "o_key"],
                    "id" => "o_id",
                    "published" => "o_published",
                    "modificationDate" => "o_modificationDate",
                    "creationDate" => "o_creationDate"
                ];

                $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($request->request->all());
                if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
                    $orderKey = $sortingSettings['orderKey'];
                    if (array_key_exists($orderKey, $colMappings)) {
                        $orderKey = $colMappings[$orderKey];
                    }
                    $order = $sortingSettings['order'];
                }

                if ($request->get("dir")) {
                    $order = $request->get("dir");
                }

                $listClass = "\\Pimcore\\Model\\Object\\" . ucfirst($className) . "\\Listing";

                $conditionFilters = ["o_parentId = " . $parentObject->getId()];
                // create filter condition
                if ($request->get("filter")) {
                    $conditionFilters[] =  Object\Service::getFilterCondition($request->get("filter"), $class);
                }
                if ($request->get("condition")) {
                    $conditionFilters[] = "(" . $request->get("condition") . ")";
                }

                $list = new $listClass();
                if (!empty($bricks)) {
                    foreach ($bricks as $b) {
                        $list->addObjectbrick($b);
                    }
                }
                $list->setCondition(implode(" AND ", $conditionFilters));
                $list->setLimit($limit);
                $list->setOffset($start);
                $list->setOrder($order);
                $list->setOrderKey($orderKey);
                $list->setObjectTypes([Object\AbstractObject::OBJECT_TYPE_VARIANT]);

                $list->load();

                $objects = [];
                foreach ($list->getObjects() as $object) {
                    if ($object->isAllowed("view")) {
                        $o = Object\Service::gridObjectData($object, $fields);
                        $objects[] = $o;
                    }
                }

                return $this->json(["data" => $objects, "success" => true, "total" => $list->getTotalCount()]);
            } else {
                throw new \Exception("Permission denied");
            }
        }
    }
}
