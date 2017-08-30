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
 * @category   Pimcore
 * @package    Staticroute
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

use Pimcore\Event\FrontendEvents;
use Pimcore\Logger;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @method \Pimcore\Model\Staticroute\Dao getDao()
 */
class Staticroute extends AbstractModel
{
    /**
     * @var int
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
     * @var array
     */
    public $siteId;

    /**
     * @var int
     */
    public $priority = 1;

    /**
     * @var bool
     */
    public $legacy = false;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * Associative array filled on match() that holds matched path values
     * for given variable names.
     *
     * @var array
     */
    public $_values = [];

    /**
     * this is a small per request cache to know which route is which is, this info is used in self::getByName()
     *
     * @var array
     */
    protected static $nameIdMappingCache = [];

    /**
     * contains the static route which the current request matches (it he does), this is used in the view to get the current route
     *
     * @var Staticroute
     */
    protected static $_currentRoute;

    /**
     * @static
     *
     * @param $route
     */
    public static function setCurrentRoute($route)
    {
        self::$_currentRoute = $route;
    }

    /**
     * @static
     *
     * @return Staticroute
     */
    public static function getCurrentRoute()
    {
        return self::$_currentRoute;
    }

    /**
     * @param int $id
     *
     * @return Staticroute
     */
    public static function getById($id)
    {
        $cacheKey = 'staticroute_' . $id;

        try {
            $route = \Pimcore\Cache\Runtime::get($cacheKey);
            if (!$route) {
                throw new \Exception('Route in registry is null');
            }
        } catch (\Exception $e) {
            try {
                $route = new self();
                \Pimcore\Cache\Runtime::set($cacheKey, $route);
                $route->setId(intval($id));
                $route->getDao()->getById();
            } catch (\Exception $e) {
                Logger::error($e);

                return null;
            }
        }

        return $route;
    }

    /**
     * @param string $name
     * @param null $siteId
     *
     * @return Staticroute
     */
    public static function getByName($name, $siteId = null)
    {
        $cacheKey = $name . '~~~' . $siteId;

        // check if pimcore already knows the id for this $name, if yes just return it
        if (array_key_exists($cacheKey, self::$nameIdMappingCache)) {
            return self::getById(self::$nameIdMappingCache[$cacheKey]);
        }

        // create a tmp object to obtain the id
        $route = new self();

        try {
            $route->getDao()->getByName($name, $siteId);
        } catch (\Exception $e) {
            Logger::warn($e);

            return null;
        }

        // to have a singleton in a way. like all instances of Element\ElementInterface do also, like DataObject\AbstractObject
        if ($route->getId() > 0) {
            // add it to the mini-per request cache
            self::$nameIdMappingCache[$cacheKey] = $route->getId();

            return self::getById($route->getId());
        }
    }

    /**
     * @return Staticroute
     */
    public static function create()
    {
        $route = new self();
        $route->save();

        return $route;
    }

