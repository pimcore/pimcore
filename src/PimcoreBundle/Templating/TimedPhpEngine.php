<?php

namespace PimcoreBundle\Templating;

use PimcoreBundle\Templating\Traits\NameResolverAwareEngine;

class TimedPhpEngine extends \Symfony\Bundle\FrameworkBundle\Templating\TimedPhpEngine
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
