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

namespace Pimcore\Bundle\AdminBundle\Controller\Rest\Element;

use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\DataObject;
use Pimcore\Model\Webservice\Data\DataObject\Concrete\In as WebserviceObjectIn;
use Pimcore\Model\Webservice\Data\DataObject\Concrete\Out as WebserviceObjectOut;
use Pimcore\Model\Webservice\Data\DataObject\Folder\In as WebserviceFolderIn;
use Pimcore\Model\Webservice\Data\DataObject\Folder\Out as WebserviceFolderOut;
use Pimcore\Tool;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * end point for object related data.
 *
 * - get object by id
 *      GET http://[YOUR-DOMAIN]/webservice/rest/object/id/1281?apikey=[API-KEY]
 *      returns json-encoded object data.
 * - delete object by id
 *      DELETE http://[YOUR-DOMAIN]/webservice/rest/object/id/1281?apikey=[API-KEY]
 *      returns json encoded success value
 * - create object
 *      PUT or POST http://[YOUR-DOMAIN]/webservice/rest/object?apikey=[API-KEY]
 *      body: json-encoded object data in the same format as returned by get object by id
 *              but with missing id field or id set to 0
 *      returns json encoded object id
 * - update object
 *      PUT or POST http://[YOUR-DOMAIN]/webservice/rest/object/id/1281?apikey=[API-KEY]
 *      body: same as for create object. object id can be either in URI or as request payload
 *      returns json encoded success value
 */
class ObjectController extends AbstractElementController
{
    /**
     * @Method("GET")
     * @Route("/object/id/{id}", requirements={"id": "\d+"})
     * @Route("/object")
     *
     * @api {get} /object Get object data
     * @apiName Get object by id
     * @apiGroup Object
     * @apiSampleRequest off
     * @apiParam {int} id an object id
     * @apiParam {string} apikey your access token
     * @apiParamExample {json} Request-Example:
     *     {
     *         "id": 1,
     *         "apikey": "21314njdsfn1342134"
     *      }
     * @apiSuccess {json} success parameter of the returned data = true
     * @apiError {json} success parameter of the returned data = false
     * @apiErrorExample {json} Error-Response:
     *                  {"success":false, "msg":"exception 'Exception' with message '....'"}
     * @apiSuccessExample {json} Success-Response:
     *                    HTTP/1.1 200 OK
     *                    {
     *                      "success": true
     *                      "data": {
     *                       "path": "/crm/inquiries/",
     *                       "creationDate": 1368630916,
     *                       "modificationDate": 1388409137,
     *                       "userModification": null,
     *                       "childs": null,
     *                       "elements": [
     *                       {
     *                           "type": "gender",
     *                           "value": "female",
     *                           "name": "gender",
     *                           "language": null
     *                      },
     *
     *                      ...
     *
     *                    }
     *
     * @param Request  $request
     * @param int|null $id
     *
     * @return JsonResponse
     *
     * @throws ResponseException
     */
    public function getAction(Request $request, $id = null)
    {
        $id = $this->resolveId($request, $id);

        $profile     = $request->get('profiling');
        $profileName = 'rest_object_get';

        /** @var Stopwatch $stopwatch */
        $stopwatch = null;
        if ($profile) {
            $stopwatch = $this->startProfiling();
            $stopwatch->start('get', $profileName);
        }

        $object = $this->loadObject($id);

        if ($profile) {
            $stopwatch->stop('get');
            $stopwatch->start('perm', $profileName);
        }

        $this->checkElementPermission($object, 'get');

        if ($profile) {
            $stopwatch->stop('perm');
            $stopwatch->start('ws', $profileName);
        }

        /** @var WebserviceObjectOut|WebserviceFolderOut $out */
        if ($object instanceof DataObject\Folder) {
            $out = $this->service->getObjectFolderById($id);
        } else {
            $out = $this->service->getObjectConcreteById($id);
        }

        if ($profile) {
            $stopwatch->stop('ws');
        }

        $data = $this->createSuccessData($out);

        if ($profile) {
            $data['profiling'] = $this->getProfilingData($profileName);
        }

        return $this->json($data);
    }

