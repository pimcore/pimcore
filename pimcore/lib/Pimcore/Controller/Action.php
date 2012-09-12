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

class Pimcore_Controller_Action extends Zend_Controller_Action {

    /**
     * Indicator if the custom view is already initialized or not
     * this isn't necessary any more because this functionality has moved to Pimcore_Controller_Action_Helper_ViewRenderer
     * @deprecated
     * @var bool
     */
    protected static $_customViewInitialized = false;

    public function init() {
        parent::init();

        $this->view->setRequest($this->getRequest());

        // init view | only once if there are called other actions
        // this is just for compatibilty reasons see $this->initCustomView();
        if (!self::$_customViewInitialized) {
            $this->initCustomView();
            self::$_customViewInitialized = true;
        }

        // set content type
        if($this->getResponse()->canSendHeaders()) {
            $this->getResponse()->setHeader("Content-Type", "text/html; charset=UTF-8", true);
        }
    }

    protected function disableBrowserCache () {
        // set this headers to avoid problems with proxies, ...
        if($this->getResponse()->canSendHeaders()) {
            $this->getResponse()->setHeader("Cache-Control","no-cache, private, no-store, must-revalidate, max-stale=0, post-check=0, pre-check=0, max-age=0", true);
            $this->getResponse()->setHeader("Pragma","no-cache", true);
            $this->getResponse()->setHeader("Expires", "Tue, 01 Jan 1980 00:00:00 GMT", true);
        }
    }

    protected function removeViewRenderer() {
        Zend_Controller_Action_HelperBroker::removeHelper('viewRenderer');

        $this->viewEnabled = false;
    }

    protected function enableLayout() {

        $viewRenderer = Zend_Controller_Action_HelperBroker::getExistingHelper("viewRenderer");
        $viewRenderer->setIsInitialized(false); // reset so that the view get's initialized again, because of error page from other modules
        $viewRenderer->initView();

        Zend_Layout::startMvc();
        $layout = Zend_Layout::getMvcInstance();
        $layout->setViewSuffix(Pimcore_View::getViewScriptSuffix());
    }

    protected function disableLayout() {
        $layout = Zend_Layout::getMvcInstance();
        if ($layout) {
            $layout->disableLayout();
        }

        $this->layoutEnabled = false;
    }

    protected function setLayout($name) {
        $layout = Zend_Layout::getMvcInstance();
        if ($layout instanceof Zend_Layout) {
            $layout->setLayout($name);
        }
    }

    protected function disableViewAutoRender() {
        $this->_helper->viewRenderer->setNoRender();
    }

    protected function viewScriptExists($path) {
        $scriptPath = $this->view->getScriptPaths();
        $scriptPath = $scriptPath[0];

        if (is_file($scriptPath . $path)) {
            return true;
        }
        return false;
    }

    public function preDispatch() {
        if ($this->hasParam("_segment")) {
            $this->_helper->viewRenderer->setResponseSegment($this->getParam("_segment"));
        }
    }

    /**
     * @deprecated
     */
    protected function initCustomView() {
        // just for compatibility
    }
}
