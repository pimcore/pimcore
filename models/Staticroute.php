<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

use Pimcore\Event\FrontendEvents;
use Pimcore\Model\Exception\NotFoundException;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @method Staticroute\Dao getDao()
 * @method void save()
 * @method void delete()
 */
final class Staticroute extends AbstractModel
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $pattern;

    /**
     * @var string
     */
    protected $reverse;

    /**
     * @var string
     */
    protected $controller;

    /**
     * @var string
     */
    protected $variables;

    /**
     * @var string
     */
    protected $defaults;

    /**
     * @var array
     */
    protected $siteId;

    /**
     * @var array
     */
    protected $methods;

    /**
     * @var int
     */
    protected $priority = 1;

    /**
     * @var int
     */
    protected $creationDate;

    /**
     * @var int
     */
    protected $modificationDate;

    /**
     * Associative array filled on match() that holds matched path values
     * for given variable names.
     *
     * @var array
     */
    protected $_values = [];

    /**
     * this is a small per request cache to know which route is which is, this info is used in self::getByName()
     *
     * @var array
     */
    protected static $nameIdMappingCache = [];

    /**
     * contains the static route which the current request matches (it he does), this is used in the view to get the current route
     *
     * @var Staticroute|null
     */
    protected static ?Staticroute $_currentRoute = null;

    /**
     * @static
     *
     * @param Staticroute|null $route
     */
    public static function setCurrentRoute(?Staticroute $route)
    {
        self::$_currentRoute = $route;
    }

    /**
     * @static
     *
     * @return Staticroute|null
     */
    public static function getCurrentRoute(): ?Staticroute
    {
        return self::$_currentRoute;
    }

    /**
     * @param int $id
     *
     * @return self|null
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
                $route->setId((int)$id);
                $route->getDao()->getById();
                \Pimcore\Cache\Runtime::set($cacheKey, $route);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $route;
    }

    /**
     * @param string $name
     * @param int|null $siteId
     *
     * @return self|null
     *
     * @throws \Exception
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
        } catch (NotFoundException $e) {
            return null;
        }

        // to have a singleton in a way. like all instances of Element\ElementInterface do also, like DataObject\AbstractObject
        if ($route->getId() > 0) {
            // add it to the mini-per request cache
            self::$nameIdMappingCache[$cacheKey] = $route->getId();

            return self::getById($route->getId());
        }

        return null;
    }

    /**
     * @return self
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
    private function getDefaultsArray()
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
    public function getController()
    {
        return $this->controller;
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

            if ($site = Site::getById($siteId)) {
                $result[] = $siteId;
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
     * @internal
     *
     * @param array $urlOptions
     * @param bool $encode
     *
     * @return mixed|string
     */
    public function assemble(array $urlOptions = [], $encode = true)
    {
        $defaultValues = $this->getDefaultsArray();

        // apply values (controller, ... ) from previous match if applicable (only when )
        if (self::$_currentRoute && (self::$_currentRoute->getName() == $this->getName())) {
            $defaultValues = array_merge($defaultValues, self::$_currentRoute->_values);
        }

        // merge with defaults
        // merge router.request_context params e.g. "_locale"
        $requestParameters = \Pimcore::getContainer()->get('pimcore.routing.router.request_context')->getParameters();
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
                if (array_key_exists($key, $urlOptions)) {
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
            'encode' => $encode,
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::STATICROUTE_PATH);
        $url = $event->getArgument('frontendPath');

        return $url;
    }

    /**
     * @internal
     *
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

            $params['controller'] = $this->getController();

            // remember for reverse assemble
            $this->_values = $params;

            return $params;
        }

        return [];
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        if ($this->methods && is_string($this->methods)) {
            $this->methods = explode(',', $this->methods);
        }

        return $this->methods;
    }

    /**
     * @param array|string $methods
     *
     * @return $this
     */
    public function setMethods($methods)
    {
        if (is_string($methods)) {
            $methods = strlen($methods) ? explode(',', $methods) : [];
            $methods = array_map('trim', $methods);
        }

        $this->methods = $methods;

        return $this;
    }

    /**
     * @param int $modificationDate
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
     * @param int $creationDate
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
