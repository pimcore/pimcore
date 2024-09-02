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

/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_Navigation_Page
 * ----------------------------------------------------------------------------------
 */

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Navigation;

use Exception;
use Pimcore\Navigation\Page\Url;

abstract class Page extends Container
{
    /**
     * Page label
     */
    protected ?string $_label = null;

    /**
     * Fragment identifier (anchor identifier)
     *
     * The fragment identifier (anchor identifier) pointing to an anchor within
     * a resource that is subordinate to another, primary resource.
     * The fragment identifier introduced by a hash mark "#".
     * Example: http://www.example.org/foo.html#bar ("bar" is the fragment identifier)
     *
     * @link http://www.w3.org/TR/html401/intro/intro.html#fragment-uri
     */
    protected ?string $_fragment = null;

    /**
     * Page id
     */
    protected ?string $_id = null;

    /**
     * Style class for this page (CSS)
     */
    protected ?string $_class = null;

    /**
     * A more descriptive title for this page
     */
    protected ?string $_title = null;

    /**
     * This page's target
     */
    protected ?string $_target = null;

    /**
     * Accessibility key character
     *
     * This attribute assigns an access key to an element. An access key is a
     * single character from the document character set.
     *
     * @link http://www.w3.org/TR/html401/interact/forms.html#access-keys
     */
    protected ?string $_accesskey = null;

    /**
     * Forward links to other pages
     *
     * @link http://www.w3.org/TR/html4/struct/links.html#h-12.3.1
     *
     */
    protected array $_rel = [];

    /**
     * Reverse links to other pages
     *
     * @link http://www.w3.org/TR/html4/struct/links.html#h-12.3.1
     *
     */
    protected array $_rev = [];

    /**
     * Page order used by parent container
     */
    protected ?int $_order = null;

    /**
     * Whether this page should be considered active
     */
    protected bool $_active = false;

    /**
     * Whether this page should be considered visible
     */
    protected bool $_visible = true;

    /**
     * Parent container
     */
    protected ?Container $_parent = null;

    /**
     * Custom page properties, used by __set(), __get() and __isset()
     */
    protected array $_properties = [];

    /**
     * Custom HTML attributes
     */
    protected array $_customHtmlAttribs = [];

    /**
     * @deprecated will be removed in Pimcore 12.
     *
     * The type of page to use when it wasn't set
     *
     */
    protected static ?string $_defaultPageType = null;

    // Initialization:

    /**
     * Factory for Pimcore\Navigation\Page classes
     *
     * A specific type to construct can be specified by specifying the key
     * 'type' in $options. If type is 'uri' or 'mvc', the type will be resolved
     * to Uri. Any other value for 'type' will be considered the full name of the class to construct.
     * A valid custom page class must extend Page.
     *
     * If 'type' is not given, the type of page to construct will be determined
     * by the following rules:
     * - If $options contains the key 'uri', a Url page
     *   will be created.
     *
     * @param array $options  options used for creating page
     *
     * @return Url|Page        a page instance
     *
     * @throws Exception
     */
    public static function factory(array $options): Url|Page
    {
        if (isset($options['type'])) {
            $type = $options['type'];
        } elseif (self::$_defaultPageType != null) {
            $type = self::$_defaultPageType;
        }

        if (isset($type)) {
            if (is_string($type) && !empty($type)) {
                switch (strtolower($type)) {
                    case 'uri':
                        $type = '\Pimcore\Navigation\Page\Url';

                        break;
                }

                $page = new $type($options);
                if (!$page instanceof self) {
                    throw new Exception(sprintf(
                        'Invalid argument: Detected type "%s", which is not an instance of Page',
                        $type
                    ));
                }

                return $page;
            }
        }

        $hasUri = isset($options['uri']);

        if ($hasUri) {
            return new Url($options);
        } else {
            $message = 'Invalid argument: Unable to determine class to instantiate';
            if (isset($options['label'])) {
                $message .= ' (Page label: ' . $options['label'] . ')';
            }

            throw new Exception($message);
        }
    }

    /**
     * Page constructor
     *
     * @param array|null $options   [optional] page options. Default is null, which should set defaults.
     *
     * @throws Exception    if invalid options are given
     */
    public function __construct(array $options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }

