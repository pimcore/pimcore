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

final class NotificationEvents
{
    /**
     * @Event("Pimcore\Event\Model\NotificationEvent")
     *
     * @var string
     */
    const PRE_SAVE = 'pimcore.notification.preSave';

    /**
     * @Event("Pimcore\Event\Model\NotificationEvent")
     *
     * @var string
     */
    const POST_SAVE = 'pimcore.notification.postSave';

    /**
     * @Event("Pimcore\Event\Model\NotificationEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.notification.preDelete';

    /**
     * @Event("Pimcore\Event\Model\NotificationEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.notification.postDelete';
}
