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

abstract class Pimcore_Controller_Action_Admin extends Pimcore_Controller_Action {

    protected $user;
    protected $language = "en";

    public function init() {

        parent::init();

        // set language
        try {
            $locale = Zend_Registry::get("Zend_Locale");
            $this->setLanguage($locale->getLanguage());
        }
        catch (Exception $e) {
            if ($this->_getParam("language")) {
                $this->setLanguage($this->_getParam("language"));
            }
            else {
                $this->setLanguage(Zend_Registry::get("pimcore_config_system")->general->language);
            }
        }

        try {
            Zend_Registry::get("pimcore_admin_initialized");

            $this->setUser(Zend_Registry::get("pimcore_admin_user"));
        }
        catch (Exception $e) {
            // general definitions
            define("PIMCORE_ADMIN", true);

            // init session
            self::initSession();

            // init translations
            self::initTranslations($this);

            // init zend action helpers
            Zend_Controller_Action_HelperBroker::addPrefix('Pimcore_Controller_Action_Helper');


            // get session namespace
            $adminSession = new Zend_Session_Namespace("pimcore_admin");

            // user informations
            $thawedUser = User::thaw($adminSession->frozenuser);
            if ($thawedUser instanceof User) {
                $this->setUser($thawedUser);
                if ($this->getUser()->getLanguage()) {
                    $this->setLanguage($this->getUser()->getLanguage());
                }
            }
            else {
                // auth for WebDAV
                if ($this->_getParam("module") == "admin" && $this->_getParam("controller") == "asset" && $this->_getParam("action") == "webdav") {

                    // the following is a fix for Basic Auth in an FastCGI Environment
                    if (isset($_SERVER['Authorization']) && !empty($_SERVER['Authorization'])) {
                        $parts = explode(" ", $_SERVER['Authorization']);
                        $type = array_shift($parts);
                        $cred = implode(" ", $parts);
                        
                        if ($type == 'Digest') {
                            $_SERVER["PHP_AUTH_DIGEST"] = $cred;
                        }
                    }

                    // only digest auth is supported anymore
                    try {

                        $auth = new Sabre_HTTP_DigestAuth();
                        $auth->setRealm("pimcore");
                        $auth->init();

                        if ($user = User::getByName($auth->getUsername())) {
                            if(!$user->isAdmin()) {
                                throw new Exception("Only admins can access WebDAV");
                            }
                            if ($auth->validateA1($user->getPassword())) {
                                $this->setUser($user);
                                return;
                            }
                        }
                        throw new Exception("Authentication required");
                    }
                    catch (Exception $e) {
                        $auth->requireLogin();
                        echo "Authentication required\n";
                        die();
                    }
                }
            }
            
            if (!$this->getUser() instanceof User) {
                $this->getResponse()->setHeader("X-Pimcore-Auth","required");
            }

            if (!$this->getUser() instanceof User && !($this->_getParam("module") == "admin" && $this->_getParam("controller") == "login")) {
                $this->_redirect("/admin/login");
                $this->getResponse()->sendResponse();
                exit;
            }

            Zend_Registry::set("pimcore_admin_user", $this->getUser());

            Zend_Registry::set("pimcore_admin_initialized", true);
        }
    }

    /**
     * returns the current user
     * @return User $user
     */
    public function getUser() {
        return $this->user;
    }

    public function setUser(User $user) {
        $this->user = $user;
        Zend_Registry::set("pimcore_user", $this->user);

        $this->setLanguage($this->user->getLanguage());
    }

    public function getLanguage() {
        return $this->language;
    }

    public function setLanguage($language) {

        if (Zend_Locale::isLocale($language, true)) {
            $locale = new Zend_Locale($language);
        }
        else {
            $locale = new Zend_Locale("en");
        }

        $this->language = $locale->getLanguage();
        $this->view->language = $this->getLanguage();

        Zend_Registry::set("Zend_Locale", $locale);

        try {
            $t = Zend_Registry::get("Zend_Translate");
            $t->setLocale($locale);
        }
        catch (Exception $e) {
            // translator not available yet
        }
    }

    public static function initSession() {

        $sessionLifetime = 7200;
        Zend_Session::setOptions(array(
            "throw_startup_exceptions" => false,
            "gc_maxlifetime" => $sessionLifetime,
            "name" => "pimcore_admin_sid",
            "strict" => false,
            "use_only_cookies" => false
        ));
        
        try {
            // register session
            $front = Zend_Controller_Front::getInstance();
            if ($front->getRequest() != null && $front->getRequest()->getParam("pimcore_admin_sid")) {
                // hack to get zend_session work with session-id via get (since SwfUpload doesn't support cookies)
                $_REQUEST["pimcore_admin_sid"] = $front->getRequest()->getParam("pimcore_admin_sid");
                $_COOKIE["pimcore_admin_sid"] = $front->getRequest()->getParam("pimcore_admin_sid");
            }
            if (!empty($_GET["pimcore_admin_sid"])) {
                // hack to get zend_session work with session-id via get (since SwfUpload doesn't support cookies)
                $_REQUEST["pimcore_admin_sid"] = $_GET["pimcore_admin_sid"];
                $_COOKIE["pimcore_admin_sid"] = $_GET["pimcore_admin_sid"];
            }
            
            try {
                Zend_Session::start();
            }
            catch (Exception $e) {
                logger::error("Problem while starting session");
                logger::error($e);
            }
            
        }
        catch (Exception $e) {
            logger::emergency("there is a problem with admin session");
            die();
        }
    }

    public static function initTranslations($instance) {

        //add translations to registry
        $coreLanguageFile = Pimcore_Tool_Admin::getLanguageFile("en");
        $translator = new Zend_Translate('csv', $coreLanguageFile, 'en', array('delimiter' => ','));
        
        $availableLanguages = Pimcore_Tool_Admin::getLanguages();
        
        foreach ($availableLanguages as $lang) {
            if($lang != "en") {
                $languageFile = Pimcore_Tool_Admin::getLanguageFile($lang);
                $translator->addTranslation($languageFile, $lang);
            }
        }
        
        try {
            $locale = Zend_Registry::get("Zend_Locale");
            @$translator->setLocale($locale->getLanguage());
        } catch (Exception $e) {}
        
        Zend_Registry::set("Zend_Translate", $translator);

        if ($instance) {
            $instance->setTranslator($translator);
        }
    }

    public function setTranslator(Zend_Translate $t) {
        $this->translator = $t;
    }

    public function getTranslator() {
        return $this->translator;
    }


}
