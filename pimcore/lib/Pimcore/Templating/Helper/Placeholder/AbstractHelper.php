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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_View_Helper_Placeholder_Container_Standalone
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
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Templating\Helper\Placeholder;

use Pimcore\Templating\Helper\Exception;
use Symfony\Component\Templating\Helper\Helper;

/**
 * @method void set(mixed $value)
 * @method void prepend(mixed $value)
 * @method void append(mixed $value)
 * @method Container setPrefix(string $prefix)
 * @method string getPrefix()
 * @method Container setPostfix(string $postfix)
 * @method string getPostfix()
 * @method Container setSeparator(string $separator)
 * @method string getSeparator()
 * @method Container setIndent(string|int $intent)
 * @method string|int getIndent()
 * @method string getWhitespace(string|int $indent)
 * @method void captureStart($type = Container::APPEND, $key = null)
 * @method void captureEnd()
 */
abstract class AbstractHelper extends Helper implements \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * @var Container
     */
    protected $_container;

    /**
     * Registry key under which container registers itself
     *
     * @var string
     */
    protected $_regKey;

    /**
     * Flag whether to automatically escape output, must also be
     * enforced in the child class if __toString/toString is overwritten
     *
     * @var bool
     */
    protected $_autoEscape = true;

    /**
     * AbstractHelper constructor.
     *
     * @param ContainerService $containerService
     *
     * @internal param Container $container
     */
    public function __construct(ContainerService $containerService)
    {
        $this->setContainer($containerService->getContainer($this->_regKey));
    }

    /**
     * Set whether or not auto escaping should be used
     *
     * @param  bool $autoEscape whether or not to auto escape output
     *
     * @return AbstractHelper
     */
    public function setAutoEscape($autoEscape = true)
    {
        $this->_autoEscape = ($autoEscape) ? true : false;

        return $this;
    }

    /**
     * Return whether autoEscaping is enabled or disabled
     *
     * return bool
     */
    public function getAutoEscape()
    {
        return $this->_autoEscape;
    }

    /**
     * Escape a string
     *
     * @param  string $string
     *
     * @return string
     */
    protected function _escape($string)
    {
        return htmlspecialchars((string) $string);
    }

    /**
     * Set container on which to operate
     *
     * @param  Container $container
     *
     * @return AbstractHelper
     */
    public function setContainer(Container $container)
    {
        $this->_container = $container;

        return $this;
    }

    /**
     * Retrieve placeholder container
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * Overloading: set property value
     *
     * @param  string $key
     * @param  mixed $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $container = $this->getContainer();
        $container[$key] = $value;
    }

    /**
     * Overloading: retrieve property
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $container = $this->getContainer();
        if (isset($container[$key])) {
            return $container[$key];
        }

        return null;
    }

    /**
     * Overloading: check if property is set
     *
     * @param  string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        $container = $this->getContainer();

        return isset($container[$key]);
    }

    /**
     * Overloading: unset property
     *
     * @param  string $key
     *
     * @return void
     */
    public function __unset($key)
    {
        $container = $this->getContainer();
        if (isset($container[$key])) {
            unset($container[$key]);
        }
    }

    /**
     * Overload
     *
     * Proxy to container methods
     *
     * @param  string $method
     * @param  array $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        $container = $this->getContainer();
        if (method_exists($container, $method)) {
            $return = call_user_func_array([$container, $method], $args);
            if ($return === $container) {
                // If the container is returned, we really want the current object
                return $this;
            }

            return $return;
        }

        throw new Exception('Method "' . $method . '" does not exist');
    }

    /**
     * String representation
     *
     * @return string
     */
    public function toString()
    {
        return $this->getContainer()->toString();
    }

    /**
     * Cast to string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Countable
     *
     * @return int
     */
    public function count()
    {
        $container = $this->getContainer();

        return count($container);
    }

    /**
     * ArrayAccess: offsetExists
     *
     * @param  string|int $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->getContainer()->offsetExists($offset);
    }

    /**
     * ArrayAccess: offsetGet
     *
     * @param  string|int $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getContainer()->offsetGet($offset);
    }

    /**
     * ArrayAccess: offsetSet
     *
     * @param  string|int $offset
     * @param  mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->getContainer()->offsetSet($offset, $value);
    }

    /**
     * ArrayAccess: offsetUnset
     *
     * @param  string|int $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->getContainer()->offsetUnset($offset);
    }

    /**
     * IteratorAggregate: get Iterator
     *
     * @return \Iterator
     */
    public function getIterator()
    {
        return $this->getContainer()->getIterator();
    }
}
