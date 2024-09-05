<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Event;

final class WebsiteSettingEvents
{
    /**
     * @Event("Pimcore\Event\Model\WebsiteSettingEvent")
     *
     * @var string
     */
    public const PRE_ADD = 'pimcore.websiteSetting.preAdd';

    /**
     * @Event("Pimcore\Event\Model\WebsiteSettingEvent")
     *
     * @var string
     */
    public const POST_ADD = 'pimcore.websiteSetting.postAdd';

    /**
     * @Event("Pimcore\Event\Model\WebsiteSettingEvent")
     *
     * @var string
     */
    public const PRE_UPDATE = 'pimcore.websiteSetting.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\WebsiteSettingEvent")
     *
     * @var string
     */
    public const POST_UPDATE = 'pimcore.websiteSetting.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\WebsiteSettingEvent")
     *
     * @var string
     */
    public const PRE_DELETE = 'pimcore.websiteSetting.preDelete';

    /**
     * @Event("Pimcore\Event\Model\WebsiteSettingEvent")
     *
     * @var string
     */
    public const POST_DELETE = 'pimcore.websiteSetting.postDelete';
}
