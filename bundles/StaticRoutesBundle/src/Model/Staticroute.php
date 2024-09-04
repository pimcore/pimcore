<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StaticRoutesBundle\Model;

use Exception;
use Pimcore;
use Pimcore\Event\FrontendEvents;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Site;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * @method bool isWriteable()
 * @method string getWriteTarget()
 * @method Staticroute\Dao getDao()
 * @method void save()
 * @method void delete()
 */
final class Staticroute extends AbstractModel
{
    protected ?string $id = null;

    protected ?string $name = null;

    protected string $pattern = '';

    protected ?string $reverse = null;

    protected ?string $controller = null;

    protected ?string $variables = null;

    protected ?string $defaults = null;

    /**
     * @var int[]
     */
    protected array $siteId = [];

    /**
     * @var string[]
     */
    protected array $methods = [];

    protected int $priority = 1;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    /**
     * Associative array filled on match() that holds matched path values
     * for given variable names.
     */
    protected array $_values = [];

    /**
     * this is a small per request cache to know which route is which is, this info is used in self::getByName()
     */
    protected static array $nameIdMappingCache = [];

    /**
     * contains the static route which the current request matches (it he does), this is used in the view to get the current route
     */
    protected static ?Staticroute $_currentRoute = null;

    public static function setCurrentRoute(?Staticroute $route): void
    {
        self::$_currentRoute = $route;
    }

    public static function getCurrentRoute(): ?Staticroute
    {
        return self::$_currentRoute;
    }

    /**
     * Static helper to retrieve an instance of Staticroute by the given ID
     */
    public static function getById(string $id): ?Staticroute
    {
        $cacheKey = 'staticroute_' . $id;

        try {
            $route = \Pimcore\Cache\RuntimeCache::get($cacheKey);
            if (!$route) {
                throw new Exception('Route in registry is null');
            }
        } catch (Exception $e) {
            try {
                $route = new self();
                $route->setId($id);
                $route->getDao()->getById();
                \Pimcore\Cache\RuntimeCache::set($cacheKey, $route);
            } catch (NotFoundException $e) {
                return null;
            }
        }

        return $route;
    }

    /**
     * @throws Exception
     */
    public static function getByName(string $name, int $siteId = null): ?Staticroute
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

    public static function create(): Staticroute
    {
        $route = new self();
        $route->save();

        return $route;
    }

    /**
     * Get the defaults defined in a string as array
     */
    private function getDefaultsArray(): array
    {
        $defaultsString = $this->getDefaults();
        if (empty($defaultsString)) {
            return [];
        }

        $defaults = [];

        $t = explode('|', $defaultsString);
        foreach ($t as $v) {
            $d = explode('=', $v);
            if (strlen($d[0]) > 0 && strlen($d[1]) > 0) {
                $defaults[$d[0]] = $d[1];
            }
        }

        return $defaults;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function getVariables(): string
    {
        return $this->variables;
    }

    public function getDefaults(): ?string
    {
        return $this->defaults;
    }

    /**
     * @return $this
     */
    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function setPattern(string $pattern): static
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * @return $this
     */
    public function setController(?string $controller): static
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @return $this
     */
    public function setVariables(string $variables): static
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDefaults(string $defaults): static
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * @return $this
     */
    public function setPriority(int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setReverse(string $reverse): static
    {
        $this->reverse = $reverse;

        return $this;
    }

    public function getReverse(): ?string
    {
        return $this->reverse;
    }

    /**
     * @param int[]|string|null $siteId
     *
     * @return $this
     */
    public function setSiteId(array|string|null $siteId): static
    {
        $result = [];

        if (null === $siteId) {
            $siteId = [];
        }

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
     * @return int[]
     */
    public function getSiteId(): array
    {
        return $this->siteId;
    }

    /**
     * @internal
     */
    public function assemble(array $urlOptions = [], bool $encode = true): string
    {
        $defaultValues = $this->getDefaultsArray();

        // apply values (controller, ... ) from previous match if applicable (only when )
        if (self::$_currentRoute && (self::$_currentRoute->getName() == $this->getName())) {
            $defaultValues = array_merge($defaultValues, self::$_currentRoute->_values);
        }

        // merge with defaults
        // merge router.request_context params e.g. "_locale"
        $requestParameters = Pimcore::getContainer()->get('pimcore.routing.router.request_context')->getParameters();
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
            if (str_contains($tmpReversePattern, '%' . $key)) {
                $parametersInReversePattern[$key] = (string) $param;

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
        Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::STATICROUTE_PATH);
        $url = $event->getArgument('frontendPath');

        return $url;
    }

    /**
     * @internal
     *
     * @throws Exception
     */
    public function match(string $path, array $params = []): false|array
    {
        if (@preg_match($this->getPattern(), $path)) {
            // check for site
            if ($this->getSiteId()) {
                if (!Site::isSiteRequest() || !in_array(Site::getCurrentSite()->getId(), $this->getSiteId())) {
                    return false;
                }
            }

            $variables = explode(',', $this->getVariables());

            preg_match_all($this->getPattern(), $path, $matches);

            foreach ($matches as $index => $match) {
                if (!empty($variables[$index - 1])) {
                    $paramValue = urldecode($match[0]);
                    if (!empty($paramValue) || !array_key_exists($variables[$index - 1], $params)) {
                        $params[$variables[$index - 1]] = $paramValue;
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
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param string[]|string $methods
     *
     * @return $this
     */
    public function setMethods(array|string $methods): static
    {
        if (is_string($methods)) {
            $methods = strlen($methods) ? explode(',', $methods) : [];
            foreach ($methods as $key => $method) {
                $methods[$key] = trim($method);
            }
        }

        $this->methods = $methods;

        return $this;
    }

    /**
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    /**
     * @return $this
     */
    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function __clone(): void
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }
    }
}
