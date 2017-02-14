<?php

namespace Pimcore\Bundle\PimcoreLegacyBundle\Templating\HelperBroker;

use Pimcore\Bundle\PimcoreBundle\Templating\HelperBroker\HelperBrokerInterface;
use Pimcore\Bundle\PimcoreBundle\Templating\PhpEngine;
use Pimcore\Bundle\PimcoreBundle\View\ZendViewHelperBridge;

class LegacyZendViewHelper implements HelperBrokerInterface
{
    /**
     * @var ZendViewHelperBridge
     */
    protected $zendViewHelperBridge;

    /**
     * @param ZendViewHelperBridge $zendViewHelperBridge
     */
    public function __construct(ZendViewHelperBridge $zendViewHelperBridge)
    {
        $this->zendViewHelperBridge = $zendViewHelperBridge;
    }

    /**
     * @inheritDoc
     */
    public function supports(PhpEngine $engine, $method)
    {
        return $this->zendViewHelperBridge->hasHelper($method);
    }

    /**
     * @inheritDoc
     */
    public function helper(PhpEngine $engine, $method, array $arguments)
    {
        return $this->zendViewHelperBridge->execute($method, $arguments);
    }
}
