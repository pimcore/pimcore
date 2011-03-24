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

class Pimcore_Controller_Router_Route_Frontend extends Zend_Controller_Router_Route_Abstract {

    /**
     * @var array
     */
    protected $redirects = array();

    /**
     * @var array
     */
    public $_defaults = array();

    /**
     * @return int
     */
    public function getVersion() {
        return 1;
    }

    /**
     * @static
     * @param Zend_Config $config
     * @return Pimcore_Controller_Router_Route_Frontend
     */
    public static function getInstance(Zend_Config $config) {
        return new self();
    }


    /**
     * @param  $path
     * @param bool $partial
     * @return array|bool
     */
    public function match($path, $partial = false) {
                
        $matchFound = false;
        $config = Zend_Registry::get("pimcore_config_system");

        $routeingDefaults = Pimcore_Tool::getRoutingDefaults();

        $params = array_merge($_GET, $_POST);
        $params = array_merge($routeingDefaults, $params);
        
        // set the original path
        $originalPath = $path;
        
        // check for a registered site
        try {
            if ($config->general->domain != $_SERVER["HTTP_HOST"]) {
                $domain = $_SERVER["HTTP_HOST"];
                $site = Site::getByDomain($domain);
                $site->setRootPath($site->getRootDocument()->getFullPath());
                $path = $site->getRootDocument()->getFullPath() . $path;

                Zend_Registry::set("pimcore_site", $site);
            }
        }
        catch (Exception $e) {}


        // check for direct definition of controller/action
        if (!empty($_REQUEST["controller"]) && !empty($_REQUEST["action"])) {
            $matchFound = true;
        }
        
        // you can also call a page by it's ID /?pimcore_document=XXXX
        if (!$matchFound) {
            if(!empty($params["pimcore_document"])) {
                $doc = Document::getById($params["pimcore_document"]);
                if($doc instanceof Document) {
                    $path = $doc->getFullPath();
                }
            }
        }

        // test if there is a suitable redirect with override = all (=> priority = 99)
        if (!$matchFound) {
            $this->checkForRedirect(true);
        }

        // test if there is a suitable page
        if (!$matchFound) {
            try {
                $document = Document::getByPath($path);

                if ($document instanceof Document) {
                    
                    if ($document->getType() == "page" || $document->getType() == "snippet") {

                        if (!empty($params["pimcore_version"]) || !empty($params["pimcore_preview"]) || !empty($params["pimcore_admin"]) || !empty($params["pimcore_editmode"]) || $document->isPublished()) {
                            $params["document"] = $document;
                            if ($controller = $document->getController()) {
                                $params["controller"] = $controller;
                                $params["action"] = "index";
                            }
                            if ($action = $document->getAction()) {
                                $params["action"] = $action;
                            }

                            // check for a trailing slash in path, if exists, redirect to this page without the slash
                            // the only reason for this is: SEO, Analytics, ... there is no system specific reason, pimcore would work also with a trailing slash without problems
                            // use $originalPath because of the sites
                            if($config->documents->allowtrailingslash) {
                                if($config->documents->allowtrailingslash == "no") {
                                    if(substr($originalPath, strlen($originalPath)-1,1) == "/" && $originalPath != "/") {
                                        $redirectUrl = rtrim($originalPath,"/");
                                        if($_SERVER["QUERY_STRING"]) {
                                            $redirectUrl .= "?" . $_SERVER["QUERY_STRING"];
                                        }
                                        header("Location: " . $redirectUrl, true, 301);
                                        exit;
                                    }
                                }
                            }

                            if($config->documents->allowcapitals) {
                                if($config->documents->allowcapitals == "no") {
                                    if(strtolower($originalPath) != $originalPath) {
                                        $redirectUrl = strtolower($originalPath);
                                        if($_SERVER["QUERY_STRING"]) {
                                            $redirectUrl .= "?" . $_SERVER["QUERY_STRING"];
                                        }
                                        header("Location: " . $redirectUrl, true, 301);
                                        exit;
                                    }
                                }
                            }


                            $matchFound = true;
                        }
                    } else if ($document->getType() == "link")  {
                        // if the document is a link just redirect to the location/href of the link
                        header("Location: " . $document->getHref(),true,301);
                        exit;
                    }
                }
            }
            catch (Exception $e) {
                // no suitable page found
            }
        }

        // test if there is a suitable static route
        if (!$matchFound) {
            try {
                
                $cacheKey = "system_route_staticroute";
                if (!$routes = Pimcore_Model_Cache::load($cacheKey)) {
                
                    $list = new Staticroute_List();
                    $list->setOrderKey("priority");
                    $list->setOrder("DESC");
                    $routes = $list->load();
                    
                    Pimcore_Model_Cache::save($routes, $cacheKey, array("system","staticroute","route"));
                }
                
                foreach ($routes as $route) {

                    if (@preg_match($route->getPattern(), $originalPath) && !$matchFound) {
                        $params = array_merge($route->getDefaultsArray(), $params);

                        $variables = explode(",", $route->getVariables());

                        preg_match_all($route->getPattern(), $originalPath, $matches);

                        if (is_array($matches) && count($matches) > 1) {
                            foreach ($matches as $index => $match) {
                                if ($variables[$index - 1]) {
                                    $params[$variables[$index - 1]] = $match[0];
                                }
                            }
                        }

                        $params["controller"] = $route->getController();
                        $params["action"] = $route->getAction();

                        // try to get nearest document to the route
                        $pathes[] = "/";
                        $pathParts = explode("/", $path);
                        $tmpPathes = array();
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
                                $params["document"] = $document;
                                break;
                            }
                        }

                        $matchFound = true;
                        break;
                    }
                }
            }
            catch (Exception $e) {
                // no suitable route found
            }
        }
        
        // test if there is a suitable redirect
        if (!$matchFound) {
            $this->checkForRedirect(false);
        }

        if (!$matchFound && $site instanceof Site) {
            if ($config->general->domain) {
                header("Location: http://" . $config->general->domain . $originalPath);
            }
            else {
                header("Location: /");
            }
            exit;
        }


        if (!$matchFound) {
            return false;
        }
        
        // remove pimcore magic parameters
        unset($params["pimcore_outputfilters_disabled"]); 
        unset($params["pimcore_document"]);
        unset($params["nocache"]);
        
        return $params;
    }

    /**
     * Checks for a suitable redirect
     * @throws Exception
     * @param bool $override
     * @return void
     */
    protected function checkForRedirect ($override = false) {
        try {

            $cacheKey = "system_route_redirect";
            if (empty($this->redirects) && !$this->redirects = Pimcore_Model_Cache::load($cacheKey)) {

                $list = new Redirect_List();
                $list->setOrder("DESC");
                $list->setOrderKey("priority");
                $this->redirects = $list->load();

                Pimcore_Model_Cache::save($this->redirects, $cacheKey, array("system","redirect","route"));
            }

            foreach ($this->redirects as $redirect) {

                // if override is true the priority has to be 99 which means that overriding is ok
                if(!$override || ($override && $redirect->getPriority() == 99)) {
                    if (@preg_match($redirect->getSource(), $_SERVER["REQUEST_URI"], $matches)) {

                        array_shift($matches);

                        $target = $redirect->getTarget();
                        if(is_numeric($target)){
                            $d = Document::getById($target);
                            if($d instanceof Document_Page){
                                $target = $d->getFullPath();
                            } else {
                                throw new Exception("Target of redirect no found!");
                            }
                        }
                        $url = vsprintf($target, $matches);

                        header($redirect->getHttpStatus());
                        header("Location: " . $url);
                        exit;
                    }
                }
            }
        }
        catch (Exception $e) {
            // no suitable route found
        }
    }

    public function assemble($data = array(), $reset = false, $encode = true, $partial = false) {
        return "~NOT~SUPPORTED~";
    }

    public function getDefault($name) {
        if (isset($this->_defaults[$name])) {
            return $this->_defaults[$name];
        }
    }

    public function getDefaults() {
        return $this->_defaults;
    }

}
