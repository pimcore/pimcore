<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\HelperBroker;

use Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine;

interface HelperBrokerInterface
{
    /**
     * Determines if broker supports method
     *
     * @param PhpEngine $engine
     * @param string $method
     * @return bool
     */
    public function supports(PhpEngine $engine, $method);

    /**
     * Runs helper
     *
     * @param PhpEngine $engine
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function helper(PhpEngine $engine, $method, array $arguments);
}
