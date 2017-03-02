<?php

namespace Pimcore\Event;

final class ObjectCustomLayoutEvents
{
    /**
     * @Event("Pimcore\Event\Model\Object\CustomLayoutEvent")
     * @var string
     */
    const PRE_ADD = 'pimcore.object.customLayout.preAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\CustomLayoutEvent")
     * @var string
     */
    const PRE_UPDATE = 'pimcore.object.customLayout.preUpdate';
}
