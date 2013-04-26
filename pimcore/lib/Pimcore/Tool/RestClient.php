<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jaichhorn
 * Date: 31.01.13
 * Time: 10:50
 * To change this template use File | Settings | File Templates.
 */
class Pimcore_Tool_RestClient {

    static private $loggingEnabled = false;

    static private $instance = null;

    /**
     * @var bool
     */
    static private $testMode = false;

    static private $host;

    public static function setDisableMappingExceptions($disableMappingExceptions)
    {
        self::$disableMappingExceptions = $disableMappingExceptions;
    }

    public static function getDisableMappingExceptions()
    {
        return self::$disableMappingExceptions;
    }

    static private $baseUrl;

    static private $apikey;

    static private $disableMappingExceptions = false;

    static private $enableProfiling = false;

    static private $condense = false;

    public static function setCondense($condense)
    {
        self::$condense = $condense;
    }

    public static function setEnableProfiling($enableProfiling)
    {
        self::$enableProfiling = $enableProfiling;
    }

    public static function getEnableProfiling()
    {
        return self::$enableProfiling;
    }


    /** Set the host name.
     * @param $host e.g. pimcore.jenkins.elements.at
     */
    public static function setHost($host) {
        self::$host = $host;
    }

    /**
     * Set the base url
     * @param $base e.g. http://pimcore.jenkins.elements.at/webservice/rest/
     */
    public static function setBaseUrl($base) {
        self::$baseUrl = $base;
    }

    /**
     * Enables the test mode. X-pimcore-unit-test-request=true header will be sent.
     */
    public static function enableTestMode() {
        self::$testMode = true;
    }

    public static function setApiKey($apikey) {
        self::$apikey = $apikey;
    }

    public function __construct() {
        if (!self::$host || !self::$baseUrl) {
            throw new Exception("configuration missing");
        }

        $this->client = Pimcore_Tool::getHttpClient();
        $apikey = self::$apikey;

        if (self::$testMode) {
            $this->client->setHeaders("X-pimcore-unit-test-request", "true");

            if (!$apikey) {
                $username = "rest";
                $password = $username;

                $user = User::getByName("$username");

                if (!$user) {
                    $user = User::create(array(
                        "parentId" => 0,
                        "username" => "rest",
                        "password" => Pimcore_Tool_Authentication::getPasswordHash($username, $username),
                        "active" => true
                    ));
                    $user->setAdmin(true);
                    $user->save();
                }
                $apikey = $user->getPassword();
            }
        }
        $this->client->setHeaders("Host", self::$host);

        self::$apikey = $apikey;;
    }


    static public function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private static function map($wsData, $data) {
        if (!($data instanceof stdClass)) {
            throw new Pimcore_Tool_RestClient_Exception("Ws data format error");
        }

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

    private static function fillWebserviceData($class, $data) {
        if (!Pimcore_Tool::classExists($class)) {
            throw new Exception("cannot fill web service data " . $class);
        }
        $wsData = new $class();
        return self::map($wsData, $data);
    }


    /** Does the actual request.
     * @param $uri
     * @param string $method
     * @param null $body
     * @return mixed
     */
    private function doRequest($uri, $method = "GET", $body = null) {
        $client = $this->client;
        $client->setMethod($method);
        if (self::$loggingEnabled) {
            print("    " . $method . " " . $uri . "\n");
        }
        $client->setUri($uri);
        if ($body != null && ($method == "PUT" || $method == "POST")) {
            $client->setRawData($body);
//                print("    body: " . $body . "\n");
        }

        $result = $client->request();

        $body = $result->getBody();
        $statusCode = $result->getStatus();
        if ($statusCode != 200) {
            throw new Pimcore_Tool_RestClient_Exception("Status code " . $statusCode . " " . $uri);
        }
        $body = json_decode($body);
        return $body;
    }

    public static function setLoggingEnabled($loggingEnabled)
    {
        self::$loggingEnabled = $loggingEnabled;
    }


    private function fillParms($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $objectClass = null) {
        $params = "";
        if ($condition) {
            $params .= "&condition=" . urlencode($condition);
        }

        if ($order) {
            $params .= "&order=" . $order;
        }

        if ($orderKey) {
            $params .= "&orderKey=" . $orderKey;
        }

        if ($offset) {
            $params .= "&offset=" . $offset;
        }

        if ($limit) {
            $params .= "&limit=" . $limit;
        }

        if ($groupBy) {
            $params .= "&groupBy=" . $groupBy;
        }

        if ($objectClass) {
            $params .= "&objectClass=" . $objectClass;
        }
        return $params;
    }

    public function getObjectList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $decode = true, $objectClass = null) {
        $params = $this->fillParms($condition, $order, $orderKey, $offset, $limit, $groupBy, $objectClass);

        $response = $this->doRequest(self::$baseUrl .  "object-list/?apikey=" . self::$apikey . $params, "GET");

        $response = $response->data;


        if (!is_array($response)) {
            throw new Exception("response is empty");
        }
        $result = array();
        foreach ($response as $item) {
            $wsDocument = self::fillWebserviceData("Webservice_Data_Object_List_Item", $item);
            if (!$decode) {
                $result[] = $wsDocument;
            } else {
                $object = new Object_Abstract();
                $wsDocument->reverseMap($object);
                $result[] = $object;
            }
        }
        return $result;
    }

