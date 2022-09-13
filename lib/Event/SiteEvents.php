<?php

namespace Pimcore\Event;

final class SiteEvents
{
    /**
     * @Event("Pimcore\Event\Model\SiteEvent")
     *
     * @var string
     */
    const PRE_SAVE = 'pimcore.site.preSave';

    /**
     * @Event("Pimcore\Event\Model\SiteEvent")
     *
     * @var string
     */
    const POST_SAVE = 'pimcore.site.postSave';

    /**
     * @Event("Pimcore\Event\Model\SiteEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.site.preDelete';

    /**
     * @Event("Pimcore\Event\Model\SiteEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.site.postDelete';
}
