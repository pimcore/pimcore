<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\ControllerApi\Rest;

use Pimcore\Bundle\PimcoreAdminBundle\ControllerApi\AbstractApiController;
use Pimcore\Model\Object;
use Pimcore\Model\Webservice\Service;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * @Route("/object")
 *
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
 *      PUT or POST http://[YOUR-DOMAIN]/webservice/rest/object?apikey=[API-KEY]
 *      body: same as for create object but with object id
 *      returns json encoded success value
 */
class ObjectController extends AbstractApiController
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
     * @Route("/id/{id}", requirements={"id": "\d+"})
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
        $profile   = $request->get("profiling");
        $stopwatch = $this->get('debug.stopwatch');

        /** @var StopwatchEvent[] $profiling */
        $profiling = [];

        if ($profile) {
            $stopwatch->openSection();
            $stopwatch->start('get');
        }

        sleep(3);

        $object = Object::getById($id);
        if (!$object) {
            return $this->createErrorResponse([
                "msg"  => "Object does not exist",
                "code" => static::ELEMENT_DOES_NOT_EXIST
            ], Response::HTTP_NOT_FOUND);
        }

        if ($profile) {
            $stopwatch->stop('get');
            $stopwatch->start('perm');
        }

        $this->checkElementPermission($object, "get");

        if ($profile) {
            // $stopwatch->stopSection('perm');
            // $stopwatch->openSection();
            $stopwatch->stop('perm');
            $stopwatch->start('ws');
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
            $stopwatch->stopSection('rest-object-get');

            $data['profiling'] = [];
            foreach ($stopwatch->getSectionEvents('rest-object-get') as $name => $event) {
                $data['profiling'][$name] = $event->getDuration();
            }
        } else {
            return $this->createSuccessResponse([
                'data' => $object
            ]);
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("")
     * @Method("POST")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        return new JsonResponse([
            'method' => $request->getMethod(),
        ]);
    }

    /**
     * @Route("/id/{id}", requirements={"id": "\d+"})
     * @Method("PUT")
     *
     * @param Request $request
     * @param int $id
     *
     * @return JsonResponse
     */
    public function updateAction(Request $request, $id)
    {
        return new JsonResponse([
            'method' => $request->getMethod(),
            'id'     => $id
        ]);
    }

    /**
     * @Route("/id/{id}", requirements={"id": "\d+"})
     * @Method("DELETE")
     *
     * @param Request $request
     * @param int $id
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, $id)
    {
        return new JsonResponse([
            'method' => $request->getMethod(),
            'id'     => $id
        ]);
    }
}
