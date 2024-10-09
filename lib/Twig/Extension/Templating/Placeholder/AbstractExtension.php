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

namespace Pimcore\Twig\Extension\Templating\Placeholder;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Pimcore\Twig\Extension\Templating\Traits\HelperCharsetTrait;
use Traversable;
use Twig\Extension\RuntimeExtensionInterface;

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
 *
 */
abstract class AbstractExtension implements IteratorAggregate, Countable, ArrayAccess, RuntimeExtensionInterface
{
    use HelperCharsetTrait;

    protected ContainerService $containerService;

    protected Container $_container;

    /**
     * Registry key under which container registers itself
     *
     */
    protected string $_regKey;

    /**
     * Flag whether to automatically escape output, must also be
     * enforced in the child class if __toString/toString is overwritten
     *
     */
    protected bool $_autoEscape = true;

    public function __construct(ContainerService $containerService)
    {
        $this->containerService = $containerService;
    }

    /**
     * Set whether or not auto escaping should be used
     *
     * @param bool $autoEscape whether or not to auto escape output
     *
     * @return $this
     */
    public function setAutoEscape(bool $autoEscape = true): static
    {
        $this->_autoEscape = ($autoEscape) ? true : false;

        return $this;
    }

    /**
     * Return whether autoEscaping is enabled or disabled
     *
     * return bool
     */
    public function getAutoEscape(): bool
    {
        return $this->_autoEscape;
    }

    /**
     * Escape a string
     *
     *
     */
    protected function _escape(string $string): string
    {
        return htmlspecialchars($string);
    }

    /**
     * Set container on which to operate
     *
     *
     * @return $this
     */
    public function setContainer(Container $container): static
    {
        $this->containerService->setContainer($this->_regKey, $container);

        return $this;
    }

    /**
     * Retrieve placeholder container
     *
     */
    public function getContainer(): Container
    {
        return $this->containerService->getContainer($this->_regKey);
    }

    /**
     * Overloading: set property value
     */
    public function __set(string $key, mixed $value): void
    {
        $container = $this->getContainer();
        $container[$key] = $value;
    }

    /**
     * Overloading: retrieve property
     *
     *
     * @return mixed
     */
    public function __get(string $key)
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
     *
     * @return bool
     */
    public function __isset(string $key)
    {
        $container = $this->getContainer();

        return isset($container[$key]);
    }

    /**
     * Overloading: unset property
     *
     *
     * @return void
     */
    public function __unset(string $key)
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
     */
    public function __call(string $method, array $args): mixed
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
     */
    public function toString(): string
    {
        return $this->getContainer()->toString();
    }

    /**
     * Cast to string representation
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Countable
     *
     */
    public function count(): int
    {
        $container = $this->getContainer();

        return count($container);
    }

    /**
     * ArrayAccess: offsetExists
     *
     * @param  string|int $offset
     *
     */
    public function offsetExists($offset): bool
    {
        return $this->getContainer()->offsetExists($offset);
    }

    /**
     * ArrayAccess: offsetGet
     *
     * @param  string|int $offset
     *
     */
    public function offsetGet($offset): mixed
    {
        return $this->getContainer()->offsetGet($offset);
    }

    /**
     * ArrayAccess: offsetSet
     *
     * @param  string|int $offset
     *
     */
    public function offsetSet($offset, mixed $value): void
    {
        $this->getContainer()->offsetSet($offset, $value);
    }

    /**
     * ArrayAccess: offsetUnset
     *
     * @param  string|int $offset
     *
     */
    public function offsetUnset($offset): void
    {
        $this->getContainer()->offsetUnset($offset);
    }

    /**
     * IteratorAggregate: get Iterator
     *
     */
    public function getIterator(): Traversable
    {
        return $this->getContainer()->getIterator();
    }
}
