<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2013 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 *
 * @author      JA
 */

class Webservice_RestController extends Pimcore_Controller_Action_Webservice {

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

    public function init() {
        parent::init();
        $this->disableViewAutoRender();
        $this->service = new Webservice_Service();
        // initialize json encoder by default, maybe support xml in the near future
        $this->encoder = new Webservice_JsonEncoder();
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
     * @throws Exception
     */
    public function objectAction() {

        $id = $this->getParam("id");
        $success = false;

        try {
            if ($this->isGet()) {
                if ($id) {
                    $object = Object_Abstract::getById($id);
                    if ($object instanceof Object_Folder) {
                        $object = $this->service->getObjectFolderById($id);
                    } else {
                        $object = $this->service->getObjectConcreteById($id);
                    }

                    $this->encoder->encode(array("success" => true, "data" => $object));
                    return;
                }
            } else if ($this->isDelete()) {
                $success = $this->service->deleteObject($id);
                $this->encoder->encode(array("success" => $success));
                return;
            } else if ($this->isPost() || $this->isPut()) {
                $data = file_get_contents("php://input");
                $data = Zend_Json::decode($data);

                $type = $data["type"];
                $id = null;

                if ($data["id"]) {
                    $isUpdate = true;
                    if ($type == "folder") {
                        $wsData = self::fillWebserviceData("Webservice_Data_Object_Folder_In", $data);
                        $success = $this->service->updateObjectFolder($wsData);
                    } else {
                        $wsData = self::fillWebserviceData("Webservice_Data_Object_Concrete_In", $data);
                        $success = $this->service->updateObjectConcrete($wsData);
                    }
                } else {
                    if ($type == "folder") {
                        $wsData = self::fillWebserviceData("Webservice_Data_Object_Folder_In", $data);
                        $id = $this->service->createObjectFolder($wsData);
                    } else {
                        $wsData = self::fillWebserviceData("Webservice_Data_Object_Concrete_In", $data);
                        $id = $this->service->createObjectConcrete($wsData);
                    }
                }

                if (!$isUpdate) {
                    $success = $id != null;
                }

                if ($success && !$isUpdate) {
                    $this->encoder->encode(array("success" => $success, "id" => $id));
                } else {
                    $this->encoder->encode(array("success" => $success));
                }
                return;

            }
        } catch (Exception $e) {
            Logger::error($e);
            $this->encoder->encode(array("success" => false, "msg" => $e));
        }

        throw new Exception("not implemented");
    }

    /** end point for object metadata
     * GET http://[YOUR-DOMAIN]/webservice/rest/object-meta/id/1281?apikey=[API-KEY]
     *      returns the json-encoded class definition for the given object
     *
     */
    public function objectMetaAction() {

        $id = $this->getParam("id");
        $success = false;

        try {
            if ($id) {
                $class = $this->service->getObjectMetadataById($id);
                $this->encoder->encode(array("success" => true, "data" => $class));
                return;
            }
        } catch (Exception $e) {
            $this->encoder->encode(array("success" => false, "message" => $e));
            Logger::error($e);
        }

        $this->encoder->encode(array("success" => false));

    }


    /** end point for the class definition
     * GET http://[YOUR-DOMAIN]/webservice/rest/class/id/1281?apikey=[API-KEY]
     *      returns the class definition for the given class
     *
     */
    public function classAction() {
        try {
            $id = $this->getParam("id");
            if ($id) {
                $class = $this->service->getClassById($id);
                $this->encoder->encode(array("success" => true, "data" => $class));
                return;
            }
        } catch (Exception $e) {
            Logger::error($e);
            $this->encoder->encode(array("success" => false, "msg" => $e));
        }
        $this->encoder->encode(array("success" => false));
    }



    /** end point for the object-brick definition
     * GET http://[YOUR-DOMAIN]/webservice/rest/object-brick/id/abt1?apikey=[API-KEY]
     *      returns the class definition for the given class
     *
     */
    public function objectBrickAction() {
        try {
            $fc = Object_Objectbrick_Definition::getByKey($this->getParam("id"));
            $this->_helper->json(array("success" => true, "data" => $fc));
        } catch (Exception $e) {
            Logger::error($e);
            $this->encoder->encode(array("success" => false, "msg" => $e));
        }
        $this->encoder->encode(array("success" => false));
    }

    /** end point for the field collection definition
     * GET http://[YOUR-DOMAIN]/webservice/rest/field-collection/id/abt1?apikey=[API-KEY]
     *      returns the class definition for the given class
     *
     */
    public function fieldCollectionAction() {
        try {
            $fc = Object_Fieldcollection_Definition::getByKey($this->getParam("id"));
            $this->_helper->json(array("success" => true, "data" => $fc));
        } catch (Exception $e) {
            Logger::error($e);
            $this->encoder->encode(array("success" => false, "msg" => $e));
        }
        $this->encoder->encode(array("success" => false));
    }



    /** GET http://[YOUR-DOMAIN]/webservice/rest/user?apikey=[API-KEY]
     *      returns the json-encoded user data for the current user
     *
     */
    public function userAction() {
        try {

            $object = $this->service->getuser();
            $this->encoder->encode(array("success" => true, "data" => $object));

        } catch (Exception $e) {
            Logger::error($e);
        }
        $this->encoder->encode(array("success" => false));
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
     * @throws Exception
     */
    public function assetAction() {
        $id = $this->getParam("id");
        $success = false;

        try {
            if ($this->isGet()) {
                $asset = Asset::getById($id);

                if ($asset instanceof Asset_Folder) {
                    $object = $this->service->getAssetFolderById($id);
                } else {
                    $object = $this->service->getAssetFileById($id);
                    $light = $this->getParam("light");
                    if ($light) {
                        unset($object->data);
                    }
                }
                $this->encoder->encode(array("success" => true, "data" => $object));
                return;
            } else if ($this->isDelete()) {
                $success = $this->service->deleteAsset($id);
                $this->encoder->encode(array("success" => $success));
                return;
            } else if ($this->isPost() || $this->isPut()) {
                $data = file_get_contents("php://input");
                $data = Zend_Json::decode($data);

                $type = $data["type"];
                $id = null;

                if ($data["id"]) {
                    $isUpdate = true;
                    if ($type == "folder") {
                        $wsData = self::fillWebserviceData("Webservice_Data_Asset_Folder_In", $data);
                        $success = $this->service->updateAssetFolder($wsData);
                    } else {
                        $wsData = self::fillWebserviceData("Webservice_Data_Asset_File_In", $data);
                        $success = $this->service->updateAssetFile($wsData);
                    }
                } else {
                    if ($type == "folder") {
                        $wsData = self::fillWebserviceData("Webservice_Data_Asset_Folder_In", $data);
                        $id = $this->service->createAssetFolder($wsData);
                    } else {
                        $wsData = self::fillWebserviceData("Webservice_Data_Asset_File_In", $data);
                        $id = $this->service->createAssetFile($wsData);
                    }
                }

                if (!$isUpdate) {
                    $success = $id != null;
                }

                if ($success && !$isUpdate) {
                    $this->encoder->encode(array("success" => $success, "data" => array("id" => $id)));
                } else {
                    $this->encoder->encode(array("success" => $success));
                }
                return;

            }
        } catch (Exception $e) {
            Logger::error($e);
            $this->encoder->encode(array("success" => false, "msg" => $e));
        }
        $this->encoder->encode(array("success" => false));
    }

    /** Returns the group/key config as JSON.
     * @return mixed
     */
    public function keyValueDefinitionAction() {

        try {
            if ($this->isGet()) {

                $definition = array();

                $list = new Object_KeyValue_GroupConfig_List();
                $list->load();
                $items = $list->getList();

                $groups = array();

                foreach ($items as $item) {
                    $group = array();
                    $group["id"] = $item->getId();
                    $group["name"] =  $item->getName();
                    if ($item->getDescription()) {
                        $group["description"] =  $item->getDescription();
                    }
                    $groups[] = $group;
                }
                $definition["groups"] = $groups;

                $list = new Object_KeyValue_KeyConfig_List();
                $list->load();
                $items = $list->getList();

                $keys = array();

                foreach ($items as $item) {
                    $key= array();
                    $key['id'] = $item->getId();
                    $key['name'] = $item->getName();
                    if ($item->getDescription()) {
                        $key['description'] = $item->getDescription();
                    }
                    $key['type'] = $item->getType();
                    if ($item->getUnit()) {
                        $key['unit'] = $item->getUnit();
                    }
                    if ($item->getGroup()) {
                        $key['group'] = $item->getGroup();
                    }
                    if ($item->getPossibleValues()) {
                        $key['possiblevalues'] = $item->getPossibleValues();
                    }
                    $keys[] = $key;
                }
                $definition["keys"] = $keys;
                $this->encoder->encode(array("success" => true, "data" => $definition));
            }
        } catch (Exception $e) {
            $this->encoder->encode(array("success" => false, "msg" => $e));
        }
        $this->encoder->encode(array("success" => false));
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
     * @throws Exception
     */
    public function documentAction() {
        $id = $this->getParam("id");
        $success = false;

        try {
            if ($this->isGet()) {
                $doc = Document::getById($id);

                if ($doc) {
                    $type = $doc->getType();
                    $getter = "getDocument" . ucfirst($type) . "ById";

                    if (method_exists($this->service, $getter)) {
                        $object = $this->service->$getter($id);
                    } else {
                        // check if the getter is implemented by a plugin
                        $class = "Webservice_Data_Document_" . ucfirst($type) . "_Out";
                        if (class_exists($class)) {
                            Document_Service::loadAllDocumentFields($doc);
                            $object = Webservice_Data_Mapper::map($doc, $class, "out");
                        } else {
                            throw new Exception("unknown type");
                        }

                    }

                }

                if (!$object) {
                    throw new Exception("could not find document");
                }
                @$this->encoder->encode(array("success" => true, "data" => $object));
                return;
            } else if ($this->isDelete()) {
                $success = $this->service->deleteDocument($id);
                $this->encoder->encode(array("success" => $success));
                return;
            } else if ($this->isPost() || $this->isPut()) {
                $data = file_get_contents("php://input");
                $data = Zend_Json::decode($data);

                $type = $data["type"];
                $id = null;
                $typeUpper = ucfirst($type);
                $className = "Webservice_Data_Document_" . $typeUpper . "_In";

                if ($data["id"]) {
                    $isUpdate = true;
                    $setter = "updateDocument" . $typeUpper;
                    if (!method_exists($this->service, $setter)) {
                        throw new Exception("method does not exist " . $setter);
                    }
                    $wsData = self::fillWebserviceData($className, $data);
                    $success = $this->service->$setter($wsData);

                } else {
                    $setter = "createDocument" . $typeUpper;
                    if (!method_exists($this->service, $setter)) {
                        throw new Exception("method does not exist " . $setter);
                    }
                    $wsData = self::fillWebserviceData($className, $data);
                    $id = $this->service->$setter($wsData);

                }

                if (!$isUpdate) {
                    $success = $id != null;
                }

                if ($success && !$isUpdate) {
                    $this->encoder->encode(array("success" => $success, "id" => $id));
                } else {
                    $this->encoder->encode(array("success" => $success));
                }
                return;

            }

        } catch (Exception $e) {
            $this->encoder->encode(array("success" => false, "msg" => $e));
        }
        $this->encoder->encode(array("success" => false));
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
    public function assetListAction() {
        $condition = $this->getParam("condition");
        $order = $this->getParam("order");
        $orderKey = $this->getParam("orderKey");
        $offset = $this->getParam("offset");
        $limit = $this->getParam("limit");
        $groupBy = $this->getParam("groupBy");
        $result = $this->service->getAssetList($condition, $order, $orderKey, $offset, $limit, $groupBy);
        $this->encoder->encode(array("success" => true, "data" => $result));
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
    public function documentListAction() {
        $condition = urldecode($this->getParam("condition"));
        $order = $this->getParam("order");
        $orderKey = $this->getParam("orderKey");
        $offset = $this->getParam("offset");
        $limit = $this->getParam("limit");
        $groupBy = $this->getParam("groupBy");
        $result = $this->service->getDocumentList($condition, $order, $orderKey, $offset, $limit, $groupBy);
        $this->encoder->encode(array("success" => true, "data" => $result));
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
    public function objectListAction() {
        $condition = urldecode($this->getParam("condition"));
        $order = $this->getParam("order");
        $orderKey = $this->getParam("orderKey");
        $offset = $this->getParam("offset");
        $limit = $this->getParam("limit");
        $groupBy = $this->getParam("groupBy");
        $objectClass = $this->getParam("objectClass");
        $result = $this->service->getObjectList($condition, $order, $orderKey, $offset, $limit, $groupBy, $objectClass);
        $this->encoder->encode(array("success" => true, "data" => $result));
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
    public function objectCountAction() {
        $condition = urldecode($this->getParam("condition"));
        $groupBy = $this->getParam("groupBy");
        $objectClass = $this->getParam("objectClass");
        $params = array();

        if (!empty($condition)) $params["condition"] = $condition;
        if (!empty($groupBy)) $params["groupBy"] = $groupBy;

        $listClassName = "Object_Abstract";
        if(!empty($objectClass)) {
            $listClassName = "Object_" . ucfirst($objectClass);
            if(!Pimcore_Tool::classExists($listClassName)) {
                $listClassName = "Object_Abstract";
            }
        }

        $count = $listClassName::getTotalCount($params);

        $this->encoder->encode(array("success" => true, "data" => array("totalCount" => $count)));
    }


    /** Returns the total number of assets matching the given condition
     *  GET http://[YOUR-DOMAIN]/webservice/rest/asset-count?apikey=[API-KEY]&condition=type%3D%27folder%27
     *
     * Parameters:
     *      - condition
     *      - group by key
     */
    public function assetCountAction() {
        $condition = urldecode($this->getParam("condition"));
        $groupBy = $this->getParam("groupBy");
        $params = array();

        if (!empty($condition)) $params["condition"] = $condition;
        if (!empty($groupBy)) $params["groupBy"] = $groupBy;


        $count = Asset::getTotalCount($params);

        $this->encoder->encode(array("success" => true, "data" => array ("totalCount" => $count)));
    }

    /** Returns the total number of documents matching the given condition
     *  GET http://[YOUR-DOMAIN]/webservice/rest/asset-count?apikey=[API-KEY]&condition=type%3D%27folder%27
     *
     * Parameters:
     *      - condition
     *      - group by key
     */
    public function documentCountAction() {
        $condition = urldecode($this->getParam("condition"));
        $groupBy = $this->getParam("groupBy");
        $params = array();

        if (!empty($condition)) $params["condition"] = $condition;
        if (!empty($groupBy)) $params["groupBy"] = $groupBy;


        $count = Document::getTotalCount($params);

        $this->encoder->encode(array("success" => true, "data" => array("totalCount" => $count)));
    }

    /**
     * Returns a list of all class definitions.
     */
    public function classesAction() {
        $list = new Object_Class_List();
        $classes = $list->load();
        $result = array();

        foreach ($classes as $class) {
            $item = array(
                "id" => $class->getId(),
                "name" => $class->getName()
            );
            $result[] = $item;
        }

        $this->encoder->encode(array("success" => true, "data" => $result));
    }

    /**
     * Returns a list of all object brick definitions.
     */
    public function objectBricksAction() {
        $list = new Object_Objectbrick_Definition_List();
        $bricks = $list->load();

        $result = array();

        foreach ($bricks as $brick) {
            $item = array(
                "name" => $brick->getKey()
            );
            $result[] = $item;
        }

        $this->encoder->encode(array("success" => true, "data" => $result));
    }

    /**
     * Returns a list of all field collection definitions.
     */
    public function fieldCollectionsAction() {
        $list = new Object_Fieldcollection_Definition_List();
        $fieldCollections = $list->load();

        $result = array();

        foreach ($fieldCollections as $fc) {
            $item = array(
                "name" => $fc->getKey()
            );
            $result[] = $item;
        }

        $this->encoder->encode(array("success" => true, "data" => $result));
    }


    private static function map($wsData, $data) {
        foreach($data as $key => $value) {
            if (is_array($value)) {
                $tmp = array();

                foreach ($value as $subkey => $subvalue) {
                    if (is_array($subvalue)) {
                        $object = new stdClass();
                        $tmp[] = self::map($object, $subvalue);
                    } else {
                        $tmp[$subkey] = $subvalue;
                    }
                }
                $value = $tmp;
            }
            $wsData->$key = $value;

        }
        return $wsData;
    }

    public static function fillWebserviceData($class, $data) {
        $wsData = new $class();
        return self::map($wsData, $data);
    }


    /** Returns true if this is a DELETE request. Can be overridden by providing a
     * a method=delete parameter.
     * @return bool
     */
    public function isDelete() {
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
    public function isGet() {
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
    public function isPost() {
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
    public function isPut() {
        $request = $this->getRequest();
        $overrideMethod = $request->getParam("method");
        if (strtoupper($overrideMethod) == "PUT") {
            return true;
        }
        return $request->isPut();
    }



    /**
     * Returns a list of all class definitions.
     */
    public function serverInfoAction() {
        $result = array();
        $pimcore = array();
        $pimcore["version"] = Pimcore_Version::getVersion();
//        $pimcore["svnInfo"] = Pimcore_Version::getSvnInfo();
        $pimcore["revision"] = Pimcore_Version::getRevision();

        $plugins = Pimcore_ExtensionManager::getPluginConfigs();

//        $phpInfo = $this->phpinfo_array();

        $this->encoder->encode(array("success" => true, "pimcore" => $pimcore,
//            "phpinfo" => $phpInfo,
            "plugins" => $plugins
        ));
    }



    private function phpinfo_array()
    {
        ob_start();
        phpinfo(-1);

        $pi = preg_replace(
            array(
                '#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
                '#<h1>Configuration</h1>#',  "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
                "#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a><h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                "# +#", '#<tr>#', '#</tr>#'),
            array(
                '$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
                '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
                    "\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
                '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
                '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
                    '<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'
            ),
            ob_get_clean()
        );

        $sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
        unset($sections[0]);

        $pi = array();
        foreach ($sections as $section)
        {
            $n = substr($section, 0, strpos($section, '</h2>'));
            preg_match_all('#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#', $section, $askapache, PREG_SET_ORDER);
            foreach($askapache as $m)
            {
                $pi[$n][$m[1]]=(!isset($m[3])||$m[2]==$m[3])?$m[2]:array_slice($m,2);
            }
        }

        return $pi;

    }



}
