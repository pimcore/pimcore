<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Model;

use Symfony\Component\HttpFoundation\ParameterBag;

interface ViewModelInterface extends \Countable, \IteratorAggregate, \ArrayAccess, \JsonSerializable
{
    /**
     * @return ParameterBag
     */
    public function getParameters();

    /**
     * Get parameter value
     *
     * @param string $key
     * @param mixed|null $default
     * @return bool
     */
    public function get($key, $default = null);

    /**
     * Check if parameter is set
     *
     * @param string $key
     * @return bool
     */
    public function has($key);
}
