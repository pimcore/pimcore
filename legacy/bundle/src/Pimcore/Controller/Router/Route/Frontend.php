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

namespace Pimcore\Controller\Router\Route;

use Pimcore\Tool;
use Pimcore\Config;
use Pimcore\Cache;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Model\Redirect;
use Pimcore\Model\Staticroute;
use Pimcore\Logger;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;

class Frontend extends \Zend_Controller_Router_Route_Abstract
{

    /**
     * @var array
     */
    public static $directRouteTypes = ["page", "snippet", "email", "newsletter", "printpage", "printcontainer"];

    /**
     * @param $type
     */
    public static function addDirectRouteDocumentType($type)
    {
        if (!in_array($type, self::$directRouteTypes)) {
            self::$directRouteTypes[] = $type;
        }
    }

    /**
     * @return array
     */
    public static function getDirectRouteDocumentTypes()
    {
        return self::$directRouteTypes;
    }

    /**
     * @var array
     */
    protected $redirects = [];

    /**
     * @var array
     */
    public $_defaults = [];

    /**
     * @var array
     */
    protected $nearestDocumentByPath = [];

    /**
     * @return int
     */
    public function getVersion()
    {
        return 1;
    }

    /**
     * @param \Zend_Config $config
     * @return Frontend
     */
    public static function getInstance(\Zend_Config $config)
    {
        return new self();
    }

