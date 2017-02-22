<?php

namespace Pimcore\Bundle\PimcoreBundle\Context;

use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class AdminModeInitializer extends AbstractContextInitializer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
        if (!$this->isMasterRequest($requestType)) {
            return;
        }

        if ($context === PimcoreContextResolver::CONTEXT_ADMIN) {
            $this->logger->debug('Setting admin mode');
            \Pimcore::setAdminMode();
        } else {
            $this->logger->debug('Unsetting admin mode');
            \Pimcore::unsetAdminMode();
        }
    }
}
