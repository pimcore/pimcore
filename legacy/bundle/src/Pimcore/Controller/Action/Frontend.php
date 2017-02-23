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

use Pimcore\Controller\Action;
use Pimcore\Config;
use Pimcore\Tool;
use Pimcore\Tool\Authentication;
use Pimcore\Tool\Session;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Pimcore\Model;
use Pimcore\Translate;
use Pimcore\Logger;

abstract class Frontend extends Action
{

    /**
     * @var Document
     */
    public $document;

    /**
     * @var bool
     */
    public $editmode;

    /**
     * @var \Pimcore\Config\Config
     */
    public $config;

    /**
     * @var bool
     */
    protected $viewRendered = false;

    /**
     * @var \Zend_Locale|null
     */
    protected $previousLocale;

    /**
     * @var bool
     */
    public static $isInitial = true;

    /**
     * @var string
     */
    private static $locale;

    /**
     * @throws \Zend_Controller_Router_Exception
     */
    public function init()
    {

        // this is only executed once per request (first request)
        if (self::$isInitial) {
            \Pimcore::getEventManager()->trigger("frontend.controller.preInit", $this);
        }

        parent::init();

        // log exceptions if handled by error_handler
        $this->checkForErrors();

        // general definitions
        if (self::$isInitial) {
            \Pimcore::unsetAdminMode();
            Document::setHideUnpublished(true);
            Object\AbstractObject::setHideUnpublished(true);
            Object\AbstractObject::setGetInheritedValues(true);
            Object\Localizedfield::setGetFallbackValues(true);
        }

        // assign variables
        $this->view->controller = $this;

        // init website config
        $config = Config::getWebsiteConfig();
        $this->config = $config;
        $this->view->config = $config;

        $document = $this->getParam("document");
        if (!$document instanceof Document) {
            \Zend_Registry::set("pimcore_editmode", false);
            $this->editmode = false;
            $this->view->editmode = false;

            self::$isInitial = false;

            // check for a locale first, and set it if available
            if ($this->getParam("pimcore_parentDocument")) {
                // this is a special exception for renderlets in editmode (ajax request), because they depend on the locale of the parent document
                // otherwise there'll be notices like:  Notice: 'No translation for the language 'XX' available.'
                if ($parentDocument = Document::getById($this->getParam("pimcore_parentDocument"))) {
                    if ($parentDocument->getProperty("language")) {
                        $this->setLocaleFromDocument($parentDocument->getProperty("language"));
                    }
                }
            }

            // no document available, continue, ...
            return;
        } else {
            $this->setDocument($document);

            // register global locale if the document has the system property "language"
            if ($this->getDocument()->getProperty("language")) {
                $this->setLocaleFromDocument($this->getDocument()->getProperty("language"));
            }

            if (self::$isInitial) {
                // append meta-data to the headMeta() view helper,  if it is a document-request
                if (!Model\Staticroute::getCurrentRoute() && ($this->getDocument() instanceof Document\Page)) {
                    if (is_array($this->getDocument()->getMetaData())) {
                        foreach ($this->getDocument()->getMetaData() as $meta) {
                            $this->view->headMeta()->addRaw($meta);
                        }
                    }
                }
            }
        }

        // this is only executed once per request (first request)
        if (self::$isInitial) {

            // contains the logged in user if necessary
            $user = null;

            // default is to set the editmode to false, is enabled later if necessary
            \Zend_Registry::set("pimcore_editmode", false);

            if (Tool::isFrontentRequestByAdmin()) {
                $this->disableBrowserCache();

                // start admin session & get logged in user
                $user = Authentication::authenticateSession();
            }

            if (\Pimcore::inDebugMode()) {
                $this->disableBrowserCache();
            }

            if (!$this->document->isPublished()) {
                if (Tool::isFrontentRequestByAdmin()) {
                    if (!$user) {
                        throw new \Zend_Controller_Router_Exception("access denied for " . $this->document->getFullPath());
                    }
                } else {
                    throw new \Zend_Controller_Router_Exception("access denied for " . $this->document->getFullPath());
                }
            }

            // logged in users only
            if ($user) {

                // set the user to registry so that it is available via \Pimcore\Tool\Admin::getCurrentUser();
                \Zend_Registry::set("pimcore_admin_user", $user);

                // document editmode
                if ($this->getParam("pimcore_editmode")) {
                    \Zend_Registry::set("pimcore_editmode", true);

                    // check if there is the document in the session
                    $docKey = "document_" . $this->getDocument()->getId();
                    $docSession = Session::getReadOnly("pimcore_documents");

                    if ($docSession->$docKey) {
                        // if there is a document in the session use it
                        $this->setDocument($docSession->$docKey);
                    } else {
                        // set the latest available version for editmode if there is no doc in the session
                        $latestVersion = $this->getDocument()->getLatestVersion();
                        if ($latestVersion) {
                            $latestDoc = $latestVersion->loadData();
                            if ($latestDoc instanceof Document\PageSnippet) {
                                $this->setDocument($latestDoc);
                            }
                        }
                    }

                    // register editmode plugin
                    $front = \Zend_Controller_Front::getInstance();
                    $front->registerPlugin(new \Pimcore\Controller\Plugin\Frontend\Editmode($this), 1000);
                }

                // document preview
                if ($this->getParam("pimcore_preview")) {
                    // get document from session
                    $docKey = "document_" . $this->getParam("document")->getId();
                    $docSession = Session::getReadOnly("pimcore_documents");

                    if ($docSession->$docKey) {
                        $this->setDocument($docSession->$docKey);
                    }
                }

                // object preview
                if ($this->getParam("pimcore_object_preview")) {
                    $key = "object_" . $this->getParam("pimcore_object_preview");

                    $session = Session::getReadOnly("pimcore_objects");
                    if ($session->$key) {
                        $object = $session->$key;
                        // add the object to the registry so every call to Object::getById() will return this object instead of the real one
                        \Zend_Registry::set("object_" . $object->getId(), $object);
                    }
                }

                // for version preview
                if ($this->getParam("pimcore_version")) {
                    // only get version data at the first call || because of embedded Snippets ...
                    if (!\Zend_Registry::isRegistered("pimcore_version_active")) {
                        $version = Model\Version::getById($this->getParam("pimcore_version"));
                        $this->setDocument($version->getData());

                        \Zend_Registry::set("pimcore_version_active", true);
                    }
                }
            }


            // for public versions
            if ($this->getParam("v")) {
                try {
                    $version = Model\Version::getById($this->getParam("v"));
                    if ($version->getPublic()) {
                        $this->setDocument($version->getData());
                    }
                } catch (\Exception $e) {
                }
            }

            // check for persona
            if ($this->getDocument() instanceof Document\Page) {
                $this->getDocument()->setUsePersona(null); // reset because of preview and editmode (saved in session)
                if ($this->getParam("_ptp") && self::$isInitial) {
                    $this->getDocument()->setUsePersona($this->getParam("_ptp"));
                }
            }

            // check if document is a wrapped hardlink, if this is the case send a rel=canonical header to the source document
            if ($this->getDocument() instanceof Document\Hardlink\Wrapper\WrapperInterface) {
                // get the cononical (source) document
                $hardlinkCanonicalSourceDocument = Document::getById($this->getDocument()->getId());
                $request = $this->getRequest();
                $canonical = null;

                if (\Pimcore\Tool\Frontend::isDocumentInCurrentSite($hardlinkCanonicalSourceDocument)) {
                    $canonical = $request->getScheme() . "://" . $request->getHttpHost() . $hardlinkCanonicalSourceDocument->getFullPath();
                } elseif (Model\Site::isSiteRequest()) {
                    $sourceSite = \Pimcore\Tool\Frontend::getSiteForDocument($hardlinkCanonicalSourceDocument);
                    if ($sourceSite) {
                        if ($sourceSite->getMainDomain()) {
                            $sourceSiteRelPath = preg_replace("@^" . preg_quote($sourceSite->getRootPath(), "@") . "@", "", $hardlinkCanonicalSourceDocument->getRealFullPath());
                            $canonical = $request->getScheme() . "://" . $sourceSite->getMainDomain() . $sourceSiteRelPath;
                        }
                    }
                }

                if ($canonical) {
                    $this->getResponse()->setHeader("Link", '<' . $canonical . '>; rel="canonical"');
                }
            }

            \Pimcore::getEventManager()->trigger("frontend.controller.postInit", $this);
        }


        // set some parameters
        $this->editmode = \Zend_Registry::get("pimcore_editmode");
        $this->view->editmode = \Zend_Registry::get("pimcore_editmode");

        self::$isInitial = false;
    }

