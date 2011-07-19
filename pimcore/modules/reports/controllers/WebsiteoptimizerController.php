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

class Reports_WebsiteoptimizerController extends Pimcore_Controller_Action_Admin_Reports {
    
    public function init () {
        parent::init();
        
        $credentials = $this->getAnalyticsCredentials();
        if(!$credentials) {
            die("Analytics not configured");
        }
    }
    
    public function getDocumentVersionsAction () {
        
        if ($this->_getParam("id")) {
            $doc = Document::getById($this->_getParam("id"));
            $versions = $doc->getVersions();
            $publicVersions = array();            
            
            foreach ($versions as $version) {
                if($version->isPublic()) {
                    $publicVersions[] = $version;  
                }
            }
            
            $this->_helper->json(array("versions" => $publicVersions));
        }
    }
    
    protected function getService () {
        
        $credentials = $this->getAnalyticsCredentials();
        
        if(!$this->gdclient) {
            $this->gdclient  = Zend_Gdata_ClientLogin::getHttpClient($credentials["username"], $credentials["password"], Zend_Gdata_Analytics::AUTH_SERVICE_NAME, Pimcore_Tool::getHttpClient("Zend_Gdata_HttpClient"));
        }
		$service = new Zend_Gdata_Analytics($this->gdclient);
        
        return $service;
    }
    
