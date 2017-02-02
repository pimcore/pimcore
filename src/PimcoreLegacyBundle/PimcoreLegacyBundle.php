<?php

namespace PimcoreLegacyBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use PimcoreLegacyBundle\ClassLoader\LegacyClassLoader;

class PimcoreLegacyBundle extends Bundle
{
    public function boot()
    {
        $loader = new LegacyClassLoader();
        $loader->register();
    }
}
