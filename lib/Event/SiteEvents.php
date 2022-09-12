<?php

namespace Pimcore\Event;

final class SiteEvents
{
    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *  - oldPath | the old full path in case the path has changed
     *
     * @Event("Pimcore\Event\Model\SiteEvent")
     *
     * @var string
     */
    const POST_UPDATE = 'pimcore.site.postUpdate';
}
