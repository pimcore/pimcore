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
 * @category   Pimcore
 * @package    Staticroute
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model;

class Staticroute extends AbstractModel {

    /**
     * @var integer
     */
    public $id;
    
    /**
     * @var string
     */
    public $name;
    
    /**
     * @var string
     */
    public $pattern;
    
    /**
     * @var string
     */
    public $reverse;

    /**
     * @var string
     */
    public $module;

    /**
     * @var string
     */
    public $controller;

    /**
     * @var string
     */
    public $action;

    /**
     * @var string
     */
    public $variables;

    /**
     * @var string
     */
    public $defaults;

    /**
     * @var int
     */
    public $siteId;

    /**
     * @var integer
     */
    public $priority = 1;

    /**
     * @var integer
     */
    public $creationDate;

    /**
     * @var integer
     */
    public $modificationDate;


    /**
     * this is a small per request cache to know which route is which is, this info is used in self::getByName()
     *
     * @var array
     */
    protected static $nameIdMappingCache = array();

    /**
     * contains the static route which the current request matches (it he does), this is used in the view to get the current route
     *
     * @var Staticroute
     */
    private static $_currentRoute;

    /**
     * @static
     * @param $route
     * @return void
     */
    public static function setCurrentRoute($route) {
        self::$_currentRoute = $route;
    }

    /**
     * @static
     * @return Staticroute
     */
    public static function getCurrentRoute() {
        return self::$_currentRoute;
    }

    /**
     * @param integer $id
     * @return Staticroute
     */
    public static function getById($id) {
        
        $cacheKey = "staticroute_" . $id;

        try {
            $route = \Zend_Registry::get($cacheKey);
            if(!$route){
                throw new \Exception("Route in registry is null");
            }
        }
        catch (\Exception $e) {

            try {
                $route = new self();
                \Zend_Registry::set($cacheKey, $route);
                $route->setId(intval($id));
                $route->getResource()->getById();

            } catch (\Exception $e) {
                \Logger::error($e);
                return null;
            }
        }

        return $route;
    }
    
    /**
     * @param string $name
     * @return Staticroute
     */
    public static function getByName($name, $siteId = null) {

        $cacheKey = $name . "~~~" . $siteId;

        // check if pimcore already knows the id for this $name, if yes just return it
        if(array_key_exists($cacheKey, self::$nameIdMappingCache)) {
            return self::getById(self::$nameIdMappingCache[$cacheKey]);
        }

        // create a tmp object to obtain the id
        $route = new self();

        try {
            $route->getResource()->getByName($name, $siteId);
        } catch (\Exception $e) {
            \Logger::warn($e);
            return null;
        }

        // to have a singleton in a way. like all instances of Element\ElementInterface do also, like Object\AbstractObject
        if($route->getId() > 0) {
            // add it to the mini-per request cache
            self::$nameIdMappingCache[$cacheKey] = $route->getId();
            return self::getById($route->getId());
        }
    }

    /**
     * @return Staticroute
     */
    public static function create() {
        $route = new self();
        $route->save();

        return $route;
    }

