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

    /**
     * Indicates if this is the first call or a following
     * @var bool
     */
    protected static $adminInitialized = false;

    public function init() {

        parent::init();

        // set language
        if(Zend_Registry::isRegistered("Zend_Locale")) {
            $locale = (string) Zend_Registry::get("Zend_Locale");
            $this->setLanguage($locale);
        } else {
            if ($this->getParam("language")) {
                $this->setLanguage($this->getParam("language"));
            }
            else {
                $config = Pimcore_Config::getSystemConfig();
                $this->setLanguage($config->general->language);

                // try to set browser-language (validation if installed is in $this->setLanguage() )
                $this->setLanguage(new Zend_Locale());
            }
        }

        if(self::$adminInitialized) {
            // this will be executed on every call to this init() method
            try {
                $this->setUser(Zend_Registry::get("pimcore_admin_user"));
            } catch (Exception $e) {
                Logger::emerg("adminInitialized was set to true although there was no user set in the registry -> to be save the process was killed");
                exit;
            }
        } else {
            // the following code is only called once, even when there are some subcalls (eg. with $this->action, ... )

            $this->disableBrowserCache();

            // general definitions
            Document::setHideUnpublished(false);
            Object_Abstract::setHideUnpublished(false);
            Object_Abstract::setGetInheritedValues(false);
            Pimcore::setAdminMode();

            // init translations
            self::initTranslations($this);

            // init zend action helpers
            Zend_Controller_Action_HelperBroker::addPrefix('Pimcore_Controller_Action_Helper');

            // this is to make it possible to use the session id as a part of the route (ZF default route) used for pixlr.com editors, etc.
            if($this->getParam("pimcore_admin_sid")) {
                $_REQUEST["pimcore_admin_sid"] = $this->getParam("pimcore_admin_sid");
            }

            // authenticate user, first try to authenticate with session information
            $user = Pimcore_Tool_Authentication::authenticateSession();
            if($user instanceof User) {
                $this->setUser($user);
                if ($this->getUser()->getLanguage()) {
                    $this->setLanguage($this->getUser()->getLanguage());
                }
            } else {
                // try to authenticate with digest, but this is only allowed for WebDAV
                if ($this->getParam("module") == "admin" && $this->getParam("controller") == "asset" && $this->getParam("action") == "webdav") {
                    $user = Pimcore_Tool_Authentication::authenticateDigest();
                    if($user instanceof User) {
                        $this->setUser($user);

                        Zend_Registry::set("pimcore_admin_user", $this->getUser());
                        self::$adminInitialized = true;

                        return;
                    }
                }
            }

            // redirect to the login-page if the user isn't authenticated
            if (!$this->getUser() instanceof User && !($this->getParam("module") == "admin" && $this->getParam("controller") == "login")) {

                // put a detailed message into the debug.log
                Logger::warn("Prevented access to " . $_SERVER["REQUEST_URI"] . " because there is no user in the session!");
                Logger::warn(array(
                    "server" => $_SERVER,
                    "get" => $_GET,
                    "post" => $_POST,
                    "session" => $_SESSION,
                    "cookie" => $_COOKIE
                ));

                // send a auth header for the client (is covered by the ajax object in javascript)
                $this->getResponse()->setHeader("X-Pimcore-Auth","required");
                // redirect to login page
                $this->redirect("/admin/login");
                // exit the execution -> just to be sure
                exit;
            }

            // we're now authenticated so we can remove the default error handler so that we get just the normal PHP errors
            if($this->getParam("controller") != "login") {
                $front = Zend_Controller_Front::getInstance();
                $front->unregisterPlugin("Pimcore_Controller_Plugin_ErrorHandler");
                $front->throwExceptions(true);
                @ini_set("display_errors", "On");
                @ini_set("display_startup_errors", "On");
            }

            Zend_Registry::set("pimcore_admin_user", $this->getUser());
            self::$adminInitialized = true;

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

    public function setLanguage($language, $useFrontendLanguages = false) {

        if (Zend_Locale::isLocale($language, true)) {
            $locale = new Zend_Locale($language);
        }
        else {
            $locale = new Zend_Locale("en");
        }

        if($useFrontendLanguages) {
            // check if given language is a valid language
            if(!Pimcore_Tool::isValidLanguage($locale)) {
                return;
            }

            Zend_Registry::set("Zend_Locale", $locale);
        } else {
            // check if given language is installed if not => skip
            if(!in_array((string) $locale, Pimcore_Tool_Admin::getLanguages())) {
                return;
            }

            Zend_Registry::set("Zend_Locale", $locale);
            if(Zend_Registry::isRegistered("Zend_Translate")) {
                $t = Zend_Registry::get("Zend_Translate");
                $t->setLocale($locale);
            }

        }

        $this->language = (string) $locale;
        $this->view->language = $this->getLanguage();

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
        
        if(Zend_Registry::isRegistered("Zend_Locale")) {
            $locale = Zend_Registry::get("Zend_Locale");
            @$translator->setLocale($locale);
        }
        
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
