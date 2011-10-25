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

class Webservice_SoapController extends Pimcore_Controller_Action_Webservice {

 
    public function endpointAction() {

        // disable wsdl cache
        if (PIMCORE_DEVMODE) {
            ini_set("soap.wsdl_cache_enabled", "0");
        }

        // create classmappings
        $classMap = Webservice_Tool::createClassMappings();
//        p_r($classMap); die();


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
        chmod($wsdlFile, 0766);


        // let's go
        if (isset($_GET["wsdl"])) {
            header("Content-Type: text/xml; charset=utf8");
            echo $wsdl;
        } else {

            Pimcore::setAdminMode();
            Document::setHideUnpublished(false);
            Object_Abstract::setHideUnpublished(false);
            Object_Abstract::setGetInheritedValues(false);

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
                Logger::log("Soap request failed");
                Logger::log($e);
                throw $e;
            }
        }

        exit;
    }
}
