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

    /**
     * @var User
     */
    protected $user;

    /**
     * @var string
     */
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
                $config = Pimcore_Config::getSystemConfig();
                $this->setLanguage($config->general->language);

                // try to set browser-language (validation if installed is in $this->setLanguage() )
                $this->setLanguage(new Zend_Locale());
            }
        }

        try {
            Zend_Registry::get("pimcore_admin_initialized");
            $this->setUser(Zend_Registry::get("pimcore_admin_user"));
        }
        catch (Exception $e) {
            // general definitions
            Document::setHideUnpublished(false);
            Object_Abstract::setHideUnpublished(false);
            Object_Abstract::setGetInheritedValues(false);
            Pimcore::setAdminMode();


            // init translations
            self::initTranslations($this);

            // init zend action helpers
            Zend_Controller_Action_HelperBroker::addPrefix('Pimcore_Controller_Action_Helper');

            // authenticate user, first try to authenticate with session information
            $user = Pimcore_Tool_Authentication::authenticateSession();
            if($user instanceof User) {
                $this->setUser($user);
                if ($this->getUser()->getLanguage()) {
                    $this->setLanguage($this->getUser()->getLanguage());
                }
            } else {
                // try to authenticate with digest, but this is only allowed for WebDAV
                if ($this->_getParam("module") == "admin" && $this->_getParam("controller") == "asset" && $this->_getParam("action") == "webdav") {
                    $user = Pimcore_Tool_Authentication::authenticateDigest();
                    if($user instanceof User) {
                        $this->setUser($user);
                        return;
                    }
                }
            }

            // redirect to the login-page if the user isn't authenticated
            if (!$this->getUser() instanceof User && !($this->_getParam("module") == "admin" && $this->_getParam("controller") == "login")) {

                // put a detailed message into the debug.log
                Logger::crit("Prevented access to " . print_r($this->getRequest()->getParams()) . " because there is no user in the session!");
                Logger::crit(array(
                    "server" => $_SERVER,
                    "get" => $_GET,
                    "post" => $_POST,
                    "session" => $_SESSION,
                    "cookie" => $_COOKIE
                ));

                // send a auth header for the client (is covered by the ajax object in javascript)
                $this->getResponse()->setHeader("X-Pimcore-Auth","required");
                // redirect to login page
                $this->_redirect("/admin/login");
                // send immetiatley the response and exit the execution
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

        // check if given language is installed if not => skip
        if(!in_array($locale->getLanguage(), Pimcore_Tool_Admin::getLanguages())) {
            return;
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

    /**
     * @deprecated
     * @static
     * @return void
     */
    public static function initSession() {
        Pimcore_Tool_Authentication::initSession();
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
