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

abstract class Pimcore_Controller_Action_Frontend extends Pimcore_Controller_Action {

    public $document;
    public $editmode;
    public $config;

    public function init() {

        parent::init();

        // log exceptions if handled by error_handler
        $this->checkForErrors();

       // general definitions
        Pimcore::unsetAdminMode();
        Document::setHideUnpublished(true);
        Object_Abstract::setHideUnpublished(true);
        Object_Abstract::setGetInheritedValues(true);
        
        $adminSession = null;

        // assign variables
        $this->view->controller = $this; 
        
        // init website config
        $config = Pimcore_Config::getWebsiteConfig();
        $this->config = $config;
        $this->view->config = $config;
        
        
        if (!$this->_getParam("document")) {
            Zend_Registry::set("pimcore_editmode", false);
            $this->editmode = false;
            $this->view->editmode = false;

            // no document available, continue, ...
            return;
        }
        else {
            $this->setDocument($this->_getParam("document"));
        }


        if ($this->_getParam("pimcore_editmode") || $this->_getParam("pimcore_version") || $this->_getParam("pimcore_preview") || $this->_getParam("pimcore_admin") || $this->_getParam("pimcore_object_preview")) {

            $specialAdminRequest = true;

            Pimcore_Tool_Authentication::initSession();
            // start admin session
            $adminSession = new Zend_Session_Namespace("pimcore_admin");
        }


        if (!$this->document->isPublished()) {
            if ($specialAdminRequest) {
                if (!$adminSession->user instanceof User) {
                    throw new Exception("access denied for " . $this->document->getFullPath());
                }
            }
            else {
                throw new Exception("access denied for " . $this->document->getFullPath());
            }
        }

        // register global locale if the document has the system property "language"
        if($this->document->getProperty("language")) {
            $locale = new Zend_Locale($this->document->getProperty("language"));
		    Zend_Registry::set('Zend_Locale', $locale);
        }


        // for editmode
        if ($adminSession && $adminSession->user instanceof User) {
            if ($this->_getParam("pimcore_editmode") and !Zend_Registry::isRegistered("pimcore_editmode")) {
                Zend_Registry::set("pimcore_editmode", true);
                
                // check if there is the document in the session
                $docKey = "document_" . $this->getDocument()->getId();
                $docSession = new Zend_Session_Namespace("pimcore_documents");

                if ($docSession->$docKey) {
                    // if there is a document in the session use it
                    $this->setDocument($docSession->$docKey);
                } else {
                    // set the latest available version for editmode if there is no doc in the session
                    $latestVersion = $this->getDocument()->getLatestVersion();
                    if($latestVersion) {
                        $latestDoc = $latestVersion->loadData();
                        if($latestDoc instanceof Document_PageSnippet) {
                            $this->setDocument($latestDoc);
                        }
                    }
                }
                
                // register editmode plugin
                $front = Zend_Controller_Front::getInstance();
                $front->registerPlugin(new Pimcore_Controller_Plugin_Frontend_Editmode($this), 1000);
            }
            else {
                Zend_Registry::set("pimcore_editmode", false);
            }
        }
        else {
            Zend_Registry::set("pimcore_editmode", false);
        }

        // for preview
        if ($adminSession && $adminSession->user instanceof User) {
            // document preview
            if ($this->_getParam("pimcore_preview")) {
                // get document from session
                $docKey = "document_" . $this->_getParam("document")->getId();
                $docSession = new Zend_Session_Namespace("pimcore_documents");

                if ($docSession->$docKey) {
                    $this->setDocument($docSession->$docKey);
                }
            }

            // object preview
            if ($this->_getParam("pimcore_object_preview")) {
                $key = "object_" . $this->_getParam("pimcore_object_preview");
                $session = new Zend_Session_Namespace("pimcore_objects");
                if($session->$key) {
                    $object = $session->$key;
                    // add the object to the registry so every call to Object_Abstract::getById() will return this object instead of the real one
                    Zend_Registry::set("object_" . $object->getId(), $object);
                }
            }
        }

        // for version preview
        if ($this->_getParam("pimcore_version")) {
            if ($adminSession && $adminSession->user instanceof User) {

                // only get version data at the first call || because of embedded Snippets ...
                try {
                    Zend_Registry::get("pimcore_version_active");
                }
                catch (Exception $e) {
                    $version = Version::getById($this->_getParam("pimcore_version"));
                    $this->setDocument($version->getData());

                    Zend_Registry::set("pimcore_version_active", true);
                }
            }
        }

        // for public versions
        if ($this->_getParam("v")) {
            try {
                $version = Version::getById($this->_getParam("v"));
                if ($version->getPublic()) {
                    $this->setDocument($version->getData());
                }
            }
            catch (Exception $e) {
            }
        }
        
        // set some parameters
        $this->editmode = Zend_Registry::get("pimcore_editmode");
        $this->view->editmode = Zend_Registry::get("pimcore_editmode");
    }
    
