<?php

namespace Pimcore\Bundle\PimcoreBundle\Context;

abstract class AbstractContextInitializer implements ContextInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($context)
    {
        return in_array($context, $this->getSupportedContexts());
    }

    /**
     * @return array
     */
    abstract protected function getSupportedContexts();
}