    /**
     * @Method({"POST", "PUT"})
     * @Route("/object")
     *
     * @api {post} /object Create a new object
     * @apiName Create a new object
     * @apiGroup Object
     * @apiSampleRequest off
     * @apiDescription
     * Request body: JSON-encoded object data in the same format as returned by get object by id for the data segment but with missing id field or id set to 0
     *
     * @apiParam {json} data a new object data
     * @apiParam {string} apikey your access token
     * @apiParamExample {json} Request-Example:
     *     {
     *         "apikey": "21314njdsfn1342134",
     *         "data": {
     *               "id": 61,
     *               "parentId": 48,
     *               "key": "test-product-key",
     *               "className": "product",
     *               "type": "object",
     *               "elements": [
     *                   {
     *                   "type": "input",
     *                   "value": "some identyfier",
     *                   "name": "identyfier",
     *                   "language": null
     *                   },
     *                   {
     *                   "type": "localizedfields",
     *                   "value": [
     *                   {
     *                   "type": "input",
     *                   "value": "Test",
     *                   "name": "name1",
     *                   "language": "en"
     *                   },
     *                   {
     *                   "type": "input",
     *                   "value": "1",
     *                   "name": "name2",
     *                   "language": "en"
     *                   },
     *                   {
     *                   "type": "input",
     *                   "value": null,
     *                   "name": "name1",
     *                   "language": "de"
     *                   },
     *                   {
     *                   "type": "input",
     *                   "value": "aaa",
     *                   "name": "name2",
     *                   "language": "de"
     *                   }
     *                   ],
     *                   "name": "localizedfields",
     *                   "language": null
     *                       }
     *               ]
     *           }
     *     }
     * @apiSuccess {json} success parameter of the returned data = true
     * @apiError {json} success parameter of the returned data = false
     * @apiErrorExample {json} Error-Response:
     *                  {"success":false, "msg":"exception 'Exception' with message '....'"}
     * @apiSuccessExample {json} Success-Response:
     *                    HTTP/1.1 200 OK
     *                    {
     *                      "success": true
     *                    }
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $data = $this->getJsonData($request);

        // get and normalize type
        $type = $data['type'] = isset($data['type']) ? $data['type'] : 'object';

        // add support for legacy behaviour, accepting the ID as payload parameter
        if (isset($data['id'])) {
            $id     = $data['id'];
            $object = $this->loadObject($id);

            return $this->updateObject($object, $type, $data);
        }

        return $this->createObject($type, $data);
    }

    /**
     * @Method({"POST", "PUT"})
     * @Route("/object/id/{id}", requirements={"id": "\d+"})
     *
     * @api {put} /object/id/{id} Update an object
     * @apiName Create a new object
     * @apiGroup Object
     * @apiSampleRequest off
     * @apiDescription
     * Request body: JSON-encoded object data in the same format as returned by get object by id for the data segment but with missing id field or id set to 0
     *
     * @apiParam {json} data a new object data
     * @apiParam {string} apikey your access token
     * @apiParamExample {json} Request-Example:
     *     {
     *         "apikey": "21314njdsfn1342134",
     *         "id": 66
     *         "data": {
     *               "parentId": 48,
     *               "key": "test-product-key",
     *               "className": "product",
     *               "type": "object",
     *               "elements": [
     *                   {
     *                   "type": "input",
     *                   "value": "some identyfier",
     *                   "name": "identyfier",
     *                   "language": null
     *                   },
     *                   {
     *                   "type": "localizedfields",
     *                   "value": [
     *                   {
     *                   "type": "input",
     *                   "value": "Test new",
     *                   "name": "name1",
     *                   "language": "en"
     *                   },
     *                   {
     *                   "type": "input",
     *                   "value": "1",
     *                   "name": "name2",
     *                   "language": "en"
     *                   },
     *                   {
     *                   "type": "input",
     *                   "value": null,
     *                   "name": "name1",
     *                   "language": "de"
     *                   },
     *                   {
     *                   "type": "input",
     *                   "value": "aaa",
     *                   "name": "name2",
     *                   "language": "de"
     *                   }
     *                   ],
     *                   "name": "localizedfields",
     *                   "language": null
     *                       }
     *               ]
     *           }
     *     }
     * @apiSuccess {json} success parameter of the returned data = true
     * @apiError {json} success parameter of the returned data = false
     * @apiErrorExample {json} Error-Response:
     *                  {"success":false, "msg":"exception 'Exception' with message '....'"}
     * @apiSuccessExample {json} Success-Response:
     *                    HTTP/1.1 200 OK
     *                    {
     *                      "success": true,
     *                      "id": 66
     *                    }
     *
     * @param Request  $request
     * @param int|null $id
     *
     * @return JsonResponse
     */
    public function updateAction(Request $request, $id)
    {
        $id   = $this->resolveId($request, $id);
        $data = $this->getJsonData($request);

        // get and normalize type
        $type = $data['type'] = isset($data['type']) ? $data['type'] : 'object';

        $object = $this->loadObject($id);

        return $this->updateObject($object, $type, $data);
    }

