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

    public function init() {
        parent::init();

        // set contenttype
        $this->getResponse()->setHeader("Content-Type", "text/html; charset=UTF-8", true);

        // init view | only once if there are called other actions
        try {
            if (!Zend_Registry::get("pimcore_custom_view")) {
                $this->initCustomView();
            }
        }
        catch (Exception $e) {
            $this->initCustomView();
        }

        // add some parameters
        $this->view->setRequest($this->getRequest());
    }

    protected function removeViewRenderer() {
        Zend_Controller_Action_HelperBroker::removeHelper('viewRenderer');

        $this->viewEnabled = false;
    }

    protected function enableLayout() {

        Zend_Layout::startMvc();
        $layout = Zend_Layout::getMvcInstance();

        $layout->setViewSuffix($this->getViewSuffix());
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
        if ($this->_hasParam("_segment")) {
            $this->_helper->viewRenderer->setResponseSegment($this->_getParam("_segment"));
        }
    }

    protected function initCustomView() {

        $viewHelper = Zend_Controller_Action_HelperBroker::getExistingHelper("ViewRenderer");

        $view = new Pimcore_View();

        // script pathes
        foreach ($viewHelper->view->getScriptPaths() as $path) {
            $view->addScriptPath($path);
            $view->addScriptPath(str_replace(DIRECTORY_SEPARATOR . "scripts", DIRECTORY_SEPARATOR . "layouts", $path));
        }

        // view helper
        foreach ($viewHelper->view->getHelperPaths() as $prefix => $path) { 
            $view->addHelperPath($path, $prefix); 
        }
        $view->addHelperPath(PIMCORE_PATH . "/lib/Pimcore/View/Helper", "Pimcore_View_Helper_");

        // add helper to controller
        $viewHelper->setView($view);
        $viewHelper->setViewSuffix($this->getViewSuffix());
        Zend_Controller_Action_HelperBroker::addHelper($viewHelper);

        $this->view = $view;

        Zend_Registry::set("pimcore_custom_view", true);
    }

    protected function getViewSuffix () {

        // default is php
        $viewSuffix = "php";

        // custom view suffixes are only available for the frontend module (website)
        if($this->getRequest()->getModuleName() == PIMCORE_FRONTEND_MODULE) {
            $customViewSuffix = Pimcore_Config::getSystemConfig()->general->viewSuffix;
            if(!empty($customViewSuffix)) {
                $viewSuffix = $customViewSuffix;
            }
        }

        return $viewSuffix;
    }
}
