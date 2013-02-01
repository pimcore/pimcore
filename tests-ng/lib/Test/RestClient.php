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
            print($method . " " . $uri . "\n");
        }
        $client->setUri($uri);
        if ($body != null && ($method == "PUT" || $method == "POST")) {
                $client->setRawData($body);
//                print("body: " . $body . "\n");
        }

        $result = $client->request();
        $body = $result->getBody();
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

    /** Creates a new object.
     * @param $object
     * @return mixed json encoded success value and id
     */
    public function createObjectConcrete($object) {
        $wsDocument = Webservice_Data_Mapper::map($object, "Webservice_Data_Object_Concrete_Out", "out");
        $encodedData = json_encode($wsDocument);
        $response = $this->doRequest(self::$baseUrl .  "object/?apikey=" . $this->apikey, "PUT", $encodedData);
        return $response;
    }

}
