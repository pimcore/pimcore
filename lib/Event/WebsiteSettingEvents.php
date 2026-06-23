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
