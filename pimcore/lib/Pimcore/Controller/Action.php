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

namespace Pimcore\Controller;

class Action extends \Zend_Controller_Action {

    /**
     * @throws \Zend_Controller_Response_Exception
     */
    public function init() {
        parent::init();

        $this->view->setRequest($this->getRequest());

        // set content type
        if($this->getResponse()->canSendHeaders()) {
            $this->getResponse()->setHeader("Content-Type", "text/html; charset=UTF-8", true);
        }
    }

    /**
     * @throws \Zend_Controller_Response_Exception
     */
    protected function disableBrowserCache () {
        // set this headers to avoid problems with proxies, ...
        if($this->getResponse()->canSendHeaders()) {
            $this->getResponse()->setHeader("Cache-Control","no-cache, private, no-store, must-revalidate, max-stale=0, post-check=0, pre-check=0, max-age=0", true);
            $this->getResponse()->setHeader("Cache-Control", "no-transform"); // this is for mod_pagespeed
            $this->getResponse()->setHeader("Pragma","no-cache", true);
            $this->getResponse()->setHeader("Expires", "Tue, 01 Jan 1980 00:00:00 GMT", true);
        }
    }

    /**
     *
     */
    protected function removeViewRenderer() {
        \Zend_Controller_Action_HelperBroker::removeHelper('viewRenderer');

        $this->viewEnabled = false;
    }

    /**
     * @return null|\Zend_Layout
     */
    protected function layout() {
        return $this->enableLayout();
    }

    /**
     * @return null|\Zend_Layout
     * @throws \Zend_Controller_Action_Exception
     */
    protected function enableLayout() {

        $viewRenderer = \Zend_Controller_Action_HelperBroker::getExistingHelper("viewRenderer");
        $viewRenderer->setIsInitialized(false); // reset so that the view get's initialized again, because of error page from other modules
        $viewRenderer->initView();

        \Zend_Layout::startMvc();
        $layout = \Zend_Layout::getMvcInstance();
        $layout->enableLayout();
        $layout->setViewSuffix(\Pimcore\View::getViewScriptSuffix());

        return $layout;
    }

    /**
     *
     */
    protected function disableLayout() {
        $layout = \Zend_Layout::getMvcInstance();
        if ($layout) {
            $layout->disableLayout();
        }
    }

    /**
     * @param $name
     * @return $this
     */
    protected function setLayout($name) {
        $layout = \Zend_Layout::getMvcInstance();
        if ($layout instanceof \Zend_Layout) {
            $layout->setLayout($name);
        }
        return $this;
    }

    /**
     *
     */
    protected function disableViewAutoRender() {
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * @param $path
     * @return bool
     */
    protected function viewScriptExists($path) {
        $scriptPaths = $this->view->getScriptPaths();
        foreach ($scriptPaths as $scriptPath) {
            if (is_file($scriptPath . $path)) {
                return true;
            }
        }
    }

    /**
     *
     */
    public function preDispatch() {
        if ($this->hasParam("_segment")) {
            $this->_helper->viewRenderer->setResponseSegment($this->getParam("_segment"));
        }
    }
}
