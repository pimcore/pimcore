<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jaichhorn
 * Date: 31.01.13
 * Time: 10:50
 * To change this template use File | Settings | File Templates.
 */
class Test_RestClient {

    const loggingEnabled = true;

    static private $instance = null;

    /**
     * @var bool
     */
    static private $testMode = false;

    static private $host;

    static private $baseUrl;


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

    public function __construct() {
        if (!self::$host || !self::$baseUrl) {
            throw new Exception("configuration missing");
        }

        $this->client = Pimcore_Tool::getHttpClient();
        if (self::$testMode) {
            $this->client->setHeaders("X-pimcore-unit-test-request", "true");
        }
        $this->client->setHeaders("Host", self::$host);

        //TODO make this configurable, maybe by extracting the code and let the user provide the api key

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

        $this->apikey = $user->getPassword();
    }


    static public function getInstance() {
         if (null === self::$instance) {
               self::$instance = new self;
         }
         return self::$instance;
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

    private static function fillWebserviceData($class, $data) {
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
        if (self::loggingEnabled) {
            print("    " . $method . " " . $uri . "\n");
        }
        $client->setUri($uri);
        if ($body != null && ($method == "PUT" || $method == "POST")) {
                $client->setRawData($body);
//                print("    body: " . $body . "\n");
        }

        $result = $client->request();

        $body = $result->getBody();
        print($body . "\n");
        $body = json_decode($body);
        return $body;
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

   public function getObjectList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $objectClass = null, $decode = true) {
        $params = $this->fillParms($condition, $order, $orderKey, $offset, $limit, $groupBy, $objectClass);

       $response = $this->doRequest(self::$baseUrl .  "object-list/?apikey=" . $this->apikey . $params, "GET");

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

        $response = $this->doRequest(self::$baseUrl .  "asset-list/?apikey=" . $this->apikey . $params, "GET");

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


    public function getDocumentList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $decode = true) {
        $params = $this->fillParms($condition, $order, $orderKey, $offset, $limit, $groupBy);

        $response = $this->doRequest(self::$baseUrl .  "document-list/?apikey=" . $this->apikey . $params, "GET");

        $result = array();
        foreach ($response as $item) {
            $wsDocument = self::fillWebserviceData("Webservice_Data_Document_List_Item", $item);
            if (!$decode) {
                $result[] = $wsDocument;
            } else {
                $type = $wsDocument->type;
                $type = "Document_" . ucfirst($type);
                $asset = new $type();
                $wsDocument->reverseMap($asset);
                $result[] = $asset;
            }
        }
        return $result;
    }



     public function getObjectById($id, $decode = true) {
         $response = $this->doRequest(self::$baseUrl .  "object/id/" . $id . "?apikey=" . $this->apikey, "GET");
         $wsDocument = self::fillWebserviceData("Webservice_Data_Object_Concrete_In", $response);

         if (!$decode) {
             return $wsDocument;
         }

         if ($wsDocument->type == "folder") {
             $object = new Object_Folder();
             $wsDocument->reverseMap($object);
             return $object;
        } else if ($wsDocument->type == "object") {
             $classname = "Object_" . ucfirst($wsDocument->className);
             if (Pimcore_Tool::classExists($classname)) {
                 $object = new $classname();

                 if ($object instanceof Object_Concrete) {
                     $wsDocument->reverseMap($object);
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
    public function getDocumentById($id, $decode = true) {
        $response = $this->doRequest(self::$baseUrl .  "document/id/" . $id . "?apikey=" . $this->apikey, "GET");

        if ($response->type == "folder") {
            $wsDocument = self::fillWebserviceData("Webservice_Data_Document_Folder_In", $response);
            if (!$decode) {
                return $wsDocument;
            }
            $doc = new Document_Folder();
            $wsDocument->reverseMap($doc);
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
                $wsDocument->reverseMap($document);
                return $document;
            }
        }
    }


    public function getAssetById($id, $decode = true) {
        $response = $this->doRequest(self::$baseUrl .  "asset/id/" . $id . "?apikey=" . $this->apikey, "GET");


        if ($response->type == "folder") {
            $wsDocument = self::fillWebserviceData("Webservice_Data_Asset_Folder_In", $response);
            if (!$decode) {
                return $wsDocument;
            }
            $asset = new Asset_Folder();
            $wsDocument->reverseMap($asset);
            return $asset;
        } else {
            $wsDocument = self::fillWebserviceData("Webservice_Data_Asset_File_In", $response);
            if (!$decode) {
                return $wsDocument;
            }

            $type = $wsDocument->type;
            if (!empty($type)) {
                $type = "Asset_" . ucfirst($type);
                $asset = new $type();
                $wsDocument->reverseMap($asset);
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
        $response = $this->doRequest(self::$baseUrl .  "document/?apikey=" . $this->apikey, "PUT", $encodedData);
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
        $response = $this->doRequest(self::$baseUrl .  "object/?apikey=" . $this->apikey, "PUT", $encodedData);
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
        $response = $this->doRequest(self::$baseUrl .  "asset/?apikey=" . $this->apikey, "PUT", $encodedData);
        return $response;
    }

    /** Delete object.
     * @param $objectId
     * @return mixed json encoded success value and id
     */
    public function deleteObject($objectId) {
        $response = $this->doRequest(self::$baseUrl .  "object/id/" . $objectId . "?apikey=" . $this->apikey, "DELETE");
        return $response;
    }

    /** Delete asset.
     * @param $assetId
     * @return mixed json encoded success value and id
     */
    public function deleteAsset($assetId) {
        $response = $this->doRequest(self::$baseUrl .  "asset/id/" . $assetId . "?apikey=" . $this->apikey, "DELETE");
        return $response;
    }

    /** Delete document.
     * @param $documentId
     * @return mixed json encoded success value and id
     */
    public function deleteDocument($documentId) {
        $response = $this->doRequest(self::$baseUrl .  "document/id/" . $documentId . "?apikey=" . $this->apikey, "DELETE");
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
        $response = $this->doRequest(self::$baseUrl .  "class/id/" . $id . "?apikey=" . $this->apikey, "GET");
        $wsDocument = self::fillWebserviceData("Webservice_Data_Class_In", $response);

        if (!$decode) {
            return $wsDocument;
        }

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
        $response = $this->doRequest(self::$baseUrl .  "object-meta/id/" . $id . "?apikey=" . $this->apikey, "GET");

        $wsDocument = self::fillWebserviceData("Webservice_Data_Class_In", $response);

        if (!$decode) {
            return $wsDocument;
        }

        $class = new Object_Class();
        $wsDocument->reverseMap($class);
        return $class;
    }


}
