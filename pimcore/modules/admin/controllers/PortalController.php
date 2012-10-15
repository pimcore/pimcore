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
 
class Admin_PortalController extends Pimcore_Controller_Action_Admin {
    

    protected function getConfigDir () {
        return PIMCORE_CONFIGURATION_DIRECTORY."/portal";
    }
    
    protected function getConfigFile () {
        return $this->getConfigDir()."/portal_".$this->getUser()->getId().".psf";
    }
    
    protected function getCurrentConfiguration () {
        
        if(is_file($this->getConfigFile())) {
            $conf = Pimcore_Tool_Serialize::unserialize(file_get_contents($this->getConfigFile()));
            if($conf["positions"]) {
                return $conf;
            }
        }
        
        // if no configuration exists, return the base config
        return array(
            "positions" => array(
                array(
                    "pimcore.layout.portlets.modificationStatistic",
                    "pimcore.layout.portlets.modifiedAssets"
                ),
                array(
                    "pimcore.layout.portlets.modifiedObjects",
                    "pimcore.layout.portlets.modifiedDocuments"
                )
            )
        );
    }
    
    protected function saveConfiguration ($config) {
        if(!is_dir($this->getConfigDir())) {
            mkdir($this->getConfigDir());
        }
        
        file_put_contents($this->getConfigFile(), Pimcore_Tool_Serialize::serialize($config));
        chmod($this->getConfigFile(), 0766);
    }
    
    public function getConfigurationAction () {
        $this->_helper->json($this->getCurrentConfiguration());
    }
    
    public function removeWidgetAction () {
        
        $config = $this->getCurrentConfiguration();
        $newConfig = array(array(),array());
        $colCount = 0;
        
        foreach ($config["positions"] as $col) {
            foreach ($col as $row) {
                if($row != $this->getParam("type")) {
                    $newConfig[$colCount][] = $row;
                }
            }
            $colCount++;
        }
        
        $config["positions"] = $newConfig;
        $this->saveConfiguration($config);
        
        $this->_helper->json(array("success" => true));
    }
    
    public function addWidgetAction () {
        
        $config = $this->getCurrentConfiguration();
        
        $config["positions"][0][] = $this->getParam("type");
        
        $this->saveConfiguration($config);
        
        $this->_helper->json(array("success" => true));
    }
    
    public function reorderWidgetAction () {
        
        $config = $this->getCurrentConfiguration();
        
        
        $config = $this->getCurrentConfiguration();
        $newConfig = array(array(),array());
        $colCount = 0;
        
        foreach ($config["positions"] as $col) {
            foreach ($col as $row) {
                if($row != $this->getParam("type")) {
                    $newConfig[$colCount][] = $row;
                }
            }
            $colCount++;
        }
        
        array_splice($newConfig[$this->getParam("column")],$this->getParam("row"),0,$this->getParam("type"));
        
        $config["positions"] = $newConfig;
        $this->saveConfiguration($config);
        
        $this->_helper->json(array("success" => true));
    }
    
    
    
    
    
    public function portletFeedAction () {
        $config = $this->getCurrentConfiguration();
        
        $cache = Pimcore_Model_Cache::getInstance();
        if($cache) {
            $cache->setLifetime(10);
            Zend_Feed_Reader::setCache($cache);
        }
        
        
        $feedUrl = "";
        if($config["settings"]["pimcore.layout.portlets.feed"]["url"]) {
            $feedUrl = $config["settings"]["pimcore.layout.portlets.feed"]["url"];
        }

        $feed = null;
        if(!empty($feedUrl)) {
            try {
                $feed = Zend_Feed_Reader::import($feedUrl);
            } catch (Exception $e) {
                Logger::error($e);
            }
        }

        $count = 0;
        $entries = array();

        if($feed) {
            foreach ($feed as $entry) {

                // display only the latest 11 entries
                $count++;
                if($count > 10) {
                    break;
                }


                $entries[] = array(
                    "title" => $entry->getTitle(),
                    "description" => $entry->getDescription(),
                    'authors' => $entry->getAuthors(),
                    'link' => $entry->getLink(),
                    'content' => $entry->getContent()
                );
            }
        }
        
        $this->_helper->json(array(
            "entries" => $entries
        ));
    }
    
    public function portletFeedSaveAction () {
        $config = $this->getCurrentConfiguration();
        
        $config["settings"]["pimcore.layout.portlets.feed"]["url"] = $this->getParam("url");
        
        $this->saveConfiguration($config);

        $this->_helper->json(array("success" => true));
    }
    
    public function portletModifiedDocumentsAction () {
        
        $list = Document::getList(array(
            "limit" => 10,
            "order" => "DESC",
            "orderKey" => "modificationDate"
        ));
        
        
        $response = array();
        $response["documents"] = array();
        
        foreach ($list as $doc) {
            $response["documents"][] = array(
                "id" => $doc->getId(),
                "type" => $doc->getType(),
                "path" => $doc->getFullPath(),
                "date" => $doc->getModificationDate(),
                "condition" => "userModification = '".$this->getUser()->getId()."'"
            );
        }
        
        $this->_helper->json($response);
    }
    
    public function portletModifiedAssetsAction () {
        
        $list = Asset::getList(array(
            "limit" => 10,
            "order" => "DESC",
            "orderKey" => "modificationDate"
        ));
        
        
        $response = array();
        $response["assets"] = array();
        
        foreach ($list as $doc) {
            $response["assets"][] = array(
                "id" => $doc->getId(),
                "type" => $doc->getType(),
                "path" => $doc->getFullPath(),
                "date" => $doc->getModificationDate(),
                "condition" => "userModification = '".$this->getUser()->getId()."'"
            );
        }
        
        $this->_helper->json($response);
    }
    
    public function portletModifiedObjectsAction () {
        
        $list = Object_Abstract::getList(array(
            "limit" => 10,
            "order" => "DESC",
            "orderKey" => "o_modificationDate",
            "condition" => "o_userModification = '".$this->getUser()->getId()."'"
        ));
        
        
        $response = array();
        $response["objects"] = array();
        
        foreach ($list as $object) {
            $response["objects"][] = array(
                "id" => $object->getId(),
                "type" => $object->getType(),
                "path" => $object->getFullPath(),
                "date" => $object->getModificationDate()
            );
        }
        
        $this->_helper->json($response);
    }
    
    public function portletModificationStatisticsAction () {
        
        $db = Pimcore_Resource::get();
        
        $days = 31;
        $startDate = mktime(23,59,59,date("m"),date("d"),date("Y"));
        $currentDate = $startDate;
        
        $data = array();
        
        for ($i=0; $i<$days; $i++) {
            // documents
            $end = $startDate - ($i*86400);
            $start = $end - 86399;
            
            $o = $db->fetchOne("SELECT COUNT(*) AS count FROM objects WHERE o_modificationDate > ".$start . " AND o_modificationDate < ".$end);
            $a = $db->fetchOne("SELECT COUNT(*) AS count FROM assets WHERE modificationDate > ".$start . " AND modificationDate < ".$end);
            $d = $db->fetchOne("SELECT COUNT(*) AS count FROM documents WHERE modificationDate > ".$start . " AND modificationDate < ".$end);
            
            $date = new Zend_Date($start);
            
            $data[] = array(
                "timestamp" => $start,
                "datetext" => $date->get(Zend_Date::DATE_LONG),
                "objects" => (int) $o,
                "documents" => (int) $d,
                "assets" => (int) $a
            );
        }
        
        $data = array_reverse($data);        
        
        //p_r($data);
        //exit;
        
        $this->_helper->json(array("data" => $data));
    }
    
    
    
}
