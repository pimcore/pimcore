<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Rest;

use Pimcore\Bundle\PimcoreBundle\Http\Exception\ResponseException;
use Pimcore\Model\Object;
use Pimcore\Model\Webservice\Data\Object\Concrete\In as WebserviceObjectIn;
use Pimcore\Model\Webservice\Data\Object\Folder\In as WebserviceFolderIn;
use Pimcore\Model\Webservice\Service;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
class ObjectController extends AbstractRestController
{
    /**
     * @var Service
     */
    protected $service;

    public function __construct()
    {
        $this->service = new Service();
    }

    /**
     * @Route("/object/id/{id}", requirements={"id": "\d+"})
     * @Method("GET")
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
     * @param Request $request
     * @param int $id
     *
     * @return JsonResponse
     */
    public function getAction(Request $request, $id)
    {
        $profile     = $request->get('profiling');
        $profileName = 'rest_object_get';

        /** @var Stopwatch $stopwatch */
        $stopwatch = null;
        if ($profile) {
            $stopwatch = $this->startProfiling();
            $stopwatch->start('get', $profileName);
        }

        $object = Object::getById($id);
        if (!$object) {
            return $this->createErrorResponse([
                "msg"  => "Object does not exist",
                "code" => static::ELEMENT_DOES_NOT_EXIST
            ], Response::HTTP_NOT_FOUND);
        }

        if ($profile) {
            $stopwatch->stop('get');
            $stopwatch->start('perm', $profileName);
        }

        $this->checkElementPermission($object, "get");

        if ($profile) {
            $stopwatch->stop('perm');
            $stopwatch->start('ws', $profileName);
        }

        if ($object instanceof Object\Folder) {
            $object = $this->service->getObjectFolderById($id);
        } else {
            $object = $this->service->getObjectConcreteById($id);
        }

        if ($profile) {
            $stopwatch->stop('ws');
        }

        $data = $this->createSuccessData([
            'data' => $object
        ]);

        if ($profile) {
            $data['profiling'] = $this->getProfilingData($profileName);
        }

        return $this->json($data);
    }

    /**
     * @Route("/object")
     * @Method({"POST", "PUT"})
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
     * @Route("/object/id/{id}", requirements={"id": "\d+"})
     * @Method("PUT")
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
     * @param Request $request
     * @param int $id
     *
     * @return JsonResponse
     */
    public function updateAction(Request $request, $id)
    {
        $data = $this->getJsonData($request);

        // get and normalize type
        $type = $data['type'] = isset($data['type']) ? $data['type'] : 'object';

        if (!$id) {
            return $this->createErrorResponse('Missing ID');
        }

        $object = $this->loadObject($id);

        return $this->updateObject($object, $type, $data);
    }

    /**
     * @Route("/object/id/{id}", requirements={"id": "\d+"})
     * @Method("DELETE")
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
     * @param Request $request
     * @param int $id
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, $id)
    {
        $object = Object::getById($id);

        if ($object) {
            $this->checkElementPermission($object, 'delete');
        } else {
            return $this->createErrorResponse(Response::$statusTexts[Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        $success = $this->service->deleteObject($id);
        if ($success) {
            return $this->createSuccessResponse();
        } else {
            // TODO what to do on delete error? is bad request appropiate?
            return $this->createErrorResponse();
        }
    }

    /**
     * @Route("/object-list")
     * @Method("GET")
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

        return $this->createSuccessResponse([
            'data' => $result
        ]);
    }

    /**
     * @param int $id
     * @return Object\AbstractObject
     *
     * @throws ResponseException
     *      if no object was found
     */
    protected function loadObject($id)
    {
        $object = Object::getById((int)$id);

        if ($object) {
            return $object;
        }

        throw new ResponseException($this->createErrorResponse(
            Response::$statusTexts[Response::HTTP_NOT_FOUND],
            Response::HTTP_NOT_FOUND
        ));
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
        if ($type === "folder") {
            $class  = WebserviceFolderIn::class;
            $method = "createObjectFolder";
        } else {
            $class  = WebserviceObjectIn::class;
            $method = "createObjectConcrete";
        }

        $wsData = $this->fillWebserviceData($class, $data);

        $object = new Object();
        $object->setId($wsData->parentId);

        $this->checkElementPermission($object, 'create');

        $id = $this->service->$method($wsData);

        if (null !== $id) {
            return $this->createSuccessResponse([
                'id' => $id
            ]);
        } else {
            return $this->createErrorResponse();
        }
    }

    /**
     * Update an existing object
     *
     * @param Object\AbstractObject $object
     * @param string $type
     * @param array $data
     *
     * @return JsonResponse
     */
    protected function updateObject(Object\AbstractObject $object, $type, array $data)
    {
        $data['id'] = $object->getId();

        $success = false;
        if ($type === "folder") {
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