    /**
     * @return \Pimcore\Config\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param $locale
     */
    protected function setLocaleFromDocument($locale)
    {

        // we need to backup the locale that is currently set (if so), so that we can restore it on ::postDispatch()
        // this is especially important when a document includes another document ($this->inc, $this->snippet, ...)
        // and the included document has a different locale. If we do not restore the previous locale this would have
        // the effect that the parent document will have a wrong language from the point on where the document with
        // the different locale was included, so e.g. $this->translate() would end in wrong translations
        if (\Zend_Registry::isRegistered("Zend_Locale")) {
            if ((string) \Zend_Registry::get("Zend_Locale") != (string) $locale) {
                $this->previousLocale = \Zend_Registry::get("Zend_Locale");
            }
        }
        self::setLocale($locale);
    }

    /**
     * @param $locale
     */
    public function setLocale($locale)
    {
        if ((string) $locale != self::$locale && \Zend_Locale::isLocale($locale)) {
            $locale = new \Zend_Locale($locale);
            \Zend_Registry::set('Zend_Locale', $locale);
            $this->getResponse()->setHeader("Content-Language", strtolower(str_replace("_", "-", (string) $locale)), true);

            // now we prepare everything for setlocale()
            $localeList = [(string) $locale . ".utf8"];

            if ($locale->getRegion()) {
                // add only the language to the list as a fallback
                $localeList[] = $locale->getLanguage() . ".utf8";
            } else {
                // try to get a list of territories for this language
                // usually OS have no "language only" locale, only the combination language-territory (eg. Debian)
                $languageRegionMapping = include PIMCORE_PATH . "/config/data/cldr-language-territory-mapping.php";
                if (isset($languageRegionMapping[$locale->getLanguage()])) {
                    foreach ($languageRegionMapping[$locale->getLanguage()] as $territory) {
                        $localeList[] = $locale->getLanguage() . "_" . $territory . ".utf8";
                    }
                }
            }

            // currently we have to exclude LC_MONETARY from being set, because of issues in combination with
            // Zend_Currency -> see also https://github.com/zendframework/zf1/issues/706
            // once this is resolved we can safely set the locale for LC_MONETARY as well.
            setlocale(LC_ALL & ~LC_MONETARY, $localeList);

            // reconfigure translation management
            if (\Zend_Registry::isRegistered("Zend_Translate")) {
                $translator = \Zend_Registry::get("Zend_Translate");
                if ($translator instanceof Translate) {
                    $translator->setLocale($locale);
                }
            }

            self::$locale = (string) $locale;
        }
    }

