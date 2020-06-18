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
use Pimcore\Event\Webservice\FilterEvent;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\Document;
use Pimcore\Model\Webservice;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @deprecated
 *
 * end point for document related data.
 * - get document by id
 *      GET http://[YOUR-DOMAIN]/webservice/rest/document/id/1281?apikey=[API-KEY]
 *      returns json-encoded document data.
 * - delete document by id
 *      DELETE http://[YOUR-DOMAIN]/webservice/rest/document/id/1281?apikey=[API-KEY]
 *      returns json encoded success value
 * - create document
 *      PUT or POST http://[YOUR-DOMAIN]/webservice/rest/document?apikey=[API-KEY]
 *      body: json-encoded document data in the same format as returned by get document by id
 *              but with missing id field or id set to 0
 *      returns json encoded document id
 * - update document
 *      PUT or POST http://[YOUR-DOMAIN]/webservice/rest/document?apikey=[API-KEY]
 *      body: same as for create document but with object id
 *      returns json encoded success value
 */
class DocumentController extends AbstractElementController
{
    /**
     * @Route("/document/id/{id}", name="pimcore_api_rest_element_document_get", requirements={"id": "\d+"}, methods={"GET"})
     *
     * @api              {get} /document Get document
     * @apiName          getDocument
     * @apiGroup         Document
     * @apiSampleRequest off
     * @apiParam {int} id The id of document you search
     * @apiParamExample {json} Request-Example:
     *     {
     *       "id": 4711
     *       "apikey": '2132sdf2321rwefdcvvce22'
     *     }
     * @apiParam {string} apikey your access token
     * @apiSuccess {boolean} success Returns true if finished successfully
     * @apiSuccessExample {json} Succes-Response:
     *                    HTTP/1.1 200 OK
     *                    {
     *                        "success":true
     *                    }
     * @apiError {boolean} success Returns false if failed
     * @apiErrorExample {json} Error-Response:
     *                  {"success":false,"msg":"exception 'Exception' with message 'Document with given ID (712131243) does not exist.'"}
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

        $document = $this->loadDocument($id);

        $this->checkElementPermission($document, 'get');

        $type = $document->getType();
        $getter = sprintf('getDocument%sById', ucfirst($type));
        $object = null;

        if (method_exists($this->service, $getter)) {
            $object = $this->service->$getter($id);
        } else {
            // check if the getter is implemented by a plugin
            $class = '\\Pimcore\\Model\\Webservice\\Data\\Document\\' . ucfirst($type) . '\\Out';

            if (Tool::classExists($class)) {
                Document\Service::loadAllDocumentFields($document);
                $object = Webservice\Data\Mapper::map($document, $class, 'out');
            } else {
                return $this->createErrorResponse('Unknown type');
            }
        }

        return $this->createSuccessResponse($object);
    }

    /**
     * @Route("/document", name="pimcore_api_rest_element_document_create", methods={"POST", "PUT"})
     *
     * @api              {post} /document/id/{id} Create document
     * @apiName          createDocument
     * @apiGroup         Document
     * @apiSampleRequest off
     * @apiParamExample {json} Request-Example:
     *     {
     *       "apikey": '2132sdf2321rwefdcvvce22'
     *     }
     * @apiParam {string} apikey your access token
     * @apiSuccess {boolean} success Returns true if finished successfully
     * @apiSuccessExample {json} Succes-Response:
     *                    HTTP/1.1 200 OK
     *                    {
     *                        "success":true
     *                    }
     * @apiError {boolean} success Returns false if failed
     * @apiErrorExample {json} Error-Response:
     *                  {"success":false,"msg":"exception 'Exception' with message 'Document with given ID (712131243) does not exist.'"}
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $data = $this->getJsonData($request);
        $type = $data['type'];

        // add support for legacy behaviour, accepting the ID as payload parameter
        if (isset($data['id'])) {
            $id = $data['id'];
            $document = $this->loadDocument($id);

            return $this->updateDocument($document, $type, $data);
        }

        return $this->createDocument($type, $data);
    }

    /**
     * @Route("/document/id/{id}", name="pimcore_api_rest_element_document_update", requirements={"id": "\d+"}, methods={"POST", "PUT"})
     *
     * @api              {put} /document/id/{id} Update document
     * @apiName          updateDocument
     * @apiGroup         Document
     * @apiParam {int} id The id of document you delete
     * @apiSampleRequest off
     * @apiParamExample {json} Request-Example:
     *     {
     *       "id": 4711
     *       "apikey": '2132sdf2321rwefdcvvce22'
     *     }
     * @apiParam {string} apikey your access token
     * @apiSuccess {boolean} success Returns true if finished successfully
     * @apiSuccessExample {json} Succes-Response:
     *                    HTTP/1.1 200 OK
     *                    {
     *                        "success":true
     *                    }
     * @apiError {boolean} success Returns false if failed
     * @apiErrorExample {json} Error-Response:
     *                  {"success":false,"msg":"exception 'Exception' with message 'Document with given ID (712131243) does not exist.'"}
     *
     * @param Request  $request
     * @param int|null $id
     *
     * @return JsonResponse
     */
    public function updateAction(Request $request, $id)
    {
        $id = $this->resolveId($request, $id);
        $data = $this->getJsonData($request);
        $type = $data['type'];
        $document = $this->loadDocument($id);

        return $this->updateDocument($document, $type, $data);
    }

