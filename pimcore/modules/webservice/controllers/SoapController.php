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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Webservice_SoapController extends Pimcore_Controller_Action {



    public function init() {

        if(!$this->_getParam("apikey")){
            throw new Exception("API key missing");
        }

        $userList = new User_List();
        $userList->setCondition("password ='".$this->_getParam("apikey")."'");
        $users = $userList->load();

        if(!is_array($users) or count($users)!==1){
            throw new Exception("API key error");
        }
        $user = $users[0];
        Zend_Registry::set("pimcore_user", $user);

        parent::init();
    }

    public function endpointAction() {

        // disable wsdl cache
        if (PIMCORE_DEVMODE) {
            ini_set("soap.wsdl_cache_enabled", "0");
        }

        // create classmappings
        $classMap = Webservice_Tool::createClassMappings();


        // create wsdl
        // @TODO create a cache here
        $strategy = new Zend_Soap_Wsdl_Strategy_Composite(array(
            "object[]" => "Zend_Soap_Wsdl_Strategy_AnyType"
        ), "Zend_Soap_Wsdl_Strategy_ArrayOfTypeComplex");

        $autodiscover = new Zend_Soap_AutoDiscover($strategy);
        $autodiscover->setClass('Webservice_Service');


        $wsdl = $autodiscover->toXml();
        //TODO: do we really want to normalize class names since we had to introduce request and response objects anyway?
        $wsdl = str_replace("Webservice_Data_", "", $wsdl); // normalize classnames
        $wsdlFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/wsdl.xml";
        file_put_contents($wsdlFile, $wsdl);


        // let's go
        if (isset($_GET["wsdl"])) {
            header("Content-Type: text/xml; charset=utf8");
            echo $wsdl;
        } else {

            define("PIMCORE_ADMIN", true);

            try {
                $server = new Zend_Soap_Server($wsdlFile, array(
                    "cache_wsdl" => false,
                    "soap_version" => SOAP_1_2,
                    "classmap" => $classMap
                ));

                $server->registerFaultException("Exception");
                $server->setClass("Webservice_Service");
                $server->handle();

            }
            catch (Exception $e) {
                logger::log("Soap request failed");
                logger::log($e);
                throw $e;
            }
        }

        exit;
    }
}
