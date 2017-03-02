<?php

namespace Pimcore\Event;

final class UserRoleEvents
{
    /**
     * @Event("Pimcore\Event\Model\Object\UserRoleEvent")
     * @var string
     */
    const PRE_ADD = 'pimcore.user.preAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\UserRoleEvent")
     * @var string
     */
    const POST_ADD = 'pimcore.user.postAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\UserRoleEvent")
     * @var string
     */
    const PRE_UPDATE = 'pimcore.user.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\UserRoleEvent")
     * @var string
     */
    const POST_UPDATE = 'pimcore.user.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\UserRoleEvent")
     * @var string
     */
    const PRE_DELETE = 'pimcore.user.preDelete';

    /**
     * @Event("Pimcore\Event\Model\Object\UserRoleEvent")
     * @var string
     */
    const POST_DELETE = 'pimcore.user.postDelete';

}