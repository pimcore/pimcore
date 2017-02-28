<?php

namespace Pimcore\Event;

final class AdminEvents
{
    /**
     * The LOGIN_CREDENTIALS event is triggered after login credentials were resolved from request.
     *
     * This event allows you to influence the credentials resolved in the authenticator before
     * they are passed to getUser().
     *
     * @Event("Pimcore\Event\Admin\Login\LoginCredentialsEvent")
     *
     * @var string
     */
    const LOGIN_CREDENTIALS = 'pimcore.admin.login.credentials';

    /**
     * The LOGIN_FAILED event is triggered when credentials were invalid.
     *
     * This event allows you to set a custom user which is resolved from the given credentials
     * from a third-party authentication system (e.g. an external service).
     *
     * @Event("Pimcore\Event\Admin\Login\LoginFailedEvent")
     *
     * @var string
     */
    const LOGIN_FAILED = 'pimcore.admin.login.failed';

    /**
     * The LOGIN_LOSTPASSWORD event is triggered before the lost password email
     * is sent.
     *
     * This event allows you to alter the lost password mail or to prevent
     * mail sending at all. For full control, it allows you to set the response
     * to be returned.
     *
     * @Event("Pimcore\Event\Admin\Login\LostPasswordEvent")
     *
     * @var string
     */
    const LOGIN_LOSTPASSWORD = 'pimcore.admin.login.lostpassword';

    /**
     * The LOGIN_LOGOUT event is triggered before the user is logged out.
     *
     * By setting a response on the event, you're able to control the response
     * returned after logout.
     *
     * @Event("Pimcore\Event\Admin\Login\LogoutEvent")
     *
     * @var string
     */
    const LOGIN_LOGOUT = 'pimcore.admin.login.logout';
}
