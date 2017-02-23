<?php

namespace PimcoreLegacyBundle\Templating\HelperBroker;

use Pimcore\Bundle\PimcoreBundle\Templating\HelperBroker\HelperBrokerInterface;
use Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine;
use PimcoreLegacyBundle\Zend\View\ViewHelperBridge;

class ZendViewHelper implements HelperBrokerInterface
{
    /**
     * @var ViewHelperBridge
     */
    protected $viewHelperBridge;

    /**
     * @param ViewHelperBridge $zendViewHelperBridge
     */
    public function __construct(ViewHelperBridge $zendViewHelperBridge)
    {
        $this->viewHelperBridge = $zendViewHelperBridge;
    }

    /**
     * @inheritDoc
     */
    public function supports(PhpEngine $engine, $method)
    {
        return $this->viewHelperBridge->hasHelper($method);
    }

    /**
     * @inheritDoc
     */
    public function helper(PhpEngine $engine, $method, array $arguments)
    {
        return $this->viewHelperBridge->execute($method, $arguments);
    }
}
