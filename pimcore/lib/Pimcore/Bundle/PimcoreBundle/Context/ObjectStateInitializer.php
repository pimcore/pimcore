<?php

namespace Pimcore\Bundle\PimcoreBundle\Context;

use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolver;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class ObjectStateInitializer extends AbstractContextInitializer implements LoggerAwareInterface
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

        $this->logger->debug('Initializing object state for {context} context', ['context' => $context]);

        if ($context === PimcoreContextResolver::CONTEXT_ADMIN) {
            Document::setHideUnpublished(false);
            Object\AbstractObject::setHideUnpublished(false);
            Object\AbstractObject::setGetInheritedValues(false);
            Object\Localizedfield::setGetFallbackValues(false);
        } else {
            Document::setHideUnpublished(true);
            Object\AbstractObject::setHideUnpublished(true);
            Object\AbstractObject::setGetInheritedValues(true);
            Object\Localizedfield::setGetFallbackValues(true);
        }
    }
}