    /**
     * @Method("DELETE")
     * @Route("/object/id/{id}", requirements={"id": "\d+"})
     * @Route("/object")
     *
     * @api {delete} /object/id/{id} Delete object
     * @apiName Delete object
     * @apiGroup Object
     * @apiSampleRequest off
     * @apiParam {int} id an object id
     * @apiParam {string} apikey your access token
     * @apiParamExample {json} Request-Example:
     *     {
     *         "id": 1,
     *         "apikey": "21314njdsfn1342134"
     *     }
     * @apiSuccess {json} success parameter of the returned data = true
     * @apiError {json} success parameter of the returned data = false
     * @apiErrorExample {json} Error-Response:
     *                  {"success":false, "msg":"exception 'Exception' with message '....'"}
     * @apiSuccessExample {json} Success-Response:
     *                    HTTP/1.1 200 OK
     *                    {
     *                      "success": true,
     *                    }
     *
     * @param Request  $request
     * @param int|null $id
     *
     * @return JsonResponse
     *
     * @throws ResponseException
     */
    public function deleteAction(Request $request, $id = null)
    {
        $id     = $this->resolveId($request, $id);
        $object = $this->loadObject($id);

        $this->checkElementPermission($object, 'delete');

        $success = $this->service->deleteObject($id);
        if ($success) {
            return $this->createSuccessResponse();
        } else {
            // TODO what to do on delete error? is bad request appropiate?
            return $this->createErrorResponse();
        }
    }

    /**
     * @Method("GET")
     * @Route("/object-list")
     *
     * Returns a list of object id/type pairs matching the given criteria.
     *  Example:
     *  GET http://[YOUR-DOMAIN]/webservice/rest/object-list?apikey=[API-KEY]&order=DESC&offset=3&orderKey=id&limit=2&condition=type%3D%27folder%27
     *
     * Parameters:
     *      - condition
     *      - sort order (if supplied then also the key must be provided)
     *      - sort order key
     *      - offset
     *      - limit
     *      - group by key
     *      - objectClass the name of the object class (without "Object_"). If the class does
     *          not exist the filter criteria will be ignored!
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        $this->checkPermission('objects');

        $condition   = urldecode($request->get('condition'));
        $order       = $request->get('order');
        $orderKey    = $request->get('orderKey');
        $offset      = $request->get('offset');
        $limit       = $request->get('limit');
        $groupBy     = $request->get('groupBy');
        $objectClass = $request->get('objectClass');

        $result = $this->service->getObjectList($condition, $order, $orderKey, $offset, $limit, $groupBy, $objectClass);

        return $this->createCollectionSuccessResponse($result);
    }

    /**
     * @Method("GET")
     * @Route("/object-meta/id/{id}", requirements={"id": "\d+"})
     *
     * end point for object metadata
     *  Example:
     *  GET http://[YOUR-DOMAIN]/webservice/rest/object-meta/id/1281?apikey=[API-KEY]
     *      returns the json-encoded class definition for the given object
     *
     * @param int $id
     *
     * @return JsonResponse
     *
     * @throws ResponseException
     */
    public function objectMetaAction($id)
    {
        $this->checkPermission('classes');

        $class = $this->service->getObjectMetadataById($id);
        if (!$class) {
            throw $this->createNotFoundException();
        }

        return $this->createSuccessResponse($class);
    }

