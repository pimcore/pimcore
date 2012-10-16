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

        // contains the logged in user if necessary
        $user = null;

        // assign variables
        $this->view->controller = $this; 
        
        // init website config
        $config = Pimcore_Config::getWebsiteConfig();
        $this->config = $config;
        $this->view->config = $config;
        
        
        if (!$this->getParam("document")) {
            Zend_Registry::set("pimcore_editmode", false);
            $this->editmode = false;
            $this->view->editmode = false;

            // no document available, continue, ...
            return;
        }
        else {
            $this->setDocument($this->getParam("document"));
        }


        if (Pimcore_Tool::isFrontentRequestByAdmin()) {
            $this->disableBrowserCache();

            // start admin session & get logged in user
            $user = Pimcore_Tool_Authentication::authenticateSession();
        }


        if (!$this->document->isPublished()) {
            if (Pimcore_Tool::isFrontentRequestByAdmin()) {
                if (!$user) {
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
            $this->getResponse()->setHeader("Content-Language",strtolower(str_replace("_","-", (string) $locale)), true);
        }


        // for editmode
        if ($user) {
            if ($this->getParam("pimcore_editmode") and !Zend_Registry::isRegistered("pimcore_editmode")) {
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
        if ($user) {
            // document preview
            if ($this->getParam("pimcore_preview")) {
                // get document from session
                $docKey = "document_" . $this->getParam("document")->getId();
                $docSession = new Zend_Session_Namespace("pimcore_documents");

                if ($docSession->$docKey) {
                    $this->setDocument($docSession->$docKey);
                }
            }

            // object preview
            if ($this->getParam("pimcore_object_preview")) {
                $key = "object_" . $this->getParam("pimcore_object_preview");
                $session = new Zend_Session_Namespace("pimcore_objects");
                if($session->$key) {
                    $object = $session->$key;
                    // add the object to the registry so every call to Object_Abstract::getById() will return this object instead of the real one
                    Zend_Registry::set("object_" . $object->getId(), $object);
                }
            }
        }

        // for version preview
        if ($this->getParam("pimcore_version")) {
            if ($user) {

                // only get version data at the first call || because of embedded Snippets ...
                if(!Zend_Registry::isRegistered("pimcore_version_active")) {
                    $version = Version::getById($this->getParam("pimcore_version"));
                    $this->setDocument($version->getData());

                    Zend_Registry::set("pimcore_version_active", true);
                }
            }
        }

        // for public versions
        if ($this->getParam("v")) {
            try {
                $version = Version::getById($this->getParam("v"));
                if ($version->getPublic()) {
                    $this->setDocument($version->getData());
                }
            }
            catch (Exception $e) {
            }
        }

        // check if document is a wrapped hardlink, if this is the case send a rel=canonical header to the source document
        if($this->getDocument() instanceof Document_Hardlink_Wrapper_Interface) {
            // get the cononical (source) document
            $hardlinkCanonicalSourceDocument = Document::getById($this->getDocument()->getId());
            $request = $this->getRequest();

            if(Pimcore_Tool_Frontend::isDocumentInCurrentSite($hardlinkCanonicalSourceDocument)) {
                $this->getResponse()->setHeader("Link", '<' . $request->getScheme() . "://" . $request->getHttpHost() . $hardlinkCanonicalSourceDocument->getFullPath() . '>; rel="canonical"');
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
        
        if(Zend_Registry::isRegistered("Zend_Translate")) {
            $translate = Zend_Registry::get("Zend_Translate");
        } else {
            // setup Zend_Translate
            try {
                $locale = Zend_Registry::get("Zend_Locale");
                $cacheKey = "translator_website";

                if (!$translate = Pimcore_Model_Cache::load($cacheKey)) {
                    $translate = new Pimcore_Translate_Website($locale);
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

        return $translate;
    }

    public function getRenderScript() {

        // try to get the template out of the params
        if ($this->getParam("template")) {
            return $this->getParam("template");
        }

        // try to get template out of the document object, but only if the parameter `staticroute´ is not set, which indicates
        // if a request comes through a static/custom route (contains the route Object => Staticroute)
        // see PIMCORE-1545
        if ($this->document instanceof Document && !in_array($this->getParam("pimcore_request_source"), array("staticroute", "renderlet"))) {
            if(method_exists($this->document, "getTemplate") && $this->document->getTemplate()) {
                return $this->document->getTemplate();
            }
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

    public function render($action = null, $name = null, $noController = false) {
        parent::render($action, $name, $noController);
        $this->viewRendered = true;
    }


    public function renderScript($script, $name = null) {
        parent::renderScript($script, $name);
        $this->viewRendered = true;
    }

    public function preDispatch() {

        // initialize translation if required
        $this->initTranslation();

        // this is for $this->action() in templates when they are inside a block element
        try {
            if (!$this->getParam("disableBlockClearing")) {
                $this->parentBlockCurrent = Zend_Registry::get("pimcore_tag_block_current");
                $this->parentBlockNumeration = Zend_Registry::get("pimcore_tag_block_numeration");

                Zend_Registry::set("pimcore_tag_block_current", null);
                Zend_Registry::set("pimcore_tag_block_numeration", null);
            }
        }
        catch (Exception $e) {
            Logger::debug($e);
        }
    }

    public function postDispatch() {
        parent::postDispatch();

        if ($this->parentBlockCurrent && !$this->getParam("disableBlockClearing")) {
            $this->forceRender();

            Zend_Registry::set("pimcore_tag_block_current", $this->parentBlockCurrent);
            Zend_Registry::set("pimcore_tag_block_numeration", $this->parentBlockNumeration);
        }
    }

    public function checkForErrors() {
        if ($error = $this->getParam('error_handler')) {
            if ($error->exception) {

                if ($error->exception instanceof Zend_Controller_Router_Exception || $error->exception instanceof Zend_Controller_Action_Exception) {
                    header('HTTP/1.1 404 Not Found');
                    //$this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
                    $this->getResponse()->setHttpResponseCode(404);
                }
                else {
                    header('HTTP/1.1 503 Service Temporarily Unavailable');
                    //$this->getResponse()->setRawHeader('HTTP/1.1 503 Service Temporarily Unavailable');
                    $this->getResponse()->setHttpResponseCode(503);
                }

                Logger::emergency("Unable to load URL: " . $_SERVER["REQUEST_URI"]);
                Logger::emergency($error->exception);

                try {
                    // check if we have the error page already in the cache
                    // the cache is written in Pimcore_Controller_Plugin_HttpErrorLog::dispatchLoopShutdown()
                    $cacheKey = "error_page_response_" . Pimcore_Tool_Frontend::getSiteKey();
                    if($responseBody = Pimcore_Model_Cache::load($cacheKey)) {
                        $this->getResponse()->setBody($responseBody);
                        $this->getResponse()->sendResponse();
                        exit;
                    } else {
                        $document = Zend_Registry::get("pimcore_error_document");
                        $this->setDocument($document);
                        $this->setParam("document", $document);
                        $this->disableLayout();
                    }
                }
                catch (Exception $e) {
                    die("Unable to load error document");
                }
            }
        }
    }



}
