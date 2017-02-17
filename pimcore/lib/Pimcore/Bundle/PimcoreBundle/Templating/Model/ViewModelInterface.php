<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Model;

use Symfony\Component\HttpFoundation\ParameterBag;

interface ViewModelInterface extends \Countable, \IteratorAggregate, \ArrayAccess, \JsonSerializable
{
    /**
     * @return ParameterBag
     */
    public function getParameters();
}