    public function abSaveAction () {
        
        // source-page 
        $sourceDoc = Document::getById($this->_getParam("documentId"));
        $goalDoc = Document::getByPath($this->_getParam("conversionPage"));
        
        if(!$sourceDoc || !$goalDoc) {
            exit;
        }
        
        // clean properties
        $sourceDoc = $this->clearProperties($sourceDoc);
        $goalDoc = $this->clearProperties($goalDoc);
        
        // google stuff
        $credentials = $this->getAnalyticsCredentials();
        $config = Pimcore_Google_Analytics::getSiteConfig($site);
        $gdata = $this->getService();
        
        
        
        // create new experiment
        $entryResult = $gdata->insertEntry("
            <entry xmlns='http://www.w3.org/2005/Atom'
                   xmlns:gwo='http://schemas.google.com/analytics/websiteoptimizer/2009'
                   xmlns:app='http://www.w3.org/2007/app'
                   xmlns:gd='http://schemas.google.com/g/2005'>
                <title>" . $this->_getParam("name") . "</title>
                <gwo:analyticsAccountId>" . $config->accountid . "</gwo:analyticsAccountId>
                <gwo:experimentType>AB</gwo:experimentType>
                <gwo:status>Running</gwo:status>
                <link rel='gwo:testUrl' type='text/html' href='http://" . Pimcore_Tool::getHostname() . $sourceDoc->getFullPath() . "' />
                <link rel='gwo:goalUrl' type='text/html' href='http://" . Pimcore_Tool::getHostname() . $goalDoc->getFullPath() . "' />
            </entry>
        ","https://www.google.com/analytics/feeds/websiteoptimizer/experiments");
        
        
        $e = $entryResult->getExtensionElements();
        $data = array();
        foreach ($e as $a) {
            $data[$a->rootElement] = $a->getText();
        }
        
        // get tracking code
        $d = preg_match("/_getTracker\(\"(.*)\"\)/",$data["trackingScript"],$matches);
        $trackingId = $matches[1];
        
        // get test id
        $d = preg_match("/_trackPageview\(\"\/([0-9]+)/",$data["trackingScript"],$matches);
        $testId = $matches[1];
        
        // set original page
        $entryResult = $gdata->insertEntry("
            <entry xmlns='http://www.w3.org/2005/Atom'
                   xmlns:gwo='http://schemas.google.com/analytics/websiteoptimizer/2009'
                   xmlns:app='http://www.w3.org/2007/app'
                   xmlns:gd='http://schemas.google.com/g/2005'>
            <title>Original</title>
            <content>http://" . Pimcore_Tool::getHostname() . $sourceDoc->getFullPath() . "</content>
            </entry>
        ","https://www.google.com/analytics/feeds/websiteoptimizer/experiments/" . $data["experimentId"] . "/abpagevariations");
        
        // set testing pages
        for ($i=1; $i<100; $i++) {
            if($this->_getParam("page_name_".$i)) {
                
                $pageUrl = "";
                if($this->_getParam("page_url_".$i)) {
                    if($variantDoc = Document::getByPath($this->_getParam("page_url_".$i))) {
                        $pageUrl = $this->getRequest()->getScheme() . "://" . Pimcore_Tool::getHostname() . $variantDoc->getFullPath();
                        
                        // add properties to variant page
                        $variantDoc = $this->clearProperties($variantDoc);
                        $variantDoc->setProperty("google_website_optimizer_test_id","text",$testId);
                        $variantDoc->setProperty("google_website_optimizer_track_id","text",$trackingId);
                        $variantDoc->save();
                    }
                    else {
                        logger::warn("Added a invalid URL to A/B test.");
                        exit;
                    }
                }
                /*if($this->_getParam("page_version_".$i)) {
                    $pageUrl = "http://" . Pimcore_Tool::getHostname() . $sourceDoc->getFullPath() . "?v=" . $this->_getParam("page_version_".$i);
                }
                */
                
                if($pageUrl) {
                    try {
                    $entryResult = $gdata->insertEntry("
                        <entry xmlns='http://www.w3.org/2005/Atom'
                               xmlns:gwo='http://schemas.google.com/analytics/websiteoptimizer/2009'
                               xmlns:app='http://www.w3.org/2007/app'
                               xmlns:gd='http://schemas.google.com/g/2005'>
                        <title>" . $this->_getParam("page_name_".$i) . "</title>
                        <content>" . $pageUrl . "</content>
                        </entry>
                    ","https://www.google.com/analytics/feeds/websiteoptimizer/experiments/" . $data["experimentId"] . "/abpagevariations");
                    }
                    catch (Exception $e) {
                        logger::err($e);
                    }
                }
            }
            else {
                break;
            }
        }
        
        
        
        
        // @todo START EXPERIMENT HERE
        //$entryResult = $gdata->getEntry("https://www.google.com/analytics/feeds/websiteoptimizer/experiments/" . $data["experimentId"]);
        
        //$gdata->updateEntry($entryResult->getXml(),"https://www.google.com/analytics/feeds/websiteoptimizer/experiments/" . $data["experimentId"]);
        /*$gdata->updateEntry("
            <entry xmlns='http://www.w3.org/2005/Atom'
                   xmlns:gwo='http://schemas.google.com/analytics/websiteoptimizer/2009'
                   xmlns:app='http://www.w3.org/2007/app'
                   xmlns:gd='http://schemas.google.com/g/2005'>
            <gwo:status>Running</gwo:status>
            </entry>
        ","https://www.google.com/analytics/feeds/websiteoptimizer/experiments/" . $data["experimentId"]);
        */
        
        // source-page
        $sourceDoc->setProperty("google_website_optimizer_test_id","text",$testId);
        $sourceDoc->setProperty("google_website_optimizer_track_id","text",$trackingId);
        $sourceDoc->setProperty("google_website_optimizer_original_page","bool",true);
        $sourceDoc->save();
        
        // conversion-page
        $goalDoc->setProperty("google_website_optimizer_test_id","text",$testId);
        $goalDoc->setProperty("google_website_optimizer_track_id","text",$trackingId);
        $goalDoc->setProperty("google_website_optimizer_conversion_page","bool",true);
        $goalDoc->save();
        
        
        
        // clear output cache
        Pimcore_Model_Cache::clearTag("output");
        Pimcore_Model_Cache::clearTag("properties");
        
        $this->_helper->json(array("success" => true));
    }
    
    protected function clearProperties ($doc) {
        $properties = $doc->getProperties();
        
        foreach ($properties as $key => $value) {
            if(strpos($key,"google_website_optimizer_") === false) {
                $cleanedProperties[$key] = $value;
            }
        }
        
        $doc->setProperties($cleanedProperties);
        
        return $doc;
    }
}

