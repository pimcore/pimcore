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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Object;
use Pimcore\Model\Site;

class Admin_PortalController extends \Pimcore\Controller\Action\Admin {

    /**
     * @var \\Pimcore\\Helper\\Dashboard
     */
    protected $dashboardHelper = null;

    public function init() {
        parent::init();
        $this->dashboardHelper = new \Pimcore\Helper\Dashboard($this->getUser());
    }

    protected function getCurrentConfiguration () {
        return $this->dashboardHelper->getDashboard($this->getParam("key"));
    }

    protected function saveConfiguration ($config) {
        $this->dashboardHelper->saveDashboard($this->getParam("key"), $config);
    }

    public function dashboardListAction() {
        $dashboards = $this->dashboardHelper->getAllDashboards();

        $data = array();
        foreach($dashboards as $key => $config) {
            if($key != "welcome") {
                $data[] = $key;
            }
        }

        $this->_helper->json($data);
    }

    public function createDashboardAction() {
        $dashboards = $this->dashboardHelper->getAllDashboards();
        $key = trim($this->getParam("key"));

        if($dashboards[$key]) {
            $this->_helper->json(array("success" => false, "message" => "dashboard_already_exists"));
        } else if (!empty($key)) {
            $this->dashboardHelper->saveDashboard($key);
            $this->_helper->json(array("success" => true));
        } else {
            $this->_helper->json(array("success" => false, "message" => "empty"));
        }
    }

    public function deleteDashboardAction() {
        $key = $this->getParam("key");
        $this->dashboardHelper->deleteDashboard($key);
        $this->_helper->json(array("success" => true));
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
                if($row['id'] != $this->getParam("id")) {
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

        $nextId = 0;
        foreach($config['positions'] as $col) {
            foreach($col as $row) {
                $nextId = ($row['id'] > $nextId ? $row['id'] : $nextId);
            }
        }

        $nextId = $nextId+1;
        $config["positions"][0][] = array("id" => $nextId, "type" => $this->getParam("type"), "config" => null);

        $this->saveConfiguration($config);

        $this->_helper->json(array("success" => true, "id" => $nextId));
    }

    public function reorderWidgetAction () {

        $config = $this->getCurrentConfiguration();
        $newConfig = array(array(),array());
        $colCount = 0;

        foreach ($config["positions"] as $col) {
            foreach ($col as $row) {
                if($row['id'] != $this->getParam("id")) {
                    $newConfig[$colCount][] = $row;
                } else {
                   $toMove = $row;
                }
            }
            $colCount++;
        }

        array_splice($newConfig[$this->getParam("column")],$this->getParam("row"),0,array($toMove));

        $config["positions"] = $newConfig;
        $this->saveConfiguration($config);

        $this->_helper->json(array("success" => true));
    }


    public function updatePortletConfigAction() {

        $key = $this->getParam("key");
        $id = $this->getParam("id");
        $configuration = $this->getParam("config");

        $dashboard = $this->dashboardHelper->getDashboard($key);
        foreach ($dashboard["positions"] as &$col) {
            foreach ($col as &$portlet) {
                if($portlet['id'] == $id) {
                    $portlet['config'] = $configuration;
                    break;
                }
            }
        }
        $this->dashboardHelper->saveDashboard($key, $dashboard);

        $this->_helper->json(array("success" => true));
    }


    public function portletFeedAction () {
        $dashboard = $this->getCurrentConfiguration();
        $id = $this->getParam("id");

        $cache = \Pimcore\Model\Cache::getInstance();
        if($cache) {
            $cache->setLifetime(10);
            \Zend_Feed_Reader::setCache($cache);
        }

        $portlet = array();
        foreach ($dashboard["positions"] as $col) {
            foreach ($col as $row) {
                if($row['id'] == $id) {
                    $portlet = $row;
                }
            }
        }

        $feedUrl = $portlet['config'];

        $feed = null;
        if(!empty($feedUrl)) {
            try {
                $feed = \Zend_Feed_Reader::import($feedUrl);
            } catch (\Exception $e) {
                \Logger::error($e);
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

                $entry = array(
                    "title" => $entry->getTitle(),
                    "description" => $entry->getDescription(),
                    'authors' => $entry->getAuthors(),
                    'link' => $entry->getLink(),
                    'content' => $entry->getContent()
                );

                foreach($entry as &$content) {
                    $content = strip_tags($content, "<h1><h2><h3><h4><h5><p><br><a><img><div><b><strong><i>");
                    $content = preg_replace('/on([a-z]+)([ ]+)?=/i', "data-on$1=", $content);
                }

                $entries[] = $entry;
            }
        }

        $this->_helper->json(array(
            "entries" => $entries
        ));
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

        $list = Object::getList(array(
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

        $db = \Pimcore\Resource::get();

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

            $date = new \Zend_Date($start);

            $data[] = array(
                "timestamp" => $start,
                "datetext" => $date->get(\Zend_Date::DATE_LONG),
                "objects" => (int) $o,
                "documents" => (int) $d,
                "assets" => (int) $a
            );
        }

        $data = array_reverse($data);

        $this->_helper->json(array("data" => $data));
    }

    public function portletAnalyticsSitesAction() {

        $t = \Zend_Registry::get("Zend_Translate");

        $sites = new Site\Listing();
        $data = array(
            array (
                "id" => 0,
                "site" => $t->translate("main_site")
            )
        );

        foreach ($sites->load() as $site) {
            if (\Pimcore\Google\Analytics::isConfigured($site)) {
                $data[] = array(
                    "id" => $site->getId(),
                    "site" => $site->getMainDomain()
                );
            }

        }

        $this->_helper->json(array("data" => $data));
    }

}