    /**
     * @param $document
     * @return $this
     */
    public function setDocument($document)
    {
        if ($document instanceof Document) {
            $this->document = $document;
            $this->view->document = $document;
        }

        return $this;
    }

    /**
     * @return Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @return null|\Pimcore\Translate|\Pimcore\Translate\Website
     * @throws \Zend_Exception
     */
    public function initTranslation()
    {
        $translate = null;
        if (\Zend_Registry::isRegistered("Zend_Translate")) {
            $t = \Zend_Registry::get("Zend_Translate");
            // this check is necessary for the case that a document is rendered within an admin request
            // example: send test newsletter
            if ($t instanceof \Pimcore\Translate) {
                $translate = $t;
            }
        }

        if (!$translate) {
            // setup \Zend_Translate
            try {
                $locale = \Zend_Registry::get("Zend_Locale");

                $translate = new \Pimcore\Translate\Website($locale);

                if (Tool::isValidLanguage($locale)) {
                    $translate->setLocale($locale);
                } else {
                    Logger::error("You want to use an invalid language which is not defined in the system settings: " . $locale);
                    // fall back to the first (default) language defined
                    $languages = Tool::getValidLanguages();
                    if ($languages[0]) {
                        Logger::error("Using '" . $languages[0] . "' as a fallback, because the language '".$locale."' is not defined in system settings");
                        $translate = new \Pimcore\Translate\Website($languages[0]); // reinit with new locale
                        $translate->setLocale($languages[0]);
                    } else {
                        throw new \Exception("You have not defined a language in the system settings (Website -> Frontend-Languages), please add at least one language.");
                    }
                }


                // register the translator in \Zend_Registry with the key "\Zend_Translate" to use the translate helper for \Zend_View
                \Zend_Registry::set("Zend_Translate", $translate);
            } catch (\Exception $e) {
                Logger::error("initialization of Pimcore_Translate failed");
                Logger::error($e);
            }
        }

        return $translate;
    }