    /**
     * @Route("/document/id/{id}", name="pimcore_api_rest_element_document_delete", requirements={"id": "\d+"}, methods={"DELETE"})
     *
     * @api              {delete} /document Delete document
     * @apiName          deleteDocument
     * @apiGroup         Document
     * @apiParam {int} id The id of document you delete
     * @apiSampleRequest off
     * @apiParamExample {json} Request-Example:
     *     {
     *       "id": 4711
     *       "apikey": '2132sdf2321rwefdcvvce22'
     *     }
     * @apiParam {string} apikey your access token
     * @apiSuccess {boolean} success Returns true if finished successfully
     * @apiSuccessExample {json} Succes-Response:
     *                    HTTP/1.1 200 OK
     *                    {
     *                        "success":true
     *                    }
     * @apiError {boolean} success Returns false if failed
     * @apiErrorExample {json} Error-Response:
     *                  {"success":false,"msg":"exception 'Exception' with message 'Document with given ID (712131243) does not exist.'"}
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
        $id = $this->resolveId($request, $id);
        $document = $this->loadDocument($id);

        $this->checkElementPermission($document, 'delete');

        $success = $this->service->deleteDocument($id);
        if ($success) {
            return $this->createSuccessResponse();
        } else {
            // TODO what to do on delete error? is bad request appropiate?
            return $this->createErrorResponse();
        }
    }

    /**
     * @Route("/document-list", name="pimcore_api_rest_element_document_list", methods={"GET"})
     *
     * Returns a list of document id/type pairs matching the given criteria.
     *  Example:
     *  GET http://[YOUR-DOMAIN]/webservice/rest/document-list?apikey=[API-KEY]&order=DESC&offset=3&orderKey=id&limit=2&q={"type":%20"folder"}
     *
     * Parameters:
     *      - query filter (q)
     *      - sort order (if supplied then also the key must be provided)
     *      - sort order key
     *      - offset
     *      - limit
     *      - group by key
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        $this->checkPermission('documents');

        $condition = $this->buildCondition($request);

        $eventData = new FilterEvent($request, 'document', 'list', $condition);
        $this->dispatchBeforeLoadEvent($request, $eventData);
        $condition = $eventData->getCondition();

        $this->checkCondition($condition);
        $order = $request->get('order');
        $orderKey = $request->get('orderKey');
        $offset = $request->get('offset');
        $limit = $request->get('limit');
        $groupBy = $request->get('groupBy');

        $result = $this->service->getDocumentList($condition, $order, $orderKey, $offset, $limit, $groupBy);

        return $this->createCollectionSuccessResponse($result);
    }

    /**
     * @Route("/document-count", name="pimcore_api_rest_element_document_count", methods={"GET"})
     *
     * Returns the total number of documents matching the given condition
     *  GET http://[YOUR-DOMAIN]/webservice/rest/asset-count?apikey=[API-KEY]&q={"type": "folder"}
     *
     * Parameters:
     *      - query filter (q)
     *      - group by key
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function countAction(Request $request)
    {
        $this->checkPermission('documents');

        $condition = $this->buildCondition($request);

        $eventData = new FilterEvent($request, 'document', 'count', $condition);
        $this->dispatchBeforeLoadEvent($request, $eventData);
        $condition = $eventData->getCondition();

        $this->checkCondition($condition);
        $groupBy = $request->get('groupBy');

        $params = [];
        if (!empty($condition)) {
            $params['condition'] = $condition;
        }

        if (!empty($groupBy)) {
            $params['groupBy'] = $groupBy;
        }

        $count = Document::getTotalCount($params);

        return $this->createSuccessResponse([
            'totalCount' => $count,
        ]);
    }

    /**
     * @Route("/document-inquire", name="pimcore_api_rest_element_document_inquire", methods={"GET", "POST"})
     *
     * Checks for existence of the given document IDs
     *
     *  GET http://[YOUR-DOMAIN]/webservice/rest/document-inquire?apikey=[API-KEY]
     *
     * Parameters:
     *      - id single document ID
     *      - ids comma separated list of document IDs
     * Returns:
     *      - List with true or false for each ID
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function inquireAction(Request $request)
    {
        return $this->inquire($request, 'document');
    }

    /**
     * @param int $id
     *
     * @return Document
     *
     * @throws ResponseException
     *      if document was not found
     */
    protected function loadDocument($id)
    {
        $document = Document::getById((int)$id);

        if ($document) {
            return $document;
        }

        throw $this->createNotFoundResponseException([
            'msg' => sprintf('Document %d does not exist', (int)$id),
            'code' => static::ELEMENT_DOES_NOT_EXIST,
        ]);
    }

