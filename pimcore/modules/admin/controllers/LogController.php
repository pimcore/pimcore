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

use \Pimcore\Resource;
use \Pimcore\Log;
use \Pimcore\Log\Writer;

class Admin_LogController extends \Pimcore\Controller\Action\Admin {

    public function init() {
        parent::init();
    }

    public function showAction(){
        $offset = $this->_getParam("start");
        $limit = $this->_getParam("limit");

        $orderby = "ORDER BY id DESC";
        $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
        if($sortingSettings['orderKey']) {
            $orderby = "ORDER BY " . $sortingSettings['orderKey'] . " " . $sortingSettings['order'];
        }


        $queryString = " WHERE 1=1";
        if($this->_getParam("priority") != "-1" && ($this->_getParam("priority") == "0" || $this->_getParam("priority"))) {
            $queryString .= " AND priority <= " . $this->_getParam("priority");
        } else {
            $queryString .= " AND (priority = 6 OR priority = 5 OR priority = 4 OR priority = 3 OR priority = 2 OR priority = 1 OR priority = 0)";
        }
        if($this->_getParam("fromDate")) {
            $datetime = $this->_getParam("fromDate");
            if($this->_getParam("fromTime")) {
                $datetime =  substr($datetime, 0, 11) . $this->_getParam("fromTime") . ":00";
            }
            $queryString .= " AND timestamp >= '" . $datetime . "'";
        }
        if($this->_getParam("toDate")) {
            $datetime = $this->_getParam("toDate");
            if($this->_getParam("toTime")) {
                $datetime =  substr($datetime, 0, 11) . $this->_getParam("toTime") . ":00";
            }
            $queryString .= " AND timestamp <= '" . $datetime . "'";
        }
        
        if($this->_getParam("component")) {
            $queryString .= " AND component =  '" . $this->_getParam("component") . "'";
        }
         
        if($this->_getParam("relatedobject")) {
            $queryString .= " AND relatedobject = " . $this->_getParam("relatedobject");
        }

        if($this->_getParam("message")) {
            $queryString .= " AND message like '%" . $this->_getParam("message") ."%'";
        }


        $db = Resource::get();
        $count = $db->fetchCol("SELECT count(*) FROM " . Log\Helper::ERROR_LOG_TABLE_NAME . $queryString);
        $total = $count[0];


        $result = $db->fetchAll("SELECT * FROM " . Log\Helper::ERROR_LOG_TABLE_NAME . $queryString . " $orderby LIMIT $offset, $limit");

        $errorDataList = array();
        if(!empty($result)) {
            foreach($result as $r) {

                $parts = explode("/", $r['filelink']);
                $filename = $parts[count($parts)-1];
                $fileobject = str_replace(PIMCORE_DOCUMENT_ROOT, "", $r['fileobject']);

                $errorData =  array("id"=>$r['id'],
                                    "message"=>$r['message'],
                                    "timestamp"=>$r['timestamp'],
                                    "priority"=>$this->getPriorityName($r['priority']),
                                    "filename" => $filename,
                                    "fileobject" => $fileobject,
            						"relatedobject" => $r['relatedobject'],
                                    "component" => $r['component'],
                                    "source" => $r['source']);
                $errorDataList[] = $errorData;
            }
        }

        $results = array("p_totalCount"=>$total, "p_results"=>$errorDataList);
        $this->_helper->json($results);
    }

    private function getPriorityName($priority) {
        $p = Writer\Db::getPriorities();
        return $p[$priority];
    }
    
    public function priorityJsonAction() {

        $priorities[] = array("key" => "-1", "value" => "-");
        foreach(Writer\Db::getPriorities() as $key => $p) {
            $priorities[] = array("key" => $key, "value" => $p);
        }

        $this->_helper->json(array("priorities" => $priorities));
    }

    public function componentJsonAction() {
        $components[] = array("key" => "-", "value" => "");
        foreach(Writer\Db::getComponents() as $p) {
            $components[] = array("key" => $p, "value" => $p);
        }

        $this->_helper->json(array("components" => $components));
    }    

}
