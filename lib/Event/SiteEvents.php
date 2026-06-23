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
