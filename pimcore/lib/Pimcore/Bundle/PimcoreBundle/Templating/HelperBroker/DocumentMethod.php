<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\HelperBroker;

use Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine;

class DocumentMethod implements HelperBrokerInterface
{
    /**
     * @inheritDoc
     */
    public function supports(PhpEngine $engine, $method)
    {
        $document = $engine->getViewParameter('document');
        if (null !== $document && method_exists($document, $method)) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function helper(PhpEngine $engine, $method, array $arguments)
    {
        $document = $engine->getViewParameter('document');

        return call_user_func_array([$document, $method], $arguments);
    }
}
