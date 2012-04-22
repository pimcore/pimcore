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

class Reports_EventsController extends Pimcore_Controller_Action_Admin_Reports {
    

    public function getAvailableCategoriesAction () {

        $db = Pimcore_Resource::get();
        $db->fetchCol("select label from (select label from tracking_event_2011_04 group by label union select label from tracking_event_2012_03 group by label) as b;");

        $this->_helper->json(array(
            "data" => array()
        ));
    }

    public function getAvailableActionsAction () {

    }

    public function getAvailableLabelsAction () {

    }

    public function getAvailableValuesAction () {

    }

}
