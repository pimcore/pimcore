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

namespace Pimcore\Templating\Model;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @deprecated
 */
class ViewModel implements ViewModelInterface
{
    /**
     * @var ParameterBag
     */
    protected $parameters;

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->initialize($parameters);
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function initialize(array $parameters = [])
    {
        $this->parameters = new ParameterBag($parameters);

        return $this;
    }

    /**
     * @return ParameterBag
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getAllParameters()
    {
        return $this->parameters->all();
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        return $this->getParameters()->get($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        return $this->parameters->has($key);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->parameters->get($name, null);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->parameters->set($name, $value);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->parameters->has($name);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return $this->parameters->getIterator();
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->parameters->count();
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->parameters->has($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->parameters->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->parameters->set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->parameters->remove($offset);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->parameters->all();
    }
}
