<?php

namespace Pimcore\Bundle\PimcoreLegacyBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Pimcore\Bundle\PimcoreLegacyBundle\ClassLoader\LegacyClassLoader;

class PimcoreLegacyBundle extends Bundle
{
    public function boot()
    {
        $loader = new LegacyClassLoader();
        $loader->register();
    }
}
