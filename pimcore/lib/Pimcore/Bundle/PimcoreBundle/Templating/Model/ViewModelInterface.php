<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Model;

use Symfony\Component\HttpFoundation\ParameterBag;

interface ViewModelInterface extends \Countable, \IteratorAggregate, \ArrayAccess
{
    /**
     * @return ParameterBag
     */
    public function getParameters();

    /**
     * @return ParameterBag
     */
    public function getAttributes();
}
