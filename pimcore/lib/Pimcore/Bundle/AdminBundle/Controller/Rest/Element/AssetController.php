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
use Pimcore\Model\Asset;
use Pimcore\Model\Webservice\Data\Asset\File\In as WebserviceAssetFileIn;
use Pimcore\Model\Webservice\Data\Asset\Folder\In as WebserviceAssetFolderIn;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AssetController extends AbstractElementController
{
    /**
     * @Method("GET")
     * @Route("/asset/id/{id}", requirements={"id": "\d+"})
     * @Route("/asset")
     *
     * @api {get} /asset Get asset
     * @apiParamExample {json} Request-Example:
     *     {
     *       "id": 4711
     *       "apikey": '2132sdf2321rwefdcvvce22'
     *     }
     * @apiName getAssetFileById
     * @apiSampleRequest off
     * @apiGroup Asset
     * @apiParam {int} id The id of asset you search
     * @apiParam {string} apikey your access token
     * @apiSuccessExample {json} Success-Response:
     *                    {"success": "true", "data":{"path":"\/crm\/inquiries\/","creationDate":1368630916,"modificationDate":1388409137,"userModification":null,"childs":null}}
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
        $id    = $this->resolveId($request, $id);
        $asset = $this->loadAsset($id);

        $this->checkElementPermission($asset, 'get');

        if ($asset instanceof Asset\Folder) {
            $object = $this->service->getAssetFolderById($id);
        } else {
            $light   = $request->get('light');
            $options = [
                'LIGHT' => $light ? 1 : 0
            ];

            $object = $this->service->getAssetFileById($id, $options);
            $algo   = 'sha1';

            $thumbnailConfig = $request->get('thumbnail');
            if ($thumbnailConfig && $asset->getType() === 'image') {
                /** @var Asset\Image $asset */
                $checksum = $asset->getThumbnail($thumbnailConfig)->getChecksum($algo);

                $object->thumbnail = (string) $asset->getThumbnail($thumbnailConfig);
            } else {
                $checksum = $asset->getChecksum($algo);
            }

            $object->checksum = [
                'algo'  => $algo,
                'value' => $checksum
            ];

            if ($light) {
                unset($object->data);
            }
        }

        return $this->createSuccessResponse($object);
    }

    /**
     * @Method({"POST", "PUT"})
     * @Route("/asset")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $data = $this->getJsonData($request);

        // get and normalize type
        $type = $data['type'] = isset($data['type']) ? $data['type'] : 'asset';

        // add support for legacy behaviour, accepting the ID as payload parameter
        if (isset($data['id'])) {
            $id    = $data['id'];
            $asset = $this->loadAsset($id);

            return $this->updateAsset($asset, $type, $data);
        }

        return $this->createAsset($type, $data);
    }

    /**
     * @Method({"POST", "PUT"})
     * @Route("/asset/id/{id}", requirements={"id": "\d+"})
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
        $type = $data['type'] = isset($data['type']) ? $data['type'] : 'asset';

        $asset = $this->loadAsset($id);

        return $this->updateAsset($asset, $type, $data);
    }

    /**
     * @Method("DELETE")
     * @Route("/asset/id/{id}", requirements={"id": "\d+"})
     * @Route("/asset")
     *
     * @api {delete} /asset Delete asset
     * @apiName deleteAsset
     * @apiGroup Asset
     * @apiParam {int} id The id of asset you delete
     * @apiSampleRequest off
     * @apiParamExample {json} Request-Example:
     *     {
     *       "id": 4711
     *       "apikey": '2132sdf2321rwefdcvvce22'
     *     }
     * @apiParam {string} apikey your access token
     * @apiSuccess {boolean} success Returns true if finished successfully
     * @apiSuccessExample {json} Succes-Response:
     *                    {"success":true}
     * @apiError {boolean} success Returns false if failed
     * @apiErrorExample {json} Error-Response:
     *                  {"success":false,"msg":"exception 'Exception' with message 'Asset with given ID (712131243) does not exist.'"}
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
        $id    = $this->resolveId($request, $id);
        $asset = $this->loadAsset($id);

        $this->checkElementPermission($asset, 'delete');

        $success = $this->service->deleteAsset($id);
        if ($success) {
            return $this->createSuccessResponse();
        } else {
            // TODO what to do on delete error? is bad request appropiate?
            return $this->createErrorResponse();
        }
    }

    /**
     * @Method("GET")
     * @Route("/asset-list")
     *
     * Returns a list of assets id/type pairs matching the given criteria.
     *  Example:
     *  GET http://[YOUR-DOMAIN]/webservice/rest/asset-list?apikey=[API-KEY]&order=DESC&offset=3&orderKey=id&limit=2&condition=type%3D%27folder%27
     *
     * Parameters:
     *      - condition
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
        $this->checkPermission('assets');

        $condition = urldecode($request->get('condition'));
        $order     = $request->get('order');
        $orderKey  = $request->get('orderKey');
        $offset    = $request->get('offset');
        $limit     = $request->get('limit');
        $groupBy   = $request->get('groupBy');

        $result = $this->service->getAssetList($condition, $order, $orderKey, $offset, $limit, $groupBy);

        return $this->createCollectionSuccessResponse($result);
    }

    /**
     * @Method("GET")
     * @Route("/asset-count")
     *
     * Returns the total number of assets matching the given condition
     *  GET http://[YOUR-DOMAIN]/webservice/rest/asset-count?apikey=[API-KEY]&condition=type%3D%27folder%27
     *
     * Parameters:
     *      - condition
     *      - group by key
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function countAction(Request $request)
    {
        $this->checkPermission('assets');

        $condition = urldecode($request->get('condition'));
        $groupBy   = $request->get('groupBy');

        $params = [];
        if (!empty($condition)) {
            $params['condition'] = $condition;
        }

        if (!empty($groupBy)) {
            $params['groupBy'] = $groupBy;
        }

        $count = Asset::getTotalCount($params);

        return $this->createSuccessResponse([
            'totalCount' => $count
        ]);
    }

    /**
     * @Method({"GET", "POST"})
     * @Route("/asset-inquire")
     *
     * Checks for existence of the given asset IDs
     *
     *  GET http://[YOUR-DOMAIN]/webservice/rest/asset-inquire?apikey=[API-KEY]
     *
     * Parameters:
     *      - id single asset ID
     *      - ids comma separated list of asset IDs
     * Returns:
     *      - List with true or false for each ID
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function inquireAction(Request $request)
    {
        return $this->inquire($request, 'asset');
    }

    /**
     * @param int $id
     *
     * @return Asset
     *
     * @throws ResponseException
     *      if asset was not found
     */
    protected function loadAsset($id)
    {
        $asset = Asset::getById((int)$id);

        if ($asset) {
            return $asset;
        }

        throw $this->createNotFoundException([
            'msg'  => sprintf('Asset %d does not exist', (int)$id),
            'code' => static::ELEMENT_DOES_NOT_EXIST
        ]);
    }

    /**
     * Create an asset
     *
     * @param string $type
     * @param array  $data
     *
     * @return JsonResponse
     */
    protected function createAsset($type, array $data)
    {
        if ($type === 'folder') {
            $class  = WebserviceAssetFolderIn::class;
            $method = 'createAssetFolder';
        } else {
            $class  = WebserviceAssetFileIn::class;
            $method = 'createAssetFile';
        }

        $wsData = $this->fillWebserviceData($class, $data);

        $asset = new Asset();
        $asset->setId($wsData->parentId);

        $this->checkElementPermission($asset, 'create');

        $id = $this->service->$method($wsData);

        if (null !== $id) {
            return $this->createSuccessResponse([
                'id' => $id
            ], true);
        } else {
            return $this->createErrorResponse();
        }
    }

    /**
     * Update an asset
     *
     * @param Asset  $asset
     * @param string $type
     * @param array  $data
     *
     * @return JsonResponse
     */
    protected function updateAsset(Asset $asset, $type, array $data)
    {
        $this->checkElementPermission($asset, 'update');

        $data['id'] = $asset->getId();

        $success = false;
        if ($type === 'folder') {
            $wsData  = $this->fillWebserviceData(WebserviceAssetFolderIn::class, $data);
            $success = $this->service->updateAssetFolder($wsData);
        } else {
            $wsData  = $this->fillWebserviceData(WebserviceAssetFileIn::class, $data);
            $success = $this->service->updateAssetFile($wsData);
        }

        if ($success) {
            return $this->createSuccessResponse();
        } else {
            return $this->createErrorResponse();
        }
    }
}
