<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

use Pimcore\Db;
use Pimcore\Log;
use Pimcore\Log\Writer;
use Pimcore\Log\Handler\ApplicationLoggerDb;

class Admin_LogController extends \Pimcore\Controller\Action\Admin {

    public function init() {
        parent::init();
    }

    public function showAction(){
        $offset = $this->getParam("start");
        $limit = $this->getParam("limit");

        $orderby = "ORDER BY id DESC";
        $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
        if($sortingSettings['orderKey']) {
            $orderby = "ORDER BY " . $sortingSettings['orderKey'] . " " . $sortingSettings['order'];
        }


        $queryString = " WHERE 1=1";

        if($this->getParam("priority") != "-1" && ($this->getParam("priority") == "0" || $this->getParam("priority"))) {
            $levels = [];
            foreach(["emergency","alert","critical","error","warning","notice","info","debug"] as $level) {
                $levels[] = "priority = '" . $level . "'";
                
                if($this->getParam("priority") == $level) {
                    break;
                }
            }

            $queryString .= " AND (" . implode(" OR ", $levels) . ")";
        }

        if($this->getParam("fromDate")) {
            $datetime = $this->getParam("fromDate");
            if($this->getParam("fromTime")) {
                $datetime =  substr($datetime, 0, 11) . $this->getParam("fromTime") . ":00";
            }
            $queryString .= " AND timestamp >= '" . $datetime . "'";
        }
        if($this->getParam("toDate")) {
            $datetime = $this->getParam("toDate");
            if($this->getParam("toTime")) {
                $datetime =  substr($datetime, 0, 11) . $this->getParam("toTime") . ":00";
            }
            $queryString .= " AND timestamp <= '" . $datetime . "'";
        }
        
        if($this->getParam("component")) {
            $queryString .= " AND component =  '" . $this->getParam("component") . "'";
        }
         
        if($this->getParam("relatedobject")) {
            $queryString .= " AND relatedobject = " . $this->getParam("relatedobject");
        }

        if($this->getParam("message")) {
            $queryString .= " AND message like '%" . $this->getParam("message") ."%'";
        }


        $db = Db::get();
        $count = $db->fetchCol("SELECT count(*) FROM " . \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_NAME . $queryString);
        $total = $count[0];


        $result = $db->fetchAll("SELECT * FROM " . \Pimcore\Log\Handler\ApplicationLoggerDb::TABLE_NAME . $queryString . " $orderby LIMIT $offset, $limit");

        $errorDataList = array();
        if(!empty($result)) {
            foreach($result as $r) {

                $parts = explode("/", $r['filelink']);
                $filename = $parts[count($parts)-1];
                $fileobject = str_replace(PIMCORE_DOCUMENT_ROOT, "", $r['fileobject']);

                $errorData =  array("id"=>$r['id'],
                                    "pid" => $r['pid'],
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
        $p = ApplicationLoggerDb::getPriorities();
        return $p[$priority];
    }
    
    public function priorityJsonAction() {

        $priorities[] = array("key" => "-1", "value" => "-");
        foreach(ApplicationLoggerDb::getPriorities() as $key => $p) {
            $priorities[] = array("key" => $key, "value" => $p);
        }

        $this->_helper->json(array("priorities" => $priorities));
    }

    public function componentJsonAction() {
        $components[] = array("key" => "-", "value" => "");
        foreach(ApplicationLoggerDb::getComponents() as $p) {
            $components[] = array("key" => $p, "value" => $p);
        }

        $this->_helper->json(array("components" => $components));
    }    

}
