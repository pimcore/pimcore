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

class Reports_SettingsController extends Pimcore_Controller_Action_Admin_Reports {
    
    public function getAction () {
        
        if ($this->getUser()->isAllowed("system_settings")) {
            
            $conf = $this->getConfig();
            
            $response = array(
                "values" => $conf->toArray(),
                "config" => array()
            );

            $this->_helper->json($response);
        }

        $this->_helper->json(false);
    }
    
    public function saveAction () {
        if ($this->getUser()->isAllowed("system_settings")) {
            
            $values = Zend_Json::decode($this->_getParam("data"));

            $config = new Zend_Config($values, true);
            $writer = new Zend_Config_Writer_Xml(array(
                "config" => $config,
                "filename" => PIMCORE_CONFIGURATION_DIRECTORY . "/reports.xml"
            ));
            $writer->write();

            $this->_helper->json(array("success" => true));
        } 
        $this->_helper->json(false);
    }
    
    public function getAnalyticsProfilesAction () {
        
        $credentials = $this->getAnalyticsCredentials();
        if($credentials) {
            $username = $credentials["username"];
            $password = $credentials["password"];
        }
        
        if($this->_getParam("username") && $this->_getParam("password")) {
            $username = $this->_getParam("username");
            $password = $this->_getParam("password");
        }
        
        try {
            $client = Zend_Gdata_ClientLogin::getHttpClient($username, $password, Zend_Gdata_Analytics::AUTH_SERVICE_NAME, Pimcore_Tool::getHttpClient("Zend_Gdata_HttpClient"));
    		$service = new Zend_Gdata_Analytics($client);
    	
    		$result = $service->getAccountFeed();
         
            
            $data = array(
                "data" => array()
            );
            
            foreach ($result as $entry) {
                $data["data"][] = array(
                    "id" => (string) $entry->profileId, 
                    "name" => (string) $entry->accountName . " | " . $entry->title,
                    "trackid" => (string) $entry->webPropertyId,
                    "accountid" => (string) $entry->accountId
                );
            }
            
            $this->_helper->json($data);
        }
        catch (Exception $e) {
           $this->_helper->json(false); 
        }
    }
 
    public function getWebmastertoolsSitesAction () {
        
        $credentials = $this->getWebmastertoolsCredentials();
        if($credentials) {
            $username = $credentials["username"];
            $password = $credentials["password"];
        }
        
        if($this->_getParam("username") && $this->_getParam("password")) {
            $username = $this->_getParam("username");
            $password = $this->_getParam("password");
        }
        
        try {
            $client = Zend_Gdata_ClientLogin::getHttpClient($username, $password, "sitemaps", Pimcore_Tool::getHttpClient("Zend_Gdata_HttpClient"));
    		$service = new Zend_Gdata($client);
            
            $data = $service->getFeed("https://www.google.com/webmasters/tools/feeds/sites/");
            

            foreach ($data->getEntry() as $e) {
                
                $verification = "";
                // get verification filename
                foreach ($e->getExtensionElements() as $d) {
                    $a = $d->getExtensionAttributes();
                    if($a["type"]["value"] == "htmlpage") {
                        $verification = $d->getText();
                        break;
                    }
                }
     
                $sites[] = array(
                    "profile" => (string) $e->getTitle(),
                    "verification" => $verification
                );
            }
            $this->_helper->json(array("data" => $sites));
        }
        catch (Exception $e) {
            $this->_helper->json(false);
        }
    }
}
