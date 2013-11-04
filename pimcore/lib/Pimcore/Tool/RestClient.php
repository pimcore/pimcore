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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Tool_RestClient
{
    protected $loggingEnabled = false;
    protected $testMode = false;
    protected $host;
    protected $baseUrl;
    protected $apikey;
    protected $disableMappingExceptions = false;
    protected $enableProfiling = false;
    protected $condense = false;

    /**
     * @param array $data
     * @return void
     */
    public function setValues($data = array())
    {
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $key => $value) {
                $this->setValue($key, $value);
            }
        }
        return $this;
    }

    /**
     * @param  $key
     * @param  $value
     * @return void
     */
    public function setValue($key, $value)
    {
        $method = "set" . $key;
        if (method_exists($this, $method)) {
            $this->$method($value);
        }
        return $this;
    }

    public function setDisableMappingExceptions($disableMappingExceptions)
    {
        $this->disableMappingExceptions = $disableMappingExceptions;
        return $this;
    }

    public function getDisableMappingExceptions()
    {
        return $this->disableMappingExceptions;
    }

    public function setCondense($condense)
    {
        $this->condense = $condense;
        return $this;
    }

    public function getCondense()
    {
        return $this->condense;
    }

    public function setEnableProfiling($enableProfiling)
    {
        $this->enableProfiling = $enableProfiling;
        return $this;
    }

    public function getEnableProfiling()
    {
        return $this->enableProfiling;
    }


    /** Set the host name.
     * @param $host e.g. pimcore.jenkins.elements.at
     */
    public function setHost($host)
    {
        $this->host = $host;
        $this->client->setHeaders("Host", $host);
        return $this;
    }

    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the base url
     * @param $base e.g. http://pimcore.jenkins.elements.at/webservice/rest/
     */
    public function setBaseUrl($base)
    {
        $this->baseUrl = $base;
        return $this;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Enables the test mode. X-pimcore-unit-test-request=true header will be sent.
     */
    public function enableTestMode()
    {
        $this->client->setHeaders("X-pimcore-unit-test-request", "true");

        if (!$this->getApiKey()) {
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
            $apikey = $user->getApiKey();
            $this->setApiKey($apikey);
        }
        $this->setTestMode(true);
    }

    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;
        return $this;
    }

    public function getTestMode()
    {
        return $this->testMode;
    }

    public function setApiKey($apikey)
    {
        $this->apikey = $apikey;
        return $this;
    }

    public function getApiKey()
    {
        return $this->apikey;
    }

    public function setClient(Zend_Http_Client $client)
    {
        $this->client = $client;
        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function __construct($options = array())
    {
        $this->client = Pimcore_Tool::getHttpClient();
        $this->setValues($options);
    }

    private function map($wsData, $data)
    {
        if (!($data instanceof stdClass)) {
            throw new Pimcore_Tool_RestClient_Exception("Ws data format error");
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $tmp = array();

                foreach ($value as $subkey => $subvalue) {
                    if (is_array($subvalue)) {
                        $object = new stdClass();
                        $tmp[] = $this->map($object, $subvalue);
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

    private function fillWebserviceData($class, $data)
    {
        if (!Pimcore_Tool::classExists($class)) {
            throw new Exception("cannot fill web service data " . $class);
        }
        $wsData = new $class();
        return $this->map($wsData, $data);
    }


    /** Does the actual request.
     * @param $uri
     * @param string $method
     * @param null $body
     * @return mixed
     */
    private function doRequest($uri, $method = "GET", $body = null)
    {
        $client = $this->client;
        $client->setMethod($method);
        if ($this->loggingEnabled) {
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

        if ($result->getHeader('content-type') != 'application/json') {
            echo($body); Exit;
            throw new Pimcore_Tool_RestClient_Exception("No JSON response " . $statusCode . " " . $uri);
        }

        $body = json_decode($body);
        return $body;
    }

    public function setLoggingEnabled($loggingEnabled)
    {
        $this->loggingEnabled = $loggingEnabled;
    }

    public function getLoggingEnabled()
    {
        return $this->loggingEnabled;
    }


    private function fillParms($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $objectClass = null)
    {
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

    public function getObjectList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $decode = true, $objectClass = null)
    {
        $params = $this->fillParms($condition, $order, $orderKey, $offset, $limit, $groupBy, $objectClass);

        $response = $this->doRequest($this->buildEndpointUrl("object-list/") . $params, "GET");

        $response = $response->data;


        if (!is_array($response)) {
            throw new Exception("response is empty");
        }
        $result = array();
        foreach ($response as $item) {
            $wsDocument = $this->fillWebserviceData("Webservice_Data_Object_List_Item", $item);
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
    public function getAssetList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $decode = true)
    {
        $params = $this->fillParms($condition, $order, $orderKey, $offset, $limit, $groupBy);

        $response = $this->doRequest($this->buildEndpointUrl("asset-list/") . $params, "GET");
        $response = $response->data;

        if (!is_array($response)) {
            throw new Exception("response is empty");
        }

        $result = array();
        foreach ($response as $item) {
            $wsDocument = $this->fillWebserviceData("Webservice_Data_Asset_List_Item", $item);
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
                                    $groupBy = null, $decode = true)
    {
        $params = $this->fillParms($condition, $order, $orderKey, $offset, $limit, $groupBy);

        $response = $this->doRequest($this->buildEndpointUrl("document-list/") . $params, "GET");
        $response = $response->data;
        if (!is_array($response)) {
            throw new Exception("response is empty");
        }

        $result = array();
        foreach ($response as $item) {
            $wsDocument = $this->fillWebserviceData("Webservice_Data_Document_List_Item", $item);
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


    public function getProfilingInfo()
    {
        return $this->profilingInfo;
    }

    public function getObjectById($id, $decode = true, $idMapper = null)
    {
        $url = $this->buildEndpointUrl("object/id/" . $id);
        if ($this->getEnableProfiling()) {
            $this->profilingInfo = null;
            $url .= "&profiling=1";
        }

        if ($this->getCondense()) {
            $url .= "&condense=1";
        }

        $response = $this->doRequest($url, "GET");
        if ($this->getEnableProfiling()) {
            $this->profilingInfo = $response->profiling;
        }

        $response = $response->data;


        $wsDocument = $this->fillWebserviceData("Webservice_Data_Object_Concrete_In", $response);

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
                    $wsDocument->reverseMap($object, $this->getDisableMappingExceptions(), $idMapper);
                    $timeConsumed = round(microtime(true) - $curTime, 3) * 1000;

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
    public function getDocumentById($id, $decode = true, $idMapper = null)
    {
        $response = $this->doRequest($this->buildEndpointUrl("document/id/" . $id), "GET");
        $response = $response->data;

        if ($response->type == "folder") {
            $wsDocument = $this->fillWebserviceData("Webservice_Data_Document_Folder_In", $response);
            if (!$decode) {
                return $wsDocument;
            }
            $doc = new Document_Folder();
            $wsDocument->reverseMap($doc, $this->getDisableMappingExceptions(), $idMapper);
            return $doc;
        } else {
            $type = ucfirst($response->type);
            $class = "Webservice_Data_Document_" . $type . "_In";

            $wsDocument = $this->fillWebserviceData($class, $response);
            if (!$decode) {
                return $wsDocument;
            }

            if (!empty($type)) {
                $type = "Document_" . ucfirst($wsDocument->type);
                $document = new $type();
                $wsDocument->reverseMap($document, $this->getDisableMappingExceptions(), $idMapper);
                return $document;
            }
        }
    }


    public function changeExtension($filename, $extension)
    {
        $idx = strrpos($filename, ".");
        if ($idx >= 0) {
            $filename = substr($filename, 0, $idx) . "." . $extension;
        }
        return $filename;
    }

    public function getAssetById($id, $decode = true, $idMapper = null, $light = false, $thumbnail = null, $tolerant = false, $protocol = "http://")
    {
        $uri = $this->buildEndpointUrl("asset/id/" . $id);
        if ($light) {
            $uri .= "&light=1";
        }
        $response = $this->doRequest($uri, "GET");
        $response = $response->data;

        if ($response->type == "folder") {
            $wsDocument = $this->fillWebserviceData("Webservice_Data_Asset_Folder_In", $response);
            if (!$decode) {
                return $wsDocument;
            }
            $asset = new Asset_Folder();
            $wsDocument->reverseMap($asset, $this->getDisableMappingExceptions(), $idMapper);
            return $asset;
        } else {
            $wsDocument = $this->fillWebserviceData("Webservice_Data_Asset_File_In", $response);
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
                $wsDocument->reverseMap($asset, $this->getDisableMappingExceptions(), $idMapper);

                if ($light) {
                    $client = Pimcore_Tool::getHttpClient();
                    $client->setMethod("GET");

                    $assetType = $asset->getType();
                    $data = null;

                    if ($assetType == "image" && strlen($thumbnail) > 0) {
                        // try to retrieve thumbnail first
                        // http://example.com/website/var/tmp/thumb_9__fancybox_thumb
                        $uri = $protocol . $this->getHost() . "/website/var/tmp/thumb_" . $asset->getId() . "__" . $thumbnail;
                        $client->setUri($uri);

                        if ($this->getLoggingEnabled()) {
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
                        $uri = $protocol . $this->getHost() . "/website/var/assets" . $path . $filename;
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
    public function createDocument($document)
    {
        $type = $document->getType();
        $typeUpper = ucfirst($type);
        $className = "Webservice_Data_Document_" . $typeUpper . "_In";

        $wsDocument = Webservice_Data_Mapper::map($document, $className, "out");
        $encodedData = json_encode($wsDocument);
        $response = $this->doRequest($this->buildEndpointUrl("document/"), "PUT", $encodedData);
        return $response;
    }


    /** Creates a new object.
     * @param $object
     * @return mixed json encoded success value and id
     */
    public function createObjectConcrete($object)
    {
        if ($object->getType() == "folder") {
            $documentType = "Webservice_Data_Object_Folder_Out";
        } else {
            $documentType = "Webservice_Data_Object_Concrete_Out";
        }
        $wsDocument = Webservice_Data_Mapper::map($object, $documentType, "out");
        $encodedData = json_encode($wsDocument);
        $response = $this->doRequest($this->buildEndpointUrl("object/"), "PUT", $encodedData);
        return $response;
    }

    /** Creates a new asset.
     * @param $object
     * @return mixed json encoded success value and id
     */
    public function createAsset($asset)
    {
        if ($asset->getType() == "folder") {
            $documentType = "Webservice_Data_Asset_Folder_Out";
        } else {
            $documentType = "Webservice_Data_Asset_File_Out";
        }
        $wsDocument = Webservice_Data_Mapper::map($asset, $documentType, "out");
        $encodedData = json_encode($wsDocument);
        $response = $this->doRequest($this->buildEndpointUrl("asset/"), "PUT", $encodedData);
        $response = $response->data;
        return $response;
    }

    /** Delete object.
     * @param $objectId
     * @return mixed json encoded success value and id
     */
    public function deleteObject($objectId)
    {
        $response = $this->doRequest($this->buildEndpointUrl("object/id/" . $objectId), "DELETE");
        return $response;
    }

    /** Delete asset.
     * @param $assetId
     * @return mixed json encoded success value and id
     */
    public function deleteAsset($assetId)
    {
        $response = $this->doRequest($this->buildEndpointUrl("asset/id/" . $assetId), "DELETE");
        return $response;
    }

    /** Delete document.
     * @param $documentId
     * @return mixed json encoded success value and id
     */
    public function deleteDocument($documentId)
    {
        $response = $this->doRequest($this->buildEndpointUrl("document/id/" . $documentId), "DELETE");
        return $response;
    }

    /** Creates a new object folder.
     * @param $objectFolder object folder.
     * @return mixed
     */
    public function createObjectFolder($objectFolder)
    {
        return $this->createObjectConcrete($objectFolder);
    }


    /** Creates a new document folder.
     * @param $objectFolder document folder.
     * @return mixed
     */
    public function createDocumentFolder($documentFolder)
    {
        return $this->createDocument($documentFolder);
    }


    /** Creates a new asset folder.
     * @param $assetFolder document folder.
     * @return mixed
     */
    public function createAssetFolder($assetFolder)
    {
        return $this->createAsset($assetFolder);
    }


    /** Returns class information for the class with the given id.
     * @param $id
     * @param bool $decode
     * @return Object_Concrete|Object_Folder
     * @throws Exception
     */
    public function getClassById($id, $decode = true)
    {
        $response = $this->doRequest($this->buildEndpointUrl("class/id/" . $id), "GET");
        $responseData = $response->data;

        if (!$decode) {
            return $response;
        }

        $wsDocument = $this->fillWebserviceData("Webservice_Data_Class_In", $responseData);

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
    public function getObjectMetaById($id, $decode = true)
    {
        $response = $this->doRequest($this->buildEndpointUrl("object-meta/id/" . $id), "GET");
        $response = $response->data;

        $wsDocument = $this->fillWebserviceData("Webservice_Data_Class_In", $response);

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
    public function getKeyValueDefinition()
    {
        $response = $this->doRequest($this->buildEndpointUrl("key-value-definition"), "GET");
        $response = $response->data;

        return $response;
    }


    public function getAssetCount($condition = null, $groupBy = null)
    {
        $params = $this->fillParms($condition, null, null, null, null, $groupBy, null);

        $response = (array)$this->doRequest($this->buildEndpointUrl("asset-count") . $params, "GET");

        if (!$response || !$response["success"]) {
            throw new Exception("Could not retrieve asset count");
        }
        return $response["data"]->totalCount;
    }

    public function getDocumentCount($condition = null, $groupBy = null)
    {
        $params = $this->fillParms($condition, null, null, null, null, $groupBy, null);

        $response = (array)$this->doRequest($this->buildEndpointUrl("document-count") . $params, "GET");
        if (!$response || !$response["success"]) {
            throw new Exception("Could not retrieve document count");
        }
        return $response["data"]->totalCount;
    }

    public function getObjectCount($condition = null, $groupBy = null, $objectClass = null)
    {
        $params = $this->fillParms($condition, null, null, null, null, $groupBy, $objectClass);

        $response = (array)$this->doRequest($this->buildEndpointUrl("object-count") . $params, "GET");
        if (!$response || !$response["success"]) {
            throw new Exception("Could not retrieve object count");
        }
        return $response["data"]->totalCount;
    }

    /** Returns the current user
     * @return mixed
     */
    public function getUser()
    {
        $response = $this->doRequest($this->buildEndpointUrl("user"), "GET");
        $response = array("success" => true, "data" => $response->data);
        return $response;
    }

    public function getFieldCollections()
    {
        $response = $this->doRequest($this->buildEndpointUrl("field-collections"), "GET");

        return $response;
    }

    public function getFieldCollection($id)
    {
        $response = $this->doRequest($this->buildEndpointUrl("field-collection/id/" . $id), "GET");

        return $response;
    }


    /** Returns a list of defined classes
     * @return mixed
     */
    public function getClasses()
    {
        $response = $this->doRequest($this->buildEndpointUrl("classes"), "GET");

        return $response;
    }

    /** Returns a list of defined object bricks
     * @return mixed
     */
    public function getObjectBricks()
    {
        $response = $this->doRequest($this->buildEndpointUrl("object-bricks"), "GET");
        return $response;
    }

    /** Returns the given object brick definition
     * @param $id
     * @return mixed
     */
    public function getObjectBrick($id)
    {
        $response = $this->doRequest($this->buildEndpointUrl("object-brick/id/" . $id), "GET");

        return $response;
    }

    /** Returns the current server time
     * @return mixed
     */
    public function getCurrentTime()
    {
        $response = $this->doRequest($this->buildEndpointUrl("system-clock"), "GET");

        return $response;
    }

    /** Returns a list of image thumbnail configurations.
     * @return mixed
     */
    public function getImageThumbnails()
    {
        $response = $this->doRequest($this->buildEndpointUrl("image-thumbnails"), "GET");
        return $response;
    }

    /** Returns the image thumbnail configuration with the given ID.
     * @param $id
     * @return mixed
     */
    public function getImageThumbnail($id)
    {
        $response = $this->doRequest($this->buildEndpointUrl("image-thumbnail/id/" . $id), "GET");
        return $response;
    }

    /**
     * Returns: server-info including pimcore version, current time and extension data.
     * @return mixed
     */
    public function getServerInfo()
    {
        $url = $this->buildEndpointUrl("server-info");
        $response = $this->doRequest($this->buildEndpointUrl("server-info"), "GET");
        return $response;
    }

    public function buildEndpointUrl($customUrlPath,$params = array())
    {
        $url = $this->getBaseUrl() . $customUrlPath . "?apikey=" . $this->getApiKey();
        if(!empty($params)){
            $url .= '&' . http_build_query($params);
        }
        return $url;
    }
}