    /**
     * @param  $path
     * @param bool $partial
     * @return array|bool
     */
    public function match($path, $partial = false)
    {

        // this allows the usage of UTF8 URLs and within static routes
        $path = urldecode($path);

        $front = \Zend_Controller_Front::getInstance();
        $matchFound = false;
        $config = Config::getSystemConfig();

        $routeingDefaults = Tool::getRoutingDefaults();

        $params = array_merge($_GET, $_POST);
        $params = array_merge($routeingDefaults, $params);

        // set the original path
        $originalPath = $path;

        // check for password protection (http auth)
        if ($config->general->http_auth) {
            $username = $config->general->http_auth->username;
            $password = $config->general->http_auth->password;
            if ($username && $password && (!Tool::isFrontentRequestByAdmin() || !Tool\Authentication::authenticateSession())) {
                $adapter = new \Zend_Auth_Adapter_Http([
                    "accept_schemes" => "basic",
                    "realm" => Tool::getHostname()
                ]);

                $basicResolver = new \Pimcore\Helper\Auth\Adapter\Http\ResolverStatic($username, $password);
                $adapter->setBasicResolver($basicResolver);
                $adapter->setRequest($front->getRequest());
                $adapter->setResponse($front->getResponse());

                $result = $adapter->authenticate();
                if (!$result->isValid()) {
                    // Bad userame/password, or canceled password prompt
                    echo "Authentication Required";
                    $front->getResponse()->sendResponse();
                    exit;
                }
            }
        }

        // check for a registered site
        try {
            // do not initialize a site if it is a "special" admin request
            if (!Tool::isFrontentRequestByAdmin()) {
                $domain = Tool::getHostname();
                $site = \Zend_Registry::isRegistered("pimcore_site") ? \Zend_Registry::get("pimcore_site") : Site::getByDomain($domain);
                $path = $site->getRootPath() . $path;

                \Zend_Registry::set("pimcore_site", $site);
            }
        } catch (\Exception $e) {
        }


        // test if there is a suitable redirect with override = all (=> priority = 99)
        $this->checkForRedirect($originalPath, true);

        // do not allow requests including /index.php/ => SEO
        // this is after the first redirect check, to allow redirects in index.php?xxx
        if (preg_match("@^/index.php(.*)@", $_SERVER["REQUEST_URI"], $matches) && strtolower($_SERVER["REQUEST_METHOD"]) == "get") {
            $redirectUrl = $matches[1];
            $redirectUrl = ltrim($redirectUrl, "/");
            $redirectUrl = "/" . $redirectUrl;
            header("Location: " . $redirectUrl, true, 301);
            exit;
        }

        // redirect to the main domain if specified
        try {
            $hostRedirect = null;
            if ($config->general->redirect_to_maindomain && $config->general->domain && $config->general->domain != Tool::getHostname() && !Site::isSiteRequest() && !Tool::isFrontentRequestByAdmin()) {
                $hostRedirect = $config->general->domain;
            }
            if (Site::isSiteRequest()) {
                $site = Site::getCurrentSite();
                if ($site->getRedirectToMainDomain() && $site->getMainDomain() != Tool::getHostname()) {
                    $hostRedirect = $site->getMainDomain();
                }
            }

            if ($hostRedirect && !isset($_GET["pimcore_disable_host_redirect"])) {
                $url = ($front->getRequest()->isSecure() ? "https" : "http") . "://" . $hostRedirect . $_SERVER["REQUEST_URI"];

                header("HTTP/1.1 301 Moved Permanently");
                header("Location: " . $url, true, 301);

                // log all redirects to the redirect log
                \Pimcore\Log\Simple::log("redirect", Tool::getAnonymizedClientIp() . " \t Host-Redirect Source: " . $_SERVER["REQUEST_URI"] . " -> " . $url);
                exit;
            }
        } catch (\Exception $e) {
        }


        // check for direct definition of controller/action
        if (!empty($_REQUEST["controller"]) && !empty($_REQUEST["action"])) {
            $matchFound = true;
        }

        // test if there is a suitable page
        if (!$matchFound) {
            try {
                $document = Document::getByPath($path);

                // check for a pretty url inside a site
                if (!$document && Site::isSiteRequest()) {
                    $documentService = new Document\Service();
                    $sitePrettyDocId = $documentService->getDocumentIdByPrettyUrlInSite(Site::getCurrentSite(), $originalPath);

                    if ($sitePrettyDocId) {
                        if ($sitePrettyDoc = Document::getById($sitePrettyDocId)) {
                            $document = $sitePrettyDoc;
                            // undo the modification of the path by the site detection (prefixing with site root path)
                            // this is not necessary when using pretty-urls and will cause problems when validating the
                            // prettyUrl later (redirecting to the prettyUrl in the case the page was called by the real path)
                            $path = $originalPath;
                        }
                    }
                }

                // check for a parent hardlink with childs
                if (!$document instanceof Document) {
                    $hardlinkedParentDocument = $this->getNearestDocumentByPath($path, true);
                    if ($hardlinkedParentDocument instanceof Document\Hardlink) {
                        if ($hardLinkedDocument = Document\Hardlink\Service::getChildByPath($hardlinkedParentDocument, $path)) {
                            $document = $hardLinkedDocument;
                        }
                    }
                }

                // check for direct hardlink
                if ($document instanceof Document\Hardlink) {
                    $hardlinkParentDocument = $document;
                    $document = Document\Hardlink\Service::wrap($hardlinkParentDocument);
                }

                if ($document instanceof Document) {
                    if (in_array($document->getType(), self::getDirectRouteDocumentTypes())) {
                        if (Tool::isFrontentRequestByAdmin() || $document->isPublished()) {
                            $redirectTargetUrl = $originalPath;

                            // check for a pretty url, and if the document is called by that, otherwise redirect to pretty url
                            if ($document instanceof Document\Page
                                && !($document instanceof Document\Hardlink\Wrapper\WrapperInterface)
                                && $document->getPrettyUrl()
                                && !Tool::isFrontentRequestByAdmin()
                            ) {
                                if (rtrim(strtolower($document->getPrettyUrl()), " /") != rtrim(strtolower($originalPath), "/")) {
                                    $redirectTargetUrl = $document->getPrettyUrl();
                                }
                            }

                            $params["document"] = $document;
                            if ($controller = $document->getController()) {
                                $params["controller"] = $controller;
                                $params["action"] = "index";
                            }
                            if ($action = $document->getAction()) {
                                $params["action"] = $action;
                            }
                            if ($module = $document->getModule()) {
                                $params["module"] = $module;
                            }

                            // check for a trailing slash in path, if exists, redirect to this page without the slash
                            // the only reason for this is: SEO, Analytics, ... there is no system specific reason, pimcore would work also with a trailing slash without problems
                            // use $originalPath because of the sites
                            // only do redirecting with GET requests
                            if (strtolower($_SERVER["REQUEST_METHOD"]) == "get") {
                                if ($config->documents->allowtrailingslash) {
                                    if ($config->documents->allowtrailingslash == "no") {
                                        if (substr($redirectTargetUrl, strlen($redirectTargetUrl) - 1, 1) == "/" && $redirectTargetUrl != "/") {
                                            $redirectTargetUrl = rtrim($redirectTargetUrl, "/");
                                        }
                                    }
                                }

                                // only allow the original key of a document to be the URL (lowercase/uppercase)
                                if ($redirectTargetUrl != rawurldecode($document->getFullPath())) {
                                    $redirectTargetUrl = $document->getFullPath();
                                }
                            }

                            if ($redirectTargetUrl !== $originalPath) {
                                if ($_SERVER["QUERY_STRING"]) {
                                    $redirectTargetUrl .= "?" . $_SERVER["QUERY_STRING"];
                                }
                                header("Location: " . $redirectTargetUrl, true, 301);
                                exit;
                            }

                            $matchFound = true;
                        }
                    } elseif ($document->getType() == "link") {
                        // if the document is a link just redirect to the location/href of the link
                        header("Location: " . $document->getHref(), true, 301);
                        exit;
                    }
                }
            } catch (\Exception $e) {
                // no suitable page found
                $foo = "bar";
            }
        }

        // test if there is a suitable static route
        if (!$matchFound) {
            try {
                $list = new Staticroute\Listing();
                $list->setOrder(function ($a, $b) {
                    
                    // give site ids a higher priority
                    if ($a["siteId"] && !$b["siteId"]) {
                        return -1;
                    }
                    if (!$a["siteId"] && $b["siteId"]) {
                        return 1;
                    }

                    if ($a["priority"] == $b["priority"]) {
                        return 0;
                    }

                    return ($a["priority"] < $b["priority"]) ? 1 : -1;
                });
                $routes = $list->load();

                foreach ($routes as $route) {
                    if (!$matchFound) {
                        $routeParams = $route->match($originalPath, $params);
                        if ($routeParams) {
                            $params = $routeParams;

                            // try to get nearest document to the route
                            $document = $this->getNearestDocumentByPath($path, false, ["page", "snippet", "hardlink"]);
                            if ($document instanceof Document\Hardlink) {
                                $document = Document\Hardlink\Service::wrap($document);
                            }
                            $params["document"] = $document;

                            $matchFound = true;
                            Staticroute::setCurrentRoute($route);

                            // add the route object also as parameter to the request object, this is needed in
                            // Pimcore_Controller_Action_Frontend::getRenderScript()
                            // to determine if a call to an action was made through a staticroute or not
                            // more on that infos see Pimcore_Controller_Action_Frontend::getRenderScript()
                            $params["pimcore_request_source"] = "staticroute";

                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                // no suitable route found
            }
        }

        // test if there is a suitable redirect
        if (!$matchFound) {
            $this->checkForRedirect($originalPath, false);
        }

        if (!$matchFound) {
            return false;
        }

        if(isset($params["document"])) {
            \Pimcore::getContainer()->get("request_stack")->getMasterRequest()->attributes->set(DynamicRouter::CONTENT_KEY, $params["document"]);
        }

        // remove pimcore magic parameters
        unset($params["pimcore_outputfilters_disabled"]);
        unset($params["pimcore_document"]);
        unset($params["nocache"]);

        return $params;
    }


    /**
     * @param $path
     * @param bool $ignoreHardlinks
     * @param array $types
     * @return Document|Document\PageSnippet|null|string
     */
    protected function getNearestDocumentByPath($path, $ignoreHardlinks = false, $types = [])
    {
        $cacheKey = $ignoreHardlinks . implode("-", $types);
        $document = null;

        if (isset($this->nearestDocumentByPath[$cacheKey])) {
            $document = $this->nearestDocumentByPath[$cacheKey];
        } else {
            $pathes = [];

            $pathes[] = "/";
            $pathParts = explode("/", $path);
            $tmpPathes = [];
            foreach ($pathParts as $pathPart) {
                $tmpPathes[] = $pathPart;
                $t = implode("/", $tmpPathes);
                if (!empty($t)) {
                    $pathes[] = $t;
                }
            }

            $pathes = array_reverse($pathes);

            foreach ($pathes as $p) {
                if ($document = Document::getByPath($p)) {
                    if (empty($types) || in_array($document->getType(), $types)) {
                        $this->nearestDocumentByPath[$cacheKey] = $document;
                        break;
                    }
                } elseif (Site::isSiteRequest()) {
                    // also check for a pretty url in a site
                    $site = Site::getCurrentSite();
                    $documentService = new Document\Service();

                    // undo the changed made by the site detection in self::match()
                    $originalPath = preg_replace("@^" . $site->getRootPath() . "@", "", $p);

                    $sitePrettyDocId = $documentService->getDocumentIdByPrettyUrlInSite($site, $originalPath);
                    if ($sitePrettyDocId) {
                        if ($sitePrettyDoc = Document::getById($sitePrettyDocId)) {
                            $this->nearestDocumentByPath[$cacheKey] = $sitePrettyDoc;
                            break;
                        }
                    }
                }
            }
        }


        if ($document) {
            if (!$ignoreHardlinks) {
                if ($document instanceof Document\Hardlink) {
                    if ($hardLinkedDocument = Document\Hardlink\Service::getNearestChildByPath($document, $path)) {
                        $document = $hardLinkedDocument;
                    } else {
                        $document = Document\Hardlink\Service::wrap($document);
                    }
                }
            }

            return $document;
        }

        return null;
    }

    /**
     * @param $matchRequestUri
     * @param bool $override
     */
    protected function checkForRedirect($matchRequestUri, $override = false)
    {

        // not for admin requests
        if (Tool::isFrontentRequestByAdmin()) {
            return;
        }

        try {
            $front = \Zend_Controller_Front::getInstance();
            $config = Config::getSystemConfig();

            // get current site if available
            $sourceSite = null;
            if (Site::isSiteRequest()) {
                $sourceSite = Site::getCurrentSite();
            }

            $cacheKey = "system_route_redirect";
            if (empty($this->redirects) && !($this->redirects = Cache::load($cacheKey))) {
                $list = new Redirect\Listing();
                $list->setCondition("active = 1");
                $list->setOrder("DESC");
                $list->setOrderKey("priority");
                $this->redirects = $list->load();

                Cache::save($this->redirects, $cacheKey, ["system", "redirect", "route"], null, 998);
            }

            $requestScheme = Tool::getRequestScheme();
            $matchUrl = Tool::getHostUrl() . $matchRequestUri;
            if (!empty($_SERVER["QUERY_STRING"])) {
                $matchUrl .= "?" . $_SERVER["QUERY_STRING"];
            }

            foreach ($this->redirects as $redirect) {
                $matchAgainst = $matchRequestUri;
                if ($redirect->getSourceEntireUrl()) {
                    $matchAgainst = $matchUrl;
                }

                // if override is true the priority has to be 99 which means that overriding is ok
                if (!$override || ($override && $redirect->getPriority() == 99)) {
                    if (@preg_match($redirect->getSource(), $matchAgainst, $matches)) {

                        // check for a site
                        if ($redirect->getSourceSite() || $sourceSite) {
                            if (!$sourceSite || $sourceSite->getId() != $redirect->getSourceSite()) {
                                continue;
                            }
                        }

                        array_shift($matches);

                        $target = $redirect->getTarget();
                        if (is_numeric($target)) {
                            $d = Document::getById($target);
                            if ($d instanceof Document\Page || $d instanceof Document\Link || $d instanceof Document\Hardlink) {
                                $target = $d->getFullPath();
                            } else {
                                Logger::error("Target of redirect no found (Document-ID: " . $target . ")!");
                                continue;
                            }
                        }

                        // support for pcre backreferences
                        $url = replace_pcre_backreferences($target, $matches);

                        if ($redirect->getTargetSite() && !preg_match("@http(s)?://@i", $url)) {
                            try {
                                $targetSite = Site::getById($redirect->getTargetSite());

                                // if the target site is specified and and the target-path is starting at root (not absolute to site)
                                // the root-path will be replaced so that the page can be shown
                                $url = preg_replace("@^" . $targetSite->getRootPath() . "/@", "/", $url);
                                $url = $requestScheme . "://" . $targetSite->getMainDomain() . $url;
                            } catch (\Exception $e) {
                                Logger::error("Site with ID " . $redirect->getTargetSite() . " not found.");
                                continue;
                            }
                        } elseif (!preg_match("@http(s)?://@i", $url) && $config->general->domain && $redirect->getSourceEntireUrl()) {
                            // prepend the host and scheme to avoid infinite loops when using "domain" redirects
                            $url = ($front->getRequest()->isSecure() ? "https" : "http") . "://" . $config->general->domain . $url;
                        }

                        // pass-through parameters if specified
                        $queryString = $_SERVER["QUERY_STRING"];
                        if ($redirect->getPassThroughParameters() && !empty($queryString)) {
                            $glue = "?";
                            if (strpos($url, "?")) {
                                $glue = "&";
                            }

                            $url .= $glue;
                            $url .= $queryString;
                        }

                        header($redirect->getHttpStatus());
                        header("Location: " . $url, true, $redirect->getStatusCode());

                        // log all redirects to the redirect log
                        \Pimcore\Log\Simple::log("redirect", Tool::getAnonymizedClientIp() . " \t Custom-Redirect ID: " . $redirect->getId() . " , Source: " . $_SERVER["REQUEST_URI"] . " -> " . $url);
                        exit;
                    }
                }
            }
        } catch (\Exception $e) {
            // no suitable route found
        }
    }

    /**
     * @param array $data
     * @param bool $reset
     * @param bool $encode
     * @param bool $partial
     * @return string
     */
    public function assemble($data = [], $reset = false, $encode = true, $partial = false)
    {
        $pathPrefix = "";
        $hasPath = false;

        // try to get document from controller front
        $front = \Zend_Controller_Front::getInstance();

        if (array_key_exists("document", $data) && $data["document"] instanceof Document) {
            $pathPrefix = $data["document"]->getFullPath();
            unset($data["document"]);
            $hasPath = true;
        } elseif ($doc = $front->getRequest()->getParam("document")) {
            $pathPrefix = $doc->getFullPath();
            $hasPath = true;
        }

        $pathPrefix = ltrim($pathPrefix, "/");

        // this is only to append parameters to an existing document
        if (!$reset) {
            $data = array_merge($_GET, $data);
        }

        if (!empty($data)) {
            return $pathPrefix . "?" . array_urlencode($data);
        } elseif ($hasPath) {
            return $pathPrefix;
        }

        return "~NOT~SUPPORTED~";
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getDefault($name)
    {
        if (isset($this->_defaults[$name])) {
            return $this->_defaults[$name];
        }
    }

    /**
     * @return array
     */
    public function getDefaults()
    {
        return $this->_defaults;
    }
}