    /**
     * @return null
     */
    public function getRenderScript()
    {

        // try to get the template out of the params
        if ($this->getParam("template")) {
            return $this->getParam("template");
        }

        // try to get template out of the document object, but only if the parameter `staticroute´ is not set, which indicates
        // if a request comes through a static/custom route (contains the route Object => Staticroute)
        // see PIMCORE-1545
        if ($this->document instanceof Document && !in_array($this->getParam("pimcore_request_source"), ["staticroute", "renderlet"])) {
            if (method_exists($this->document, "getTemplate") && $this->document->getTemplate()) {
                return $this->document->getTemplate();
            }
        }

        return null;
    }

    /**
     *
     */
    protected function forceRender()
    {
        if (!$this->viewRendered) {
            if ($script = $this->getRenderScript()) {
                $this->renderScript($script);
            } else {
                $this->render();
            }
        }
    }

    /**
     * @param null $action
     * @param null $name
     * @param bool $noController
     */
    public function render($action = null, $name = null, $noController = false)
    {
        if (!$this->viewRendered) {
            $this->viewRendered = true;
            parent::render($action, $name, $noController);
        }
    }

    /**
     * @param string $script
     * @param null $name
     */
    public function renderScript($script, $name = null)
    {
        if (!$this->viewRendered) {
            $this->viewRendered = true;
            parent::renderScript($script, $name);
        }
    }

    /**
     *
     */
    public function preDispatch()
    {

        // initialize translation if required
        $this->initTranslation();

        // this is for $this->action() in templates when they are inside a block element
        try {
            if (!$this->getParam("disableBlockClearing")) {
                $this->parentBlockCurrent = [];
                if (\Zend_Registry::isRegistered('pimcore_tag_block_current')) {
                    $this->parentBlockCurrent = \Zend_Registry::get("pimcore_tag_block_current");
                }

                $this->parentBlockNumeration = [];
                if (\Zend_Registry::isRegistered('pimcore_tag_block_numeration')) {
                    $this->parentBlockNumeration = \Zend_Registry::get("pimcore_tag_block_numeration");
                }

                \Zend_Registry::set("pimcore_tag_block_current", []);
                \Zend_Registry::set("pimcore_tag_block_numeration", []);
            }
        } catch (\Exception $e) {
            Logger::debug($e);
        }
    }

