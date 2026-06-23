<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Event;

final class UserRoleEvents
{
    /**
     * @Event("Pimcore\Event\Model\UserRoleEvent")
     *
     * @var string
     */
    const PRE_ADD = 'pimcore.user.preAdd';

    /**
     * @Event("Pimcore\Event\Model\UserRoleEvent")
     *
     * @var string
     */
    const POST_ADD = 'pimcore.user.postAdd';

    /**
     * @Event("Pimcore\Event\Model\UserRoleEvent")
     *
     * @var string
     */
    const PRE_UPDATE = 'pimcore.user.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\UserRoleEvent")
     *
     * @var string
     */
    const POST_UPDATE = 'pimcore.user.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\UserRoleEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.user.preDelete';

    /**
     * @Event("Pimcore\Event\Model\UserRoleEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.user.postDelete';
}