        // do custom initialization
        $this->_init();
    }

    /**
     * Initializes page (used by subclasses)
     *
     */
    protected function _init(): void
    {
    }

    /**
     * Sets page properties using options from an associative array
     *
     * Each key in the array corresponds to the according set*() method, and
     * each word is separated by underscores, e.g. the option 'target'
     * corresponds to setTarget(), and the option 'reset_params' corresponds to
     * the method setResetParams().
     *
     * @param  array $options             associative array of options to set
     *
     * @return $this       fluent interface, returns self
     *
     * @throws Exception  if invalid options are given
     */
    public function setOptions(array $options): static
    {
        foreach ($options as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    // Accessors:

    /**
     * Sets page label
     *
     * @param string|null $label new page label
     *
     * @return $this fluent interface, returns self
     */
    public function setLabel(?string $label): static
    {
        $this->_label = $label;

        return $this;
    }

    /**
     * Returns page label
     *
     * @return string|null  page label or null
     */
    public function getLabel(): ?string
    {
        return $this->_label;
    }

    /**
     * Sets a fragment identifier
     *
     * @param string|null $fragment new fragment identifier
     *
     * @return $this fluent interface, returns self
     */
    public function setFragment(?string $fragment): static
    {
        $this->_fragment = $fragment;

        return $this;
    }

    /**
     * Returns fragment identifier
     *
     * @return string|null  fragment identifier
     */
    public function getFragment(): ?string
    {
        return $this->_fragment;
    }

    /**
     * Sets page id
     *
     * @param  string|null $id id to set. Default is null, which sets no id.
     *
     * @return $this fluent interface, returns self
     */
    public function setId(?string $id = null): static
    {
        $this->_id = $id;

        return $this;
    }

    /**
     * Returns page id
     *
     * @return string|null  page id or null
     */
    public function getId(): ?string
    {
        return $this->_id;
    }

    /**
     * Sets page CSS class
     *
     * @param  string|null $class CSS class to set. Default is null, which sets no CSS class.
     *
     * @return $this fluent interface, returns self
     */
    public function setClass(?string $class = null): static
    {
        $this->_class = $class;

        return $this;
    }

    /**
     * Returns page class (CSS)
     *
     * @return string|null  page's CSS class or null
     */
    public function getClass(): ?string
    {
        return $this->_class;
    }

    /**
     * Sets page title
     *
     * @param string|null $title page title. Default is null, which sets no title.
     *
     * @return $this fluent interface, returns self
     *
     * @throws Exception  if not given string or null
     */
    public function setTitle(?string $title = null): static
    {
        $this->_title = $title;

        return $this;
    }

    /**
     * Returns page title
     *
     * @return string|null  page title or null
     */
    public function getTitle(): ?string
    {
        return $this->_title;
    }

    /**
     * Sets page target
     *
     * @param string|null $target target to set. Default is null, which sets no target.
     *
     * @return $this fluent interface, returns self
     */
    public function setTarget(?string $target = null): static
    {
        $this->_target = $target;

        return $this;
    }

    /**
     * Returns page target
     *
     * @return string|null  page target or null
     */
    public function getTarget(): ?string
    {
        return $this->_target;
    }

    /**
     * Sets access key for this page
     *
     * @param string|null $character access key to set. Default is null, which sets no access key.
     *
     * @return $this fluent interface, returns self
     *
     * @throws Exception if the string length not equal to one
     */
    public function setAccesskey(?string $character = null): static
    {
        if (is_string($character) && 1 !== strlen($character)) {
            throw new Exception('Invalid argument: $character must be a single character or null');
        }

        $this->_accesskey = $character;

        return $this;
    }

    /**
     * Returns page access key
     *
     * @return string|null page access key or null
     */
    public function getAccesskey(): ?string
    {
        return $this->_accesskey;
    }

    /**
     * Sets the page's forward links to other pages
     *
     * This method expects an associative array of forward links to other pages,
     * where each element's key is the name of the relation (e.g. alternate,
     * prev, next, help, etc), and the value is a mixed value that could somehow
     * be considered a page.
     *
     * @param array|null $relations an associative array of forward links to other pages
     *
     * @return $this fluent interface, returns self
     */
    public function setRel(?array $relations = null): static
    {
        $this->_rel = [];

        if ($relations) {
            foreach ($relations as $name => $relation) {
                if (is_string($name)) {
                    $this->_rel[$name] = $relation;
                }
            }
        }

        return $this;
    }

    /**
     * Returns the page's forward links to other pages
     *
     * This method returns an associative array of forward links to other pages,
     * where each element's key is the name of the relation (e.g. alternate,
     * prev, next, help, etc), and the value is a mixed value that could somehow
     * be considered a page.
     *
     * @param string|null $relation name of relation to return. If not given, all relations will be returned.
     *
     * @return array|null an array of relations. If $relation is not specified, all relations will be returned in an associative array.
     */
    public function getRel(?string $relation = null): ?array
    {
        if (null !== $relation) {
            return $this->_rel[$relation] ?? null;
        }

        return $this->_rel;
    }

    /**
     * Sets the page's reverse links to other pages
     *
     * This method expects an associative array of reverse links to other pages,
     * where each element's key is the name of the relation (e.g. alternate,
     * prev, next, help, etc), and the value is a mixed value that could somehow
     * be considered a page.
     *
     * @param array|null $relations an associative array of reverse links to other pages
     *
     * @return $this fluent interface, returns self
     *
     * @throws Exception
     */
    public function setRev(?array $relations = null): static
    {
        $this->_rev = [];

        if ($relations) {
            foreach ($relations as $name => $relation) {
                if (is_string($name)) {
                    $this->_rev[$name] = $relation;
                }
            }
        }

        return $this;
    }

    /**
     * Returns the page's reverse links to other pages
     *
     * This method returns an associative array of forward links to other pages,
     * where each element's key is the name of the relation (e.g. alternate,
     * prev, next, help, etc), and the value is a mixed value that could somehow
     * be considered a page.
     *
     * @param string|null $relation name of relation to return. If not given, all relations will be returned.
     *
     * @return array|null an array of relations. If $relation is not specified, all relations will be returned in an associative array.
     */
    public function getRev(?string $relation = null): ?array
    {
        if (null !== $relation) {
            return $this->_rev[$relation] ?? null;
        }

        return $this->_rev;
    }

    /**
     * Sets a single custom HTML attribute
     *
     * @param string $name name of the HTML attribute
     * @param string|null $value value for the HTML attribute
     *
     * @return $this fluent interface, returns self
     */
    public function setCustomHtmlAttrib(string $name, ?string $value): static
    {
        if (null === $value && isset($this->_customHtmlAttribs[$name])) {
            unset($this->_customHtmlAttribs[$name]);
        } else {
            $this->_customHtmlAttribs[$name] = $value;
        }

        return $this;
    }

    /**
     * Returns a single custom HTML attributes by name
     *
     * @param string $name name of the HTML attribute
     *
     * @return string|null value for the HTML attribute or null
     */
    public function getCustomHtmlAttrib(string $name): ?string
    {
        return $this->_customHtmlAttribs[$name] ?? null;
    }

    /**
     * Sets multiple custom HTML attributes at once
     *
     * @param array $attribs        an associative array of html attributes
     *
     * @return $this fluent interface, returns self
     */
    public function setCustomHtmlAttribs(array $attribs): static
    {
        foreach ($attribs as $key => $value) {
            $this->setCustomHtmlAttrib($key, $value);
        }

        return $this;
    }

    /**
     * Returns all custom HTML attributes as an array
     *
     * @return array    an array containing custom HTML attributes
     */
    public function getCustomHtmlAttribs(): array
    {
        return $this->_customHtmlAttribs;
    }

    /**
     * Removes a custom HTML attribute from the page
     *
     * @param string $name name of the custom HTML attribute
     *
     * @return $this fluent interface, returns self
     */
    public function removeCustomHtmlAttrib(string $name): static
    {
        unset($this->_customHtmlAttribs[$name]);

        return $this;
    }

    /**
     * Clear all custom HTML attributes
     *
     * @return $this fluent interface, returns self
     */
    public function clearCustomHtmlAttribs(): static
    {
        $this->_customHtmlAttribs = [];

        return $this;
    }

    /**
     * Sets page order to use in parent container
     *
     * @param int|string|null $order                 [optional] page order in container.
     *                                    Default is null, which sets no
     *                                    specific order.
     *
     * @return $this       fluent interface, returns self
     *
     * @throws Exception  if order is not integer or null
     */
    public function setOrder(int|string $order = null): static
    {
        if (is_string($order)) {
            $temp = (int) $order;
            if ($temp < 0 || $temp > 0 || $order === '0') {
                $order = $temp;
            }
        }

        if (null !== $order && !is_int($order)) {
            throw new Exception('Invalid argument: $order must be an integer or null, ' .
                    'or a string that casts to an integer');
        }

        $this->_order = $order;

        // notify parent, if any
        if (isset($this->_parent)) {
            $this->_parent->notifyOrderUpdated();
        }

        return $this;
    }

    /**
     * Returns page order used in parent container
     *
     * @return int|null  page order or null
     */
    public function getOrder(): ?int
    {
        return $this->_order;
    }

    /**
     * Sets whether page should be considered active or not
     *
     * @param bool $active          [optional] whether page should be
     *                               considered active or not. Default is true.
     *
     * @return $this  fluent interface, returns self
     */
    public function setActive(bool $active = true): static
    {
        $this->_active = $active;

        return $this;
    }

    /**
     * Returns whether page should be considered active or not
     *
     * @param bool $recursive  [optional] whether page should be considered
     *                          active if any child pages are active. Default is
     *                          false.
     *
     * @return bool             whether page should be considered active
     */
    public function isActive(bool $recursive = false): bool
    {
        if (!$this->_active && $recursive) {
            foreach ($this->_pages as $page) {
                if ($page->isActive(true)) {
                    return true;
                }
            }

            return false;
        }

        return $this->_active;
    }

    /**
     * Proxy to isActive()
     *
     * @param bool $recursive  [optional] whether page should be considered
     *                          active if any child pages are active. Default
     *                          is false.
     *
     * @return bool             whether page should be considered active
     */
    public function getActive(bool $recursive = false): bool
    {
        return $this->isActive($recursive);
    }

    /**
     * Sets whether the page should be visible or not
     *
     * @param bool|string $visible whether page should be considered visible or not. Default is true.
     *
     * @return $this  fluent interface, returns self
     */
    public function setVisible(bool|string $visible = true): static
    {
        if (is_string($visible) && 'false' === strtolower($visible)) {
            $visible = false;
        }
        $this->_visible = (bool) $visible;

        return $this;
    }

    /**
     * Returns a boolean value indicating whether the page is visible
     *
     * @param bool $recursive whether page should be considered invisible if parent is invisible. Default is false.
     *
     * @return bool whether page should be considered visible
     */
    public function isVisible(bool $recursive = false): bool
    {
        if ($recursive && isset($this->_parent) && $this->_parent instanceof self) {
            if (!$this->_parent->isVisible(true)) {
                return false;
            }
        }

        return $this->_visible;
    }

    /**
     * Proxy to isVisible()
     *
     * Returns a boolean value indicating whether the page is visible
     *
     * @param bool $recursive whether page should be considered invisible if parent is invisible. Default is false.
     *
     * @return bool             whether page should be considered visible
     */
    public function getVisible(bool $recursive = false): bool
    {
        return $this->isVisible($recursive);
    }

    /**
     * Sets parent container
     *
     * @param Container|null $parent new parent to set. Default is null which will set no parent.
     *
     * @return $this fluent interface, returns self
     *
     * @throws Exception
     */
    public function setParent(?Container $parent = null): static
    {
        if ($parent === $this) {
            throw new Exception('A page cannot have itself as a parent');
        }

        // return if the given parent already is parent
        if ($parent === $this->_parent) {
            return $this;
        }

        // remove from old parent
        if (null !== $this->_parent) {
            $this->_parent->removePage($this);
        }

        // set new parent
        $this->_parent = $parent;

        // add to parent if page and not already a child
        if (null !== $this->_parent && !$this->_parent->hasPage($this, false)) {
            $this->_parent->addPage($this);
        }

        return $this;
    }

    /**
     * Returns parent container
     *
     * @return Container|null  parent container or null
     */
    public function getParent(): ?Container
    {
        return $this->_parent;
    }

    /**
     * Sets the given property
     *
     * If the given property is native (id, class, title, etc), the matching
     * set method will be used. Otherwise, it will be set as a custom property.
     *
     * @param  string $property           property name
     * @param  mixed  $value              value to set
     *
     * @return $this       fluent interface, returns self
     *
     * @throws Exception  if property name is invalid
     */
    public function set(string $property, mixed $value): static
    {
        if (empty($property)) {
            throw new Exception('Invalid argument: $property must be a non-empty string');
        }

        $method = 'set' . self::_normalizePropertyName($property);

        if ($method !== 'setOptions' && $method !== 'setConfig' && method_exists($this, $method)) {
            $this->$method($value);
        } else {
            $this->_properties[$property] = $value;
        }

        return $this;
    }

    /**
     * Returns the value of the given property
     *
     * If the given property is native (id, class, title, etc), the matching
     * get method will be used. Otherwise, it will return the matching custom
     * property, or null if not found.
     *
     * @param  string $property           property name
     *
     * @return mixed                      the property's value or null
     *
     * @throws Exception  if property name is invalid
     */
    public function get(string $property): mixed
    {
        if (empty($property)) {
            throw new Exception('Invalid argument: $property must be a non-empty string');
        }

        $method = 'get' . self::_normalizePropertyName($property);

        if (method_exists($this, $method)) {
            return $this->$method();
        }
        if (isset($this->_properties[$property])) {
            return $this->_properties[$property];
        }

        return null;
    }

    // Magic overloads:

    /**
     * Sets a custom property
     *
     * Magic overload for enabling <code>$page->propname = $value</code>.
     *
     * @throws Exception  if property name is invalid
     */
    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    /**
     * Returns a property, or null if it doesn't exist
     *
     * Magic overload for enabling <code>$page->propname</code>.
     *
     * @param  string $name               property name
     *
     * @return mixed                      property value or null
     *
     * @throws Exception  if property name is invalid
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Checks if a property is set
     *
     * Magic overload for enabling <code>isset($page->propname)</code>.
     *
     * Returns true if the property is native (id, class, title, etc), and
     * true or false if it's a custom property (depending on whether the
     * property actually is set).
     *
     * @param string $name  property name
     *
     * @return bool          whether the given property exists
     */
    public function __isset(string $name)
    {
        $method = 'get' . self::_normalizePropertyName($name);
        if (method_exists($this, $method)) {
            return true;
        }

        return isset($this->_properties[$name]);
    }

    /**
     * Unsets the given custom property
     *
     * Magic overload for enabling <code>unset($page->propname)</code>.
     *
     * @param string $name               property name
     *
     * @return void
     *
     * @throws Exception  if the property is native
     */
    public function __unset(string $name)
    {
        $method = 'set' . self::_normalizePropertyName($name);
        if (method_exists($this, $method)) {
            throw new Exception(sprintf('Unsetting native property "%s" is not allowed', $name));
        }

        unset($this->_properties[$name]);
    }

    /**
     * Returns page label
     *
     * Magic overload for enabling <code>echo $page</code>.
     *
     * @return string  page label
     */
    public function __toString(): string
    {
        return $this->_label ?? '';
    }

    // Public methods:

    /**
     * Adds a forward relation to the page
     *
     * @param string $relation      relation name (e.g. alternate, glossary,
     *                               canonical, etc)
     * @param  mixed  $value         value to set for relation
     *
     * @return $this  fluent interface, returns self
     */
    public function addRel(string $relation, mixed $value): static
    {
        $this->_rel[$relation] = $value;

        return $this;
    }

    /**
     * Adds a reverse relation to the page
     *
     * @param string $relation      relation name (e.g. alternate, glossary,
     *                               canonical, etc)
     * @param  mixed  $value         value to set for relation
     *
     * @return $this  fluent interface, returns self
     */
    public function addRev(string $relation, mixed $value): static
    {
        $this->_rev[$relation] = $value;

        return $this;
    }

    /**
     * Removes a forward relation from the page
     *
     * @param string $relation      name of relation to remove
     *
     * @return $this  fluent interface, returns self
     */
    public function removeRel(string $relation): static
    {
        unset($this->_rel[$relation]);

        return $this;
    }

    /**
     * Removes a reverse relation from the page
     *
     * @param string $relation      name of relation to remove
     *
     * @return $this  fluent interface, returns self
     */
    public function removeRev(string $relation): static
    {
        if (isset($this->_rev[$relation])) {
            unset($this->_rev[$relation]);
        }

        return $this;
    }

    /**
     * Returns an array containing the defined forward relations
     *
     * @return array  defined forward relations
     */
    public function getDefinedRel(): array
    {
        return array_keys($this->_rel);
    }

    /**
     * Returns an array containing the defined reverse relations
     *
     * @return array  defined reverse relations
     */
    public function getDefinedRev(): array
    {
        return array_keys($this->_rev);
    }

    /**
     * Returns custom properties as an array
     *
     * @return array an array containing custom properties
     */
    public function getCustomProperties(): array
    {
        return $this->_properties;
    }

    /**
     * Returns a unique code value for the page
     *
     * @return int a unique code value for this page
     */
    final public function hashCode(): int
    {
        return spl_object_id($this);
    }

    public function toArray(): array
    {
        return array_merge(
            $this->getCustomProperties(),
            [
                'label' => $this->getlabel(),
                'fragment' => $this->getFragment(),
                'id' => $this->getId(),
                'class' => $this->getClass(),
                'title' => $this->getTitle(),
                'target' => $this->getTarget(),
                'accesskey' => $this->getAccesskey(),
                'rel' => $this->getRel(),
                'rev' => $this->getRev(),
                'customHtmlAttribs' => $this->getCustomHtmlAttribs(),
                'order' => $this->getOrder(),
                'active' => $this->isActive(),
                'visible' => $this->isVisible(),
                'type' => get_class($this),
                'pages' => parent::toArray(),
            ]
        );
    }

    /**
     * Normalizes a property name
     *
     * @internal
     */
    protected static function _normalizePropertyName(string $property): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
    }

    /**
     * Returns href for this page
     *
     * @return string  the page's href
     */
    abstract public function getHref(): string;
}
