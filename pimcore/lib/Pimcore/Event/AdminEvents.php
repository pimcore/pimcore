<?php

namespace Pimcore\Event;

final class AdminEvents
{
    /**
     * This event is triggered when the AdminControllerListener double-checks authentication for admin controllers.
     *
     * There's a whitelist of requests which shouldn't be double-checked (e.g. login) which can be altered through this
     * event.
     *
     * @Event("Pimcore\Event\Admin\UnauthenticatedRequestWhitelistEvent")
     *
     * @var string
     */
    const UNAUTHENTICATED_REQUEST_WHITELIST = 'pimcore.admin.unauthenticated_request_whitelist';
}
