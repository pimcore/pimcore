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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 *
 * @author      JA
 */

use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Pimcore\Model\Element;
use Pimcore\Model\Webservice;
use Pimcore\Tool;

class Webservice_RestController extends \Pimcore\Controller\Action\Webservice
{
    const ELEMENT_DOES_NOT_EXIST = -1;
    const TAG_DOES_NOT_EXIST = -1;
    /**
     * the webservice
     * @var
     */
    private $service;

    /**
     * The output encoder (e.g. json)
     * @var
     */
    private $encoder;


    public function init()
    {
        if ($this->getParam("condense")) {
            Object\ClassDefinition\Data::setDropNullValues(true);
            Webservice\Data\Object::setDropNullValues(true);
        }

        $profile = $this->getParam("profiling");
        if ($profile) {
            $startTs = microtime(true);
        }
        parent::init();
        $this->disableViewAutoRender();
        $this->service = new Webservice\Service();
        // initialize json encoder by default, maybe support xml in the near future
        $this->encoder = new Webservice\JsonEncoder();

        if ($profile) {
            $this->timeConsumedInit = round(microtime(true) - $startTs, 3)*1000;
        }
    }

    private function checkPermission($element, $category)
    {
        if ($category == "get") {
            if (!$element->isAllowed("view")) {
                $this->getResponse()->setHttpResponseCode(403);
                $this->encoder->encode(["success" => false, "msg" => "not allowed, permission view is needed"]);
            }
        } elseif ($category == "delete") {
            if (!$element->isAllowed("delete")) {
                $this->getResponse()->setHttpResponseCode(403);
                $this->encoder->encode(["success" => false, "msg" => "not allowed, permission delete is needed"]);
            }
        } elseif ($category == "update") {
            if (!$element->isAllowed("publish")) {
                $this->getResponse()->setHttpResponseCode(403);
                $this->encoder->encode(["success" => false, "msg" => "not allowed, permission save is needed"]);
            }
        } elseif ($category == "create") {
            if (!$element->isAllowed("create")) {
                $this->getResponse()->setHttpResponseCode(403);
                $this->encoder->encode(["success" => false, "msg" => "not allowed, permission create is needed"]);
            }
        }
    }

    private function checkUserPermission($permission)
    {
        if ($user = Tool\Admin::getCurrentUser()) {
            if ($user->isAllowed($permission)) {
                return;
            }
        }
        $this->getResponse()->setHttpResponseCode(403);
        $this->encoder->encode(["success" => false, "msg" => "not allowed"]);
    }


    /** end point for object related data.
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
     * @throws \Exception
     */
    public function objectAction()
    {
        $id = $this->getParam("id");
        $success = false;

        try {
            if ($this->isGet()) {
                /**
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
                 */
                if ($id) {
                    $profile = $this->getParam("profiling");
                    if ($profile) {
                        $startTs = microtime(true);
                    }

                    $object = Object::getById($id);
                    if (!$object) {
                        $this->encoder->encode(["success" => false,
                            "msg" => "Object does not exist",
                            "code" => self::ELEMENT_DOES_NOT_EXIST]);

                        return;
                    }

                    if ($profile) {
                        $timeConsumedGet = round(microtime(true) - $startTs, 3) * 1000;
                        $startTs = microtime(true);
                    }

                    $this->checkPermission($object, "get");

                    if ($profile) {
                        $timeConsumedPerm = round(microtime(true) - $startTs, 3) * 1000;
                        $startTs = microtime(true);
                    }

                    if ($object instanceof Object\Folder) {
                        $object = $this->service->getObjectFolderById($id);
                    } else {
                        $object = $this->service->getObjectConcreteById($id);
                    }

                    if ($profile) {
                        $timeConsumedGetWebservice = round(microtime(true) - $startTs, 3) * 1000;
                    }

                    if ($profile) {
                        $profiling = [];
                        $profiling["get"] = $timeConsumedGet;
                        $profiling["perm"] = $timeConsumedPerm;
                        $profiling["ws"] = $timeConsumedGetWebservice;
                        $profiling["init"] = $this->timeConsumedInit;
                        $result = ["success" => true, "profiling" => $profiling, "data" => $object];
                    } else {
                        $result = ["success" => true, "data" => $object];
                    }


                    $this->encoder->encode($result);

                    return;
                }
            } elseif ($this->isDelete()) {
                /**
                 * @api {delete} /object Delete object
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
                 *
                 */
                $object = Object::getById($id);
                if ($object) {
                    $this->checkPermission($object, "delete");
                }

                $success = $this->service->deleteObject($id);
                $this->encoder->encode(["success" => $success]);

                return;
            } elseif ($this->isPost() || $this->isPut()) {
                $data = file_get_contents("php://input");
                $data = \Zend_Json::decode($data);

                $type = $data["type"];
                $id = null;

                if ($data["id"]) {
                    /**
                     * @api {put} /object Create a new object
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
                     */
                    $obj = Object::getById($data["id"]);
                    if ($obj) {
                        $this->checkPermission($obj, "update");
                    }

                    $isUpdate = true;
                    if ($type == "folder") {
                        $wsData = self::fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\Object\\Folder\\In", $data);
                        $success = $this->service->updateObjectFolder($wsData);
                    } else {
                        $wsData = self::fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\Object\\Concrete\\In", $data);
                        $success = $this->service->updateObjectConcrete($wsData);
                    }
                } else {
                    /**
                     * @api {put} /object Create a new object
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
                     */
                    if ($type == "folder") {
                        $class = "\\Pimcore\\Model\\Webservice\\Data\\Object\\Folder\\In";
                        $method = "createObjectFolder";
                    } else {
                        $class = "\\Pimcore\\Model\\Webservice\\Data\\Object\\Concrete\\In";
                        $method = "createObjectConcrete";
                    }
                    $wsData = self::fillWebserviceData($class, $data);

                    $obj = new Object();
                    $obj->setId($wsData->parentId);
                    $this->checkPermission($obj, "create");

                    $id = $this->service->$method($wsData);
                }

                if (!$isUpdate) {
                    $success = $id != null;
                }


                $result = ["success" => $success];
                if ($success && !$isUpdate) {
                    $result["id"] = $id;
                }

                $this->encoder->encode($result);

                return;
            }
        } catch (\Exception $e) {
            Logger::error($e);
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }

        throw new \Exception("not implemented");
    }

