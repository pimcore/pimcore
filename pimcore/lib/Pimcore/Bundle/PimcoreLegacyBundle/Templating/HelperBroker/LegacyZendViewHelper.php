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
        return 'zf1_' === substr($method, 0, 4);
    }

    /**
     * @inheritDoc
     */
    public function helper(PhpEngine $engine, $method, array $arguments)
    {
        $helper = substr($method, 4);

        return $this->zendViewHelperBridge->execute($helper, $arguments);
    }
}
