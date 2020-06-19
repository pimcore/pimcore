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

namespace Pimcore\Bundle\AdminBundle\Controller\Rest;

use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Event\Webservice\FilterEvent;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @deprecated
 */
class ClassController extends AbstractRestController
{
    /**
     * @Route("/class/id/{id}", name="pimcore_api_rest_class_class", methods={"GET"})
     *
     * end point for the class definition
     *
     *  GET http://[YOUR-DOMAIN]/webservice/rest/class/id/1281?apikey=[API-KEY]
     *      returns the class definition for the given class
     *
     * @param string $id
     *
     * @return JsonResponse
     *
     * @throws ResponseException
     */
    public function classAction($id)
    {
        $this->checkPermission('classes');

        $e = null;

        try {
            $class = $this->service->getClassById($id);

            return $this->createSuccessResponse($class);
        } catch (\Exception $e) {
            $this->getLogger()->error($e);
        }

        throw $this->createNotFoundResponseException(sprintf('Class %s does not exist', $id), $e);
    }

    /**
     * @Route("/classes", name="pimcore_api_rest_class_classes", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function classesAction()
    {
        $this->checkPermission('classes');

        $list = new DataObject\ClassDefinition\Listing();
        $classes = $list->load();

        $result = [];

        foreach ($classes as $class) {
            $item = [
                'id' => $class->getId(),
                'name' => $class->getName(),
            ];

            $result[] = $item;
        }

        return $this->createCollectionSuccessResponse($result);
    }

    /**
     * @Route("/object-brick/id/{id}", name="pimcore_api_rest_class_objectbrick", methods={"GET"})
     *
     * end point for the object-brick definition
     *
     *  GET http://[YOUR-DOMAIN]/webservice/rest/object-brick/id/abt1?apikey=[API-KEY]
     *      returns the class definition for the given class
     *
     * @param string $id
     *
     * @return JsonResponse
     *
     * @throws ResponseException
     */
    public function objectBrickAction($id)
    {
        $this->checkPermission('classes');

        $e = null;

        if ($definition = DataObject\Objectbrick\Definition::getByKey($id)) {
            return $this->createSuccessResponse($definition);
        } else {
            $this->getLogger()->error($e);
        }

        throw $this->createNotFoundResponseException($e ? $e->getMessage() : null, $e);
    }

    /**
     * @Route("/object-bricks", name="pimcore_api_rest_class_objectbricks", methods={"GET"})
     *
     * Returns a list of all object brick definitions.
     */
    public function objectBricksAction()
    {
        $this->checkPermission('classes');

        $list = new DataObject\Objectbrick\Definition\Listing();

        /** @var DataObject\Objectbrick\Definition[] $bricks */
        $bricks = $list->load();

        $result = [];

        foreach ($bricks as $brick) {
            $item = [
                'name' => $brick->getKey(),
            ];

            $result[] = $item;
        }

        return $this->createCollectionSuccessResponse($result);
    }

    /**
     * @Route("/field-collection/id/{id}", name="pimcore_api_rest_class_fieldcollection", methods={"GET"})
     *
     * end point for the field collection definition
     *
     *  GET http://[YOUR-DOMAIN]/webservice/rest/field-collection/id/abt1?apikey=[API-KEY]
     *      returns the class definition for the given class
     *
     * @param string $id
     *
     * @return JsonResponse
     *
     * @throws ResponseException
     */
    public function fieldCollectionAction($id)
    {
        $this->checkPermission('classes');

        $e = null;

        if ($definition = DataObject\Fieldcollection\Definition::getByKey($id)) {
            return $this->createSuccessResponse($definition);
        } else {
            $this->getLogger()->error($e);
        }

        throw $this->createNotFoundResponseException($e ? $e->getMessage() : null, $e);
    }

    /**
     * @Route("/field-collections", name="pimcore_api_rest_class_fieldcollections", methods={"GET"})
     *
     * Returns a list of all field collection definitions.
     */
    public function fieldCollectionsAction()
    {
        $this->checkPermission('classes');

        $list = new DataObject\Fieldcollection\Definition\Listing();

        /** @var DataObject\Fieldcollection\Definition[] $fieldCollections */
        $fieldCollections = $list->load();

        $result = [];

        foreach ($fieldCollections as $fc) {
            $item = [
                'name' => $fc->getKey(),
            ];

            $result[] = $item;
        }

        return $this->createCollectionSuccessResponse($result);
    }

