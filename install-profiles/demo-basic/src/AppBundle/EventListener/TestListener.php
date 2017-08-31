<?php

namespace AppBundle\EventListener;

use Pimcore\Event\Model\DataObjectEvent;

class TestListener
{
    /**
     * @param DataObjectEvent $event
     */
    public function onObjectPreUpdate(DataObjectEvent $event)
    {
        $object = $event->getObject();
        // do with the object whatever you want ;-)
    }
}
