<?php

namespace AppBundle\EventListener;

use Pimcore\Event\Model\ObjectEvent;

class TestListener
{
    /**
     * @param ObjectEvent $event
     */
    public function onObjectPreUpdate(ObjectEvent $event)
    {
        $object = $event->getObject();
        // do with the object whatever you want ;-)
    }
}