    /**
     * Get the defaults defined in a string as array
     *
     * @return array
     */
    public function getDefaultsArray()
    {
        $defaults = [];

        $t = explode('|', $this->getDefaults());
        foreach ($t as $v) {
            $d = explode('=', $v);
            if (strlen($d[0]) > 0 && strlen($d[1]) > 0) {
                $defaults[$d[0]] = $d[1];
            }
        }

        return $defaults;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * @return string
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @param string $pattern
     *
     * @return $this
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * @param string $module
     *
     * @return $this
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * @param string $controller
     *
     * @return $this
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @param string $variables
     *
     * @return $this
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * @param string $defaults
     *
     * @return $this
     */
    public function setDefaults($defaults)
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = (int) $priority;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return bool
     */
    public function getLegacy()
    {
        return $this->legacy;
    }

    /**
     * @param bool $legacy
     */
    public function setLegacy($legacy)
    {
        $this->legacy = (bool)$legacy;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $reverse
     *
     * @return $this
     */
    public function setReverse($reverse)
    {
        $this->reverse = $reverse;

        return $this;
    }

    /**
     * @return string
     */
    public function getReverse()
    {
        return $this->reverse;
    }

    /**
     * @param int|array $siteId
     *
     * @return $this
     */
    public function setSiteId($siteId)
    {
        $result = [];

        if (!is_array($siteId)) {
            // backwards compatibility
            $siteIds = strlen($siteId) ? explode(',', $siteId) : [];
        } else {
            $siteIds = $siteId;
        }

        foreach ($siteIds as $siteId) {
            $siteId = (int)$siteId;
            if ($siteId < 1) {
                continue;
            }
            try {
                $site = Site::getById($siteId);
                if ($site) {
                    $result[] = $siteId;
                }
            } catch (\Exception $e) {
                // cleanup
            }
        }

        $this->siteId = $result;

        return $this;
    }

    /**
     * @return array
     */
    public function getSiteId()
    {
        if ($this->siteId && !is_array($this->siteId)) {
            $this->siteId = explode(',', $this->siteId);
        }

        return $this->siteId;
    }

    /**
     * @param array $urlOptions
     * @param bool $reset
     * @param bool $encode
     *
     * @return mixed|string
     */
    public function assemble(array $urlOptions = [], $reset=false, $encode=true)
    {
        // get request parameters
        $blockedRequestParams = ['controller', 'action', 'module', 'document'];

        // allow blocked params if we use it as variables
        $variables = explode(',', $this->getVariables());
        foreach ($variables as $name) {
            $pos = array_search($name, $blockedRequestParams);
            if ($pos !== false) {
                unset($blockedRequestParams[$pos]);
            }
        }

        if ($reset) {
            $requestParameters = [];
        } else {
            $requestParameters = \Pimcore::getContainer()->get('pimcore.routing.router.request_context')->getParameters();

            // merge route params from static routes here
            $request = \Pimcore::getContainer()->get('request_stack')->getCurrentRequest();
            if ($request->attributes->get('_route_params')) {
                $requestParameters = array_merge($requestParameters, $request->attributes->get('_route_params'));
            }

            // remove blocked parameters from request
            foreach ($blockedRequestParams as $key) {
                if (array_key_exists($key, $requestParameters)) {
                    unset($requestParameters[$key]);
                }
            }
        }

        $defaultValues = $this->getDefaultsArray();

        // apply values (controller,action,module, ... ) from previous match if applicable (only when )
        if ($reset) {
            if (self::$_currentRoute && (self::$_currentRoute->getName() == $this->getName())) {
                $defaultValues = array_merge($defaultValues, self::$_currentRoute->_values);
            }
        }

        // merge with defaults
        $urlParams = array_merge($defaultValues, $requestParameters, $urlOptions);

        $parametersInReversePattern = [];
        $parametersGet = [];
        $url = $this->getReverse();
        $forbiddenCharacters = ['#', ':', '?'];

        // check for named variables
        uksort($urlParams, function ($a, $b) {
            // order by key length, longer key have priority
            // (%abcd prior %ab, so that %ab doesn't replace %ab in [%ab]cd)
            return strlen($b) - strlen($a);
        });

        $tmpReversePattern = $this->getReverse();
        foreach ($urlParams as $key => $param) {
            if (strpos($tmpReversePattern, '%' . $key) !== false) {
                $parametersInReversePattern[$key] = $param;

                // we need to replace the found variable to that it cannot match again a placeholder
                // eg. %abcd prior %ab if %abcd matches already %ab shouldn't match again on the same placeholder
                $tmpReversePattern = str_replace('%' . $key, '---', $tmpReversePattern);
            } else {
                // only append the get parameters if there are defined in $urlOptions
                // or if they are defined in $_GET an $reset is false
                if (array_key_exists($key, $urlOptions) || (!$reset && array_key_exists($key, $_GET))) {
                    $parametersGet[$key] = $param;
                }
            }
        }

        $urlEncodeEscapeCharacters = '~|urlen' . md5(microtime()) . 'code|~';

        // replace named variables
        uksort($parametersInReversePattern, function ($a, $b) {
            // order by key length, longer key have priority
            // (%abcd prior %ab, so that %ab doesn't replace %ab in [%ab]cd)
            return strlen($b) - strlen($a);
        });

        foreach ($parametersInReversePattern as $key => $value) {
            $value = str_replace($forbiddenCharacters, '', $value);
            if (strlen($value) > 0) {
                if ($encode) {
                    $value = urlencode_ignore_slash($value);
                }
                $value = str_replace('%', $urlEncodeEscapeCharacters, $value);
                $url = str_replace('%' . $key, $value, $url);
            }
        }

        // remove optional parts
        $url = preg_replace("/\{([^\}]+)?%[^\}]+\}/", '', $url);
        $url = str_replace(['{', '}'], '', $url);

        // optional get parameters
        if (!empty($parametersGet)) {
            if ($encode) {
                $getParams = array_urlencode($parametersGet);
            } else {
                $getParams = array_toquerystring($parametersGet);
            }
            $url .= '?' . $getParams;
        }

        // convert tmp urlencode escape char back to real escape char
        $url = str_replace($urlEncodeEscapeCharacters, '%', $url);

        $event = new GenericEvent($this, [
            'frontendPath' => $url,
            'params' => $urlParams,
            'reset' => $reset,
            'encode' => $encode
        ]);
        \Pimcore::getEventDispatcher()->dispatch(FrontendEvents::STATICROUTE_PATH, $event);
        $url = $event->getArgument('frontendPath');

        return $url;
    }

    /**
     * @param string $path
     * @param array $params
     *
     * @return array|bool
     *
     * @throws \Exception
     */
    public function match($path, $params = [])
    {
        if (@preg_match($this->getPattern(), $path)) {

            // check for site
            if ($this->getSiteId()) {
                if (!Site::isSiteRequest()) {
                    return false;
                }

                $siteMatched = false;
                $siteIds = $this->getSiteId();
                foreach ($siteIds as $siteId) {
                    if ($siteId == Site::getCurrentSite()->getId()) {
                        $siteMatched = true;
                        break;
                    }
                }
                if (!$siteMatched) {
                    return false;
                }
            }

            // we need to unset this 3 params here, because otherwise the defaults wouldn't have an effect if used
            // in combination with dynamic action/controller/module configurations
            unset($params['controller'], $params['action'], $params['module']);

            $params = array_merge($this->getDefaultsArray(), $params);

            $variables = explode(',', $this->getVariables());

            preg_match_all($this->getPattern(), $path, $matches);

            if (is_array($matches) && count($matches) > 1) {
                foreach ($matches as $index => $match) {
                    if (isset($variables[$index - 1]) && $variables[$index - 1]) {
                        $paramValue = urldecode($match[0]);
                        if (!empty($paramValue) || !array_key_exists($variables[$index - 1], $params)) {
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
                if (strpos($item, '%') !== false) {
                    uksort($params, function ($a, $b) {
                        // order by key length, longer key have priority
                        // (%abcd prior %ab, so that %ab doesn't replace %ab in [%ab]cd)
                        return strlen($b) - strlen($a);
                    });

                    foreach ($params as $key => $value) {
                        $dynKey = '%' . $key;
                        if (strpos($item, $dynKey) !== false) {
                            return str_replace($dynKey, $value, $item);
                        }
                    }
                }

                return $item;
            };

            $controller = $dynamicRouteReplace($controller, $params);
            $action = $dynamicRouteReplace($action, $params);
            $module = $dynamicRouteReplace($module, $params);

            $params['controller'] = $controller;
            $params['action'] = $action;
            if (!empty($module)) {
                $params['module'] = $module;
            }
            // remember for reverse assemble
            $this->_values = $params;

            return $params;
        }

        return [];
    }

    /**
     * @param $modificationDate
     *
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
     *
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
