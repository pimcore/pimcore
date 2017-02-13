<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\HelperBroker;

use Pimcore\Bundle\PimcoreBundle\Templating\HelperBroker\HelperBrokerInterface;
use Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine;
use Zend\View\HelperPluginManager;

class ZendViewHelper implements HelperBrokerInterface
{
    /**
     * @var HelperPluginManager
     */
    protected $helperPluginManager;

    /**
     * @param HelperPluginManager $helperPluginManager
     */
    public function __construct(HelperPluginManager $helperPluginManager)
    {
        $this->helperPluginManager = $helperPluginManager;
    }

    /**
     * @inheritDoc
     */
    public function supports(PhpEngine $engine, $method)
    {
        return $this->helperPluginManager->has($method);
    }

    /**
     * @inheritDoc
     */
    public function helper(PhpEngine $engine, $method, array $arguments)
    {
        $helper = $this->helperPluginManager->get($method);

        if (is_callable($helper)) {
            return call_user_func_array($helper, $arguments);
        }

        return $helper;
    }
}
