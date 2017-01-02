<?php

namespace PimcoreBundle\Templating;

use PimcoreBundle\Templating\Traits\NameResolverAwareEngine;

class PhpEngine extends \Symfony\Bundle\FrameworkBundle\Templating\PhpEngine
{
    use NameResolverAwareEngine;

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        return parent::get($this->nameResolver->resolve($name));
    }
}