    /**
     * @Method("GET")
     * @Route("/object-count")
     *
     * Returns the total number of objects matching the given condition
     *  Example:
     *  GET http://[YOUR-DOMAIN]/webservice/rest/object-count?apikey=[API-KEY]&condition=type%3D%27folder%27
     *
     * Parameters:
     *      - condition
     *      - group by key
     *      - objectClass the name of the object class (without "Object_"). If the class does
     *          not exist the filter criteria will be ignored!
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function countAction(Request $request)
    {
        $this->checkPermission('objects');

        $condition   = urldecode($request->get('condition'));
        $groupBy     = $request->get('groupBy');
        $objectClass = $request->get('objectClass');

        $params = [
            'objectTypes' => [
                DataObject\AbstractObject::OBJECT_TYPE_FOLDER,
                DataObject\AbstractObject::OBJECT_TYPE_OBJECT,
                DataObject\AbstractObject::OBJECT_TYPE_VARIANT
            ]
        ];

        if (!empty($condition)) {
            $params['condition'] = $condition;
        }

        if (!empty($groupBy)) {
            $params['groupBy'] = $groupBy;
        }

        $listClassName = DataObject\AbstractObject::class;
        if (!empty($objectClass)) {
            $listClassName = '\\Pimcore\\Model\\DataObject\\' . ucfirst($objectClass);
            if (!Tool::classExists($listClassName)) {
                $listClassName = DataObject\AbstractObject::class;
            }
        }

        $count = $listClassName::getTotalCount($params);

        return $this->createSuccessResponse([
            'totalCount' => $count
        ]);
    }

    /**
     * @Method({"GET", "POST"})
     * @Route("/object-inquire")
     *
     * Checks for existence of the given object IDs
     *
     *  GET http://[YOUR-DOMAIN]/webservice/rest/object-inquire?apikey=[API-KEY]
     *
     * Parameters:
     *      - id single object ID
     *      - ids comma separated list of object IDs
     * Returns:
     *      - List with true or false for each ID
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function inquireAction(Request $request)
    {
        return $this->inquire($request, 'object');
    }

    /**
     * @param int $id
     *
     * @return DataObject\AbstractObject
     *
     * @throws ResponseException
     *      if object was not found
     */
    protected function loadObject($id)
    {
        $object = DataObject::getById((int)$id);

        if ($object) {
            return $object;
        }

        throw $this->createNotFoundException([
            'msg'  => sprintf('Object %d does not exist', (int)$id),
            'code' => static::ELEMENT_DOES_NOT_EXIST
        ]);
    }

    /**
     * Create an object
     *
     * @param string $type
     * @param array $data
     *
     * @return JsonResponse
     */
    protected function createObject($type, array $data)
    {
        if ($type === 'folder') {
            $class  = WebserviceFolderIn::class;
            $method = 'createObjectFolder';
        } else {
            $class  = WebserviceObjectIn::class;
            $method = 'createObjectConcrete';
        }

        $wsData = $this->fillWebserviceData($class, $data);

        $object = new Object();
        $object->setId($wsData->parentId);

        $this->checkElementPermission($object, 'create');

        $id = $this->service->$method($wsData);

        if (null !== $id) {
            return $this->createSuccessResponse([
                'id' => $id
            ], false);
        } else {
            return $this->createErrorResponse();
        }
    }

    /**
     * Update an existing object
     *
     * @param DataObject\AbstractObject $object
     * @param string $type
     * @param array $data
     *
     * @return JsonResponse
     */
    protected function updateObject(DataObject\AbstractObject $object, $type, array $data)
    {
        $this->checkElementPermission($object, 'update');

        $data['id'] = $object->getId();

        $success = false;
        if ($type === 'folder') {
            $wsData  = $this->fillWebserviceData(WebserviceFolderIn::class, $data);
            $success = $this->service->updateObjectFolder($wsData);
        } else {
            $wsData  = $this->fillWebserviceData(WebserviceObjectIn::class, $data);
            $success = $this->service->updateObjectConcrete($wsData);
        }

        if ($success) {
            return $this->createSuccessResponse();
        } else {
            return $this->createErrorResponse();
        }
    }
}
