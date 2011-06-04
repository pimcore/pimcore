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

    /**
     * ENABLE
     */


    public function enableInitAction() {

        // check if all required settings are present
        $config = Pimcore_Config::getSystemConfig()->toArray();
        if($config["staging"] && $config["staging"]["domain"] && $config["staging"]["domain"] != $_SERVER["HTTP_HOST"] && $config["staging"]["database"]["params"]["dbname"]) {

            // everything ok, let's start
            $staging = new Pimcore_Staging_Enable();
            $initInfo = $staging->init();

            $this->session->staging = $staging;

            $this->_helper->json($initInfo);
        } else {
            die("Cannot initiate staging, please be sure that you have configured a staging database and domain.");
        }
    }

    public function enableCleanupDatabaseAction () {
        $staging = $this->session->staging;
        $return = $staging->cleanupDatabase();
        $this->session->staging = $staging;

        $this->_helper->json($return);
    }

    public function enableCleanupFilesAction () {
        $staging = $this->session->staging;
        $return = $staging->cleanupFiles();
        $this->session->staging = $staging;

        $this->_helper->json($return);
    }

    public function enableSetupAction () {
        $staging = $this->session->staging;
        $return = $staging->setUpStaging();
        $this->session->staging = $staging;

        $this->_helper->json($return);
    }

    public function enableFilesAction() {

        $staging = $this->session->staging;
        $return = $staging->fileStep($this->_getParam("step"));
        $this->session->staging = $staging;
                                
        $this->_helper->json($return);
    }

    public function enableMysqlAction() {

        $name = $this->_getParam("name");
        $type = $this->_getParam("type");
        
        $staging = $this->session->staging;
        $return = $staging->mysql($name, $type);
        $this->session->staging = $staging;
                                
        $this->_helper->json($return);
    }


    public function enableCompleteAction() {
                                
        $staging = $this->session->staging;
        $return = $staging->complete();
        $this->session->staging = $staging;

        $this->_helper->json($return);
    }


    /**
     * DISABLE
     */


    public function disableRemoveConfigAction () {

        $config = new Zend_Config_Ini(PIMCORE_CONFIGURATION_STAGE);
        unlink(PIMCORE_CONFIGURATION_STAGE);

        $this->_helper->json(array(
            "livedomain" => $config->livedomain
        ));
    }

    public function disableInitAction() {

        $staging = new Pimcore_Staging_Disable();
        $initInfo = $staging->init();
        $this->session->staging = $staging;

        $this->_helper->json($initInfo);
    }

    public function disableMysqlAction() {

        $name = $this->_getParam("name");
        $type = $this->_getParam("type");

        $staging = $this->session->staging;
        $return = $staging->mysql($name, $type);
        $this->session->staging = $staging;

        $this->_helper->json($return);
    }

    public function disableCompleteAction() {

        $staging = $this->session->staging;
        $return = $staging->complete();
        $this->session->staging = $staging;

        echo json_encode($return);
        exit;
    }

    /**
     * CLEANUP
     */

    public function cleanupInitAction() {

        $staging = new Pimcore_Staging_Cleanup();
        $initInfo = $staging->init();
        $this->session->staging = $staging;

        $this->_helper->json($initInfo);
    }

    public function cleanupMysqlAction() {

        $name = $this->_getParam("name");
        $type = $this->_getParam("type");

        $staging = $this->session->staging;
        $return = $staging->mysql($name, $type);
        $this->session->staging = $staging;

        $this->_helper->json($return);
    }

    public function cleanupFilesAction () {
        $staging = $this->session->staging;
        $return = $staging->fileStep($this->_getParam("step"));
        $this->session->staging = $staging;

        $this->_helper->json($return);
    }

    public function cleanupCompleteAction() {

        $staging = $this->session->staging;
        $return = $staging->complete();
        $this->session->staging = $staging;

        $this->_helper->json($return);
    }

}