    /** end point for object metadata
     * GET http://[YOUR-DOMAIN]/webservice/rest/object-meta/id/1281?apikey=[API-KEY]
     *      returns the json-encoded class definition for the given object
     *
     */
    public function objectMetaAction()
    {
        $this->checkUserPermission("classes");

        $id = $this->getParam("id");
        $success = false;

        try {
            if ($id) {
                $class = $this->service->getObjectMetadataById($id);
                $this->encoder->encode(["success" => true, "data" => $class]);

                return;
            }
        } catch (\Exception $e) {
            $this->encoder->encode(["success" => false, "message" => (string) $e]);
            Logger::error($e);
        }

        $this->encoder->encode(["success" => false]);
    }


    /** end point for the class definition
     * GET http://[YOUR-DOMAIN]/webservice/rest/class/id/1281?apikey=[API-KEY]
     *      returns the class definition for the given class
     *
     */
    public function classAction()
    {
        $this->checkUserPermission("classes");

        try {
            $id = $this->getParam("id");
            if ($id) {
                $class = $this->service->getClassById($id);
                $this->encoder->encode(["success" => true, "data" => $class]);

                return;
            }
        } catch (\Exception $e) {
            Logger::error($e);
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }
        $this->encoder->encode(["success" => false]);
    }

    /**
     * Returns the configuration for the image thumbnail with the given ID.
     */
    public function imageThumbnailAction()
    {
        $this->checkUserPermission("thumbnails");
        try {
            $id = $this->getParam("id");
            if ($id) {
                $config = Asset\Image\Thumbnail\Config::getByName($id);
                if (!$config instanceof Asset\Image\Thumbnail\Config) {
                    throw new \Exception("Thumbnail '" . $id . "' file doesn't exists");
                }

                $this->encoder->encode(["success" => true, "data" => $config->getForWebserviceExport()]);

                return;
            }
        } catch (\Exception $e) {
            Logger::error($e);
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }
        $this->encoder->encode(["success" => false]);
    }

    /**
     * Returns a list of all image thumbnails.
     */
    public function imageThumbnailsAction()
    {
        $this->checkUserPermission("thumbnails");

        $thumbnails = [];

        $list = new Asset\Image\Thumbnail\Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $thumbnails[] = [
                "id" => $item->getName(),
                "text" => $item->getName()
            ];
        }

