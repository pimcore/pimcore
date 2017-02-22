<?php

namespace Pimcore\Bundle\PimcoreBundle\Context\Initializer;

use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class AbstractContextInitializer implements ContextInitializerInterface
{
    /**
     * @param int $requestType
     * @return bool
     */
    protected function isMasterRequest($requestType)
    {
        return $requestType === HttpKernelInterface::MASTER_REQUEST;
    }
}
