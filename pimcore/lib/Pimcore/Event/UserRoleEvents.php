<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event;

final class UserRoleEvents
{
    /**
     * @Event("Pimcore\Event\Model\Object\UserRoleEvent")
     *
     * @var string
     */
    const PRE_ADD = 'pimcore.user.preAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\UserRoleEvent")
     *
     * @var string
     */
    const POST_ADD = 'pimcore.user.postAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\UserRoleEvent")
     *
     * @var string
     */
    const PRE_UPDATE = 'pimcore.user.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\UserRoleEvent")
     *
     * @var string
     */
    const POST_UPDATE = 'pimcore.user.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\UserRoleEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.user.preDelete';

    /**
     * @Event("Pimcore\Event\Model\Object\UserRoleEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.user.postDelete';
}
