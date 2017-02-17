<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Controller\Action;

use Pimcore\Bundle\PimcoreAdminBundle\Security\User\User;
use Pimcore\Controller\Action;
use Pimcore\Config;
use Pimcore\Tool\Authentication;
use Pimcore\Tool;
use Pimcore\Tool\Session;
use Pimcore\Tool\Admin as AdminTool;
use Pimcore\Model;
use Pimcore\Logger;

abstract class Admin extends Action
{

    /**
     * @var Model\User
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

    /**
     * @throws \Zend_Exception
     */
    public function init()
    {
        parent::init();

        // set language
        if (\Zend_Registry::isRegistered("Zend_Locale")) {
            $locale = (string) \Zend_Registry::get("Zend_Locale");
            $this->setLanguage($locale);
        } else {
            if ($this->getParam("language")) {
                $this->setLanguage($this->getParam("language"));
            } else {
                $config = Config::getSystemConfig();
                $this->setLanguage($config->general->language);

                // try to set browser-language (validation if installed is in $this->setLanguage() )
                $this->setLanguage(new \Zend_Locale());
            }
        }

        $this->loadUser();

        if (self::$adminInitialized) {
            // this will be executed on every call to this init() method
            try {
                $this->setUser(\Zend_Registry::get("pimcore_admin_user"));
            } catch (\Exception $e) {
                Logger::emerg("adminInitialized was set to true although there was no user set in the registry -> to be save the process was killed");
                exit;
            }
        } else {
            // the following code is only called once, even when there are some subcalls (eg. with $this->action, ... )

            \Pimcore::getEventManager()->trigger("admin.controller.preInit", $this);

            $this->disableBrowserCache();

            // general definitions
            Model\Document::setHideUnpublished(false);
            Model\Object\AbstractObject::setHideUnpublished(false);
            Model\Object\AbstractObject::setGetInheritedValues(false);
            Model\Object\Localizedfield::setGetFallbackValues(false);
            \Pimcore::setAdminMode();

            // init translations
            self::initTranslations($this);

            // init zend action helpers, we need to leave the prefixed class name here as the plugin loader isn't able to handle namespaces
            \Zend_Controller_Action_HelperBroker::addPrefix('Pimcore_Controller_Action_Helper');

            // this is to make it possible to use the session id as a part of the route (ZF default route) used for external editors, etc.
            if ($this->getParam("pimcore_admin_sid")) {
                $_REQUEST["pimcore_admin_sid"] = $this->getParam("pimcore_admin_sid");
            }

//            // authenticate user, first try to authenticate with session information
//            $user = Authentication::authenticateSession();
//            if ($user instanceof Model\User) {
//                $this->setUser($user);
//                if ($this->getUser()->getLanguage()) {
//                    $this->setLanguage($this->getUser()->getLanguage());
//                }
//            } else {
//                // try to authenticate with http basic auth, but this is only allowed for WebDAV
//                if ($this->getParam("module") == "admin" && $this->getParam("controller") == "asset" && $this->getParam("action") == "webdav") {
//                    $user = Authentication::authenticateHttpBasic();
//                    if ($user instanceof Model\User) {
//                        $this->setUser($user);
//
//                        \Zend_Registry::set("pimcore_admin_user", $this->getUser());
//                        self::$adminInitialized = true;
//
//                        return;
//                    }
//                }
//            }

//            // redirect to the login-page if the user isn't authenticated
//            if (!$this->getUser() instanceof Model\User && !($this->getParam("module") == "admin" && $this->getParam("controller") == "login")) {
//
//                // put a detailed message into the debug.log
//                Logger::error("Prevented access to " . $_SERVER["REQUEST_URI"] . " because there is no user in the session!", [
//                    "server" => $_SERVER,
//                    "get" => $_GET,
//                    "post" => $_POST,
//                    "session" => isset($_SESSION) ? $_SESSION : null,
//                    "cookie" => $_COOKIE
//                ]);
//
//                if ($this->getRequest()->isXmlHttpRequest()) {
//                    header('HTTP/1.0 403 Forbidden', true, 403);
//                    echo "Session expired or unauthorized request. Please reload and try again!";
//                } else {
//                    // redirect to login page
//                    $this->redirect("/admin/login");
//                }
//
//                // exit the execution -> just to be sure
//                exit;
//            }

            // we're now authenticated so we can remove the default error handler so that we get just the normal PHP errors
            if ($this->getParam("controller") != "login") {
                $front = \Zend_Controller_Front::getInstance();
                $front->unregisterPlugin("Pimcore\\Controller\\Plugin\\ErrorHandler");
                $front->throwExceptions(true);
                @ini_set("display_errors", "On");
                @ini_set("display_startup_errors", "On");
            }

            \Zend_Registry::set("pimcore_admin_user", $this->getUser());
            self::$adminInitialized = true;


            // usage statistics
            $this->logUsageStatistics();

            \Pimcore::getEventManager()->trigger("admin.controller.postInit", $this);
        }
    }

    /**
     * Loads the user from the symfony security storage instead of handling authentication in init()
     *
     * @throws \Exception
     */
    protected function loadUser()
    {
        if ($user = Authentication::getUser()) {
            $this->setUser($user);
        }
    }

