<?php

namespace Pimcore\Event;

final class ObjectEvents
{
    /**
     * @Event("Pimcore\Event\Model\ObjectEvent")
     * @var string
     */
    const PRE_ADD = 'pimcore.object.preAdd';

    /**
     * @Event("Pimcore\Event\Model\ObjectEvent")
     * @var string
     */
    const POST_ADD = 'pimcore.object.postAdd';

    /**
     * @Event("Pimcore\Event\Model\ObjectEvent")
     * @var string
     */
    const PRE_UPDATE = 'pimcore.object.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\ObjectEvent")
     * @var string
     */
    const POST_UPDATE = 'pimcore.object.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\ObjectEvent")
     * @var string
     */
    const PRE_DELETE = 'pimcore.object.preDelete';

    /**
     * @Event("Pimcore\Event\Model\ObjectEvent")
     * @var string
     */
    const POST_DELETE = 'pimcore.object.postDelete';

    /**
     * @Event("Pimcore\Event\Model\ObjectEvent")
     * @var string
     */
    const POST_COPY = 'pimcore.object.postCopy';
}