        $this->encoder->encode(["success" => true, "data" => $thumbnails]);
    }


    /** end point for the object-brick definition
     * GET http://[YOUR-DOMAIN]/webservice/rest/object-brick/id/abt1?apikey=[API-KEY]
     *      returns the class definition for the given class
     *
     */
    public function objectBrickAction()
    {
        $this->checkUserPermission("classes");
        try {
            $fc = Object\Objectbrick\Definition::getByKey($this->getParam("id"));
            $this->_helper->json(["success" => true, "data" => $fc]);
        } catch (\Exception $e) {
            Logger::error($e);
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }
        $this->encoder->encode(["success" => false]);
    }

    /** end point for the field collection definition
     * GET http://[YOUR-DOMAIN]/webservice/rest/field-collection/id/abt1?apikey=[API-KEY]
     *      returns the class definition for the given class
     *
     */
    public function fieldCollectionAction()
    {
        $this->checkUserPermission("classes");
        try {
            $fc = Object\Fieldcollection\Definition::getByKey($this->getParam("id"));
            $this->_helper->json(["success" => true, "data" => $fc]);
        } catch (\Exception $e) {
            Logger::error($e);
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }
        $this->encoder->encode(["success" => false]);
    }



    /** GET http://[YOUR-DOMAIN]/webservice/rest/user?apikey=[API-KEY]
     *      returns the json-encoded user data for the current user
     *
     */
    public function userAction()
    {
        try {
            $object = $this->service->getuser();
            $this->encoder->encode(["success" => true, "data" => $object]);
        } catch (\Exception $e) {
            Logger::error($e);
        }
        $this->encoder->encode(["success" => false]);
    }

    /** end point for asset related data.
     * - get asset by id
     *      GET http://[YOUR-DOMAIN]/webservice/rest/asset/id/1281?apikey=[API-KEY]
     *      returns json-encoded asset data.
     * - delete asset by id
     *      DELETE http://[YOUR-DOMAIN]/webservice/rest/asset/id/1281?apikey=[API-KEY]
     *      returns json encoded success value
     * - create asset
     *      PUT or POST http://[YOUR-DOMAIN]/webservice/rest/asset?apikey=[API-KEY]
     *      body: json-encoded asset data in the same format as returned by get asset by id
     *              but with missing id field or id set to 0
     *      returns json encoded asset id
     * - update asset
     *      PUT or POST http://[YOUR-DOMAIN]/webservice/rest/asset?apikey=[API-KEY]
     *      body: same as for create asset but with asset id
     *      returns json encoded success value
     * @throws \Exception
     */
    public function assetAction()
    {
        $id = $this->getParam("id");
        $success = false;

        try {
            if ($this->isGet()) {
                /**
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
                 */
                $asset = Asset::getById($id);
                if (!$asset) {
                    $this->encoder->encode([  "success" => false,
                        "msg" => "Asset does not exist",
                        "code" => self::ELEMENT_DOES_NOT_EXIST]);

                    return;
                }

                $this->checkPermission($asset, "get");

                if ($asset instanceof Asset\Folder) {
                    $object = $this->service->getAssetFolderById($id);
                } else {
                    $light = $this->getParam("light");
                    $options = ["LIGHT" => $light ? 1 : 0];
                    $object = $this->service->getAssetFileById($id, $options);
                    $algo = "sha1";

                    $thumbnailConfig = $this->getParam("thumbnail");
                    if ($thumbnailConfig && $asset->getType() == "image") {
                        $checksum = $asset->getThumbnail($thumbnailConfig)->getChecksum($algo);
                        $object->thumbnail = (string) $asset->getThumbnail($thumbnailConfig);
                    } else {
                        $checksum = $asset->getChecksum($algo);
                    }

                    $object->checksum = [
                        "algo" => $algo,
                        "value" => $checksum
                    ];

                    if ($light) {
                        unset($object->data);
                    }
                }
                $this->encoder->encode(["success" => true, "data" => $object]);

                return;
            } elseif ($this->isDelete()) {
                /**
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
                 */
                $asset = Asset::getById($id);
                if ($asset) {
                    $this->checkPermission($asset, "delete");
                }

                $success = $this->service->deleteAsset($id);
                $this->encoder->encode(["success" => $success]);

                return;
            } elseif ($this->isPost() || $this->isPut()) {
                $data = file_get_contents("php://input");
                $data = \Zend_Json::decode($data);

                $type = $data["type"];
                $id = null;

                if ($data["id"]) {
                    $asset = Asset::getById($data["id"]);
                    if ($asset) {
                        $this->checkPermission($asset, "update");
                    }

                    $isUpdate = true;
                    if ($type == "folder") {
                        $wsData = self::fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\Asset\\Folder\\In", $data);
                        $success = $this->service->updateAssetFolder($wsData);
                    } else {
                        $wsData = self::fillWebserviceData("\\Pimcore\\Model\\Webservice\\Data\\Asset\\File\\In", $data);
                        $success = $this->service->updateAssetFile($wsData);
                    }
                } else {
                    if ($type == "folder") {
                        $class = "\\Pimcore\\Model\\Webservice\\Data\\Asset\\Folder\\In";
                        $method = "createAssetFolder";
                    } else {
                        $class = "\\Pimcore\\Model\\Webservice\\Data\\Asset\\File\\In";
                        $method = "createAssetFile";
                    }

                    $wsData = self::fillWebserviceData($class, $data);

                    $asset = new Asset();
                    $asset->setId($wsData->parentId);
                    $this->checkPermission($asset, "create");

                    $id = $this->service->$method($wsData);
                }

                if (!$isUpdate) {
                    $success = $id != null;
                }

                if ($success && !$isUpdate) {
                    $this->encoder->encode(["success" => $success, "data" => ["id" => $id]]);
                } else {
                    $this->encoder->encode(["success" => $success]);
                }

                return;
            }
        } catch (\Exception $e) {
            Logger::error($e);
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }
        $this->encoder->encode(["success" => false]);
    }

    /** Returns the group/key config as JSON.
     * @return mixed
     */
    public function keyValueDefinitionAction()
    {
        $this->checkUserPermission("classes");

        try {
            if ($this->isGet()) {
                $condition = urldecode($this->getParam("condition"));

                $definition = [];

                $list = new Object\KeyValue\GroupConfig\Listing();
                if ($condition) {
                    $list->setCondition($condition);
                }
                $list->load();
                $items = $list->getList();

                $groups = [];

                foreach ($items as $item) {
                    $groups[] = $item->getObjectVars();
                }
                $definition["groups"] = $groups;

                $list = new Object\KeyValue\KeyConfig\Listing();
                if ($condition) {
                    $list->setCondition($condition);
                }
                $list->load();
                $items = $list->getList();

                $keys = [];

                foreach ($items as $item) {
                    /** @var  $item Object\KeyValue\KeyConfig */
                    $keys[] = $item->getObjectVars();
                }
                $definition["keys"] = $keys;
                $this->encoder->encode(["success" => true, "data" => $definition]);
            }
        } catch (\Exception $e) {
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }
        $this->encoder->encode(["success" => false]);
    }

    /** end point for document related data.
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
     * @throws \Exception
     */
    public function documentAction()
    {
        $id = $this->getParam("id");
        $success = false;

        try {
            if ($this->isGet()) {
                /**
                 * @api {get} /document Get document
                 * @apiName getDocument
                 * @apiGroup Document
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
                 */
                $doc = Document::getById($id);
                if (!$doc) {
                    $this->encoder->encode(["success" => false,
                        "msg" => "Document does not exist",
                        "code" => self::ELEMENT_DOES_NOT_EXIST]);

                    return;
                }

                $this->checkPermission($doc, "get");

                if ($doc) {
                    $type = $doc->getType();
                    $getter = "getDocument" . ucfirst($type) . "ById";

                    if (method_exists($this->service, $getter)) {
                        $object = $this->service->$getter($id);
                    } else {
                        // check if the getter is implemented by a plugin
                        $class = "\\Pimcore\\Model\\Webservice\\Data\\Document\\" . ucfirst($type) . "\\Out";
                        if (Tool::classExists($class)) {
                            Document\Service::loadAllDocumentFields($doc);
                            $object = Webservice\Data\Mapper::map($doc, $class, "out");
                        } else {
                            throw new \Exception("unknown type");
                        }
                    }
                }

                if (!$object) {
                    throw new \Exception("could not find document");
                }
                @$this->encoder->encode(["success" => true, "data" => $object]);

                return;
            } elseif ($this->isDelete()) {
                /**
                 * @api {delete} /document Delete document
                 * @apiName deleteDocument
                 * @apiGroup Document
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
                 */
                $doc = Document::getById($id);
                if ($doc) {
                    $this->checkPermission($doc, "delete");
                }
                $success = $this->service->deleteDocument($id);
                $this->encoder->encode(["success" => $success]);

                return;
            } elseif ($this->isPost() || $this->isPut()) {
                /**
                 * @api {post} /document Update document
                 * @apiName updateDocument
                 * @apiGroup Document
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
                 */
                $data = file_get_contents("php://input");
                $data = \Zend_Json::decode($data);

                $type = $data["type"];
                $id = null;
                $typeUpper = ucfirst($type);
                $className = "\\Pimcore\\Model\\Webservice\\Data\\Document\\" . $typeUpper . "\\In";

                if ($data["id"]) {
                    $doc = Document::getById($data["id"]);
                    if ($doc) {
                        $this->checkPermission($doc, "update");
                    }

                    $isUpdate = true;
                    $setter = "updateDocument" . $typeUpper;
                    if (!method_exists($this->service, $setter)) {
                        throw new \Exception("method does not exist " . $setter);
                    }
                    $wsData = self::fillWebserviceData($className, $data);
                    $success = $this->service->$setter($wsData);
                } else {
                    $setter = "createDocument" . $typeUpper;
                    if (!method_exists($this->service, $setter)) {
                        throw new \Exception("method does not exist " . $setter);
                    }
                    $wsData = self::fillWebserviceData($className, $data);
                    $doc = new Document();
                    $doc->setId($wsData->parentId);
                    $this->checkPermission($doc, "create");

                    $id = $this->service->$setter($wsData);
                }

                if (!$isUpdate) {
                    $success = $id != null;
                }

                if ($success && !$isUpdate) {
                    $this->encoder->encode(["success" => $success, "id" => $id]);
                } else {
                    $this->encoder->encode(["success" => $success]);
                }

                return;
            }
        } catch (\Exception $e) {
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }
        $this->encoder->encode(["success" => false]);
    }


    /** Returns a list of assets id/type pairs matching the given criteria.
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
     */
    public function assetListAction()
    {
        $this->checkUserPermission("assets");

        $condition = $this->getParam("condition");
        $order = $this->getParam("order");
        $orderKey = $this->getParam("orderKey");
        $offset = $this->getParam("offset");
        $limit = $this->getParam("limit");
        $groupBy = $this->getParam("groupBy");
        $result = $this->service->getAssetList($condition, $order, $orderKey, $offset, $limit, $groupBy);
        $this->encoder->encode(["success" => true, "data" => $result]);
    }

    /** Returns a list of document id/type pairs matching the given criteria.
     *  Example:
     *  GET http://[YOUR-DOMAIN]/webservice/rest/document-list?apikey=[API-KEY]&order=DESC&offset=3&orderKey=id&limit=2&condition=type%3D%27folder%27
     *
     * Parameters:
     *      - condition
     *      - sort order (if supplied then also the key must be provided)
     *      - sort order key
     *      - offset
     *      - limit
     *      - group by key
     */
    public function documentListAction()
    {
        $this->checkUserPermission("documents");

        $condition = urldecode($this->getParam("condition"));
        $order = $this->getParam("order");
        $orderKey = $this->getParam("orderKey");
        $offset = $this->getParam("offset");
        $limit = $this->getParam("limit");
        $groupBy = $this->getParam("groupBy");
        $result = $this->service->getDocumentList($condition, $order, $orderKey, $offset, $limit, $groupBy);
        $this->encoder->encode(["success" => true, "data" => $result]);
    }

    /** Returns a list of object id/type pairs matching the given criteria.
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
    public function objectListAction()
    {
        $this->checkUserPermission("objects");

        $condition = urldecode($this->getParam("condition"));
        $order = $this->getParam("order");
        $orderKey = $this->getParam("orderKey");
        $offset = $this->getParam("offset");
        $limit = $this->getParam("limit");
        $groupBy = $this->getParam("groupBy");
        $objectClass = $this->getParam("objectClass");
        $result = $this->service->getObjectList($condition, $order, $orderKey, $offset, $limit, $groupBy, $objectClass);
        $this->encoder->encode(["success" => true, "data" => $result]);
    }

    /** Returns the total number of objects matching the given condition
     *  GET http://[YOUR-DOMAIN]/webservice/rest/object-count?apikey=[API-KEY]&condition=type%3D%27folder%27
     *
     * Parameters:
     *      - condition
     *      - group by key
     *      - objectClass the name of the object class (without "Object_"). If the class does
     *          not exist the filter criteria will be ignored!
     */
    public function objectCountAction()
    {
        $this->checkUserPermission("objects");

        $condition = urldecode($this->getParam("condition"));
        $groupBy = $this->getParam("groupBy");
        $objectClass = $this->getParam("objectClass");
        $params = ["objectTypes" => [Object\AbstractObject::OBJECT_TYPE_FOLDER, Object\AbstractObject::OBJECT_TYPE_OBJECT, Object\AbstractObject::OBJECT_TYPE_VARIANT]];

        if (!empty($condition)) {
            $params["condition"] = $condition;
        }
        if (!empty($groupBy)) {
            $params["groupBy"] = $groupBy;
        }

        $listClassName = "\\Pimcore\\Model\\Object\\AbstractObject";
        if (!empty($objectClass)) {
            $listClassName = "\\Pimcore\\Model\\Object\\" . ucfirst($objectClass);
            if (!Tool::classExists($listClassName)) {
                $listClassName = "Pimcore\\Model\\Object\\AbstractObject";
            }
        }

        $count = $listClassName::getTotalCount($params);

        $this->encoder->encode(["success" => true, "data" => ["totalCount" => $count]]);
    }


    /** Returns the total number of assets matching the given condition
     *  GET http://[YOUR-DOMAIN]/webservice/rest/asset-count?apikey=[API-KEY]&condition=type%3D%27folder%27
     *
     * Parameters:
     *      - condition
     *      - group by key
     */
    public function assetCountAction()
    {
        $this->checkUserPermission("assets");

        $condition = urldecode($this->getParam("condition"));
        $groupBy = $this->getParam("groupBy");
        $params = [];

        if (!empty($condition)) {
            $params["condition"] = $condition;
        }
        if (!empty($groupBy)) {
            $params["groupBy"] = $groupBy;
        }


        $count = Asset::getTotalCount($params);

        $this->encoder->encode(["success" => true, "data" => ["totalCount" => $count]]);
    }

    /** Returns the total number of documents matching the given condition
     *  GET http://[YOUR-DOMAIN]/webservice/rest/asset-count?apikey=[API-KEY]&condition=type%3D%27folder%27
     *
     * Parameters:
     *      - condition
     *      - group by key
     */
    public function documentCountAction()
    {
        $this->checkUserPermission("documents");

        $condition = urldecode($this->getParam("condition"));
        $groupBy = $this->getParam("groupBy");
        $params = [];

        if (!empty($condition)) {
            $params["condition"] = $condition;
        }
        if (!empty($groupBy)) {
            $params["groupBy"] = $groupBy;
        }


        $count = Document::getTotalCount($params);

        $this->encoder->encode(["success" => true, "data" => ["totalCount" => $count]]);
    }

    /**
     * Returns a list of all class definitions.
     */
    public function classesAction()
    {
        $this->checkUserPermission("classes");

        $list = new Object\ClassDefinition\Listing();
        $classes = $list->load();
        $result = [];

        foreach ($classes as $class) {
            $item = [
                "id" => $class->getId(),
                "name" => $class->getName()
            ];
            $result[] = $item;
        }

        $this->encoder->encode(["success" => true, "data" => $result]);
    }

    /**
     * Returns a list of all tags.
     *  GET http://[YOUR-DOMAIN]/webservice/rest/tag-list?apikey=[API-KEY]
     * @throws \Exception
     */
    public function tagListAction()
    {
        $this->checkUserPermission("tags_search");

        try {
            $list = new Element\Tag\Listing();
            $tags = $list->load();
            $result = [];

            foreach ($tags as $tag) {
                $item = [
                    "id" => $tag->getId(),
                    "parentId" => $tag->getParentId(),
                    "name" => $tag->getName()
                ];
                $result[] = $item;
            }
        } catch (\Exception $e) {
            Logger::error($e);
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }

        $this->encoder->encode(["success" => true, "data" => $result]);
    }

    /** Returns a list of all tags for an element.
     *  GET http://[YOUR-DOMAIN]/webservice/rest/tags-element-list?apikey=[API-KEY]&id=1281&type=object
     *
     * Parameters:
     *      - element id
     *      - type of element (document | asset | object)
     * @throws \Exception
     */
    public function tagsElementListAction()
    {
        $this->checkUserPermission("tags_search");

        $id = $this->getParam("id");
        $type = $this->getParam("type");

        try {
            if ($type === "document") {
                $this->checkUserPermission("documents");
                $element = Document::getById($id);
            } elseif ($type === "asset") {
                $this->checkUserPermission("assets");
                $element = Asset::getById($id);
            } elseif ($type === "object") {
                $this->checkUserPermission("objects");
                $element = Object::getById($id);
            } else {
                $this->encoder->encode(["success" => false]);

                return;
            }

            if (!$element) {
                $this->encoder->encode([  "success" => false,
                    "msg" => "Element does not exist",
                    "code" => self::ELEMENT_DOES_NOT_EXIST]);

                return;
            }

            $this->checkPermission($element, "get");

            $assignedTags = Element\Tag::getTagsForElement($type, $element->getId());
            $result = [];

            foreach ($assignedTags as $tag) {
                $item = [
                    "id" => $tag->getId(),
                    "parentId" => $tag->getParentId(),
                    "name" => $tag->getName()
                ];
                $result[] = $item;
            }
        } catch (\Exception $e) {
            Logger::error($e);
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }

        $this->encoder->encode(["success" => true, "data" => $result]);
    }

    /** Returns a list of elements id/type pairs for a tag.
     *  GET http://[YOUR-DOMAIN]/webservice/rest/elements-tag-list?apikey=[API-KEY]&id=12&type=object
     *
     * Parameters:
     *      - tag id
     *      - type of element (document | asset | object)
     * @throws \Exception
     */
    public function elementsTagListAction()
    {
        $this->checkUserPermission("tags_search");

        $id = $this->getParam("id");
        $type = $this->getParam("type");

        try {
            $tag = Element\Tag::getById($id);

            if (!$tag) {
                $this->encoder->encode([  "success" => false,
                    "msg" => "Tag does not exist",
                    "code" => self::TAG_DOES_NOT_EXIST]);

                return;
            }

            if ($type === "document") {
                $this->checkUserPermission("documents");
            } elseif ($type === "asset") {
                $this->checkUserPermission("assets");
            } elseif ($type === "object") {
                $this->checkUserPermission("objects");
            } else {
                $this->encoder->encode(["success" => false]);

                return;
            }

            $elementsForTag = Element\Tag::getElementsForTag($tag, $type);
            $result = [];

            foreach ($elementsForTag as $element) {
                $item = [
                    "id" => $element->getId(),
                    "type" => $element->getType()
                ];
                if (method_exists($element, "getPublished")) {
                    $item['published'] = $element->getPublished();
                }
                $result[] = $item;
            }
        } catch (\Exception $e) {
            Logger::error($e);
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }

        $this->encoder->encode(["success" => true, "data" => $result]);
    }

    private function inquire($type)
    {
        try {
            $condense = $this->getParam("condense");
            $this->checkUserPermission($type . "s");
            if ($this->isPost()) {
                $data = file_get_contents("php://input");
                $idList = explode(',', $data);
            } elseif ($this->getParam("ids")) {
                $idList = explode(',', $this->getParam("ids"));
            } else {
                $idList = [];
            }

            if ($this->getParam("id")) {
                $idList[] = $this->getParam("id");
            }

            $resultData = [];

            foreach ($idList as $id) {
                $resultData[$id] = 0;
            }

            if ($type == "object") {
                $col = "o_id";
            } else {
                $col = "id";
            }
            $sql = "select " . $col . " from " .$type . "s where " . $col . " IN (" . implode(',', $idList) . ")";

            $result = \Pimcore\Db::get()->fetchAll($sql);
            foreach ($result as $item) {
                $id = $item[$col];
                if ($condense) {
                    unset($resultData[$id]);
                } else {
                    $resultData[$id] = 1;
                }
            }
            $this->encoder->encode(["success" => true, "data" => $resultData]);
        } catch (\Exception $e) {
            $this->encoder->encode(["success" => false, "msg" => $e->getMessage()]);
        }
    }

    /** Checks for existence of the given object IDs
     * GET http://[YOUR-DOMAIN]/webservice/rest/object-inquire?apikey=[API-KEY]
     * Parameters:
     *      - id single object ID
     *      - ids comma separated list of object IDs
     * Returns:
     *      - List with true or false for each ID
     */
    public function objectInquireAction()
    {
        $this->inquire("object");
    }

    /** Checks for existence of the given asset IDs
     * GET http://[YOUR-DOMAIN]/webservice/rest/asset-inquire?apikey=[API-KEY]
     * Parameters:
     *      - id single asset ID
     *      - ids comma separated list of asset IDs
     * Returns:
     *      - List with true or false for each ID
     */
    public function assetInquireAction()
    {
        $this->inquire("asset");
    }

    /** Checks for existence of the given document IDs
     * GET http://[YOUR-DOMAIN]/webservice/rest/document-inquire?apikey=[API-KEY]
     * Parameters:
     *      - id single document ID
     *      - ids comma separated list of document IDs
     * Returns:
     *      - List with true or false for each ID
     */
    public function documentInquireAction()
    {
        $this->inquire("document");
    }



    /**
     * Returns a list of all object brick definitions.
     */
    public function objectBricksAction()
    {
        $this->checkUserPermission("classes");

        $list = new Object\Objectbrick\Definition\Listing();
        $bricks = $list->load();

        $result = [];

        foreach ($bricks as $brick) {
            $item = [
                "name" => $brick->getKey()
            ];
            $result[] = $item;
        }

        $this->encoder->encode(["success" => true, "data" => $result]);
    }

    /**
     * Returns a list of all field collection definitions.
     */
    public function fieldCollectionsAction()
    {
        $this->checkUserPermission("classes");

        $list = new Object\Fieldcollection\Definition\Listing();
        $fieldCollections = $list->load();

        $result = [];

        foreach ($fieldCollections as $fc) {
            $item = [
                "name" => $fc->getKey()
            ];
            $result[] = $item;
        }

        $this->encoder->encode(["success" => true, "data" => $result]);
    }


    private static function map($wsData, $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $tmp = [];

                foreach ($value as $subkey => $subvalue) {
                    if (is_array($subvalue)) {
                        $object = new stdClass();
                        $object = self::map($object, $subvalue);
                        ;
                        $tmp[$subkey] = $object;
                    } else {
                        $tmp[$subkey] = $subvalue;
                    }
                }
                $value = $tmp;
            }
            $wsData->$key = $value;
        }

        if ($wsData instanceof Pimcore\Model\Webservice\Data\Object) {
            /** @var Pimcore\Model\Webservice\Data\Object key */
            $wsData->key = Element\Service::getValidKey($wsData->key, "object");
        } elseif ($wsData instanceof Pimcore\Model\Webservice\Data\Document) {
            /** @var Pimcore\Model\Webservice\Data\Document key */
            $wsData->key = Element\Service::getValidKey($wsData->key, "document");
        } elseif ($wsData instanceof Pimcore\Model\Webservice\Data\Asset) {
            /** @var Pimcore\Model\Webservice\Data\Asset $wsData */
            $wsData->filename = Element\Service::getValidKey($wsData->filename, "asset");
        }

        return $wsData;
    }

    public static function fillWebserviceData($class, $data)
    {
        $wsData = new $class();

        return self::map($wsData, $data);
    }


    /** Returns true if this is a DELETE request. Can be overridden by providing a
     * a method=delete parameter.
     * @return bool
     */
    public function isDelete()
    {
        $request = $this->getRequest();
        $overrideMethod = $request->getParam("method");
        if (strtoupper($overrideMethod) == "DELETE") {
            return true;
        }

        return $request->isDelete();
    }

    /** Returns true if this is a GET request. Can be overridden by providing a
     * a method=get parameter.
     * @return bool
     */
    public function isGet()
    {
        $request = $this->getRequest();
        $overrideMethod = $request->getParam("method");
        if (strtoupper($overrideMethod) == "GET") {
            return true;
        }

        return $request->isGet();
    }

    /** Returns true if this is a POST request. Can be overridden by providing a
     * a method=post parameter.
     * @return bool
     */
    public function isPost()
    {
        $request = $this->getRequest();
        $overrideMethod = $request->getParam("method");
        if (strtoupper($overrideMethod) == "POST") {
            return true;
        }

        return $request->isPost();
    }

    /** Returns true if this is a PUT request. Can be overridden by providing a
     * a method=put parameter.
     * @return bool
     */
    public function isPut()
    {
        $request = $this->getRequest();
        $overrideMethod = $request->getParam("method");
        if (strtoupper($overrideMethod) == "PUT") {
            return true;
        }

        return $request->isPut();
    }

    /**
     * Returns the current time.
     */
    public function systemClockAction()
    {
        $this->encoder->encode(["success" => true,
            "data" => ["currentTime" => time()]]);
    }

    /**
     * Returns translations
     */
    public function translationsAction()
    {
        $this->checkUserPermission("translations");
        $type = $this->getParam('type');

        try {
            $params = $this->getRequest()->getQuery();
            $result = $this->service->getTranslations($params['type'], $params);
            $this->encoder->encode(["success" => true, "data" => $result]);
        } catch (\Exception $e) {
            Logger::error($e);
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }
    }


    /**
     * Returns a list of all class definitions.
     */
    public function serverInfoAction()
    {
        $this->checkUserPermission("system_settings");
        $systemSettings = \Pimcore\Config::getSystemConfig()->toArray();
        $system = ["currentTime" => time(),
            "phpCli" => Tool\Console::getPhpCli(),
        ];

        $pimcoreConstants = []; //only Pimcore_ constants -> others might break the \Zend_Encode functionality
        foreach ((array)get_defined_constants() as $constant => $value) {
            if (strpos($constant, 'PIMCORE_') === 0) {
                $pimcoreConstants[$constant] = $value;
            }
        }

        $pimcore = ["version" => \Pimcore\Version::getVersion(),
            "revision" => \Pimcore\Version::getRevision(),
            "instanceIdentifier" => $systemSettings["general"]["instanceIdentifier"],
            "constants" => $pimcoreConstants,
        ];

        $plugins = \Pimcore\ExtensionManager::getPluginConfigs();

        $this->encoder->encode(["success" => true, "system" => $system,
            "pimcore" => $pimcore,
            "plugins" => $plugins,
        ]);
    }



    private function phpinfo_array()
    {
        ob_start();
        phpinfo(-1);

        $pi = preg_replace(
            [
                '#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
                '#<h1>Configuration</h1>#',  "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
                "#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a><h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                "# +#", '#<tr>#', '#</tr>#'],
            [
                '$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
                '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
                "\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
                '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
                '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
                '<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'
            ],
            ob_get_clean()
        );

        $sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
        unset($sections[0]);

        $pi = [];
        foreach ($sections as $section) {
            $n = substr($section, 0, strpos($section, '</h2>'));
            preg_match_all('#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#', $section, $askapache, PREG_SET_ORDER);
            foreach ($askapache as $m) {
                $pi[$n][$m[1]]=(!isset($m[3])||$m[2]==$m[3])?$m[2]:array_slice($m, 2);
            }
        }

        return $pi;
    }

    protected function getQueryParams()
    {
        return $this->getRequest()->getQuery();
    }


    /** Returns the classification store feature definition as JSON. Could be useful to provide separate endpoints
     * for the various sub-configs.
     * @return mixed
     */
    public function classificationstoreDefinitionAction()
    {
        $this->checkUserPermission("classes");

        try {
            if ($this->isGet()) {
                $condition = urldecode($this->getParam("condition"));

                $definition = [];

                $list = new Pimcore\Model\Object\Classificationstore\StoreConfig\Listing();
                if ($condition) {
                    $list->setCondition($condition);
                }
                $list->load();
                $items = $list->getList();

                $stores = [];


                foreach ($items as $item) {
                    $stores[] = $item->getObjectVars();
                }
                $definition["stores"] = $stores;

                $list = new Pimcore\Model\Object\Classificationstore\CollectionConfig\Listing();
                if ($condition) {
                    $list->setCondition($condition);
                }
                $list->load();
                $items = $list->getList();

                $collections = [];


                foreach ($items as $item) {
                    $collections[] = $item->getObjectVars();
                }
                $definition["collections"] = $collections;


                $list = new Pimcore\Model\Object\Classificationstore\GroupConfig\Listing();
                if ($condition) {
                    $list->setCondition($condition);
                }
                $list->load();
                $items = $list->getList();

                $groups = [];


                foreach ($items as $item) {
                    $groups[] = $item->getObjectVars();
                }
                $definition["groups"] = $groups;

                $list = new Pimcore\Model\Object\Classificationstore\KeyConfig\Listing();
                if ($condition) {
                    $list->setCondition($condition);
                }
                $list->load();
                $items = $list->getList();

                $keys = [];

                foreach ($items as $item) {
                    $keys[] = $item->getObjectVars();
                }
                $definition["keys"] = $keys;

                $list = new Pimcore\Model\Object\Classificationstore\CollectionGroupRelation\Listing();
                if ($condition) {
                    $list->setCondition($condition);
                }
                $list->load();
                $items = $list->getList();

                $relations = [];

                /** @var  $item Pimcore\Model\Object\Classificationstore\CollectionGroupRelation */
                foreach ($items as $item) {
                    $relations[] = $item->getObjectVars();
                }

                $definition["collections2groups"] = $relations;

                $list = new Pimcore\Model\Object\Classificationstore\KeyGroupRelation\Listing();
                if ($condition) {
                    $list->setCondition($condition);
                }
                $list->load();
                $items = $list->getList();

                $relations = [];

                foreach ($items as $item) {
                    $relations[] = $item->getObjectVars();
                }
                $definition["groups2keys"] = $relations;



                $this->encoder->encode(["success" => true, "data" => $definition]);
            }
        } catch (\Exception $e) {
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }
        $this->encoder->encode(["success" => false]);
    }

    /** Returns the classification store feature definition as JSON. Could be useful to provide separate endpoints
     * for the various sub-configs.
     * @return mixed
     */
    public function quantityValueUnitDefinitionAction()
    {
        $this->checkUserPermission("classes");

        try {
            if ($this->isGet()) {
                $condition = urldecode($this->getParam("condition"));

                $list = new Object\QuantityValue\Unit\Listing();
                if ($condition) {
                    $list->setCondition($condition);
                }
                $items = $list->load();
                $units = [];


                foreach ($items as $item) {
                    $units[] = $item->getObjectVars();
                }

                $this->encoder->encode(["success" => true, "data" => $units]);
            }
        } catch (\Exception $e) {
            $this->encoder->encode(["success" => false, "msg" => (string) $e]);
        }
        $this->encoder->encode(["success" => false]);
    }
}
