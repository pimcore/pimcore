<?php

namespace Pimcore\Bundle\PimcoreBundle\Context\Initializer;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

interface ContextInitializerInterface
{
    /**
     * Determines if the initializer should be called for the given context
     *
     * @param string $context
     * @return bool
     */
    public function supports($context);

    /**
     * Initializes system for the given context
     *
     * @param Request $request
     * @param string $context
     * @param int $requestType
     */
    public function initialize(Request $request, $context, $requestType = KernelInterface::MASTER_REQUEST);
}
