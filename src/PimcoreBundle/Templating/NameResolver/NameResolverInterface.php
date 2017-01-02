<?php

namespace PimcoreBundle\Templating\NameResolver;

interface NameResolverInterface
{
    /**
     * Resolve helper name
     *
     * @param $name
     * @return string
     */
    public function resolve($name);
}
