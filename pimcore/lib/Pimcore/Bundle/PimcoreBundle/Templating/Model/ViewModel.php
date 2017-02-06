<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Model;

use Symfony\Component\HttpFoundation\ParameterBag;

class ViewModel implements ViewModelInterface
{
    /**
     * @var ParameterBag
     */
    protected $parameters;

    /**
     * @var ParameterBag
     */
    protected $attributes;

    /**
     * @param array $parameters
     * @param array $attributes
     */
    public function __construct(array $parameters = [], array $attributes = [])
    {
        $this->initialize($parameters, $attributes);
    }

    /**
     * @param array $parameters
     * @param array $attributes
     *
     * @return $this
     */
    public function initialize(array $parameters = [], array $attributes = [])
    {
        $this->parameters = new ParameterBag($parameters);
        $this->attributes = new ParameterBag($attributes);

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
     * @return ParameterBag
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->parameters->get($name, null);
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->parameters->set($name, $value);
    }

    /**
     * @param $name
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
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->getIterator()->offsetExists($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getIterator()->offsetGet($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->getIterator()->offsetSet($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->getIterator()->offsetUnset($offset);
    }
}
