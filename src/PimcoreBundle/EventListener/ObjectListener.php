<?php


namespace PimcoreBundle\EventListener;


use Pimcore\Model\Document;
use Pimcore\Model\Object;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Sets up pimcore objects for frontend - TODO this is definitely the wrong place event - find an appropriate place and run only in frontend
 */
class ObjectListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        \Pimcore::unsetAdminMode();
        Document::setHideUnpublished(true);
        Object\AbstractObject::setHideUnpublished(true);
        Object\AbstractObject::setGetInheritedValues(true);
        Object\Localizedfield::setGetFallbackValues(true);
    }
}
