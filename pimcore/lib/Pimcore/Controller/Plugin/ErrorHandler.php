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

class Pimcore_Controller_Plugin_ErrorHandler extends Zend_Controller_Plugin_ErrorHandler {


    protected function _handleError(Zend_Controller_Request_Abstract $request) {
        
        // remove zend error handler
        $front = Zend_Controller_Front::getInstance();
        $front->unregisterPlugin("Zend_Controller_Plugin_ErrorHandler");

        $response = $this->getResponse();

        if (($response->isException()) && (!$this->_isInsideErrorHandlerLoop)) {

            // get errorpage
            try {
                // enable error handler
                $front->setParam('noErrorHandler', false);

                $siteKey = Pimcore_Tool_Frontend::getSiteKey();
                $errorPath = Pimcore_Config::getSystemConfig()->documents->error_pages->$siteKey;
                if(empty($errorPath)) {
                    $errorPath = "/";
                }

                $document = Document::getByPath($errorPath);

                if (!$document instanceof Document_Page) {
                    // default is home
                    $document = Document::getById(1);
                }

                if ($document instanceof Document_Page) {

                    $params = Pimcore_Tool::getRoutingDefaults();

                    if ($module = $document->getModule()) {
                        $params["module"] = $module;
                    }
                    if ($controller = $document->getController()) {
                        $params["controller"] = $controller;
                        $params["action"] = "index";
                    }
                    if ($action = $document->getAction()) {
                        $params["action"] = $action;
                    }

                    $this->setErrorHandler($params);

                    $request->setParam("document", $document);
                    Zend_Registry::set("pimcore_error_document", $document);
                }

            }
            catch (Exception $e) {
                Logger::emergency("error page not found");
            }
        }

        // call default ZF error handler
        parent::_handleError($request);
    }

}

