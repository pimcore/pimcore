<?php

namespace AppBundle\EventListener;

use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestListener implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            DataObjectEvents::PRE_UPDATE => 'onObjectPreUpdate'
        ];
    }

    public function onObjectPreUpdate(DataObjectEvent $event)
    {
        // do with the object whatever you want ;-)
        $object = $event->getObject();
    }
}
