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

namespace Pimcore\Controller\Plugin;

use Pimcore\Tool;
use Pimcore\Config;
use Pimcore\Model\Document;
use Pimcore\Model\Site;

class ErrorHandler extends \Zend_Controller_Plugin_ErrorHandler {

    /**
     * @param \Zend_Controller_Request_Abstract $request
     * @throws mixed
     */
    protected function _handleError(\Zend_Controller_Request_Abstract $request) {
        
        // remove zend error handler
        $front = \Zend_Controller_Front::getInstance();
        $front->unregisterPlugin("Zend_Controller_Plugin_ErrorHandler");

        $response = $this->getResponse();

        if (($response->isException()) && (!$this->_isInsideErrorHandlerLoop)) {

            // get errorpage
            try {
                // enable error handler
                $front->setParam('noErrorHandler', false);

                $errorPath = Config::getSystemConfig()->documents->error_pages->default;

                if(Site::isSiteRequest()) {
                    $site = Site::getCurrentSite();
                    $errorPath = $site->getErrorDocument();
                }

                if(empty($errorPath)) {
                    $errorPath = "/";
                }

                $document = Document::getByPath($errorPath);

                if (!$document instanceof Document\Page) {
                    // default is home
                    $document = Document::getById(1);
                }

                if ($document instanceof Document\Page) {

                    $params = Tool::getRoutingDefaults();

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
                    \Zend_Registry::set("pimcore_error_document", $document);

                    // ensure that a viewRenderer exists, and is enabled
                    if(!\Zend_Controller_Action_HelperBroker::hasHelper("viewRenderer")) {
                        $viewRenderer = new \Pimcore\Controller\Action\Helper\ViewRenderer();
                        \Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
                    }

                    $viewRenderer = \Zend_Controller_Action_HelperBroker::getExistingHelper("viewRenderer");
                    $viewRenderer->setNoRender(false);

                    if($viewRenderer->view === null) {
                        $viewRenderer->initView(PIMCORE_WEBSITE_PATH . "/views");
                    }
                }

            }
            catch (\Exception $e) {
                \Logger::emergency("error page not found");
            }
        }

        // call default ZF error handler
        parent::_handleError($request);
    }

}