    /**
     *
     */
    public function postDispatch()
    {
        parent::postDispatch();

        if (isset($this->parentBlockCurrent) && $this->parentBlockCurrent && !$this->getParam("disableBlockClearing")) {
            $this->forceRender();

            \Zend_Registry::set("pimcore_tag_block_current", $this->parentBlockCurrent);
            \Zend_Registry::set("pimcore_tag_block_numeration", $this->parentBlockNumeration);
        }

        // restore the previous set locale if available
        // for a detailed description on this, please have a look at $this->setLocaleFromDocument()
        if ($this->previousLocale) {
            $this->forceRender();
            $this->setLocale($this->previousLocale);
            $this->previousLocale = null;
        }
    }

    /**
     * @throws \Zend_Controller_Response_Exception
     */
    public function checkForErrors()
    {
        if ($error = $this->getParam('error_handler')) {
            if ($error->exception) {
                if ($error->exception instanceof \Zend_Controller_Router_Exception || $error->exception instanceof \Zend_Controller_Action_Exception) {
                    header('HTTP/1.1 404 Not Found');
                    //$this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
                    $this->getResponse()->setHttpResponseCode(404);

                    // check if the resource that wasn't found is a common static file
                    // for them we don't show the error page, as generating this is very heavy in terms of performance
                    if (preg_match("/\.(js|css|png|jpe?g|gif|eot|ttf|woff|svg|ico|map|swf|txt)$/", $this->getRequest()->getPathInfo())) {
                        echo "HTTP/1.1 404 Not Found\nFiltered by error handler (static file exception)";
                        exit;
                    }
                } else {
                    header('HTTP/1.1 503 Service Temporarily Unavailable');
                    //$this->getResponse()->setRawHeader('HTTP/1.1 503 Service Temporarily Unavailable');
                    $this->getResponse()->setHttpResponseCode(503);
                }

                if (\Pimcore::inDebugMode()) {
                    Logger::error("Unable to find URL: " . $_SERVER["REQUEST_URI"]);
                    Logger::error($error->exception);
                }

                $results = \Pimcore::getEventManager()->trigger("frontend.error", $this, [
                    "exception" => $error->exception
                ]);

                $cacheKeySuffix = "";
                foreach ($results as $result) {
                    $cacheKeySuffix .= "_" . $result;
                }

                try {
                    // check if we have the error page already in the cache
                    // the cache is written in Pimcore\Controller\Plugin\HttpErrorLog::dispatchLoopShutdown()
                    $responseCode = $this->getResponse()->getHttpResponseCode();
                    $cacheKey = "error_" . $responseCode . "_" . \Pimcore\Tool\Frontend::getSiteKey() . $cacheKeySuffix;
                    if ($responseBody = \Pimcore\Cache::load($cacheKey)) {
                        $this->getResponse()->setBody($responseBody);
                        $this->getResponse()->sendResponse();

                        // write to http_error log
                        $errorLogPlugin = \Zend_Controller_Front::getInstance()->getPlugin("Pimcore\\Controller\\Plugin\\HttpErrorLog");
                        if ($errorLogPlugin) {
                            $errorLogPlugin->writeLog();
                        }

                        exit;
                    } else {
                        $errorLogPlugin = \Zend_Controller_Front::getInstance()->getPlugin("Pimcore\\Controller\\Plugin\\HttpErrorLog");
                        if ($errorLogPlugin) {
                            $errorLogPlugin->setCacheKey($cacheKey);
                        }
                        $document = \Zend_Registry::get("pimcore_error_document");
                        $this->setDocument($document);
                        $this->setParam("document", $document);
                        $this->disableLayout();

                        // http_error log writing is done in Pimcore_Controller_Plugin_HttpErrorLog in this case
                    }
                } catch (\Exception $e) {
                    $m = "Unable to load error document";
                    Tool::exitWithError($m);
                }
            }
        }
    }
}