    /**
     * returns the current user
     * @return Model\User $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param Model\User $user
     * @return $this
     */
    public function setUser(Model\User $user)
    {
        $this->user = $user;
        \Zend_Registry::set("pimcore_admin_user", $this->user);

        $this->setLanguage($this->user->getLanguage());

        // update perspective settings
        $requestedPerspective = $this->getParam("perspective");
        if ($requestedPerspective) {
            if ($requestedPerspective != $user->getActivePerspective()) {
                $existingPerspectives = array_keys(Config::getPerspectivesConfig()->toArray());
                if (!in_array($requestedPerspective, $existingPerspectives)) {
                    $requestedPerspective = null;
                }
            }
        }

        if (!$requestedPerspective || !$user->isAllowed($requestedPerspective, "perspective")) {
            //choose active perspective or a first allowed
            $requestedPerspective = $user->isAllowed($user->getActivePerspective(), "perspective")
                ? $user->getActivePerspective()
                : $user->getFirstAllowedPerspective();
        }

        if ($requestedPerspective != $user->getActivePerspective()) {
            $user->setActivePerspective($requestedPerspective);
            $user->save();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param $language
     * @param bool $useFrontendLanguages
     * @return $this
     * @throws \Zend_Exception
     */
    public function setLanguage($language, $useFrontendLanguages = false)
    {
        if (\Zend_Locale::isLocale($language, true)) {
            $locale = new \Zend_Locale($language);
        } else {
            $locale = new \Zend_Locale("en");
        }

        if ($useFrontendLanguages) {
            // check if given language is a valid language
            if (!Tool::isValidLanguage($locale)) {
                return;
            }

            \Zend_Registry::set("Zend_Locale", $locale);
        } else {
            // check if given language is installed if not => skip
            if (!in_array((string) $locale->getLanguage(), AdminTool::getLanguages())) {
                return;
            }

            \Zend_Registry::set("Zend_Locale", $locale);
            if (\Zend_Registry::isRegistered("Zend_Translate")) {
                $t = \Zend_Registry::get("Zend_Translate");
                if ((string) $locale != (string) $t->getLocale()) {
                    $languageFile = AdminTool::getLanguageFile($locale);
                    $t->addTranslation($languageFile, $locale);
                    $t->setLocale($locale);
                }
            }
        }

        $this->language = (string) $locale;
        $this->view->language = $this->getLanguage();

        return $this;
    }

    /**
     * @param $instance
     * @throws \Zend_Exception
     */
    public static function initTranslations($instance)
    {
        $language = "en";
        $locale = $instance->getLanguage();
        if ($locale) {
            $locale = new \Zend_Locale($locale);
            foreach ([(string) $locale, $locale->getLanguage()] as $localeVariant) {
                if (in_array($localeVariant, AdminTool::getLanguages())) {
                    $language = $localeVariant;
                    break;
                }
            }
        }

        //add translations to registry
        $coreLanguageFile = AdminTool::getLanguageFile($language);
        $translator = new \Zend_Translate('Pimcore\Translate\Adapter\Json', $coreLanguageFile, $language);

        $languageFile = AdminTool::getLanguageFile($language);
        $translator->addTranslation($languageFile, $language);

        if (\Zend_Registry::isRegistered("Zend_Locale")) {
            $locale = \Zend_Registry::get("Zend_Locale");
            @$translator->setLocale($locale);
        }

        \Zend_Registry::set("Zend_Translate", $translator);

        if ($instance) {
            $instance->setTranslator($translator);
        }
    }

    /**
     * @param \Zend_Translate $t
     * @return $this
     */
    public function setTranslator(\Zend_Translate $t)
    {
        $this->translator = $t;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     *
     */
    protected function protectCSRF()
    {
        // TODO use symfony CSRF implementation
        return;

        $csrfToken = Session::useSession(function ($adminSession) {
            return $adminSession->csrfToken;
        });

        if ($csrfToken != $_SERVER["HTTP_X_PIMCORE_CSRF_TOKEN"]) {
            die("Detected CSRF Attack! Do not do evil things with pimcore ... ;-)");
        }
    }

    /**
     * @param $permission
     * @throws \Exception
     */
    protected function checkPermission($permission)
    {
        if (!$this->getUser() || !$this->getUser()->isAllowed($permission)) {
            $message = "attempt to access " . $permission . ", but has no permission to do so.";
            Logger::err($message);
            throw new \Exception($message);
        }
    }

    /**
     * @throws \Zend_Json_Exception
     */
    protected function logUsageStatistics()
    {
        $params = [];
        $disallowedKeys = ["_dc", "module", "controller", "action", "password"];
        foreach ($this->getAllParams() as $key => $value) {
            if (is_json($value)) {
                $value = \Zend_Json::decode($value);
                if (is_array($value)) {
                    array_walk_recursive($value, function (&$item, $key) {
                        if (strpos($key, "pass") !== false) {
                            $item = "*************";
                        }
                    });
                }
                $value = \Zend_Json::encode($value);
            }


            if (!in_array($key, $disallowedKeys) && is_string($value)) {
                $params[$key] = (strlen($value) > 40) ? substr($value, 0, 40) . "..." : $value;
            }
        }

        \Pimcore\Log\Simple::log("usagelog",
            ($this->getUser() ? $this->getUser()->getId() : "0") . "|" .
            $this->getParam("module") . "|" .
            $this->getParam("controller") . "|" .
            $this->getParam("action")."|" . @json_encode($params));
    }
}