    /**
     * Get the defaults defined in a string as array
     *
     * @return array
     */
    public function getDefaultsArray() {
        $defaults = array();

        $t = explode("|", $this->getDefaults());
        foreach ($t as $v) {
            $d = explode("=", $v);
            if (strlen($d[0]) > 0 && strlen($d[1]) > 0) {
                $defaults[$d[0]] = $d[1];
            }
        }

        return $defaults;
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPattern() {
        return $this->pattern;
    }


    /**
     * @return string
     */
    public function getModule() {
        return $this->module;
    }

    /**
     * @return string
     */
    public function getController() {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getVariables() {
        return $this->variables;
    }

    /**
     * @return string
     */
    public function getDefaults() {
        return $this->defaults;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @param string $pattern
     * @return void
     */
    public function setPattern($pattern) {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @param string $module
     * @return void
     */
    public function setModule($module) {
        $this->module = $module;
        return $this;
    }


    /**
     * @param string $controller
     * @return void
     */
    public function setController($controller) {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @param string $action
     * @return void
     */
    public function setAction($action) {
        $this->action = $action;
        return $this;
    }

    /**
     * @param string $variables
     * @return void
     */
    public function setVariables($variables) {
        $this->variables = $variables;
        return $this;
    }

    /**
     * @param string $defaults
     * @return void
     */
    public function setDefaults($defaults) {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * @param integer $priority
     * @return void
     */
    public function setPriority($priority) {
        $this->priority = (int) $priority;
        return $this;
    }

    /**
     * @return integer
     */
    public function getPriority() {
        return $this->priority;
    }
    
    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * @param string $reverse
     * @return void
     */
    public function setReverse($reverse) {
        $this->reverse = $reverse;
        return $this;
    }

    /**
     * @return string
     */
    public function getReverse() {
        return $this->reverse;
    }

    /**
     * @param int $siteId
     */
    public function setSiteId($siteId)
    {
        $this->siteId = $siteId ? (int) $siteId : null;
        return $this;
    }

    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param array $urlOptions
     * @return string
     */
    public function assemble (array $urlOptions = array(), $reset=false, $encode=true) {

        // get request parameters
        $blockedRequestParams = array("controller","action","module","document");
        $front = \Zend_Controller_Front::getInstance();

        if($reset) {
            $requestParameters = array();
        } else {
            $requestParameters = $front->getRequest()->getParams();
            // remove blocked parameters from request
            foreach ($blockedRequestParams as $key) {
                if(array_key_exists($key, $requestParameters)) {
                    unset($requestParameters[$key]);
                }
            }
        }

        $urlParams = array_merge($requestParameters, $urlOptions);
        $parametersInReversePattern = array();
        $parametersGet = array();
        $parametersNotNamed = array();
        $url = $this->getReverse();
        $forbiddenCharacters = array("#",":","?");

        // check for named variables
        foreach ($urlParams as $key => $param) {
            if(strpos($this->getReverse(), "%" . $key) !== false) {
                $parametersInReversePattern[$key] = $param;
            } else if (is_numeric($key)) {
                $parametersNotNamed[$key] = $param;
            } else {
                // only append the get parameters if there are defined in $urlOptions
                // or if they are defined in $_GET an $reset is false
                if(array_key_exists($key,$urlOptions) || (!$reset && array_key_exists($key, $_GET))) {
                    $parametersGet[$key] = $param;
                }
            }
        }

        $urlEncodeEscapeCharacters = "~|urlen" . md5(microtime()) . "code|~";

        // replace named variables
        foreach ($parametersInReversePattern as $key => $value) {
            $value = str_replace($forbiddenCharacters, "", $value);
            if(strlen($value) > 0) {
                $url = str_replace(
                    "%" . $key,
                    str_replace("%", $urlEncodeEscapeCharacters, ($encode) ? urlencode_ignore_slash($value) : $value),
                    $url
                );
            }
        }


        // not named parameters
        $o = array();
        foreach ($parametersNotNamed as $option) {
            $option = str_replace($forbiddenCharacters, "", $option);
            $o[] = str_replace("%", $urlEncodeEscapeCharacters, ($encode) ? urlencode_ignore_slash($option) : $option);
        }

        // remove optional parts
        $url = preg_replace("/\{([^\}]+)?%[^\}]+\}/","",$url);
        $url = str_replace(array("{","}"),"",$url);

        $url = @vsprintf($url,$o);
        if(empty($url)) {
            $url = "ERROR_IN_YOUR_URL_CONFIGURATION:~ONE_PARAMETER_IS_MISSING_TO_GENERATE_THE_URL";
            return $url;
        }

        // optional get parameters
        if(!empty($parametersGet)) {
            if($encode) {
                $getParams = array_urlencode($parametersGet);
            } else {
                $getParams = array_toquerystring($parametersGet);
            }
            $url .= "?" . $getParams;
        }

        // convert tmp urlencode escape char back to real escape char
        $url = str_replace($urlEncodeEscapeCharacters, "%",$url);

        
        return $url;
    }

    /**
     * @param $path
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function match($path, $params = array()) {

        if (@preg_match($this->getPattern(), $path)) {

            // check for site
            if($this->getSiteId()) {
                if(!Site::isSiteRequest() || $this->getSiteId() != Site::getCurrentSite()->getId()) {
                    return false;
                }
            }

            $params = array_merge($this->getDefaultsArray(), $params);

            $variables = explode(",", $this->getVariables());

            preg_match_all($this->getPattern(), $path, $matches);

            if (is_array($matches) && count($matches) > 1) {
                foreach ($matches as $index => $match) {
                    if ($variables[$index - 1]) {
                        $paramValue = urldecode($match[0]);
                        if(!empty($paramValue) || !array_key_exists($variables[$index - 1], $params)) {
                            $params[$variables[$index - 1]] = $paramValue;
                        }
                    }
                }
            }

            $controller = $this->getController();
            $action = $this->getAction();
            $module = trim($this->getModule());

            // check for dynamic controller / action / module
            $dynamicRouteReplace = function ($item, $params) {
                if(strpos($item, "%") !== false) {
                    foreach ($params as $key => $value) {
                        $dynKey = "%" . $key;
                        if(strpos($item, $dynKey) !== false) {
                            return str_replace($dynKey, $value, $item);
                        }
                    }
                }
                return $item;
            };

            $controller = $dynamicRouteReplace($controller, $params);
            $action = $dynamicRouteReplace($action, $params);
            $module = $dynamicRouteReplace($module, $params);

            $params["controller"] = $controller;
            $params["action"] = $action;
            if(!empty($module)){
                $params["module"] = $module;
            }

            return $params;
        }
    }
    
    
    /**
     * @return void
     */
    public function clearDependentCache() {
        
        // this is mostly called in Staticroute\Resource not here
        try {
            \Pimcore\Model\Cache::clearTag("staticroute");
        }
        catch (\Exception $e) {
            \Logger::crit($e);
        }
    }

    /**
     * @param $modificationDate
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }


}
