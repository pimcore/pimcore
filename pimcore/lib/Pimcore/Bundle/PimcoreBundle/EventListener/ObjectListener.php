<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets up pimcore objects for frontend
 */
class ObjectListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5]
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // TODO run this only in frontend context!
        \Pimcore::unsetAdminMode();
        Document::setHideUnpublished(true);
        Object\AbstractObject::setHideUnpublished(true);
        Object\AbstractObject::setGetInheritedValues(true);
        Object\Localizedfield::setGetFallbackValues(true);
    }
}