    public function getConfig () {
        return $this->config;
    }

    public function setDocument($document) {
        if ($document instanceof Document) {
            $this->document = $document;
            $this->view->document = $document;
        }
    }

    public function getDocument() {
        return $this->document;
    }

    public function initTranslation() {
        
        try {
            $translator = Zend_Registry::get("Zend_Translate");
        }
        catch (Exception $e) {
            // setup Zend_Translate
            try {
                $locale = Zend_Registry::get("Zend_Locale");
                $cacheKey = "translator_website";

                if (!$translate = Pimcore_Model_Cache::load($cacheKey)) {
                    $translate = new Pimcore_Translate($locale);
                    Pimcore_Model_Cache::save($translate, $cacheKey, array("translator","translator_website","translate"), null, 999);
                }

                if(Pimcore_Tool::isValidLanguage($locale)) {
                    $translate->setLocale($locale);    
                } else {
                    Logger::error("You want to use an invalid language which is not defined in the system settings: " . $locale);
                    // fall back to the first (default) language defined
                    $languages = Pimcore_Tool::getValidLanguages();
                    if($languages[0]) {
                        Logger::error("Using '" . $languages[0] . "' as a fallback, because the language '".$locale."' is not defined in system settings");
                        $translate->setLocale($languages[0]);
                    } else {
                        throw new Exception("You have not defined a language in the system settings (Website -> Frontend-Languages), please add at least one language.");
                    }
                }


                // register the translator in Zend_Registry with the key "Zend_Translate" to use the translate helper for Zend_View
                Zend_Registry::set("Zend_Translate", $translate);
            }
            catch (Exception $e) {
                Logger::error("initialization of Pimcore_Translate failed");
                Logger::error($e);
            }
        }

        return $translator;
    }

    public function getRenderScript() {

        // try to get template out of the document object
        if ($this->document instanceof Document && $template = $this->document->getTemplate()) {
            return $template;
        }
            // try to get the template over the params
        else if ($this->_getParam("template")) {
            return $this->_getParam("template");
        }
        return null;
    }

    protected function forceRender() {

        if (!$this->viewRendered) {
            if ($script = $this->getRenderScript()) {
                $this->renderScript($script);
                $this->viewRendered = true;
            }
            else {
                $this->render();
                $this->viewRendered = true;
            }
        }
    }

    public function preDispatch() {

        // initialize translation if required
        $this->initTranslation();
        // this is for $this->action in templates when they are inside a block element
        try {
            if (!$this->_getParam("disableBlockClearing")) {
                $this->parentBlockCurrent = Zend_Registry::get("pimcore_tag_block_current");
                $this->parentBlockNumeration = Zend_Registry::get("pimcore_tag_block_numeration");

                Zend_Registry::set("pimcore_tag_block_current", null);
                Zend_Registry::set("pimcore_tag_block_numeration", null);
            }
        }
        catch (Exception $e) {
        }
    }

    public function postDispatch() {
        parent::postDispatch();

        if ($this->parentBlockCurrent && !$this->_getParam("disableBlockClearing")) {
            $this->forceRender();

            Zend_Registry::set("pimcore_tag_block_current", $this->parentBlockCurrent);
            Zend_Registry::set("pimcore_tag_block_numeration", $this->parentBlockNumeration);
        }
    }

    public function checkForErrors() {
        if ($error = $this->_getParam('error_handler')) {
            if ($error->exception) {

                if ($error->exception instanceof Zend_Controller_Router_Exception) {
                    header('HTTP/1.1 404 Not Found');
                    //$this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
                    $this->getResponse()->setHttpResponseCode(404);
                }
                else {
                    header('HTTP/1.1 503 Service Temporarily Unavailable');
                    //$this->getResponse()->setRawHeader('HTTP/1.1 503 Service Temporarily Unavailable');
                    $this->getResponse()->setHttpResponseCode(503);
                }

                Logger::emergency($error->exception);

                try {
                    $document = Zend_Registry::get("pimcore_error_document");
                    $this->setDocument($document);
                    $this->_setParam("document", $document);
                    $this->disableLayout();
                }
                catch (Exception $e) {
                    p_r($error->exception);
                    exit;
                }
            }
        }
    }



}