    /**
     * @param null $condition
     * @param null $order
     * @param null $orderKey
     * @param null $offset
     * @param null $limit
     * @param null $groupBy
     * @param bool $decode
     * @return array
     */
    public function getAssetList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $decode = true) {
        $params = $this->fillParms($condition, $order, $orderKey, $offset, $limit, $groupBy);

        $response = $this->doRequest(self::$baseUrl .  "asset-list/?apikey=" . self::$apikey . $params, "GET");
        $response = $response->data;

        if (!is_array($response)) {
            throw new Exception("response is empty");
        }

        $result = array();
        foreach ($response as $item) {
            $wsDocument = self::fillWebserviceData("Webservice_Data_Asset_List_Item", $item);
            if (!$decode) {
                $result[] = $wsDocument;
            } else {
                $type = $wsDocument->type;
                $type = "Asset_" . ucfirst($type);
                $asset = new $type();
                $wsDocument->reverseMap($asset);
                $result[] = $asset;
            }
        }
        return $result;
    }


    public function getDocumentList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null,
                                    $groupBy = null, $decode = true) {
        $params = $this->fillParms($condition, $order, $orderKey, $offset, $limit, $groupBy);

        $response = $this->doRequest(self::$baseUrl .  "document-list/?apikey=" . self::$apikey . $params, "GET");
        $response = $response->data;
        if (!is_array($response)) {
            throw new Exception("response is empty");
        }

        $result = array();
        foreach ($response as $item) {
            $wsDocument = self::fillWebserviceData("Webservice_Data_Document_List_Item", $item);
            if (!$decode) {
                $result[] = $wsDocument;
            } else {
                $type = $wsDocument->type;
                $type = "Document_" . ucfirst($type);

                if (!Pimcore_Tool::classExists($type)) {
                    throw new Exception("Class " . $type . " does not exist");
                }

                $document = new $type();
                $wsDocument->reverseMap($document);
                $result[] = $document;
            }
        }
        return $result;
    }


    public function getProfilingInfo() {
        return $this->profilingInfo;
    }

    public function getObjectById($id, $decode = true, $idMapper = null) {
        $url = self::$baseUrl .  "object/id/" . $id . "?apikey=" . self::$apikey;
        if (self::$enableProfiling) {
            $this->profilingInfo = null;
            $url .= "&profiling=1";
        }

        if (self::$condense) {
            $url .= "&condense=1";
        }

        $response = $this->doRequest($url, "GET");
        if (self::$enableProfiling) {
            $this->profilingInfo = $response->profiling;
        }

        $response = $response->data;


        $wsDocument = self::fillWebserviceData("Webservice_Data_Object_Concrete_In", $response);

        if (!$decode) {
            return $wsDocument;
        }

        if ($wsDocument->type == "folder") {
            $object = new Object_Folder();
            $wsDocument->reverseMap($object);
            return $object;
        } else if ($wsDocument->type == "object" || $wsDocument->type == "variant") {
            $classname = "Object_" . ucfirst($wsDocument->className);
            // check for a mapped class
            $classname = Pimcore_Tool::getModelClassMapping($classname);

            if (Pimcore_Tool::classExists($classname)) {
                $object = new $classname();

                if ($object instanceof Object_Concrete) {
                    $curTime = microtime(true);
                    $wsDocument->reverseMap($object, self::$disableMappingExceptions, $idMapper);
                    $timeConsumed = round(microtime(true) - $curTime,3)*1000;

                    if ($this->profilingInfo) {
                        $this->profilingInfo->reverse = $timeConsumed;
                    }
                    return $object;
                } else {
                    throw new Exception("Unable to decode object, could not instantiate Object with given class name [ $classname ]");
                }
            } else {
                throw new Exception("Unable to deocode object, class [" . $classname . "] does not exist");
            }

        }

    }

    /** Gets a document by id.
     * @param $id id.
     * @param bool
     * @return Document_Folder
     */
    public function getDocumentById($id, $decode = true, $idMapper = null) {
        $response = $this->doRequest(self::$baseUrl .  "document/id/" . $id . "?apikey=" . self::$apikey, "GET");
        $response = $response->data;

        if ($response->type == "folder") {
            $wsDocument = self::fillWebserviceData("Webservice_Data_Document_Folder_In", $response);
            if (!$decode) {
                return $wsDocument;
            }
            $doc = new Document_Folder();
            $wsDocument->reverseMap($doc, self::$disableMappingExceptions, $idMapper);
            return $doc;
        } else {
            $type = ucfirst($response->type);
            $class = "Webservice_Data_Document_" . $type . "_In";

            $wsDocument = self::fillWebserviceData($class, $response);
            if (!$decode) {
                return $wsDocument;
            }

            if (!empty($type)) {
                $type = "Document_" . ucfirst($wsDocument->type);
                $document = new $type();
                $wsDocument->reverseMap($document, self::$disableMappingExceptions, $idMapper);
                return $document;
            }
        }
    }


    public function changeExtension($filename, $extension) {
        $idx = strrpos($filename, ".");
        if ($idx >= 0) {
            $filename = substr($filename, 0, $idx) . "." . $extension;
        }
        return $filename;
    }

    public function getAssetById($id, $decode = true, $idMapper = null, $light = false, $thumbnail = null, $tolerant = false, $protocol = "http://") {
        $uri = self::$baseUrl .  "asset/id/" . $id . "?apikey=" . self::$apikey;
        if ($light) {
            $uri .= "&light=1";
        }
        $response = $this->doRequest($uri, "GET");
        $response = $response->data;

        if ($response->type == "folder") {
            $wsDocument = self::fillWebserviceData("Webservice_Data_Asset_Folder_In", $response);
            if (!$decode) {
                return $wsDocument;
            }
            $asset = new Asset_Folder();
            $wsDocument->reverseMap($asset, self::$disableMappingExceptions, $idMapper);
            return $asset;
        } else {
            $wsDocument = self::fillWebserviceData("Webservice_Data_Asset_File_In", $response);
            if (!$decode) {
                return $wsDocument;
            }

            $type = $wsDocument->type;
            if (!empty($type)) {
                $type = "Asset_" . ucfirst($type);
                if (!Pimcore_Tool::classExists($type)) {
                    throw new Exception("Asset class " . $type . " does not exist");
                }

                $asset = new $type();
                $wsDocument->reverseMap($asset, self::$disableMappingExceptions, $idMapper);

                if ($light) {
                    $client = Pimcore_Tool::getHttpClient();
                    $client->setMethod("GET");

                    $assetType = $asset->getType();
                    $data = null;

                    if  ($assetType == "image" && strlen($thumbnail) > 0) {
                        // try to retrieve thumbnail first
                        // http://frischeis.pim.elements.pm/website/var/tmp/thumb_9__fancybox_thumb
                        $uri = $protocol . self::$host . "/website/var/tmp/thumb_" . $asset->getId() . "__" . $thumbnail;
                        $client->setUri($uri);

                        if (self::$loggingEnabled) {
                            print("    =>" . $uri . "\n");
                        }

                        $result = $client->request();
                        if ($result->getStatus() == 200) {
                            $data = $result->getBody();
                        }
                        $mimeType = $result->getHeader("Content-Type");

                        $filename = $asset->getFilename();

                        switch ($mimeType) {
                            case "image/tiff":
                                $filename = $this->changeExtension($filename, "tiff");
                                break;
                            case "image/jpeg":
                                $filename = $this->changeExtension($filename, "jpg");
                                break;
                            case "image/gif":
                                $filename = $this->changeExtension($filename, "gif");
                                break;
                            case "image/png":
                                $filename = $this->changeExtension($filename, "png");
                                break;

                        }

                        Logger::debug("mimeType: " . $mimeType);
                        $asset->setFilename($filename);
                    }

                    if (!$data) {
                        $path = $wsDocument->path;
                        $filename = $wsDocument->filename;
                        $uri = $protocol . self::$host . "/website/var/assets" . $path . $filename;
                        $client->setUri($uri);
                        $result = $client->request();
                        if ($result->getStatus() != 200 && !$tolerant) {
                            throw new Exception("Could not retrieve asset");
                        }
                        $data = $result->getBody();
                    }
                    $asset->setData($data);
                }

                return $asset;
            }
        }
    }



    /** Creates a new document.
     * @param $document
     * @return mixed json encoded success value and id
     */
    public function createDocument($document) {
        $type = $document->getType();
        $typeUpper = ucfirst($type);
        $className = "Webservice_Data_Document_" . $typeUpper . "_In";

        $wsDocument = Webservice_Data_Mapper::map($document, $className, "out");
        $encodedData = json_encode($wsDocument);
        $response = $this->doRequest(self::$baseUrl .  "document/?apikey=" . self::$apikey, "PUT", $encodedData);
        return $response;
    }


    /** Creates a new object.
     * @param $object
     * @return mixed json encoded success value and id
     */
    public function createObjectConcrete($object) {
        if ($object->getType() == "folder") {
            $documentType = "Webservice_Data_Object_Folder_Out";
        } else {
            $documentType = "Webservice_Data_Object_Concrete_Out";
        }
        $wsDocument = Webservice_Data_Mapper::map($object, $documentType, "out");
        $encodedData = json_encode($wsDocument);
        $response = $this->doRequest(self::$baseUrl .  "object/?apikey=" . self::$apikey, "PUT", $encodedData);
        return $response;
    }

    /** Creates a new asset.
     * @param $object
     * @return mixed json encoded success value and id
     */
    public function createAsset($asset) {
        if ($asset->getType() == "folder") {
            $documentType = "Webservice_Data_Asset_Folder_Out";
        } else {
            $documentType = "Webservice_Data_Asset_File_Out";
        }
        $wsDocument = Webservice_Data_Mapper::map($asset, $documentType, "out");
        $encodedData = json_encode($wsDocument);
        $response = $this->doRequest(self::$baseUrl .  "asset/?apikey=" . self::$apikey, "PUT", $encodedData);
        $response = $response->data;
        var_dump($response);
        return $response;
    }

    /** Delete object.
     * @param $objectId
     * @return mixed json encoded success value and id
     */
    public function deleteObject($objectId) {
        $response = $this->doRequest(self::$baseUrl .  "object/id/" . $objectId . "?apikey=" . self::$apikey, "DELETE");
        return $response;
    }

    /** Delete asset.
     * @param $assetId
     * @return mixed json encoded success value and id
     */
    public function deleteAsset($assetId) {
        $response = $this->doRequest(self::$baseUrl .  "asset/id/" . $assetId . "?apikey=" . self::$apikey, "DELETE");
        return $response;
    }

    /** Delete document.
     * @param $documentId
     * @return mixed json encoded success value and id
     */
    public function deleteDocument($documentId) {
        $response = $this->doRequest(self::$baseUrl .  "document/id/" . $documentId . "?apikey=" . self::$apikey, "DELETE");
        return $response;
    }

    /** Creates a new object folder.
     * @param $objectFolder object folder.
     * @return mixed
     */
    public function createObjectFolder($objectFolder) {
        return $this->createObjectConcrete($objectFolder);
    }


    /** Creates a new document folder.
     * @param $objectFolder document folder.
     * @return mixed
     */
    public function createDocumentFolder($documentFolder) {
        return $this->createDocument($documentFolder);
    }


    /** Creates a new asset folder.
     * @param $assetFolder document folder.
     * @return mixed
     */
    public function createAssetFolder($assetFolder) {
        return $this->createAsset($assetFolder);
    }


    /** Returns class information for the class with the given id.
     * @param $id
     * @param bool $decode
     * @return Object_Concrete|Object_Folder
     * @throws Exception
     */
    public function getClassById($id, $decode = true) {
        $response = $this->doRequest(self::$baseUrl .  "class/id/" . $id . "?apikey=" . self::$apikey, "GET");
        $responseData = $response->data;

        if (!$decode) {
            return $response;
        }

        $wsDocument = self::fillWebserviceData("Webservice_Data_Class_In", $responseData);

        $class = new Object_Class();
        $wsDocument->reverseMap($class);
        return $class;
    }


    /** Returns class information for the class with the given id.
     * @param $id
     * @param bool $decode
     * @return Object_Concrete|Object_Folder
     * @throws Exception
     */
    public function getObjectMetaById($id, $decode = true) {
        $response = $this->doRequest(self::$baseUrl .  "object-meta/id/" . $id . "?apikey=" . self::$apikey, "GET");
        $response = $response->data;

        $wsDocument = self::fillWebserviceData("Webservice_Data_Class_In", $response);

        if (!$decode) {
            return $wsDocument;
        }

        $class = new Object_Class();
        $wsDocument->reverseMap($class);
        return $class;
    }

    /** Returns the key value definition
     * @return mixed
     */
    public function getKeyValueDefinition() {
        $response = $this->doRequest(self::$baseUrl .  "key-value-definition?apikey=" . self::$apikey, "GET");
        $response = $response->data;

        return $response;
    }


    public function getAssetCount($condition = null, $groupBy = null) {
        $params = $this->fillParms($condition, null, null, null, null, $groupBy, null);

        $response = (array) $this->doRequest(self::$baseUrl .  "asset-count/?apikey=" . self::$apikey . $params, "GET");

        if (!$response || !$response["success"]) {
            throw new Exception("Could not retrieve asset count");
        }
        return $response["data"]->totalCount;
    }

    public function getDocumentCount($condition = null, $groupBy = null) {
        $params = $this->fillParms($condition, null, null, null, null, $groupBy, null);

        $response = (array) $this->doRequest(self::$baseUrl .  "document-count/?apikey=" . self::$apikey . $params, "GET");
        if (!$response || !$response["success"]) {
            throw new Exception("Could not retrieve document count");
        }
        return $response["data"]->totalCount;
    }

    public function getObjectCount($condition = null, $groupBy = null, $objectClass = null) {
        $params = $this->fillParms($condition, null, null, null, null, $groupBy, $objectClass);

        $response = (array) $this->doRequest(self::$baseUrl .  "object-count/?apikey=" . self::$apikey . $params, "GET");
        if (!$response || !$response["success"]) {
            throw new Exception("Could not retrieve object count");
        }
        return $response["data"]->totalCount;
    }

    /** Returns the current user
     * @return mixed
     */
    public function getUser() {
        $url = self::$baseUrl .  "user?apikey=" . self::$apikey;
        $response = $this->doRequest($url, "GET");
        $response = array("success" => true, "data" => $response->data);

        return $response;
    }

    public function getFieldCollections() {
        $url = self::$baseUrl .  "field-collections?apikey=" . self::$apikey;
        $response = $this->doRequest($url, "GET");

        return $response;
    }

    public function getFieldCollection($id) {
        $url = self::$baseUrl .  "field-collection/id/" . $id . "?apikey=" . self::$apikey;
        $response = $this->doRequest($url, "GET");

        return $response;
    }


    /** Returns a list of defined classes
     * @return mixed
     */
    public function getClasses() {
        $url = self::$baseUrl .  "classes?apikey=" . self::$apikey;
        $response = $this->doRequest($url, "GET");

        return $response;
    }

    /** Returns a list of defined object bricks
     * @return mixed
     */
    public function getObjectBricks() {
        $url = self::$baseUrl .  "object-bricks?apikey=" . self::$apikey;
        $response = $this->doRequest($url, "GET");

        return $response;
    }

    /** Returns the given object brick definition
     * @param $id
     * @return mixed
     */
    public function getObjectBrick($id) {
        $url = self::$baseUrl .  "object-brick/id/" . $id . "?apikey=" . self::$apikey;
        $response = $this->doRequest($url, "GET");

        return $response;
    }

    /** Returns the current server time
     * @return mixed
     */
    public function getCurrentTime() {
        $url = self::$baseUrl .  "system-clock?apikey=" . self::$apikey;
        $response = $this->doRequest($url, "GET");

        return $response;
    }

    /** Returns a list of image thumbnail configurations.
     * @return mixed
     */
    public function getImageThumbnails() {
        $url = self::$baseUrl .  "image-thumbnails?apikey=" . self::$apikey;
        $response = $this->doRequest($url, "GET");
        return $response;
    }

    /** Returns the image thumbnail configuration with the given ID.
     * @param $id
     * @return mixed
     */
    public function getImageThumbnail($id) {
        $url = self::$baseUrl .  "image-thumbnail/id/". $id . "?apikey=" . self::$apikey;
        $response = $this->doRequest($url, "GET");
        return $response;
    }
}
