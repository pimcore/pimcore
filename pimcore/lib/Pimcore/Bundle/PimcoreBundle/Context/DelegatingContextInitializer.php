<?php

namespace Pimcore\Bundle\PimcoreBundle\Context;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Handles a collection of context initializers
 */
class DelegatingContextInitializer implements ContextInitializerInterface
{
    /**
     * @var ContextInitializerInterface[]
     */
    protected $initializers = [];

    /**
     * @param ContextInitializerInterface[] $initializers
     */
    public function __construct(array $initializers = null)
    {
        if (null !== $initializers) {
            foreach ($initializers as $initializer) {
                $this->addInitializer($initializer);
            }
        }

    }

    /**
     * @param ContextInitializerInterface $initializer
     */
    public function addInitializer(ContextInitializerInterface $initializer)
    {
        $this->initializers[] = $initializer;
    }

    /**
     * @inheritDoc
     */
    public function supports($context)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function initialize(Request $request, $context, $requestType = KernelInterface::MASTER_REQUEST)
    {
        foreach ($this->initializers as $initializer) {
            if ($initializer->supports($context)) {
                $initializer->initialize($request, $context, $requestType);
            }
        }
    }
}
