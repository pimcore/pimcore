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

class Reports_SeoController extends Pimcore_Controller_Action_Admin_Reports {


    public function overviewAction() {

        $service = new Tool_ContentAnalysis_Service();
        $summary = $service->getOverviewData();



        p_r($summary);
        exit;
    }

    public function detailAction() {

        $conditions = array("1=1");
        if($this->getParam("site")) {
            $conditions[] = "site = '" . $this->getParam("site") . "'";
        }

        $condition = implode(" AND ", $conditions);

        $service = new Tool_ContentAnalysis_Service();
        $list = $service->listData($condition, (int) $this->getParam("start"), (int) $this->getParam("limit"), $this->getParam("sort"), $this->getParam("dir"));

        $this->_helper->json(array(
            "success" => true,
            "data" => $list,
            "total" => $service->getTotal($condition)
        ));
    }
}
