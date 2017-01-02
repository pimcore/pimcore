<?php

namespace PimcoreBundle\Templating\Traits;

use PimcoreBundle\Templating\NameResolver\NameResolverInterface;

trait NameResolverAwareEngine
{
    /**
     * @var NameResolverInterface
     */
    protected $nameResolver;

    /**
     * @param NameResolverInterface $nameResolver
     * @return $this
     */
    public function setNameResolver(NameResolverInterface $nameResolver)
    {
        $this->nameResolver = $nameResolver;

        return $this;
    }
}
