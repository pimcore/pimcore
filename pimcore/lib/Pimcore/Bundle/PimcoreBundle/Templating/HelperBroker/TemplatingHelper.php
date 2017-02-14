<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\HelperBroker;

use Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine;

class TemplatingHelper implements HelperBrokerInterface
{
    /**
     * @inheritDoc
     */
    public function supports(PhpEngine $engine, $method)
    {
        if ($engine->has($method)) {
            return true;
        }

        return false;
    }

    /**
     * Run or return a native view helper
     *
     * @inheritDoc
     */
    public function helper(PhpEngine $engine, $method, array $arguments)
    {
        $helper = $engine->get($method);

        // helper implements __invoke -> run it directly
        if (is_callable($helper)) {
            return call_user_func_array($helper, $arguments);
        }

        return $helper;
    }
}
