<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating;

interface TemplatingAwareHelperInterface
{
    /**
     * @param PhpEngine $engine
     */
    public function setEngine(PhpEngine $engine);
}
