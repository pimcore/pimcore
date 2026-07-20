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

final class VersionEvents
{
    /**
     * @Event("Pimcore\Event\Model\VersionEvent")
     *
     * @var string
     */
    const PRE_SAVE = 'pimcore.version.preSave';

    /**
     * @Event("Pimcore\Event\Model\VersionEvent")
     *
     * @var string
     */
    const POST_SAVE = 'pimcore.version.postSave';

    /**
     * @Event("Pimcore\Event\Model\VersionEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.version.preDelete';

    /**
     * @Event("Pimcore\Event\Model\VersionEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.version.postDelete';
}
