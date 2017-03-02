<?php

namespace Pimcore\Event;

final class VersionEvents
{
    /**
     * @Event("Pimcore\Event\Model\VersionEvent")
     * @var string
     */
    const PRE_SAVE = 'pimcore.version.preSave';

    /**
     * @Event("Pimcore\Event\Model\VersionEvent")
     * @var string
     */
    const POST_SAVE = 'pimcore.class.postSave';

    /**
     * @Event("Pimcore\Event\Model\VersionEvent")
     * @var string
     */
    const PRE_DELETE = 'pimcore.class.preDelete';

    /**
     * @Event("Pimcore\Event\Model\VersionEvent")
     * @var string
     */
    const POST_DELETE = 'pimcore.class.postDelete';
}