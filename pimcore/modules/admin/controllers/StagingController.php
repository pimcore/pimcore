<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license dsf sdaf asdf asdf
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Admin_StagingController extends Pimcore_Controller_Action_Admin {


    public function init() {
        parent::init();

        $this->session = new Zend_Session_Namespace("pimcore_staging");
    }

    public function initAction() {

        $staging = new Pimcore_Staging();
        $initInfo = $staging->init();
        
        $this->session->staging = $staging;

        $this->_helper->json($initInfo);
    }

    public function filesAction() {

        $staging = $this->session->staging;
        $return = $staging->fileStep($this->_getParam("step"));
        $this->session->staging = $staging;
                                
        $this->_helper->json($return);
    }

    public function mysqlAction() {

        $name = $this->_getParam("name");
        $type = $this->_getParam("type");
        
        $staging = $this->session->staging;
        $return = $staging->mysql($name, $type);
        $this->session->staging = $staging;
                                
        $this->_helper->json($return);
    }

    public function mysqlCompleteAction() {

        $staging = $this->session->staging;
        $return = $staging->mysqlComplete();
        $this->session->staging = $staging;
                                
        $this->_helper->json($return);
    }

    public function completeAction() {
                                
        $staging = $this->session->staging;
        $return = $staging->complete();
        $this->session->staging = $staging;

        $this->_helper->json($return);
    }
}