    /**
     * @Route("/quantity-value-unit-definition", name="pimcore_api_rest_class_quantityvalueunitdefinition", methods={"GET"})
     *
     * Returns the classification store feature definition as JSON. Could be useful to provide separate endpoints
     * for the various sub-configs.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function quantityValueUnitDefinitionAction(Request $request)
    {
        $this->checkPermission('classes');

        $condition = $this->buildCondition($request);
        $this->checkCondition($condition);

        $eventData = new FilterEvent($request, 'class', 'quantityValueUnitDefinition', $condition);
        $this->dispatchBeforeLoadEvent($request, $eventData);
        $condition = $eventData->getCondition();

        $list = new DataObject\QuantityValue\Unit\Listing();
        if ($condition) {
            $list->setCondition($condition);
        }

        $items = $list->load();
        $units = [];

        /** @var DataObject\QuantityValue\Unit $item */
        foreach ($items as $item) {
            $units[] = $item->getObjectVars();
        }

        return $this->createCollectionSuccessResponse($units);
    }

    /**
     * @Route("/classificationstore-definition", name="pimcore_api_rest_class_classificationstoredefinition", methods={"GET"})
     *
     * Returns the classification store feature definition as JSON. Could be useful to provide separate endpoints
     * for the various sub-configs.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function classificationstoreDefinitionAction(Request $request)
    {
        $this->checkPermission('classes');

        $condition = $this->buildCondition($request);

        $eventData = new FilterEvent($request, 'class', 'classificationstoreDefinition', $condition);
        $this->dispatchBeforeLoadEvent($request, $eventData);
        $condition = $eventData->getCondition();

        $this->checkCondition($condition);

        $definition = [];

        $list = new DataObject\Classificationstore\StoreConfig\Listing();
        if ($condition) {
            $list->setCondition($condition);
        }

        $list->load();
        $items = $list->getList();

        $stores = [];
        foreach ($items as $item) {
            $stores[] = $item->getObjectVars();
        }
        $definition['stores'] = $stores;

        $list = new DataObject\Classificationstore\CollectionConfig\Listing();
        if ($condition) {
            $list->setCondition($condition);
        }

        $list->load();
        $items = $list->getList();

        $collections = [];
        foreach ($items as $item) {
            $collections[] = $item->getObjectVars();
        }
        $definition['collections'] = $collections;

        $list = new DataObject\Classificationstore\GroupConfig\Listing();
        if ($condition) {
            $list->setCondition($condition);
        }
        $list->load();
        $items = $list->getList();

        $groups = [];
        foreach ($items as $item) {
            $groups[] = $item->getObjectVars();
        }
        $definition['groups'] = $groups;

        $list = new DataObject\Classificationstore\KeyConfig\Listing();
        if ($condition) {
            $list->setCondition($condition);
        }
        $list->load();
        $items = $list->getList();

        $keys = [];
        foreach ($items as $item) {
            $keys[] = $item->getObjectVars();
        }
        $definition['keys'] = $keys;

        $list = new DataObject\Classificationstore\CollectionGroupRelation\Listing();
        if ($condition) {
            $list->setCondition($condition);
        }
        $list->load();
        $items = $list->getList();

        $relations = [];
        /** @var DataObject\Classificationstore\CollectionGroupRelation $item */
        foreach ($items as $item) {
            $relations[] = $item->getObjectVars();
        }
        $definition['collections2groups'] = $relations;

        $list = new DataObject\Classificationstore\KeyGroupRelation\Listing();
        if ($condition) {
            $list->setCondition($condition);
        }
        $list->load();
        $items = $list->getList();

        $relations = [];

        foreach ($items as $item) {
            $relations[] = $item->getObjectVars();
        }
        $definition['groups2keys'] = $relations;

        return $this->createSuccessResponse($definition);
    }
}
