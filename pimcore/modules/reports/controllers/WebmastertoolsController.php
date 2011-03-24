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

class Reports_WebmastertoolsController extends Pimcore_Controller_Action_Admin_Reports {
    
    public function init () {
        parent::init();
        
        $credentials = $this->getWebmastertoolsCredentials();
        if(!$credentials) {
            die("Webmastertools not configured");
        }
    }
    
    protected function getService () {
        
        $credentials = $this->getWebmastertoolsCredentials();
                
        $client = Zend_Gdata_ClientLogin::getHttpClient($credentials["username"], $credentials["password"], "sitemaps", Pimcore_Tool::getHttpClient("Zend_Gdata_HttpClient"));
		$service = new Zend_Gdata_Analytics($client);
        
        return $service;
    }
    
    private function getSite () {
        $siteId = $this->_getParam("site");
        
        try {
           $site = Site::getById($siteId); 
        }
        catch (Exception $e) {
            return;
        }
        
        return $site;
    }
    
    public function keywordsAction () {
        
        $site = $this->getSite();
        
        $conf = Pimcore_Google_Webmastertools::getSiteConfig($site);
		
		$service = $this->getService();
        $data = $service->getFeed("https://www.google.com/webmasters/tools/feeds/" . urlencode($conf->profile) . "/keywords/");
        
        foreach ($data->getExtensionElements() as $d) {
            $a = $d->getExtensionAttributes();
            if($a["source"]["value"] == $this->_getParam("type")) {
                $keywords[] = array(
                    "keyword" => $d->getText()
                );
            }
        }
        
        $this->_helper->json(array("keywords"  => $keywords));
    }
    
    public function crawlingAction () {
        
        $site = $this->getSite();
        
        $conf = Pimcore_Google_Webmastertools::getSiteConfig($site);
		
		$service = $this->getService();
        $data = $service->getFeed("https://www.google.com/webmasters/tools/feeds/" . urlencode($conf->profile) . "/crawlissues/");
        
        $issues = array();
        
        
        foreach ($data as $d) {
            
            $issue = array(
                "title" => (string) $d->getTitle(),
                "linkedfrom" => array()
            );
            $e = $d->getExtensionElements();
            
            foreach ($e as $a) {
                
                if($a->rootElement == "linked-from") {
                    $issue["linkedfrom"][] = $a->getText();
                }
                else {
                    $issue[str_replace("-","",$a->rootElement)] = $a->getText();
                }
            }
            
            $issue["linkedfrom"] = implode("\n",$issue["linkedfrom"]);
            $issues[] = $issue;
        }
        
        $this->_helper->json(array("issues"  => $issues));
    }
}
