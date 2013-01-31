<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jaichhorn
 * Date: 31.01.13
 * Time: 10:50
 * To change this template use File | Settings | File Templates.
 */
class Test_RestClient {

    static private $instance = null;

    static private $sendTestHeader = true;

    public function __construct() {
        $conf = Zend_Registry::get("pimcore_config_test");

        $this->baseurl = "http://" . $conf->rest->host . $conf->rest->base;

        $this->client = Pimcore_Tool::getHttpClient();
        if (self::$sendTestHeader) {
            $this->client->setHeaders("X-pimcore-unit-test-request", "true");
        }
        $this->client->setHeaders("Host", $conf->rest->host);
        $cookie = new Zend_Http_Cookie("XDEBUG_SESSION", "JOSEF_PHPSTORM", $conf->rest->host);
        $this->client->setCookie($cookie);

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

    public static function fillWebserviceData($class, $data) {
        $wsData = new $class();
        return self::map($wsData, $data);
    }


    public function getObjectList($limit = 10) {
        $client = $this->client;
        $client->setMethod("GET");
        $uri = $this->baseurl .  "object-list/?apikey=" . $this->apikey . "&limit=" . $limit;
        $client->setUri($uri);
        $result = $client->request();
        $body = $result->getBody();
        $body = json_decode($body);
        $result = array();
        foreach ($body as $item) {
            $wsDocument = self::fillWebserviceData("Webservice_Data_Object_List_Item", $item);
            $object = new Object_Abstract();
            $wsDocument->reverseMap($object);
            $result[] = $object;
        }
        return $result;
    }

     public function getObjectById($id) {
         $client = $this->client;
         $client->setMethod("GET");
         $uri = $this->baseurl .  "object/id/" . $id . "?apikey=" . $this->apikey;
         $client->setUri($uri);
         $result = $client->request();
         $body = json_decode($result->getBody());

         $wsDocument = self::fillWebserviceData("Webservice_Data_Object_Concrete_In", $body);

         if ($wsDocument->type == "folder") {
             $object = new Object_Folder();
             $wsDocument->reverseMap($object);
             return $object;
        }

    }

}