    /**
     * Create a new document
     *
     * @param string $type
     * @param array  $data
     *
     * @return JsonResponse
     */
    protected function createDocument($type, array $data)
    {
        $typeUpper = ucfirst($type);
        $className = $this->getWebserviceInClassName($type);
        $method = 'createDocument' . $typeUpper;

        $this->checkWebserviceMethod($method, $type);

        $wsData = $this->fillWebserviceData($className, $data);

        $document = new Document();
        /** @var Webservice\Data\Document $wsData */
        $document->setId($wsData->parentId);

        $this->checkElementPermission($document, 'create');

        $id = $this->service->$method($wsData);

        if (null !== $id) {
            return $this->createSuccessResponse([
                'id' => $id,
            ], false);
        } else {
            return $this->createErrorResponse();
        }
    }

    /**
     * Update an existing document
     *
     * @param Document $document
     * @param string   $type
     * @param array    $data
     *
     * @return JsonResponse
     */
    protected function updateDocument(Document $document, $type, array $data)
    {
        $this->checkElementPermission($document, 'update');

        $data['id'] = $document->getId();

        $typeUpper = ucfirst($type);
        $className = $this->getWebserviceInClassName($type);
        $method = 'updateDocument' . $typeUpper;

        $this->checkWebserviceMethod($method, $type);

        $wsData = $this->fillWebserviceData($className, $data);
        $success = $this->service->$method($wsData);

        if ($success) {
            return $this->createSuccessResponse();
        } else {
            return $this->createErrorResponse();
        }
    }

    /**
     * @param string $type
     *
     * @return string
     *
     * @throws ResponseException
     */
    protected function getWebserviceInClassName($type)
    {
        $typeUpper = ucfirst($type);
        $className = '\\Pimcore\\Model\\Webservice\\Data\\Document\\' . $typeUpper . '\\In';

        if (!Tool::classExists($className)) {
            throw new ResponseException(
                $this->createErrorResponse(sprintf('Type %s is invalid', $type))
            );
        }

        return $className;
    }

    /**
     * @param string $method
     * @param string $type
     *
     * @throws ResponseException
     */
    protected function checkWebserviceMethod($method, $type)
    {
        if (!method_exists($this->service, $method)) {
            if (\Pimcore::inDebugMode()) {
                throw new ResponseException(
                    $this->createErrorResponse(sprintf('Method %s does not exist', $method))
                );
            } else {
                throw new ResponseException(
                    $this->createErrorResponse(sprintf('Type %s is invalid', $type))
                );
            }
        }
    }
}
